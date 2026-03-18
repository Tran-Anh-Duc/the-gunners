<?php

namespace App\Http\Controllers;

use App\Services\RenderedArticleExtractor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ArticleExtractorController extends BaseController
{
    public function __construct(
        protected RenderedArticleExtractor $extractor
    ) {
    }

    public function form(): View
    {
        return view('article-extract.form');
    }

    public function extractView(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'url' => ['required', 'url'],
        ]);

        try {
            $data = $this->extractor->extract($validated['url']);
        } catch (Throwable $exception) {
            return back()
                ->withInput()
                ->withErrors(['url' => $exception->getMessage()]);
        }

        return view('article-extract.result', compact('data'));
    }
}
