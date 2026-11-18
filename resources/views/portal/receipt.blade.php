<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $receipt->receipt_no }}</title>
    <meta name="robots" content="noindex, nofollow">
    @vite(['resources/css/app.css'])
    {{-- Nonced: the print stylesheet is inline, and style-src is nonce-gated.
         Without it the .no-print blocks would be dropped and the on-screen
         chrome would print along with the receipt. --}}
    <style nonce="{{ csp_nonce() }}">
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
            .print-sheet { box-shadow: none !important; border: none !important; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-svh antialiased">
    @php($deal = $payment->deal)
    <div class="container-wx max-w-2xl py-8 md:py-12">
        <div class="no-print mb-6 flex items-center justify-between">
            <a href="{{ route('deal.portal', $deal->access_token) }}" class="text-sm font-semibold text-gray-500 hover:text-navy-800 transition-colors">
                &larr; Back to portal
            </a>
            <button type="button" onclick="window.print()"
                    class="btn btn-secondary rounded-md !min-h-11 text-sm px-5 py-2.5">
                Print / Save PDF
            </button>
        </div>

        {{-- Receipt sheet --}}
        <div class="print-sheet bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div class="bg-navy-800 px-8 py-6 flex items-center justify-between">
                <div>
                    <span class="text-white font-display font-bold tracking-wide text-xl">WEBXPRESS<span class="text-red">.</span></span>
                    <p class="text-white/60 text-xs mt-1 tracking-wide">Payment Receipt</p>
                </div>
                <div class="text-right">
                    <p class="text-white font-display font-bold tracking-wide">{{ $receipt->receipt_no }}</p>
                    <p class="text-white/60 text-xs mt-1">{{ $receipt->issued_at->format('F j, Y') }}</p>
                </div>
            </div>

            <div class="px-8 py-8">
                <div class="mb-8">
                    <p class="text-xs uppercase tracking-wider text-gray-600 font-semibold mb-1">Received from</p>
                    <p class="font-semibold text-gray-800">{{ $deal->name }}</p>
                    @if($deal->company)
                        <p class="text-sm text-gray-500">{{ $deal->company }}</p>
                    @endif
                    <p class="text-sm text-gray-500">{{ $deal->email }}</p>
                </div>

                {{-- This table is a label/value list, not a grid of comparable data, so it
                     uses <th scope="row"> on the left column. Without that,
                     assistive tech read each row as loose text and a reader
                     scanning for the amount had no way to know what any
                     number referred to. --}}
                <table class="w-full mb-8">
                    <caption class="sr-only">Payment receipt {{ $receipt->receipt_no }} details</caption>
                    <tbody class="divide-y divide-gray-100">
                        <tr>
                            <th scope="row" class="py-3 text-sm text-gray-600 w-1/2 text-left font-normal">Project</th>
                            <td class="py-3 text-sm font-semibold text-gray-800 text-right">{{ $deal->project_title ?: '—' }}</td>
                        </tr>
                        <tr>
                            <th scope="row" class="py-3 text-sm text-gray-600 text-left font-normal">Payment phase</th>
                            <td class="py-3 text-sm font-semibold text-gray-800 text-right">
                                {{ $payment->kind === 'upfront' ? 'Upfront (' . $deal->upfront_percent . '%)' : 'Balance on delivery (' . $deal->delivery_percent . '%)' }}
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" class="py-3 text-sm text-gray-600 text-left font-normal">Paystack reference</th>
                            <td class="py-3 text-sm text-gray-700 text-right">{{ $payment->paystack_reference }}</td>
                        </tr>
                        <tr>
                            <th scope="row" class="py-3 text-sm text-gray-600 text-left font-normal">Payment date</th>
                            <td class="py-3 text-sm text-gray-700 text-right">{{ $receipt->issued_at->format('M j, Y g:i A') }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="flex items-center justify-between border-t-2 border-navy-800 pt-5">
                    <p class="font-display font-bold text-navy-800 text-lg">Amount</p>
                    <p class="font-display font-bold text-navy-800 text-2xl">₦{{ number_format($receipt->amount, 2) }}</p>
                </div>
            </div>

            <div class="px-8 py-5 bg-gray-50 border-t border-gray-100">
                <p class="text-xs text-gray-600 leading-relaxed">
                    This receipt confirms payment for the project described above. For any questions, contact
                    {{ config('mail.inquiries_to', 'hello@webxpresstech.com.ng') }}.
                </p>
            </div>
        </div>
    </div>
</body>
</html>