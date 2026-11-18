{{--
    RICH TEXT — standalone, database driven.

    The one block type whose body is markup rather than plain text, so the
    body is read through the model's sanitising accessor and echoed raw. It is
    sanitised on save and again here, which is why this is the only place in
    the block library that uses {!! !!}.
--}}

@php
    $label = $section->get('label');
    $heading = $section->get('heading');
    $body = $section->getHtml('body');
    $centred = $section->get('align') === 'center';
@endphp

@if (filled($heading) || filled($body))
    <section class="section-padding bg-white" aria-labelledby="{{ $section->key }}-heading">
        <div class="container-wx">
            <div class="{{ $centred ? 'mx-auto max-w-3xl text-center' : 'max-w-3xl' }}">

                @if (filled($label))
                    <div class="section-label {{ $centred ? 'justify-center' : '' }}">
                        <span class="accent-bar"></span>
                        <span class="label-sm">{{ $label }}</span>
                    </div>
                @endif

                @if (filled($heading))
                    <h2 id="{{ $section->key }}-heading" class="heading-1">{{ $heading }}</h2>
                @endif

                @if ($body !== '')
                    {{-- Classes the editor's own markup may not carry. --}}
                    <div class="prose-wx mt-6">{!! $body !!}</div>
                @endif
            </div>
        </div>
    </section>
@endif
