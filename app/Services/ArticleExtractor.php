<?php
declare(strict_types=1);

namespace App\Services;

use GuzzleHttp\Client;
use Masterminds\HTML5;

class ArticleExtractor
{
    protected Client $http;
    protected HTML5 $html5;

    public function __construct(?Client $client = null)
    {
        $this->http  = $client ?: new Client([
            'timeout'        => 20,
            'allow_redirects'=> true,
            'http_errors'    => false,
            'verify'         => false,
            'headers'        => [
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'vi,en;q=0.8',
            ],
        ]);
        $this->html5 = new HTML5(['disable_html_ns' => true]);
    }

    public function extract(string $url): array
    {
        // 1) Tải & parse
        $html = $this->downloadHtml($url);
        $dom  = $this->html5->loadHTML($html);
        $xp   = new \DOMXPath($dom);

        // 2) Tìm container chính
        $container = $this->findContentContainer($url, $dom, $xp);
        if (!$container) {
            throw new \RuntimeException('Không tìm thấy container nội dung chính cho URL này.');
        }

        // 3) Gỡ lazy lần 1 (kéo ảnh từ data-src / noscript ra trước)
        $this->unlazyImagesInDom($container);

        // 4) Dọn rác an toàn (không xoá block có ảnh bên trong)
        $this->stripNoiseInside($container);

        // 5) Gỡ lazy lần 2 (phòng khi noscript/ảnh lộ ra sau khi dọn)
        $this->unlazyImagesInDom($container);

        // 6) Lấy tiêu đề + HTML đã sạch
        $title       = $this->findTitleIn($container) ?: $this->findGlobalH1($dom) ?: $this->titleFromHead($dom) ?: '';
        $contentHtml = $this->innerHTML($dom, $container);

        // 7) Lấy thứ tự đoạn + ảnh
        [$ordered, $paragraphs, $images] = $this->walkOrdered($container, $url, $dom);

        // 8) Khử trùng lặp ảnh / item ảnh trong ordered
        $images  = $this->uniqueImages($images);
        $ordered = $this->dedupeOrderedImages($ordered);

        // 9) Trả kết quả
        return [
            'url'          => $url,
            'title'        => $title,
            'content_html' => $contentHtml,
            'ordered'      => $ordered,
            'paragraphs'   => $paragraphs,
            'images'       => $images,
        ];
    }



    protected function unlazyImagesInDom(\DOMElement $root): void
    {
        $doc = $root->ownerDocument;
        $xp  = new \DOMXPath($doc);

        // IMG: data-* → src / srcset
        foreach ($xp->query('.//img', $root) as $imgNode) {
            /** @var \DOMElement $imgNode */
            $src = $imgNode->getAttribute('data-src')
                ?: $imgNode->getAttribute('data-original')
                    ?: $imgNode->getAttribute('data-zoom-image')
                        ?: $imgNode->getAttribute('data-image')
                            ?: $imgNode->getAttribute('data-src-mobile')
                                ?: '';

            if ($src !== '') {
                $imgNode->setAttribute('src', $src);
            }

            $srcset = $imgNode->getAttribute('data-srcset-webp')
                ?: $imgNode->getAttribute('data-srcset')
                    ?: '';
            if ($srcset !== '') {
                $imgNode->setAttribute('srcset', $srcset);
            }

            // Nếu src vẫn là placeholder SVG mà có srcset → bỏ src để trình duyệt dùng srcset
            $cur = $imgNode->getAttribute('src');
            if (stripos($cur, 'data:image/svg+xml') === 0) {
                if ($imgNode->hasAttribute('srcset')) {
                    $imgNode->removeAttribute('src');
                } elseif ($src !== '') {
                    $imgNode->setAttribute('src', $src);
                }
            }
        }

        // PICTURE/SOURCE: data-srcset(-webp)/data-srcset → srcset
        foreach ($xp->query('.//picture/source', $root) as $srcEl) {
            /** @var \DOMElement $srcEl */
            $srcset = $srcEl->getAttribute('data-srcset-webp')
                ?: $srcEl->getAttribute('data-srcset')
                    ?: '';
            if ($srcset !== '') {
                $srcEl->setAttribute('srcset', $srcset);
            }
        }

        // NOSCRIPT: nếu có <noscript><img ...>, chèn img thật ngay sau noscript
        foreach ($xp->query('.//noscript', $root) as $ns) {
            /** @var \DOMElement $ns */
            $fragHtml = '';
            foreach ($ns->childNodes as $c) $fragHtml .= $doc->saveHTML($c);
            if ($fragHtml !== '') {
                $h5  = new \Masterminds\HTML5(['disable_html_ns'=>true]);
                $sub = $h5->loadHTML('<body>'.$fragHtml.'</body>');
                $imgs = $sub->getElementsByTagName('img');
                if ($imgs->length) {
                    $real = $doc->importNode($imgs->item(0), true);
                    $ns->parentNode?->insertBefore($real, $ns->nextSibling);
            }
            }
        }
    }

    /* ============ Network ============ */

    protected function downloadHtml(string $url): string
    {
        $res  = $this->http->get($url);
        $code = $res->getStatusCode();
        if ($code < 200 || $code >= 300) {
            throw new \RuntimeException("HTTP $code khi tải URL");
        }
        $body = (string)$res->getBody();
        if ($body === '') throw new \RuntimeException('HTML rỗng');
        if (!mb_check_encoding($body, 'UTF-8')) {
            $body = mb_convert_encoding($body, 'UTF-8', 'auto');
        }
        return $body;
    }

    /* ============ Find content container (domain → common → heuristic) ============ */

    protected function findContentContainer(string $url, \DOMDocument $dom, \DOMXPath $xp): ?\DOMElement
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

        // 0) Bạn yêu cầu đơn giản: thử article.singular-container trước
        $node = $this->qOne($xp, "//article[contains(concat(' ', normalize-space(@class), ' '), ' singular-container ')]");
        if ($node) return $node;

        // 1) Domain rules
        if (str_contains($host, '24h.com.vn')) {
            foreach ([
                         "//*[@id='article_body']",
                         "//*[@itemprop='articleBody']",
                         "//div[contains(@class,'ctn_content') or contains(@class,'ctn-contents')]",
                         "//div[contains(@class,'text-content') or contains(@class,'text-conent')]",
                         "//article//*[contains(@class,'article-content') or contains(@class,'maincontent')]",
                         "//div[contains(@class,'baiViet') or contains(@class,'bvContent') or contains(@class,'newscont')]",
                     ] as $q) {
                if ($n = $this->qOne($xp, $q)) return $n;
            }
        } elseif (str_contains($host, 'vnexpress.net')) {
            foreach ([
                         "//*[@itemprop='articleBody']",
                         "//article//*[contains(@class,'fck_detail')]",
                         "//div[contains(@class,'detail__content')]",
                     ] as $q) {
                if ($n = $this->qOne($xp, $q)) return $n;
            }
        } elseif (str_contains($host, 'dantri.com.vn')) {
            foreach ([
                         "//*[contains(@class,'dt-news__content')]",
                         "//article//*[contains(@class,'article__body')]",
                         "//*[@id='dantri-content']",
                         "//*[contains(@class,'singular-content') or contains(@class,'news-content')]",
                         "//div[contains(@class,'content__body') or contains(@class,'detail__content')]",
                     ] as $q) {
                if ($n = $this->qOne($xp, $q)) return $n;
            }
        }

        // 2) Common selectors
        foreach ([
                     "//article",
                     "//*[@role='main']",
                     "//main",
                     "//*[contains(@class,'detail__content')]",
                     "//*[contains(@class,'fck_detail')]",
                     "//*[contains(@class,'content') and not(contains(@class,'comment'))]",
                     "//*[contains(@class,'main-content') or contains(@class,'article-content') or contains(@class,'post-content') or contains(@class,'entry-content')]",
                 ] as $q) {
            if ($n = $this->qOne($xp, $q)) return $n;
        }

        // 3) Heuristic: chọn node có nhiều chữ nhất trong tập selector phổ biến (tránh sidebar/ads)
        $cands = [];
        foreach ([
                     "//div", "//section", "//article", "//main"
                 ] as $q) {
            $list = @$xp->query($q);
            if ($list && $list->length) {
                foreach ($list as $n) {
                    if (!($n instanceof \DOMElement)) continue;
                    $cls = strtolower($n->getAttribute('class') ?? '');
                    $id  = strtolower($n->getAttribute('id') ?? '');
                    // loại bớt những nhóm dễ là rác
                    if (preg_match('~(breadcrumb|nav|menu|header|footer|toolbar|tool|utility|share|social|tag|keyword|comment|ad|ads|banner|qc|quangcao|live|truc-tiep|ticker)~', $cls.' '.$id)) {
                        continue;
                    }
                    $txt = trim(preg_replace('/\s+/u', ' ', $n->textContent ?? ''));
                    $len = mb_strlen($txt);
                    if ($len >= 500) { // chỉ xét những khối đủ dài
                        $cands[] = ['n'=>$n, 'len'=>$len];
                    }
                }
            }
        }
        if ($cands) {
            usort($cands, fn($a,$b)=> $b['len'] <=> $a['len']);
            return $cands[0]['n'];
        }

        return null;
    }

    protected function qOne(\DOMXPath $xp, string $query): ?\DOMElement
    {
        $nodes = @$xp->query($query);
        if ($nodes && $nodes->length) {
            $n = $nodes->item(0);
            return ($n instanceof \DOMElement) ? $n : null;
        }
        return null;
    }

    /* ============ Clean up inside container ============ */

    protected function stripNoiseInside(\DOMElement $root): void
    {
        $doc = $root->ownerDocument;
        $xp  = new \DOMXPath($doc);

        // 1) Xoá các thẻ chắc chắn không cần render nội dung
        foreach ($xp->query('.//script|.//style|.//svg|.//audio|.//video|.//iframe|.//form|.//canvas', $root) as $n) {
            $n->parentNode?->removeChild($n);
    }

        // 2) Các pattern thường là rác – CHỈ xoá khi KHÔNG chứa ảnh bên trong
        $patterns = [
            'breadcrumb','bread','cate','path','menu','nav','topbar','toolbar','tool','utility','aside','sidebar',
            'header','sticky','brand','logo','footer',
            'share','social','zalo','facebook','twitter','like','subscribe','follow','print','copylink',
            'tag','tags','keyword','keywords','author','byline','meta','source','copyright',
            'related','recommend','suggest','tin-lien-quan','box-related','box-suggest','readmore',
            'comment','comments','rating','vote',
            'ad','ads','banner','qc','quangcao','dfp','gpt',
            'live','truc-tiep','livestream','ticker','boxlive','live-box',
            'article-audio','audio-player','audio-module','player','video','embed',
            'tool-item','toolbox','utilities',
            'autolink','taglist','keyword-list'
        ];

        $cond = implode(' or ', array_map(
            fn($p) => "contains(translate(@class,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'), '$p') or contains(translate(@id,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'), '$p')",
            $patterns
        ));

        // chỉ xoá node khớp pattern và KHÔNG chứa img/picture/figure/noscript[img]
        $query = ".//*[ ($cond) and not(.//img or .//picture or .//figure or .//noscript[contains(.,'<img')]) ]";
        foreach ($xp->query($query, $root) as $n) {
            $n->parentNode?->removeChild($n);
    }

        // 3) Bóc <a> nội bộ (giữ text), tránh còn autolink rác
        foreach ($xp->query('.//a', $root) as $a) {
            /** @var \DOMElement $a */
            $href = strtolower($a->getAttribute('href') ?? '');
            // un-wrap link nội bộ hoặc rỗng
            if ($href === '' || str_starts_with($href, '/')) {
                $frag = $doc->createDocumentFragment();
                while ($a->firstChild) $frag->appendChild($a->firstChild);
                $a->parentNode?->replaceChild($frag, $a);
        }
        }

        // 4) Xoá p trống
        foreach ($xp->query('.//p', $root) as $p) {
            $text = trim(html_entity_decode($p->textContent ?? '', ENT_QUOTES|ENT_SUBSTITUTE, 'UTF-8'));
            if ($text === '') $p->parentNode?->removeChild($p);
    }
    }


    /* ============ Title helpers ============ */

    protected function findTitleIn(\DOMElement $container): ?string
    {
        foreach (['h1','h2'] as $t) {
            $nodes = $container->getElementsByTagName($t);
            if ($nodes->length) {
                $s = trim(preg_replace('/\s+/u', ' ', $nodes->item(0)->textContent ?? ''));
                if ($s !== '') return $s;
            }
        }
        return null;
    }

    protected function findGlobalH1(\DOMDocument $dom): ?string
    {
        $h1 = $dom->getElementsByTagName('h1');
        if ($h1->length) {
            $s = trim(preg_replace('/\s+/u', ' ', $h1->item(0)->textContent ?? ''));
            return $s ?: null;
        }
        return null;
    }

    protected function titleFromHead(\DOMDocument $dom): ?string
    {
        $ts = $dom->getElementsByTagName('title');
        if ($ts->length) {
            $t = trim(preg_replace('/\s+/u', ' ', $ts->item(0)->textContent ?? ''));
            return $t ?: null;
        }
        return null;
    }

    protected function innerHTML(\DOMDocument $dom, \DOMElement $el): string
    {
        $html = '';
        foreach ($el->childNodes as $c) {
            $html .= $dom->saveHTML($c);
        }
        return $html;
    }

    /* ============ Walk ordered (text + images) ============ */

    protected function walkOrdered(\DOMElement $root, string $baseUrl, \DOMDocument $dom): array
    {
        $ordered = []; $paragraphs = []; $images = [];

        $walk = function (\DOMNode $node) use (&$walk,&$ordered,&$paragraphs,&$images,$baseUrl,$dom) {
            if ($node->nodeType === XML_ELEMENT_NODE) {
                /** @var \DOMElement $el */
                $el  = $node;
                $tag = strtolower($el->tagName);

                if (in_array($tag, ['p','h1','h2','h3','h4','h5','h6'], true)) {
                    $text = trim(preg_replace('/\s+/u', ' ', $el->textContent ?? ''));
                    if ($text !== '') {
                        $ordered[]    = ['type'=>'text','tag'=>$tag,'text'=>$text];
                        if ($tag === 'p') $paragraphs[] = $text;
                    }
                }

                if (in_array($tag, ['figure','picture','img','amp-img'], true)) {
                    $srcs = $this->extractImageCandidates($el);
                    foreach ($srcs as $src) {
                        $abs = $this->toAbsoluteUrl($src, $baseUrl);
                        $item = ['type'=>'image','src'=>$abs,'alt'=>$el->getAttribute('alt') ?? ''];
                        // dedupe ngay tại chỗ theo key
                        static $seenLocal = [];
                        $k = $this->imageKey($item);
                        if (!isset($seenLocal[$k])) {
                            $seenLocal[$k] = true;
                            $ordered[] = $item;
                            $images[]  = ['src'=>$abs,'alt'=>$item['alt']];
                        }
                    }
                    // Nếu là figure/picture → đã xử lý rồi, KHÔNG đi sâu tiếp
                    if ($tag === 'figure' || $tag === 'picture') return;
                }

                if ($tag === 'noscript') {
                    $inner = '';
                    foreach ($el->childNodes as $c) $inner .= $dom->saveHTML($c);
                    if ($inner !== '') {
                        $sub = (new HTML5(['disable_html_ns'=>true]))->loadHTML('<body>'.$inner.'</body>');
                        foreach ($sub->getElementsByTagName('img') as $img) {
                            $srcs = $this->extractImageCandidates($img);
                            foreach ($srcs as $src) {
                                $abs = $this->toAbsoluteUrl($src, $baseUrl);
                                $item = ['type'=>'image','src'=>$abs,'alt'=>$img->getAttribute('alt') ?? ''];
                                static $seenLocal2 = [];
                                $k = $this->imageKey($item);
                                if (!isset($seenLocal2[$k])) {
                                    $seenLocal2[$k] = true;
                                    $ordered[] = $item;
                                    $images[]  = ['src'=>$abs,'alt'=>$item['alt']];
                                }
                            }
                        }
                    }
                    // không cần đi sâu hơn
                    return;
                }
            }
            foreach (iterator_to_array($node->childNodes) as $c) $walk($c);
        };

        foreach (iterator_to_array($root->childNodes) as $child) $walk($child);

        // dedupe toàn cục một lần nữa theo key chuẩn hoá
        return [$this->dedupeOrderedImages($ordered), $paragraphs, $this->uniqueImages($images)];
    }


    /* ============ Image helpers ============ */

    protected function extractImageCandidates(\DOMElement $el): array
    {
        $urls = [];

        if ($el->tagName === 'img' || $el->tagName === 'amp-img') {
            // Ưu tiên srcset (chọn best) → data-original → data-src → src
            $srcset = $el->getAttribute('data-srcset') ?: $el->getAttribute('srcset');
            if ($srcset) {
                $best = $this->bestFromSrcset($srcset);
                if ($best) return [$best];
            }
            foreach (['data-original','data-src','data-zoom-image','data-image','data-src-mobile','src'] as $attr) {
                $v = trim($el->getAttribute($attr) ?? '');
                if ($v !== '' && stripos($v, 'blank.gif') === false) { return [$v]; }
            }
            return [];
        }

        if ($el->tagName === 'picture') {
            // picture → ưu tiên source/srcset; nếu không có thì tới <img> con
            foreach ($el->getElementsByTagName('source') as $source) {
                $srcset = $source->getAttribute('data-srcset') ?: $source->getAttribute('srcset');
                if ($srcset) {
                    $best = $this->bestFromSrcset($srcset);
                    if ($best) return [$best];
                }
            }
            foreach ($el->getElementsByTagName('img') as $img) {
                $c = $this->extractImageCandidates($img);
                if ($c) return $c;
            }
            return [];
        }

        if ($el->tagName === 'figure') {
            // figure → lấy đúng 1 ảnh tốt nhất trong figure (tránh lặp picture+img)
            $best = null; $bestScore = -1;
            // source/srcset
            foreach ($el->getElementsByTagName('source') as $source) {
                $srcset = $source->getAttribute('data-srcset') ?: $source->getAttribute('srcset');
                if ($srcset) {
                    $u = $this->bestFromSrcset($srcset);
                    if ($u) { $best = $u; $bestScore = 999999; }
                }
            }
            // img fallback
            if ($best === null) {
                foreach ($el->getElementsByTagName('img') as $img) {
                    foreach ($this->extractImageCandidates($img) as $u) {
                        // nếu có width trong URL → chấm điểm cao hơn
                        $score = 1;
                        if (preg_match('~/thumb_w/(\d+)/~', $u, $m)) $score = (int)$m[1];
                        if ($score > $bestScore) { $bestScore = $score; $best = $u; }
                    }
                }
            }
            if ($best) return [$best];

            // bg-image cuối cùng
            $bg = $this->extractBackgroundImage($el);
            return $bg ? [$bg] : [];
        }

        // bg-image cho div/span/section/p nếu có
        $bg = $this->extractBackgroundImage($el);
        return $bg ? [$bg] : [];
    }



    protected function extractBackgroundImage(\DOMElement $el): ?string
    {
        $style = $el->getAttribute('style') ?? '';
        if ($style && preg_match('~background(?:-image)?:\s*url\((["\']?)(.*?)\1\)~i', $style, $m)) {
            $u = trim($m[2]);
            if ($u !== '' && stripos($u, 'data:') !== 0) return $u;
        }
        return null;
    }

    protected function bestFromSrcset(string $srcset): ?string
    {
        $cands = array_filter(array_map('trim', explode(',', $srcset)));
        $best = null; $bestScore = -1.0;
        foreach ($cands as $cand) {
            $parts = preg_split('/\s+/', trim($cand));
            $url   = $parts[0] ?? '';
            $desc  = $parts[1] ?? '';
            $score = 1.0;
            if ($desc !== '') {
                if (str_ends_with($desc, 'w')) { $score = (float) rtrim($desc, 'w'); }
                elseif (str_ends_with($desc, 'x')) { $score = (float) rtrim($desc, 'x') * 1000.0; }
            }
            if ($url !== '' && $score >= $bestScore) { $bestScore = $score; $best = $url; }
        }
        return $best;
    }

    /* ============ Utils ============ */

//    protected function uniqueImages(array $images): array
//    {
//        $seen = []; $out = [];
//        foreach ($images as $img) {
//            $k = $img['src'];
//            if (!isset($seen[$k])) { $seen[$k] = true; $out[] = $img; }
//        }
//        return $out;
//    }

//    protected function dedupeOrderedImages(array $ordered): array
//    {
//        $seen = []; $out = [];
//        foreach ($ordered as $item) {
//            if ($item['type'] !== 'image') { $out[] = $item; continue; }
//            $k = $item['src'];
//            if (!isset($seen[$k])) { $seen[$k] = true; $out[] = $item; }
//        }
//        return $out;
//    }

    protected function toAbsoluteUrl(string $maybeRelative, string $base): string
    {
        $r = trim($maybeRelative);
        if ($r === '') return $r;
        if (preg_match('~^https?://~i', $r)) return $r;
        if (strpos($r, '//') === 0) {
            $scheme = parse_url($base, PHP_URL_SCHEME) ?: 'https';
            return $scheme . ':' . $r;
        }
        if (stripos($r, 'data:') === 0) return $r;

        $p = parse_url($base);
        if (!$p || empty($p['scheme']) || empty($p['host'])) return $r;

        $scheme = $p['scheme']; $host = $p['host'];
        $port = isset($p['port']) ? ':' . $p['port'] : '';
        $path = $p['path'] ?? '/';

        if (str_starts_with($r, '/')) return "{$scheme}://{$host}{$port}{$r}";
        $dir = preg_replace('~/[^/]*$~', '/', $path);
        return "{$scheme}://{$host}{$port}{$dir}{$r}";
    }


    protected function canonicalImageUrl(string $u): string
    {
        $u = trim($u);
        if ($u === '') return $u;

        // bỏ querystring/hash
        $u = preg_replace('~[?#].*$~', '', $u);

        // Dân Trí: gom cùng ảnh khác width
        $u = preg_replace('~/thumb_w/\d+/~', '/thumb_w/XXX/', $u);

        // 24h: đôi khi có tham số cắt/scale trong path → gom thô (nếu cần thì thêm pattern)
        // $u = preg_replace('~/crop/\d+x\d+/~', '/crop/XXXxXXX/', $u);

        // bỏ dấu "/" dư
        $u = preg_replace('~(?<!:)//+~', '/', $u);

        return $u;
    }

    protected function imageKey(array $img): string
    {
        $src = is_array($img) ? ($img['src'] ?? '') : (string)$img;
        return strtolower($this->canonicalImageUrl($src));
    }

    protected function uniqueImages(array $images): array
    {
        $seen = []; $out = [];
        foreach ($images as $img) {
            $k = $this->imageKey($img);
            if ($k === '') continue;
            if (!isset($seen[$k])) { $seen[$k] = true; $out[] = $img; }
        }
        return $out;
    }

    protected function dedupeOrderedImages(array $ordered): array
    {
        $seen = []; $out = [];
        foreach ($ordered as $item) {
            if (($item['type'] ?? '') !== 'image') { $out[] = $item; continue; }
            $k = $this->imageKey($item);
            if ($k === '') continue;
            if (!isset($seen[$k])) { $seen[$k] = true; $out[] = $item; }
        }
        return $out;
    }


}
