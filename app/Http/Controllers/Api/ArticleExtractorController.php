<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ArticleExtractor;
use Masterminds\HTML5;

class ArticleExtractorController extends Controller
{
    /** Form nhập URL (demo) */
    public function form()
    {
        return view('article-extract.form');
    }

    /**
     * Render ra view: bóc bài + gỡ lazy ảnh (data-src → src, data-srcset → srcset)
     * Route gợi ý: POST /article-extract/view
     */
    public function extractView(Request $request, ArticleExtractor $extractor)
    {
        $validated = $request->validate([
            'url' => ['required', 'url'],
        ]);

        try {
            $data = $extractor->extract($validated['url']);

            // Gỡ lazy trong content_html để ảnh hiển thị ngay
            $data['content_html'] = $this->unlazyImagesDom($data['content_html']);

            // (Tuỳ chọn) loại trùng ảnh 1020/1360, giữ ảnh to nhất
            $data['images'] = $this->dedupeKeepLargest($data['images']);

            return view('article-extract.result', ['data' => $data]);
        } catch (\Throwable $e) {
            return back()->withErrors(['url' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Trả JSON kết quả bóc bài (không đụng tới view)
     * Route gợi ý: GET /article-extract/json?url=...
     */
    public function extractJson(Request $request, ArticleExtractor $extractor)
    {
        $validated = $request->validate([
            'url' => ['required', 'url'],
        ]);

        try {
            $data = $extractor->extract($validated['url']);
            // Nếu muốn trả content_html đã gỡ lazy, bật dòng dưới:
            // $data['content_html'] = $this->unlazyImagesDom($data['content_html']);

            return response()->json($data, 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()], 400);
        }
    }

    /* ===================== Private helpers ===================== */

    /**
     * Gỡ lazyload cho ảnh trong HTML fragment:
     * - data-src/data-original/... -> src
     * - data-srcset(-webp)/data-srcset -> srcset
     * - xử lý <picture><source>, <noscript><img>
     * - xoá placeholder SVG nếu còn
     */
    private function unlazyImagesDom(string $html): string
    {
        // Bọc vào <body> để parse an toàn
        $h5  = new HTML5(['disable_html_ns' => true]);
        $dom = $h5->loadHTML('<body>'.$html.'</body>');
        $xp  = new \DOMXPath($dom);

        // IMG
        foreach ($xp->query('//img') as $img) {
            /** @var \DOMElement $img */
            $src = $img->getAttribute('data-src')
                ?: $img->getAttribute('data-original')
                    ?: $img->getAttribute('data-zoom-image')
                        ?: $img->getAttribute('data-image')
                            ?: $img->getAttribute('data-src-mobile')
                                ?: '';

            if ($src !== '') {
                $img->setAttribute('src', $src);
            }

            $srcset = $img->getAttribute('data-srcset-webp')
                ?: $img->getAttribute('data-srcset')
                    ?: '';
            if ($srcset !== '') {
                $img->setAttribute('srcset', $srcset);
            }

            // Nếu src là placeholder SVG thì bỏ để trình duyệt dùng srcset, hoặc dùng $src gỡ lazy
            $cur = $img->getAttribute('src');
            if (stripos($cur, 'data:image/svg+xml') === 0) {
                if ($img->hasAttribute('srcset')) {
                    $img->removeAttribute('src');
                } elseif ($src !== '') {
                    $img->setAttribute('src', $src);
                }
            }
        }

        // PICTURE/SOURCE
        foreach ($xp->query('//picture/source') as $src) {
            /** @var \DOMElement $src */
            $srcset = $src->getAttribute('data-srcset-webp')
                ?: $src->getAttribute('data-srcset')
                    ?: '';
            if ($srcset !== '') {
                $src->setAttribute('srcset', $srcset);
            }
        }

        // NOSCRIPT ảnh → chèn ảnh thật ngay sau thẻ noscript
        foreach ($xp->query('//noscript') as $ns) {
            /** @var \DOMElement $ns */
            $fragHtml = '';
            foreach ($ns->childNodes as $c) {
                $fragHtml .= $dom->saveHTML($c);
            }
            if ($fragHtml !== '') {
                $sub = $h5->loadHTML('<body>'.$fragHtml.'</body>');
                $imgs = $sub->getElementsByTagName('img');
                if ($imgs->length) {
                    $real = $dom->importNode($imgs->item(0), true);
                    $ns->parentNode?->insertBefore($real, $ns->nextSibling);
                }
            }
        }

        // Xuất lại innerHTML của <body>
        $out = '';
        $body = $dom->getElementsByTagName('body')->item(0);
        foreach ($body->childNodes as $c) {
            $out .= $dom->saveHTML($c);
        }
        return $out;
    }

    /**
     * Dedupe ảnh kiểu Dân Trí (cùng ảnh có 2 size: thumb_w/1020 và thumb_w/1360).
     * Giữ bản có width lớn hơn.
     */
    private function dedupeKeepLargest(array $images): array
    {
        $pick = [];
        foreach ($images as $img) {
            $src = (string)($img['src'] ?? '');
            if ($src === '') continue;

            // gom nhóm theo "gốc" (bỏ phần số width trong đường dẫn)
            $key = preg_replace('~/thumb_w/\d+/~', '/thumb_w/XXX/', $src);

            $w = 0;
            if (preg_match('~/thumb_w/(\d+)/~', $src, $m)) {
                $w = (int)$m[1];
            }

            if (!isset($pick[$key]) || $w > ($pick[$key]['_w'] ?? -1)) {
                $img['_w'] = $w;
                $pick[$key] = $img;
            }
        }
        // clean field tạm
        return array_values(array_map(function ($it) {
            unset($it['_w']);
            return $it;
        }, $pick));
    }
}
