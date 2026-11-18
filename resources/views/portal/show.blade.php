<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Client Portal — {{ $deal->project_title ?: 'Your Project' }}</title>
    <meta name="robots" content="noindex, nofollow">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100 min-h-svh antialiased">
    {{-- Top bar --}}
    <header class="bg-navy-800 text-white">
        <div class="container-wx py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-white font-display font-bold tracking-wide text-lg">WEBXPRESS<span class="text-red">.</span></span>
                <span class="hidden sm:inline-block ml-3 px-2.5 py-0.5 rounded-full bg-white/10 text-xs font-medium tracking-wide text-white/80">Client Portal</span>
            </div>
            <a href="mailto:{{ config('mail.inquiries_to', 'hello@webxpresstech.com.ng') }}" class="text-xs text-white/70 hover:text-white transition-colors">Need help?</a>
        </div>
    </header>

    <main class="container-wx max-w-3xl py-8 md:py-12">
        {{-- Flash banners carry role="status" so they are announced when the portal
             loads. Without it they were purely visual: a screen reader user
             landing after a payment redirect had no way to learn it worked. --}}
        @if(session('success'))
            <div role="status" class="mb-6 rounded-lg border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-800 leading-relaxed">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div role="alert" class="mb-6 rounded-lg border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700 leading-relaxed">
                {{ session('error') }}
            </div>
        @endif
        @if(session('notice'))
            <div role="status" class="mb-6 rounded-lg border border-gray-200 bg-gray-50 px-5 py-4 text-sm text-gray-700 leading-relaxed">
                {{ session('notice') }}
            </div>
        @endif

        {{-- Project heading --}}
        <section class="mb-8">
            <p class="label-sm text-gray-600 mb-2">Welcome, {{ $deal->name }}</p>
            <h1 class="heading-2 mb-2">{{ $deal->project_title ?: 'Your Project' }}</h1>
            <div class="flex flex-wrap items-center gap-2 text-sm text-gray-500">
                @php
                    $statusLabel = \App\Models\Deal::statusOptions()[$deal->status] ?? $deal->status;
                    $statusTone = match ($deal->status) {
                        \App\Models\Deal::STATUS_COMPLETED => 'bg-green-100 text-green-700 border-green-200',
                        \App\Models\Deal::STATUS_CANCELLED => 'bg-gray-100 text-gray-500 border-gray-200',
                        \App\Models\Deal::STATUS_IN_PROGRESS => 'bg-navy-50 text-navy-800 border-navy-200',
                        default => 'bg-red-50 text-red-700 border-red-200',
                    };
                @endphp
                <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold {{ $statusTone }}">
                    {{ $statusLabel }}
                </span>
                @if(($deal->start_date))
                    <span>Starts {{ $deal->start_date->format('M j, Y') }}</span>
                @endif
                @if($deal->duration_label)
                    <span>&middot; {{ $deal->duration_label }}</span>
                @endif
            </div>
        </section>

        {{-- Payment section --}}
        @php
            $pending = $payments->first(fn ($p) => $p->status === \App\Models\Payment::STATUS_PENDING);
            $upfrontPaid = $payments->contains(fn ($p) => $p->kind === 'upfront' && $p->status === 'paid');
            $deliveryPaid = $payments->contains(fn ($p) => $p->kind === 'delivery' && $p->status === 'paid');
        @endphp

        @if($deal->status !== \App\Models\Deal::STATUS_CANCELLED)
        <section class="mb-8">
            <div class="bg-navy-800 rounded-lg px-6 py-6 text-white shadow-sm">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        {{-- white/60 on navy-800 sits right at the 4.5:1 threshold; white/70 is
                         6.10:1 and leaves margin for the small uppercase size. --}}
                        <p class="text-xs uppercase tracking-wider text-white/70 font-semibold mb-1">Payment status</p>
                        @if($pending)
                            <p class="text-lg font-semibold">
                                {{ $pending->kind === 'upfront' ? 'Upfront (' . $deal->upfront_percent . '%)' : 'Balance (' . $deal->delivery_percent . '%)' }} due
                            </p>
                            <p class="text-sm text-white/70">₦{{ number_format($pending->amount, 2) }}</p>
                        @elseif($upfrontPaid && $deliveryPaid)
                            <p class="text-lg font-semibold">Fully paid — thank you</p>
                            <p class="text-sm text-white/70">₦{{ number_format($deal->amount, 2) }} received</p>
                        @elseif($upfrontPaid)
                            <p class="text-lg font-semibold">Upfront received</p>
                            <p class="text-sm text-white/70">Balance due on delivery</p>
                        @else
                            <p class="text-lg font-semibold">No payment due yet</p>
                            <p class="text-sm text-white/70">We'll share your payment link shortly.</p>
                        @endif
                    </div>
                    @if($pending && $pending->authorization_url)
                        <a href="{{ route('deal.pay', [$deal->access_token, $pending->id]) }}"
                           class="btn btn-primary btn-shine rounded-md !min-h-12 shrink-0">
                            Pay ₦{{ number_format($pending->amount, 2) }}
                        </a>
                    @endif
                </div>
            </div>
        </section>
        @endif

        {{-- Documents --}}
        <section class="mb-8">
            <h2 class="label-sm text-gray-600 mb-3">Project Documents</h2>
            <div class="space-y-4">
                @foreach($documents as $document)
                    <article class="card !hover:transform-none">
                        <header class="flex items-center justify-between mb-3">
                            <h3 class="font-display font-bold text-navy-800 text-base">{{ $document->title }}</h3>
                            @if($document->type === \App\Models\DealDocument::TYPE_UPLOAD && $document->file_path)
{{-- "Open attachment" gave three links on a page the same name and no way to
                                     tell them apart. red-700 instead of red for the
                                     4.5:1 requirement. --}}
                                <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank" rel="noopener"
                                   class="text-xs font-semibold text-red-700 hover:text-red-800 transition-colors">
                                     Open {{ $document->title }} attachment &nearr;
                                 </a>
                            @endif
                        </header>
                        @if($document->type === \App\Models\DealDocument::TYPE_UPLOAD && $document->file_path)
                            <p class="text-sm text-gray-500">{{ $document->file_name ?: $document->file_path }}</p>
                        @else
                            <div class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">
                                {{ $workflow->renderDocumentContent($document) }}
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        </section>

        {{-- Receipts --}}
        @php
            $allReceipts = $payments->filter(fn ($p) => $p->receipt !== null);
        @endphp
        @if($allReceipts->isNotEmpty())
        <section class="mb-8">
            <h2 class="label-sm text-gray-600 mb-3">Receipts</h2>
            <div class="card !hover:transform-none">
                <ul class="divide-y divide-gray-100">
                    @foreach($allReceipts as $p)
                        @if($p->receipt)
                            <li class="flex items-center justify-between py-3 text-sm">
                                <div>
                                    <p class="font-semibold text-gray-700">{{ $p->receipt->receipt_no }}</p>
                                    <p class="text-gray-600 text-xs">{{ $p->receipt->issued_at->format('M j, Y g:i A') }} &middot; ₦{{ number_format($p->receipt->amount, 2) }}</p>
                                </div>
                                <a href="{{ route('deal.receipt', [$deal->access_token, $p->receipt->id]) }}"
                                   class="font-semibold text-red-700 hover:text-red-800 transition-colors">View receipt {{ $p->receipt->receipt_no }} &nearr;</a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </section>
        @endif

        {{-- Footer --}}
        <footer class="pt-6 border-t border-gray-200 mt-2">
            <p class="text-xs text-gray-600 leading-relaxed">
                Webxpress Technologies — <a href="{{ url('/') }}" class="hover:text-navy-800 transition-colors">webxpresstech.com.ng</a>
                &middot; Lagos, Nigeria<br>
                This is your private project portal. Link expires on request only — contact us anytime to update details.
            </p>
        </footer>
    </main>
</body>
</html>