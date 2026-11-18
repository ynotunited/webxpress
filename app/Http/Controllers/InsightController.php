<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class InsightController extends Controller
{
    public function index()
    {
        return view('pages.insights.index', [
            'posts' => $this->posts()->paginate(12),
        ]);
    }

    public function loadMore()
    {
        $posts = $this->posts()->paginate(12);

        return response()->json([
            'html' => view('pages.insights._cards', ['posts' => $posts])->render(),
            'has_more' => $posts->hasMorePages(),
            'next_page' => $posts->currentPage() + 1,
        ]);
    }

    private function posts()
    {
        return Post::query()
            ->published()
            ->with('category')
            ->orderByDesc('published_at');
    }

    public function show(string $slug)
    {
        $post = Post::query()
            ->published()
            ->where('slug', $slug)
            ->with('category')
            ->first();

        if ($post === null) {
            throw new NotFoundHttpException;
        }

        return view('pages.insights.show', [
            'post' => $post,
        ]);
    }
}
