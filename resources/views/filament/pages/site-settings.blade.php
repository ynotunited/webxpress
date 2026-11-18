<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}
        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#E31937] px-4 py-2 text-sm font-semibold text-white hover:bg-[#c21430]">
                Save settings
            </button>
        </div>
    </form>
</x-filament-panels::page>