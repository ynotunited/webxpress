{{-- Before/after detail for one audit entry.
     Reached by clicking "View" on the Details column in the audit trail. --}}
@php
    $changes = $record->changes ?? [];
@endphp

<div class="space-y-4 text-sm">
    <dl class="grid grid-cols-2 gap-x-4 gap-y-2">
        <div>
            <dt class="text-gray-500 dark:text-gray-400">When</dt>
            <dd class="font-medium text-gray-950 dark:text-white">
                {{ $record->created_at?->format('d M Y, H:i:s') }}
            </dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">Who</dt>
            <dd class="font-medium text-gray-950 dark:text-white">
                {{ $record->user?->email ?? $record->user_email ?? 'system' }}
            </dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">Record</dt>
            <dd class="font-medium text-gray-950 dark:text-white">
                {{ $record->subjectLabel() }} #{{ $record->subject_id }}
            </dd>
        </div>
        <div>
            <dt class="text-gray-500 dark:text-gray-400">IP</dt>
            <dd class="font-medium text-gray-950 dark:text-white">
                {{ $record->ip_address ?? '—' }}
            </dd>
        </div>
    </dl>

    @if (filled($changes))
        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-white/10">
            <table class="w-full table-fixed text-left">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="w-1/5 px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Field</th>
                        <th class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">Before</th>
                        <th class="px-3 py-2 font-medium text-gray-500 dark:text-gray-400">After</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($changes as $field => $pair)
                        <tr class="align-top">
                            <td class="px-3 py-2 font-medium text-gray-950 dark:text-white">
                                {{ $field }}
                            </td>
                            <td class="px-3 py-2 text-red-700 dark:text-red-400">
                                {{ is_array($pair) ? ($pair['old'] ?? '—') : '—' }}
                            </td>
                            <td class="px-3 py-2 text-green-700 dark:text-green-400">
                                {{ is_array($pair) ? ($pair['new'] ?? '—') : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-gray-500 dark:text-gray-400">
            No field-level changes were stored for this entry.
        </p>
    @endif
</div>