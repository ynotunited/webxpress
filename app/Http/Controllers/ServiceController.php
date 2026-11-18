<?php

namespace App\Http\Controllers;

use App\Models\ClientReview;
use App\Models\Page;
use App\Services\ServiceCatalog;
use App\Support\PageBlocks;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceController extends Controller
{
    public function index()
    {
        $reviews = ClientReview::forDisplay()->get();
        $page = Page::query()->where('slug', 'services')->where('is_published', true)->first();

        return view('pages.services.index', [
            'reviews' => $reviews,
            'blocks' => PageBlocks::forSlug('services'),
            'page' => $page,
        ]);
    }

    public function show(string $slug)
    {
        $service = ServiceCatalog::get($slug);

        if ($service === null) {
            throw new NotFoundHttpException;
        }

        $page = Page::query()->where('slug', $slug)->where('is_published', true)->first();

        $related = collect(ServiceCatalog::all())
            ->except($slug)
            ->map(fn (array $item, string $key): array => $item + ['slug' => $key])
            ->values()
            ->all();

        return view('pages.services.show', [
            'slug' => $slug,
            'service' => $service,
            'related' => $related,
            'page' => $page,
        ]);
    }
}
