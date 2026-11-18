<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    |
    | The app ships with zero third-party scripts — the audit that led here is
    | summarised in the policy below. That is what makes a strict policy
    | possible: there is no list of external origins to trust, so the policy can
    | say 'self' and mean it. Add a CDN script tomorrow and this header is what
    | stops it rendering until someone updates this file on purpose.
    |
    | CSP_ENABLED        master switch. Off = no header at all.
    | CSP_REPORT_ONLY    send Content-Security-Policy-Report-Only instead.
    |                    Turn this on first when changing the policy: browsers
    |                    report violations without breaking the page, so you
    |                    find out what you blocked before anyone else does.
    | CSP_REPORT_URI     a collector to POST violations to. Empty = no endpoint,
    |                    which is fine — report-only still logs to the console.
    |
    */

    // filter_var: (bool) env('X', false) casts the *string* 'false' to true —
    // a one-letter typo in .env silently flips the whole feature.
    'enabled' => filter_var(env('CSP_ENABLED', true), FILTER_VALIDATE_BOOL),

    'report_only' => filter_var(env('CSP_REPORT_ONLY', false), FILTER_VALIDATE_BOOL),

    'report_uri' => env('CSP_REPORT_URI'),

    /*
    | Base policy, applied to every response.
    |
    | script-src and style-src deliberately carry no 'unsafe-inline'. Our own
    | inline <script> and <style> blocks are nonced at render time via
    | csp_nonce(); anything without a nonce is blocked, which is the whole
    | point — an injected tag cannot run just because it was injected.
    |
    | style-src-attr is the exception, and it has to be: 67 legitimate
    | style="..." attributes sit across 22 view files, and a nonce cannot be
    | applied to an attribute. Style attributes cannot execute code, so the
    | residual risk is exfiltration via url() in a style attribute — narrow,
    | and worth it against rewriting every one of those into utility classes.
    |
    | object-src 'none' and media-src 'none': the site has no plugins and no
    | audio or video. base-uri 'self' stops a <base> tag being injected ahead
    | of the document and silently rewriting every relative URL. form-action
    | 'self' means a form cannot be pointed at another origin.
    |
    | frame-ancestors 'self' matters most on the deal portal: it is a
    | token-gated page showing a client their contract and payment amounts,
    | reachable with no login, which makes it a prime clickjacking target —
    | overlay it, let the victim think they are clicking something harmless.
    |
    */
    'policy' => [
        'default-src' => ["'self'"],
        'base-uri' => ["'self'"],
        'connect-src' => ["'self'"],
        'font-src' => ["'self'", 'data:'],
        'form-action' => ["'self'"],
        'frame-ancestors' => ["'self'"],
        'img-src' => ["'self'", 'data:', 'blob:'],
        'manifest-src' => ["'self'"],
        'media-src' => ["'none'"],
        'object-src' => ["'none'"],
        'script-src' => ["'self'"],
        'style-src' => ["'self'"],
        'style-src-attr' => ["'unsafe-inline'"],
        'worker-src' => ["'self'"],
    ],

    /*
    | Admin panel overrides, merged over the base policy for requests under
    | admin_path().
    |
    | This is the one place the policy is loose, and it is loose because the
    | frameworks force it rather than because it was wanted:
    |
    |   - Filament renders inline <script> in base.blade.php, index.blade.php,
    |     sidebar.blade.php and others — including branches that depend on
    |     runtime config (dark mode, broadcasting) and that interpolate @js()
    |     values. There is no nonce attribute on any of them.
    |   - Livewire emits its own inline scripts the same way.
    |   - Neither package offers nonce support; neither has for years.
    |
    | Hashes are not a way out either: the scripts are conditional, so every
    | possible branch would need its own hash, re-derived on each Filament
    | upgrade, and one missing hash silently breaks the panel.
    |
    | The cost: if attacker-controlled markup were ever rendered unescaped
    | inside the panel, this half of the policy would not stop it executing.
    | The mitigations are that Filament escapes on output, there is a single
    | administrator account behind the fail-closed ADMIN_EMAIL lock, and
    | everything in the panel is already admin-authored content. The public
    | site — where markup can arrive from a contact form or an inquiry — keeps
    | the strict half.
    |
    | If you ever upgrade Filament or Livewire, re-check for nonce support
    | before assuming this is still unavoidable.
    */
    'admin' => [
        'script-src' => ["'self'", "'unsafe-inline'"],
        'style-src' => ["'self'", "'unsafe-inline'"],
    ],

    /*
    | Origins allowed only while the Vite dev server is running, detected by
    | the hot file it writes to public/hot.
    |
    | In dev, @vite points at http://localhost:5173 instead of /build and the
    | HMR client opens a WebSocket back to it. Without these the dev server
    | would load a blank page and no build error would say why.
    */
    'dev' => [
        'origin' => 'http://localhost:5173',
        'script-src' => ["'http://localhost:5173'"],
        'style-src' => ["'http://localhost:5173'"],
        'connect-src' => ["'http://localhost:5173'", "'ws://localhost:5173'"],
    ],

];
