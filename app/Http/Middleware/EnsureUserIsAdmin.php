<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Panel access, enforced outside Filament.
 *
 * The admin preview route renders a page through the public controller stack,
 * so it is not covered by the panel's own authentication middleware. Without
 * this, "auth" alone would let any logged-in user read a draft page.
 *
 * The check is the same canAccessPanel() the panel itself uses, so there is
 * one definition of who the admin is rather than two that can drift.
 */
class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! method_exists($user, 'canAccessPanel')) {
            abort(403);
        }

        abort_unless($user->canAccessPanel(Filament::getPanel('admin')), 403);

        return $next($request);
    }
}
