<x-filament-panels::page>
    @php
        $deal = $this->record;
        $payments = collect($this->payments());
        $documents = collect($this->documents());
        $upfront = $payments->firstWhere('kind', 'upfront');
        $delivery = $payments->firstWhere('kind', 'delivery');
        $statusLabel = \App\Models\Deal::statusOptions()[$deal->status] ?? $deal->status;
        $statusColor = match ($deal->status) {
            \App\Models\Deal::STATUS_COMPLETED => 'bg-green-100 text-green-700',
            \App\Models\Deal::STATUS_CANCELLED => 'bg-gray-100 text-gray-500',
            \App\Models\Deal::STATUS_IN_PROGRESS => 'bg-blue-100 text-blue-700',
            \App\Models\Deal::STATUS_UPFRONT_PENDING, \App\Models\Deal::STATUS_DELIVERY_PENDING => 'bg-amber-100 text-amber-700',
            default => 'bg-gray-100 text-gray-700',
        };
    @endphp

    {{-- Summary card --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/5">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ $deal->project_title ?: 'Untitled project' }}
                        {{ $deal->project_type ? '· ' . $deal->project_type : '' }}
                    </h2>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusColor }}">{{ $statusLabel }}</span>
                </div>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $deal->name }} &lt;{{ $deal->email }}&gt;
                    @if($deal->phone) &middot; {{ $deal->phone }} @endif
                    @if($deal->company) &middot; {{ $deal->company }} @endif
                </p>
                <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                    Total: <span class="font-semibold text-gray-700 dark:text-gray-300">₦{{ number_format($deal->amount, 2) }}</span>
                    &middot; Upfront ({{ $deal->upfront_percent }}%): ₦{{ number_format(app(\App\Services\DealWorkflowService::class)->upfrontAmount($deal), 2) }}
                    &middot; Delivery ({{ $deal->delivery_percent }}%): ₦{{ number_format(app(\App\Services\DealWorkflowService::class)->deliveryAmount($deal), 2) }}
                </p>
                @if($deal->start_date || $deal->duration_label)
                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                        @if($deal->start_date) Starts {{ $deal->start_date->format('M j, Y') }} @endif
                        @if($deal->duration_label) &middot; {{ $deal->duration_label }} @endif
                    </p>
                @endif
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ $deal->portalUrl() }}" target="_blank"
                   class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5">
                    Open portal ↗
                </a>
                <a href="{{ \App\Filament\Resources\Deals\DealResource::getUrl('edit', ['record' => $deal]) }}"
                   class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5">
                    Edit deal
                </a>
            </div>
        </div>

        {{-- Portal link --}}
        <div class="mt-4 flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium text-gray-400">Portal link:</span>
            <code class="flex-1 min-w-[200px] rounded-lg bg-gray-100 px-3 py-1.5 text-xs text-gray-600 dark:bg-white/10 dark:text-gray-300 break-all">{{ $deal->portalUrl() }}</code>
            <button type="button"
                    onclick="navigator.clipboard.writeText('{{ $deal->portalUrl() }}')"
                    class="inline-flex items-center rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-white/20">
                Copy
            </button>
        </div>
    </div>

    {{-- Pipeline actions --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/5">
        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Pipeline</h3>
        <div class="flex flex-wrap gap-2">
            @if(in_array($deal->status, [\App\Models\Deal::STATUS_DRAFT, \App\Models\Deal::STATUS_DOCUMENTS_SENT]))
                <x-filament::button wire:click="sendPortalInvite" wire:loading.attr="disabled">
                    Send portal &amp; documents invite
                </x-filament::button>
            @endif

            @if(!empty($upfront) && ($upfront['status'] ?? '') !== 'paid')
                <x-filament::button color="danger" wire:click="generateUpfrontLink" wire:loading.attr="disabled">
                    Regenerate 70% payment link
                </x-filament::button>
            @elseif(!$upfront)
                <x-filament::button color="danger" wire:click="generateUpfrontLink" wire:loading.attr="disabled">
                    Generate 70% payment link
                </x-filament::button>
            @endif

            @if($deal->status === \App\Models\Deal::STATUS_IN_PROGRESS && !empty($delivery) && ($delivery['status'] ?? '') !== 'paid')
                <x-filament::button wire:click="markDelivered" wire:loading.attr="disabled">
                    Mark project delivered
                </x-filament::button>
            @endif

            @if($deal->status === \App\Models\Deal::STATUS_DELIVERY_PENDING)
                @if(empty($delivery) || ($delivery['status'] ?? '') !== 'paid')
                    <x-filament::button color="danger" wire:click="generateDeliveryLink" wire:loading.attr="disabled">
                        Generate 30% delivery link
                    </x-filament::button>
                @else
                    <x-filament::button color="success" wire:click="markCompleted" wire:loading.attr="disabled">
                        Mark deal complete
                    </x-filament::button>
                @endif
            @endif

            @if(!in_array($deal->status, [\App\Models\Deal::STATUS_COMPLETED, \App\Models\Deal::STATUS_CANCELLED]))
                <x-filament::button color="gray" wire:click="cancelDeal" wire:loading.attr="disabled">
                    Cancel deal
                </x-filament::button>
            @endif
        </div>

        @if($this->generatedLink)
            <div class="mt-4 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-500/20 dark:bg-green-500/10">
                <p class="mb-1 text-xs font-semibold text-green-800 dark:text-green-400">
                    {{ $this->generatedLinkKind === 'upfront' ? '70% (upfront)' : '30% (delivery)' }} payment link — copy or email it:
                </p>
                <div class="flex flex-wrap items-center gap-2">
                    <code class="flex-1 min-w-[200px] rounded-lg bg-white px-3 py-1.5 text-xs text-gray-700 break-all dark:bg-white/5">{{ $this->generatedLink }}</code>
                    <button type="button"
                            onclick="navigator.clipboard.writeText('{{ $this->generatedLink }}')"
                            class="inline-flex items-center rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50 dark:bg-white/10 dark:ring-white/10 dark:text-gray-300">
                        Copy
                    </button>
                    <button type="button" wire:click="emailPaymentLink"
                            class="inline-flex items-center rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm ring-1 ring-gray-300 hover:bg-gray-50 dark:bg-white/10 dark:ring-white/10 dark:text-gray-300">
                        Email link to client
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- Payments --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-white/5">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-white/10">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Payments</h3>
        </div>
        @if($payments->isEmpty())
            <p class="px-6 py-6 text-sm text-gray-400">No payment records yet. Generate a payment link to start.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-left text-xs text-gray-400 dark:border-white/10">
                            <th class="px-6 py-3 font-medium">Phase</th>
                            <th class="px-6 py-3 font-medium text-right">Amount</th>
                            <th class="px-6 py-3 font-medium">Status</th>
                            <th class="px-6 py-3 font-medium">Reference</th>
                            <th class="px-6 py-3 font-medium">Paid at</th>
                            <th class="px-6 py-3 font-medium">Receipt</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                        @foreach($payments as $p)
                            <tr>
                                <td class="px-6 py-3 font-medium text-gray-700 dark:text-gray-300">
                                    {{ $p['kind'] === 'upfront' ? 'Upfront ' . $deal->upfront_percent . '%' : 'Delivery ' . $deal->delivery_percent . '%' }}
                                </td>
                                <td class="px-6 py-3 text-right text-gray-700 dark:text-gray-300">₦{{ number_format($p['amount'], 2) }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                                        {{ $p['status'] === 'paid' ? 'bg-green-100 text-green-700' : ($p['status'] === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">
                                        {{ ucfirst($p['status']) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-xs text-gray-500">{{ $p['paystack_reference'] ?: '—' }}</td>
                                <td class="px-6 py-3 text-xs text-gray-500">{{ $p['paid_at'] ? \Illuminate\Support\Carbon::parse($p['paid_at'])->format('M j, Y g:i A') : '—' }}</td>
                                <td class="px-6 py-3 text-xs">
                                    @if($p['receipt'] ?? null)
                                        <a href="{{ route('deal.receipt', [$deal->access_token, $p['receipt']['id']]) }}" target="_blank"
                                           class="text-red-600 hover:text-red-700 font-semibold">#{{ $p['receipt']['receipt_no'] ?? '' }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Documents --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-white/5">
        <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between dark:border-white/10">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Documents</h3>
            <a href="{{ \App\Filament\Resources\Deals\DealResource::getUrl('edit', ['record' => $deal]) }}"
               class="text-xs font-semibold text-red-600 hover:text-red-700">Manage documents →</a>
        </div>
        @if($documents->isEmpty())
            <p class="px-6 py-6 text-sm text-gray-400">
                No custom documents — the portal will show default proposal, timeline and terms templates.
            </p>
        @else
            <ul class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach($documents as $doc)
                    <li class="flex items-center justify-between px-6 py-3 text-sm">
                        <div class="flex items-center gap-3">
                            <span class="text-gray-500">{{ $doc['title'] }}</span>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs {{ ($doc['is_active'] ?? true) ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ ($doc['is_active'] ?? true) ? 'Visible' : 'Hidden' }}
                            </span>
                        </div>
                        <span class="text-xs text-gray-400">{{ $doc['type'] === 'template' ? 'Templated' : 'Upload' }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-filament-panels::page>