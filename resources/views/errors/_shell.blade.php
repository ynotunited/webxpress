{{--
    Shared shell for HTTP error pages.

    Intentionally self-contained: no @vite, no database reads, no
    components. A 500 is frequently a database or asset-build outage, so
    anything that queries the DB or requires a compiled manifest would
    take the error page down with it.

    Expects: $errorCode, $errorHeading, $errorMessage
--}}<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $errorHeading }} — Webxpress Technologies</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <style nonce="{{ csp_nonce() }}">
        *, *::before, *::after { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100svh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            color: #1B2D5F;
            font-family: "Plus Jakarta Sans", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
            padding: 2rem 1.25rem;
        }
        .wx-error { max-width: 34rem; width: 100%; text-align: center; }
        .wx-code {
            font-size: clamp(4.5rem, 18vw, 8rem);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.04em;
            color: #E31937;
            margin: 0;
        }
        .wx-rule { width: 3.5rem; height: 3px; background: #E31937; border-radius: 999px; margin: 1.5rem auto; }
        h1 { font-size: clamp(1.5rem, 4vw, 2rem); font-weight: 700; margin: 0 0 0.75rem; letter-spacing: -0.02em; }
        p { margin: 0 auto 2rem; color: #64748b; line-height: 1.7; max-width: 30rem; }
        .wx-actions { display: flex; gap: 0.75rem; justify-content: center; flex-wrap: wrap; }
        .wx-btn {
            display: inline-block; padding: 0.8rem 1.6rem; border-radius: 999px;
            font-weight: 600; font-size: 0.95rem; text-decoration: none;
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .wx-btn:hover { transform: translateY(-1px); }
        .wx-btn-primary { background: #1B2D5F; color: #fff; }
        .wx-btn-primary:hover { box-shadow: 0 12px 28px -14px rgba(27,45,95,.7); }
        /* #e2e8f0 against white is 1.16:1, so the ghost button's boundary was
           effectively missing. #8C8C8C is 3.36:1 and still reads as a hairline. */
        .wx-btn-ghost { background: #fff; color: #1B2D5F; border: 1px solid #8C8C8C; }
        .wx-foot { margin-top: 2.5rem; font-size: 0.8rem; color: #64748b; }

        /* Same two-band ring as the main stylesheet. This page cannot load
           app.css by design (a 500 may be an asset-build outage), so the
           indicator has to be declared here too. Red inside, white outside,
           so it clears 3:1 on both the navy fill and the white ghost. */
        .wx-btn:focus-visible {
            outline: 2px solid #fff;
            outline-offset: 2px;
            box-shadow: 0 0 0 2px #E31937;
        }

        @media (prefers-reduced-motion: reduce) {
            .wx-btn { transition: none; }
            .wx-btn:hover { transform: none; }
        }
    </style>
</head>
<body>
    <main class="wx-error">
        <p class="wx-code">{{ $errorCode }}</p>
        <div class="wx-rule"></div>
        <h1>{{ $errorHeading }}</h1>
        <p>{{ $errorMessage }}</p>

        <div class="wx-actions">
            <a href="/" class="wx-btn wx-btn-primary">Back to homepage</a>
            <a href="/contact" class="wx-btn wx-btn-ghost">Contact us</a>
        </div>

        <p class="wx-foot">Webxpress Technologies</p>
    </main>
</body>
</html>
