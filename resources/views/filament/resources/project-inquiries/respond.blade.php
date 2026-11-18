<x-filament-panels::page>
    {{-- Summary --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/5">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                    {{ $record->project_type ?: 'Project inquiry' }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $record->name }} &lt;{{ $record->email }}&gt;
                </p>
                @if($record->budget || $record->timeline)
                    <p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
                        @if($record->budget) Budget: {{ $record->budget }} @endif
                        @if($record->budget && $record->timeline) &middot; @endif
                        @if($record->timeline) Timeline: {{ $record->timeline }} @endif
                    </p>
                @endif
            </div>
            <div class="flex gap-2">
                <a href="{{ \App\Filament\Resources\ProjectInquiries\ProjectInquiryResource::getUrl('view', ['record' => $record]) }}"
                   class="inline-flex items-center rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5">
                    View brief
                </a>
            </div>
        </div>
    </div>

    {{-- Thread --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-white/5">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-white/10">
            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Conversation
            </h3>
        </div>
        <div class="space-y-4 px-6 py-6">
            @forelse($this->thread() as $item)
                @php($inbound = $item['direction'] === 'inbound')
                <div class="flex {{ $inbound ? 'justify-start' : 'justify-end' }}">
                    <div class="max-w-[80%] rounded-xl px-4 py-3 text-sm leading-relaxed
                        {{ $inbound
                            ? 'border border-gray-200 bg-gray-50 text-gray-800 dark:border-white/10 dark:bg-white/5 dark:text-gray-200'
                            : 'bg-[#1B2D5F] text-white' }}">
                        <div class="mb-1 flex items-center justify-between gap-4">
                            <span class="text-[11px] font-semibold uppercase tracking-wide {{ $inbound ? 'text-gray-500' : 'text-white/70' }}">
                                {{ $inbound ? ($record->name ?: 'Client') : 'Webxpress Team' }}
                                @if($item['type'] === 'confirmation') &middot; confirmation @endif
                                @if($item['type'] === 'note') &middot; note @endif
                                @if($item['type'] === 'follow_up') &middot; AI follow-up @endif
                                @if($item['type'] === 'auto_reply') &middot; auto-reply @endif
                            </span>
                            @if($item['received_at'])
                                <span class="text-[11px] {{ $inbound ? 'text-gray-400' : 'text-white/60' }}">
                                    {{ \Illuminate\Support\Carbon::parse($item['received_at'])->format('M j, H:i') }}
                                </span>
                            @endif
                        </div>
                        <div class="whitespace-pre-line">{{ $item['body'] }}</div>
                    </div>
                </div>
            @empty
                <p class="py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                    No messages yet.
                </p>
            @endforelse
        </div>
    </div>

    {{-- Composer --}}
    <form wire:submit="sendReply" class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/10 dark:bg-white/5">
        <div class="mb-3 flex items-center justify-between">
            <label for="replyBody" class="text-sm font-semibold text-gray-900 dark:text-white">
                Reply to {{ $record->name ?: $record->email }}
            </label>
        </div>

        @error('replyBody')
            <p class="mb-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror

        <textarea
            wire:model="replyBody"
            id="replyBody"
            rows="6"
            placeholder="Write your reply here — or let AI suggest a draft first."
            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-[#E31937] focus:ring-[#E31937]/30 focus:ring-2 dark:border-white/10 dark:bg-white/5 dark:text-white"
        ></textarea>
        <div class="mb-2 flex items-center justify-end">
            <span class="text-xs text-gray-400">{{ strlen($replyBody) }}/5000</span>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button
                type="button"
                wire:click="suggestReply"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-white/10 dark:text-gray-300 dark:hover:bg-white/5"
            >
                <span wire:loading.remove wire:target="suggestReply">Suggest with AI</span>
                <span wire:loading wire:target="suggestReply" class="animate-pulse">Drafting…</span>
            </button>

            <button
                type="submit"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 rounded-lg bg-[#E31937] px-4 py-2 text-sm font-semibold text-white hover:bg-[#c21430] disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="sendReply">Send reply</span>
                <span wire:loading wire:target="sendReply" class="animate-pulse">Sending…</span>
            </button>
        </div>
    </form>
</x-filament-panels::page>