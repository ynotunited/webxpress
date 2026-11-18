{{--
    Section block dispatcher.

    Two rendering modes, so a section is never copied just to be editable:

    1. A dedicated renderer exists at sections/blocks/<type>.blade.php.
       The block is fully database driven (logo strip, steps, card grid, CTA).

    2. No dedicated renderer, so the original section partial is used and is
       handed the block. The section keeps its own markup and layout and only
       takes the editable copy (label / heading / subheading) from the block,
       falling back to its shipped defaults when absent.

    If the block is missing, unrecognised, or the seeder has not run, the
    section partial renders exactly as it always did — so promoting a section
    to the database can never take a public page down.
--}}

@php
    // Block types are snake_case (logo_strip); view names are kebab-case
    // (logo-strip). Block keys already match their section partial name
    // (trusted-by -> sections/trusted-by), so the key is used verbatim.
    $typePartial = $section?->type
        ? 'sections.blocks.'.str_replace('_', '-', $section->type)
        : null;

    $fallbackName = 'sections.'.(string) ($key ?? $section?->key);

    $partial = match (true) {
        $typePartial !== null && View::exists($typePartial) => $typePartial,
        View::exists($fallbackName) => $fallbackName,
        default => null,
    };
@endphp

@if ($partial)
    @include($partial, ['section' => $section])
@endif
