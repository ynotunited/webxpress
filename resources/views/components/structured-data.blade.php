{{--
    JSON-LD for crawlers that do not run JavaScript.

    The page copy is written for a person; this states the same facts in a shape
    a machine can read without inference. The graph is built from content the
    page already renders, so it cannot claim more than the copy does.

    The payload is escaped by App\Support\StructuredData with JSON_HEX_TAG and
    friends before it reaches this file, so it is safe to emit unescaped.
--}}
@php($structuredData = App\Support\StructuredData::forPage(request()->path()))
@if ($structuredData !== null)
    {{-- Nonced for consistency with every other inline block here. JSON-LD is
         inert (it is not a JavaScript MIME type) so the policy does not gate it,
         but there is no reason for it to be the one script tag without one. --}}
    <script type="application/ld+json" nonce="{{ csp_nonce() }}">{!! App\Support\StructuredData::toScriptTag($structuredData) !!}</script>
@endif