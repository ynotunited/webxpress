<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\DealPortalController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IndustryController;
use App\Http\Controllers\InsightController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\WorkController;
use App\Http\Middleware\VerifyPaystackWebhookSource;
use App\Support\PageBlocks;
use App\Models\Page;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Website Routes
|--------------------------------------------------------------------------
|
| Order is load-bearing at the bottom of this file: the CMS catch-all must be
| registered last so it can never shadow a route above it. Everything above
| the catch-all is matched first and wins.
|
*/

// Health check. Kept deliberately boring: deployment checks and uptime
// monitors need a route that cannot fail because of CMS content or mail.
Route::get('/up', fn () => response('ok', 200)->header('Content-Type', 'text/plain'))->name('health');

// Homepage
Route::get('/', [HomeController::class, 'index'])->name('home');

// Portfolio / Work
Route::get('/work', [WorkController::class, 'index'])->name('work.index');
Route::get('/work/{slug}', [WorkController::class, 'show'])->name('work.show');

// Services
Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
Route::get('/services/{slug}', [ServiceController::class, 'show'])->name('services.show');

// About
Route::get('/about', function () {
    return view('pages.about', [
        'title' => 'About Webxpress Technologies | Established 2010',
        'seoTitle' => 'About Webxpress Technologies | Digital Product Studio Since 2010',
        'seoDescription' => 'Webxpress Technologies has been building web and SaaS solutions for Nigerian businesses since 2010. Learn about our story, philosophy, and capabilities.',
        'blocks' => PageBlocks::forSlug('about'),
        'page' => Page::query()->where('slug', 'about')->where('is_published', true)->first(),
    ]);
})->name('about');

// Industries
Route::get('/industries', [IndustryController::class, 'index'])->name('industries.index');
Route::get('/industries/{slug}', [IndustryController::class, 'show'])->name('industries.show');

// Insights / Blog
Route::get('/insights', [InsightController::class, 'index'])->name('insights.index');
Route::get('/insights/load-more', [InsightController::class, 'loadMore'])->name('insights.load-more');
Route::get('/insights/{slug}', [InsightController::class, 'show'])->name('insights.show');

// Contact
Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:contact');

/*
|--------------------------------------------------------------------------
| CMS-Rendered Pages
|--------------------------------------------------------------------------
|
| Pages that are prose end-to-end: the template is the page body, and the
| admin edits the text.
|
*/

// Privacy Policy (CMS-backed — Site → Site Pages → privacy)
Route::get('/privacy', [PageController::class, 'show'])->defaults('slug', 'privacy')->name('privacy');

// Terms of Service (CMS-backed — Site → Site Pages → terms)
Route::get('/terms', [PageController::class, 'show'])->defaults('slug', 'terms')->name('terms');

/*
|--------------------------------------------------------------------------
| Client Deal Portal (token-secured, no auth)
|--------------------------------------------------------------------------
*/

Route::get('/d/{deal:access_token}', [DealPortalController::class, 'show'])->name('deal.portal');
Route::get('/d/{deal:access_token}/pay/{payment}', [DealPortalController::class, 'pay'])->name('deal.pay');
Route::get('/d/{deal:access_token}/pay/{payment}/callback', [DealPortalController::class, 'callback'])->name('deal.pay.callback');
Route::get('/d/{deal:access_token}/receipts/{receipt}', [DealPortalController::class, 'receipt'])->name('deal.receipt');

/*
| Paystack webhook.
|
| The URL has to be reachable by the gateway, so it cannot sit behind auth or
| CSRF. What replaces those is applied here, in the order that matters: the
| source allowlist runs first and rejects a stranger before the signature is
| ever computed, the throttle caps what one address can cost, and only then
| does the controller get to decide what the event means.
|
| The throttle exists because the gateway's own volume is negligible — a retry
| every three minutes — so anything approaching the limit is not Paystack.
*/
Route::post('/webhooks/paystack', [DealPortalController::class, 'webhook'])
    ->withoutMiddleware(VerifyCsrfToken::class)
    ->middleware([
        VerifyPaystackWebhookSource::class,
        'throttle:60,1',
    ])
    ->name('webhooks.paystack');

/*
|--------------------------------------------------------------------------
| Admin Preview
|--------------------------------------------------------------------------
|
| Lets an editor see an unpublished page exactly as a visitor would, without
| publishing it. Authenticated and behind the panel's own access check, so a
| draft is not a public URL — a signed URL would leak the draft to anyone it
| was forwarded to, and drafts are usually the work in progress.
|
| Registered above the catch-all because it is a two-segment path and the
| catch-all is one segment, so the two cannot collide; it is here for
| readability, not for precedence.
|
*/

Route::get('/preview/pages/{slug}', [PageController::class, 'preview'])
    ->middleware('admin')
    ->defaults('slug', '')
    ->name('pages.preview');

/*
|--------------------------------------------------------------------------
| CMS Catch-All — MUST BE LAST
|--------------------------------------------------------------------------
|
| Serves any published page that has no route of its own, from its own hero,
| body and blocks. This is what makes creating a page in the admin produce a
| real page rather than a row nothing renders.
|
| Registered last, so every route above wins. The slug pattern additionally
| excludes the segments the application already owns at the root, so a page
| can never be created at a path that the framework serves itself.
|
| The panel's own segment comes from admin_path() rather than being listed here,
| so moving the panel with ADMIN_PATH cannot leave a CMS page free to be created
| on top of the new location — which would shadow the login form and hand the
| panel's own path to the catch-all.
*/

Route::get('/{slug}', [PageController::class, 'generic'])
    ->where('slug', '^(?!'.implode('|', [
        admin_path(),
        'api',
        'build',
        'css',
        'd',
        'filament',
        'horizon',
        'js',
        'livewire',
        'pulse',
        'storage',
        'telescope',
        'up',
        'vendor',
        'webhooks',
    ]).'$)[A-Za-z0-9][A-Za-z0-9\-]*$')
    ->name('page');
