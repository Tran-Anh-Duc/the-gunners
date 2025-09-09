<?php
declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use Masterminds\HTML5;
use Spatie\Browsershot\Browsershot;
use andreskrey\Readability\Readability;
use andreskrey\Readability\Configuration;

class RenderedArticleExtractor
{
    protected Client $http;
    protected HTML5 $html5;

    public function __construct(?Client $client = null)
    {
        $this->http  = $client ?: new Client(['timeout' => 25, 'http_errors' => false, 'verify' => false]);
        $this->html5 = new HTML5(['disable_html_ns' => true]);
    }

    public function extract(string $url): array
    {
        // 1) Render HTML bằng headless (đợi lazy-load)
        $rendered = $this->renderHtml($url);

        // 2) Ưu tiên lấy nội dung từ JSON-LD (schema.org Article)
        $title       = null;
        $contentHtml = $this->pickFromJsonLdArticle($rendered, $url, $title);

        // 3) Nếu chưa có → thử lấy AMP
        if ($contentHtml === null) {
            $ampHtml = $this->fetchAmpHtmlIfAny($rendered, $url);
            if ($ampHtml) {
                $contentHtml = $this->pickByDomainRules($url, $ampHtml) ?? $this->smartPickMainNode($ampHtml);
                if ($contentHtml === null) {
                    $rd = $this->runReadability($ampHtml, $url, $title);
                    $contentHtml = $rd['content'] ?? null;
                    $title       = $title ?: ($rd['title'] ?? null);
                }
            }
        }

        // 4) Nếu vẫn chưa có → domain rules + heuristic trên HTML đã render
        if ($contentHtml === null) {
            $contentHtml = $this->pickByDomainRules($url, $rendered) ?? $this->smartPickMainNode($rendered);
        }

        // 5) Fallback cuối: Readability trên HTML render
        if ($contentHtml === null) {
            $rd = $this->runReadability($rendered, $url, $title);
            $contentHtml = $rd['content'] ?? null;
            $title       = $title ?: ($rd['title'] ?? null);
        }
        if ($contentHtml === null) {
            throw new \RuntimeException('Không trích xuất được nội dung chính.');
        }

        // 6) Fallback title
        $title = $title ?: $this->fallbackTitle($rendered);

        // 7) Hậu xử lý: loại header/nav/breadcrumb/share/live (giữ figure/picture/img)
        $contentHtml = $this->postClean($contentHtml);

        // 8) Duyệt đúng thứ tự đoạn & ảnh (hỗ trợ noscript/picture/srcset/amp-img/background-image)
        [$ordered, $paragraphs, $images] = $this->walkOrdered($contentHtml, $url);

        // 9) Nếu còn “khô ảnh” → og:image/twitter:image
        if (!$images) {
            foreach ($this->fallbackMetaImages($rendered, $url) as $m) {
                $ordered[] = ['type'=>'image','src'=>$m,'alt'=>''];
                $images[]  = ['src'=>$m,'alt'=>''];
            }
        }

        // 10) Khử trùng lặp
        $images  = $this->uniqueImages($images);
        $ordered = $this->dedupeOrderedImages($ordered);

        return [
            'url'          => $url,
            'title'        => $title,
            'content_html' => $contentHtml,
            'ordered'      => $ordered,
            'paragraphs'   => $paragraphs,
            'images'       => $images,
        ];
    }

    /* ---------------- Headless render ---------------- */

    protected function renderHtml(string $url): string
    {
        // Đợi mạng im + lazy-load xong, tăng viewport dài để ảnh lười tải hết
        $html = Browsershot::url($url)
            ->timeout(60)
            ->waitUntilNetworkIdle()               // chờ mạng yên
            ->setDelay(800)                        // chờ lazy JS
            ->windowSize(1280, 3000)               // dài để ảnh viewport tải
            ->deviceScaleFactor(1)
            ->userAgent('Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome Safari')
            ->bodyHtml();

        if (!mb_check_encoding($html, 'UTF-8')) {
            $html = mb_convert_encoding($html, 'UTF-8', 'auto');
        }
        return $html;
    }

    /* ---------------- JSON-LD Article first ---------------- */

    /** Lấy articleBody/description + images từ JSON-LD (schema.org Article/NewsArticle) nếu có */
    protected function pickFromJsonLdArticle(string $fullHtml, string $baseUrl, ?string &$titleOut): ?string
    {
        $dom = $this->html5->loadHTML($fullHtml);
        $xp  = new \DOMXPath($dom);
        $nodes = $xp->query("//script[@type='application/ld+json']");
        if (!$nodes || !$nodes->length) return null;

        $bestHtml = null; $bestLen = 0;

        foreach ($nodes as $n) {
            $json = trim($n->textContent ?? '');
            if ($json === '') continue;
            // Một số site nhét nhiều object trong mảng
            $decoded = json_decode($json, true);
            if (!$decoded) continue;

            $items = is_assoc($decoded) ? [$decoded] : (is_array($decoded) ? $decoded : []);
            foreach ($items as $obj) {
                if (!is_array($obj)) continue;
                $type = strtolower((string)($obj['@type'] ?? ''));
                if (!in_array($type, ['newsarticle','article','reportage','blogposting'], true)) continue;

                $title  = trim((string)($obj['headline'] ?? $obj['name'] ?? ''));
                $body   = $obj['articleBody'] ?? null;
                $desc   = $obj['description'] ?? null;

                // ảnh từ JSON-LD
                $images = [];
                if (!empty($obj['image'])) {
                    if (is_string($obj['image'])) $images[] = $obj['image'];
                    if (is_array($obj['image'])) {
                        foreach ($obj['image'] as $img) {
                            if (is_string($img)) $images[] = $img;
                            elseif (is_array($img) && isset($img['url'])) $images[] = $img['url'];
                        }
                    }
                }

                // dựng content Html
                $html = '';
                if ($body && is_string($body)) {
                    // body thường là text thuần → wrap <p>
                    foreach (preg_split('~\n{2,}~', trim($body)) as $p) {
                        $p = trim($p);
                        if ($p !== '') $html .= '<p>'.htmlspecialchars($p, ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</p>';
                    }
                } elseif ($desc && is_string($desc)) {
                    $html .= '<p>'.htmlspecialchars(trim($desc), ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</p>';
                }

                foreach ($images as $u) {
                    $u = $this->toAbsoluteUrl($u, $baseUrl);
                    $html .= '<figure><img src="'.htmlspecialchars($u,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'" alt=""></figure>';
                }

                $len = mb_strlen(strip_tags($html));
                if ($len > $bestLen) { $bestLen = $len; $bestHtml = $html; }
                if ($title && !$titleOut) $titleOut = $title;
            }
        }
        return $bestHtml;
    }

    /* ---------------- AMP helper ---------------- */

    protected function fetchAmpHtmlIfAny(string $fullHtml, string $baseUrl): ?string
    {
        $dom = $this->html5->loadHTML($fullHtml);
        $xp  = new \DOMXPath($dom);
        $n   = $xp->query("//link[translate(@rel,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='amphtml']/@href");
        if (!$n || !$n->length) return null;
        $amp = $this->toAbsoluteUrl(trim($n->item(0)->nodeValue ?? ''), $baseUrl);
        if ($amp === '') return null;

        try {
            $res = $this->http->get($amp);
            if ($res->getStatusCode() >= 200 && $res->getStatusCode() < 300) {
                $html = (string) $res->getBody();
                if (!mb_check_encoding($html, 'UTF-8')) $html = mb_convert_encoding($html, 'UTF-8', 'auto');
                return $html;
            }
        } catch (\Throwable $e) {}
        return null;
    }

    /* ---------------- Readability fallback ---------------- */

    protected function runReadability(string $html, string $url, ?string &$titleOut): array
    {
        $cfg = new Configuration();
        $cfg->setFixRelativeURLs(true);
        $cfg->setOriginalURL($url);
        $rd  = new Readability($cfg);
        if ($rd->parse($html)) {
            $titleOut = $titleOut ?: $rd->getTitle();
            return ['content' => $rd->getContent(), 'title' => $rd->getTitle()];
        }
        return [];
    }

    /* ---------------- Domain rules (gọn, đủ cho 24h/Dân Trí) ---------------- */

    protected function pickByDomainRules(string $url, string $fullHtml): ?string
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        $dom  = $this->html5->loadHTML($fullHtml);
        $xp   = new \DOMXPath($dom);
        $qs   = [];

        if (str_contains($host,'24h.com.vn')) {
            $qs = [
                "//*[@id='article_body']",
                "//*[@itemprop='articleBody']",
                "//div[contains(@class,'ctn_content') or contains(@class,'ctn-contents')]",
                "//div[contains(@class,'text-content') or contains(@class,'text-conent')]",
                "//article//*[contains(@class,'article-content') or contains(@class,'maincontent')]",
            ];
        } elseif (str_contains($host,'dantri.com.vn')) {
            $qs = [
                "//*[contains(@class,'dt-news__content')]",
                "//article//*[contains(@class,'article__body')]",
                "//*[@id='dantri-content']",
                "//*[contains(@class,'singular-content') or contains(@class,'news-content')]",
                "//div[contains(@class,'content__body') or contains(@class,'detail__content')]",
            ];
        } elseif (str_contains($host,'vnexpress.net')) {
            $qs = [
                "//*[@itemprop='articleBody']",
                "//article//*[contains(@class,'fck_detail')]",
                "//div[contains(@class,'detail__content')]",
            ];
        } else {
            return null;
        }

        foreach ($qs as $q) {
            $nodes = @$xp->query($q);
            if ($nodes && $nodes->length) {
                $n = $nodes->item(0);
                $html = '';
                foreach (iterator_to_array($n->childNodes) as $c) $html .= $dom->saveHTML($c);
                if (trim($html) !== '' && preg_match('~<(p|img|figure|picture|amp-img)\b~i',$html)) return $html;
            }
        }
        return null;
    }

    /* ---------------- Heuristic picker (không phụ thuộc domain) ---------------- */

    protected function smartPickMainNode(string $fullHtml): ?string
    {
        $dom = $this->html5->loadHTML($fullHtml);
        $xp  = new \DOMXPath($dom);

        $bad = ['breadcrumb','nav','menu','header','footer','toolbar','tool','utility',
            'share','social','tag','keyword','author','byline','meta',
            'related','recommend','suggest','comment','rating','vote',
            'ad','ads','banner','qc','quangcao',
            'live','truc-tiep','livestream','ticker','boxlive','live-box'];
        $good = ['article','content','main','detail','post','entry','body','news','story'];

        $cands = [];
        foreach (['//article','//main','//section','//div'] as $q) {
            $nodes = @$xp->query($q);
            if ($nodes) foreach ($nodes as $n) $cands[] = $n;
        }
        $cands = array_values(array_unique($cands, SORT_REGULAR));

        $best = null; $bestScore = -INF;
        foreach ($cands as $el) {
            if (!($el instanceof \DOMElement)) continue;
            $cls = strtolower($el->getAttribute('class') ?? '');
            $id  = strtolower($el->getAttribute('id') ?? '');
            foreach ($bad as $t) if (str_contains($cls,$t)||str_contains($id,$t)) continue 2;

            $score = $this->scoreNode($el);
            foreach ($good as $t) if (str_contains($cls,$t)||str_contains($id,$t)) { $score*=1.15; break; }
            $p = $el->getElementsByTagName('p')->length; $score += min($p,50)*2.0;

            if ($score>$bestScore){ $bestScore=$score; $best=$el; }
        }
        if (!$best) return null;

        $html=''; foreach (iterator_to_array($best->childNodes) as $c) $html.=$dom->saveHTML($c);
        if (!preg_match('~<(p|img|figure|picture|amp-img)\b~i',$html)) return null;
        return $html;
    }

    protected function scoreNode(\DOMElement $el): float
    {
        $textLen=0;
        foreach ($el->getElementsByTagName('p') as $p) {
            $txt=trim(preg_replace('/\s+/u',' ',$p->textContent??'')); $textLen+=mb_strlen($txt);
        }
        $h=0; foreach(['h1','h2','h3','h4','h5','h6'] as $t) $h+=$el->getElementsByTagName($t)->length;

        $imgCount=0;
        foreach ($el->getElementsByTagName('img') as $img) {
            $src=$img->getAttribute('src')?:$img->getAttribute('data-src')?:$img->getAttribute('data-original')?:$img->getAttribute('data-lazy')?:$img->getAttribute('data-image')?:'';
            $srcset=$img->getAttribute('srcset')?:$img->getAttribute('data-srcset');
            if ($src!==''||$srcset!=='') $imgCount++;
        }
        foreach ($el->getElementsByTagName('picture') as $pic) if ($pic->getElementsByTagName('source')->length) $imgCount++;

        $total=trim(preg_replace('/\s+/u',' ',$el->textContent??'')); $len=max(1,mb_strlen($total));
        $aLen=0; foreach($el->getElementsByTagName('a') as $a){ $aLen+=mb_strlen(trim(preg_replace('/\s+/u',' ',$a->textContent??''))); }
        $ld=$aLen/$len;

        $score=($textLen)+($imgCount*120)+($h*20);
        if ($ld>0.55) $score*=(1.0 - min(0.5, ($ld-0.55)*1.5));
        if ($textLen<400) $score*=0.7;
        return $score;
    }

    /* ---------------- Post-clean ---------------- */

    protected function postClean(string $contentHtml): string
    {
        $dom = $this->html5->loadHTML('<body>'.$contentHtml.'</body>');
        $xp  = new \DOMXPath($dom);

        foreach (['//header','//nav','//aside','//footer'] as $q) {
            foreach ($xp->query($q) as $n) $n->parentNode?->removeChild($n);
        }

        $patterns = [
            'breadcrumb','bread','cate','path','menu','nav','toolbar','tool','utility',
            'header','sticky','brand','logo',
            'share','social','zalo','facebook','twitter','like','subscribe','follow','print',
            'tag','tags','keyword','author','byline','meta',
            'related','recommend','suggest',
            'comment','comments','rating','vote',
            'ad','ads','banner','qc','quangcao',
            'live','truc-tiep','livestream','ticker','boxlive','live-box',
            'category','chuyen-muc','muc-luc','taglist','keyword-list'
        ];
        $cond = implode(' or ', array_map(fn($p)=>
        "contains(translate(@class,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'$p') or contains(translate(@id,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'$p')",
            $patterns
        ));
        foreach ($xp->query("//*[ $cond ]") as $n) {
            $tag=strtolower($n->nodeName);
            if (in_array($tag,['figure','picture','img'],true)) continue;
            $n->parentNode?->removeChild($n);
        }

        // link-density strip
        $this->removeHighLinkDensity($dom, 0.7, 140);

        // innerHTML
        $body=$dom->getElementsByTagName('body')->item(0); $out='';
        foreach ($body->childNodes as $c) $out.=$dom->saveHTML($c);
        return $out;
    }

    protected function removeHighLinkDensity(\DOMDocument $dom, float $threshold, int $maxChars): void
    {
        $walk=function(\DOMNode $node) use (&$walk,$threshold,$maxChars){
            if ($node->nodeType===XML_ELEMENT_NODE){
                /** @var \DOMElement $el */ $el=$node;
                $text=trim(preg_replace('/\s+/u',' ',$el->textContent??'')); $total=mb_strlen($text);
                if ($total>0){
                    $alink=0; foreach($el->getElementsByTagName('a') as $a){ $alink+=mb_strlen(trim(preg_replace('/\s+/u',' ',$a->textContent??''))); }
                    $d=$alink/$total;
                    if ($d>=$threshold && $total<=$maxChars){
                        if(!in_array(strtolower($el->tagName),['figure','picture','img'],true)){
                            $el->parentNode?->removeChild($el); return;
                        }
                    }
                }
            }
            foreach (iterator_to_array($node->childNodes) as $c) $walk($c);
        };
        $walk($dom->getElementsByTagName('body')->item(0));
    }

    /* ---------------- Walk ordered: text + images ---------------- */

    protected function walkOrdered(string $contentHtml, string $baseUrl): array
    {
        $dom  = $this->html5->loadHTML('<body>'.$contentHtml.'</body>');
        $body = $dom->getElementsByTagName('body')->item(0);

        $ordered=[]; $paragraphs=[]; $images=[];

        $walk=function(\DOMNode $node) use (&$walk,&$ordered,&$paragraphs,&$images,$baseUrl,$dom){
            if ($node->nodeType===XML_ELEMENT_NODE){
                /** @var \DOMElement $el */ $el=$node; $tag=strtolower($el->tagName);

                if (in_array($tag,['p','h1','h2','h3','h4','h5','h6'],true)){
                    $text=trim(preg_replace('/\s+/u',' ',$el->textContent??''));
                    if ($text!==''){ $ordered[]=['type'=>'text','tag'=>$tag,'text'=>$text]; $paragraphs[]=$text; }
                }

                if (in_array($tag,['img','picture','amp-img','figure'],true)){
                    $srcs=$this->extractImageCandidates($el);
                    foreach ($srcs as $src){ $abs=$this->toAbsoluteUrl($src,$baseUrl);
                        $ordered[]=['type'=>'image','src'=>$abs,'alt'=>$el->getAttribute('alt')??''];
                        $images[] =['src'=>$abs,'alt'=>$el->getAttribute('alt')??''];
                    }
                }

                if ($tag==='noscript'){
                    $inner=''; foreach ($el->childNodes as $c){ $inner.=$dom->saveHTML($c); }
                    if ($inner!==''){
                        $sub=$this->html5->loadHTML('<body>'.$inner.'</body>');
                        foreach ($sub->getElementsByTagName('img') as $img){
                            $srcs=$this->extractImageCandidates($img);
                            foreach ($srcs as $src){ $abs=$this->toAbsoluteUrl($src,$baseUrl);
                                $ordered[]=['type'=>'image','src'=>$abs,'alt'=>$img->getAttribute('alt')??''];
                                $images[] =['src'=>$abs,'alt'=>$img->getAttribute('alt')??''];
                            }
                        }
                    }
                }

                if (in_array($tag,['div','span','section','figure','p'],true)){
                    $bg=$this->extractBackgroundImage($el);
                    if ($bg){ $abs=$this->toAbsoluteUrl($bg,$baseUrl);
                        $ordered[]=['type'=>'image','src'=>$abs,'alt'=>''];
                        $images[] =['src'=>$abs,'alt'=>''];
                    }
                }
            }
            foreach (iterator_to_array($node->childNodes) as $c) $walk($c);
        };

        foreach (iterator_to_array($body->childNodes) as $child) $walk($child);

        return [$this->dedupeOrderedImages($ordered), $paragraphs, $this->uniqueImages($images)];
    }

    /* ---------------- Img helpers ---------------- */

    protected function extractImageCandidates(\DOMElement $el): array
    {
        $urls=[];
        if ($el->tagName==='img'||$el->tagName==='amp-img'){
            $cands=[
                $el->getAttribute('data-src'),
                $el->getAttribute('data-lazyload'),
                $el->getAttribute('data-original-src'),
                $el->getAttribute('data-original'),
                $el->getAttribute('data-zoom-image'),
                $el->getAttribute('data-lazy'),
                $el->getAttribute('data-actualsrc'),
                $el->getAttribute('data-image'),
                $el->getAttribute('data-src-mobile'),
                $el->getAttribute('data-src-large'),
                $el->getAttribute('data-src-small'),
                $el->getAttribute('data-thumb'),
                $el->getAttribute('src'),
            ];
            foreach ($cands as $c){ $c=trim($c??''); if($c!=='' && stripos($c,'blank.gif')===false){ $urls[]=$c; break; } }
            $srcset=$el->getAttribute('data-srcset-webp') ?: $el->getAttribute('data-srcset') ?: $el->getAttribute('srcset');
            if ($srcset){ $best=$this->bestFromSrcset($srcset); if($best) $urls[]=$best; }
        }
        if ($el->tagName==='picture'){
            foreach ($el->getElementsByTagName('source') as $s){
                $srcset=$s->getAttribute('data-srcset-webp') ?: $s->getAttribute('data-srcset') ?: $s->getAttribute('srcset');
                if ($srcset){ $best=$this->bestFromSrcset($srcset); if($best) $urls[]=$best; }
            }
            foreach ($el->getElementsByTagName('img') as $img){ foreach ($this->extractImageCandidates($img) as $u) $urls[]=$u; }
        }
        if ($el->tagName==='figure'){
            foreach ($el->getElementsByTagName('img') as $img){ foreach ($this->extractImageCandidates($img) as $u) $urls[]=$u; }
            foreach ($el->getElementsByTagName('picture') as $pic){ foreach ($this->extractImageCandidates($pic) as $u) $urls[]=$u; }
            $bg=$this->extractBackgroundImage($el); if ($bg) $urls[]=$bg;
        }
        return array_values(array_unique(array_filter($urls, fn($u)=>$u!=='')));
    }

    protected function extractBackgroundImage(\DOMElement $el): ?string
    {
        $style=$el->getAttribute('style')??'';
        if ($style && preg_match('~background(?:-image)?:\s*url\((["\']?)(.*?)\1\)~i',$style,$m)){
            $u=trim($m[2]); if ($u!=='' && stripos($u,'data:')!==0) return $u;
        }
        return null;
    }

    protected function bestFromSrcset(string $srcset): ?string
    {
        $parts=array_filter(array_map('trim', explode(',', $srcset)));
        $best=null; $score=-1.0;
        foreach ($parts as $p){
            $a=preg_split('/\s+/', $p); $u=$a[0]??''; $d=$a[1]??''; $s=1.0;
            if ($d!==''){ if (str_ends_with($d,'w')) $s=(float)rtrim($d,'w'); elseif (str_ends_with($d,'x')) $s=(float)rtrim($d,'x')*1000; }
            if ($u!=='' && $s>=$score){ $score=$s; $best=$u; }
        }
        return $best;
    }

    /* ---------------- Title & meta images ---------------- */

    protected function fallbackTitle(string $fullHtml): ?string
    {
        $dom=$this->html5->loadHTML($fullHtml);
        $t=$dom->getElementsByTagName('title');
        if ($t->length){ $s=trim(preg_replace('/\s+/u',' ',$t->item(0)->textContent??'')); if ($s!=='') return $s; }
        $xp=new \DOMXPath($dom);
        foreach (["//meta[@property='og:title']/@content","//meta[@name='twitter:title']/@content"] as $q){
            $n=$xp->query($q); if ($n && $n->length){ $s=trim($n->item(0)->nodeValue??''); if ($s!=='') return $s; }
        }
        return null;
    }

    protected function fallbackMetaImages(string $html, string $baseUrl): array
    {
        $dom=$this->html5->loadHTML($html);
        $xp=new \DOMXPath($dom);
        $urls=[];
        foreach ([
                     "//meta[@property='og:image']/@content",
                     "//meta[@property='og:image:url']/@content",
                     "//meta[@name='twitter:image']/@content",
                     "//meta[@name='twitter:image:src']/@content",
                 ] as $q){
            $n=$xp->query($q); if ($n && $n->length){ $u=trim($n->item(0)->nodeValue??''); if ($u!=='') $urls[]=$this->toAbsoluteUrl($u,$baseUrl); }
        }
        return array_values(array_unique($urls));
    }

    /* ---------------- Dedupe helpers ---------------- */

    protected function uniqueImages(array $images): array
    {
        $seen=[]; $out=[];
        foreach ($images as $img){ $k=$img['src']; if(!isset($seen[$k])){ $seen[$k]=1; $out[]=$img; } }
        return $out;
    }
    protected function dedupeOrderedImages(array $ordered): array
    {
        $seen=[]; $out=[];
        foreach ($ordered as $it){
            if ($it['type']!=='image'){ $out[]=$it; continue; }
            $k=$it['src']; if(!isset($seen[$k])){ $seen[$k]=1; $out[]=$it; }
        }
        return $out;
    }

    /* ---------------- URL normalize ---------------- */

    protected function toAbsoluteUrl(string $maybeRelative, string $base): string
    {
        $r=trim($maybeRelative); if ($r==='') return $r;
        if (preg_match('~^https?://~i',$r)) return $r;
        if (strpos($r,'//')===0){ $scheme=parse_url($base,PHP_URL_SCHEME)?:'https'; return $scheme.':'.$r; }
        if (stripos($r,'data:')===0) return $r;

        $p=parse_url($base); if(!$p||empty($p['scheme'])||empty($p['host'])) return $r;
        $scheme=$p['scheme']; $host=$p['host']; $port=isset($p['port'])?':'.$p['port']:'';
        $path=$p['path']??'/';
        if (str_starts_with($r,'/')) return "{$scheme}://{$host}{$port}{$r}";
        $dir=preg_replace('~/[^/]*$~','/',$path);
        return "{$scheme}://{$host}{$port}{$dir}{$r}";
    }
}

/** helper: check associative array */
function is_assoc(array $arr): bool {
    return array_keys($arr)!==range(0,count($arr)-1);
}
