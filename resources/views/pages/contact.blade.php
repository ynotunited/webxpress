<x-layouts.app title="Start a Project — Webxpress Technologies">

    @push('scripts')
        @vite('resources/js/contact.js')
    @endpush

    <div
        x-data="startProject()"
        class="min-h-svh flex flex-col bg-gray-50 pt-24 md:pt-28 pb-12"
    >
        {{-- ── TOP BAR: progress + back link ─────────────────── --}}
        <div
            class="sticky top-[76px] md:top-[92px] z-20 bg-white/80 backdrop-blur-xl border-b border-gray-200/60 transition-colors"
            :class="totalAnswered === questions.length ? 'bg-white/95 border-gray-200' : ''"
        >
        <div class="container-wx max-w-2xl mx-auto py-3.5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="text-sm font-semibold text-gray-600 hover:text-navy-800 transition-colors tracking-tight" aria-label="Back to home">
                    ← Start
                </a>
                <span class="w-1 h-1 rounded-full bg-gray-300" aria-hidden="true"></span>
                <h1 class="text-sm font-semibold text-navy-800 tracking-tight">Start a Project</h1>
            </div>

            {{-- Progress dots --}}
            <div
                class="flex items-center gap-1.5"
                role="progressbar"
                aria-label="Project brief progress"
                :aria-valuenow="totalAnswered"
                aria-valuemin="0"
                :aria-valuemax="questions.length"
                :aria-valuetext="totalAnswered + ' of ' + questions.length + ' questions answered'"
            >
                <template x-for="(q, i) in questions" :key="q.id">
                    <span
                        class="w-2 h-2 rounded-full transition-all duration-500"
                        :class="answers[q.id]
                            ? 'bg-red-600 scale-110'
                            : (i === currentQuestionIndex ? 'bg-navy-800 scale-110' : 'bg-gray-300')"
                        aria-hidden="true"
                    ></span>
                </template>
            </div>
        </div>
    </div>

    {{-- ── CHAT BODY ───────────────────────────────────────── --}}
    <div class="flex-1 container-wx max-w-2xl mx-auto px-4 pb-8">

        {{-- Messages list --}}
        <div class="space-y-4" x-ref="chat" role="log" aria-live="polite" aria-relevant="additions" aria-label="Conversation">

            <template x-for="(msg, i) in messages" :key="i">
                <div>
                    {{-- Brand bubble --}}
                    <div
                        x-show="msg.role === 'brand'"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="flex items-start gap-3 max-w-[88%]"
                    >
                        {{-- Avatar --}}
                        {{-- The logo files are transparent, so no coloured avatar circle is
                             needed. Use the coloured mark directly on the white bubble.
                             Decorative: the accessible name comes from the "Webxpress
                             says:" sr-only prefix, not the image. --}}
                        <div class="flex items-center shrink-0 mt-1">
                            <img src="{{ brand_logo('colored') }}" class="h-4 w-auto" alt="" aria-hidden="true" width="150" height="38" decoding="async">
                        </div>
                        {{-- Bubble --}}
                        <div class="bg-white rounded-2xl rounded-tl-md px-5 py-4 shadow-[0_1px_3px_rgba(0,0,0,.06)] border border-gray-100">
                            {{-- The logo is decorative; without a text label a
                                 screen reader announced these as unlabelled
                                 paragraphs, so the conversation was a wall of
                                 anonymous text. --}}
                            <span class="sr-only">Webxpress says: </span>
                            <p class="text-gray-800 text-[0.95rem] leading-relaxed" x-text="msg.text"></p>
                        </div>
                    </div>

                    {{-- User bubble --}}
                    <div
                        x-show="msg.role === 'user'"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        class="flex justify-end"
                    >
                        <div class="bg-navy-800 text-white rounded-2xl rounded-br-md px-5 py-3.5 max-w-[80%]">
                            <span class="sr-only">You said: </span>
                            <p class="text-[0.95rem] leading-relaxed" x-text="msg.text"></p>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Typing indicator --}}
            <div
                x-show="isTyping"
                role="status"
                aria-label="Typing"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                class="flex items-start gap-3"
            >
                <div class="flex items-center shrink-0 mt-1">
                    <img src="{{ brand_logo('colored') }}" class="h-4 w-auto" alt="" aria-hidden="true" width="150" height="38" decoding="async">
                </div>
                <div class="bg-white rounded-2xl rounded-tl-md px-5 py-4 shadow-[0_1px_3px_rgba(0,0,0,.06)] border border-gray-100">
                    <div class="flex items-center gap-1.5" aria-label="Typing">
                        <span class="w-[7px] h-[7px] bg-gray-400 rounded-full typing-dot" style="animation-delay:0ms"></span>
                        <span class="w-[7px] h-[7px] bg-gray-400 rounded-full typing-dot" style="animation-delay:160ms"></span>
                        <span class="w-[7px] h-[7px] bg-gray-400 rounded-full typing-dot" style="animation-delay:320ms"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── INPUT AREA (shown after latest brand message) ─── --}}
        <div
            x-cloak
            x-show="showInput"
            x-transition:enter="transition ease-out duration-400"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="mt-5"
            x-ref="inputArea"
        >
            {{-- Option buttons. x-ref="options" is what focusCurrentControl()
                 lands on, so keyboard focus follows the question instead of
                 being dropped on <body> when these buttons are replaced. --}}
            <template x-if="inputType === 'options'">
                <div class="flex flex-wrap gap-2.5" x-ref="options">
                    <template x-for="opt in currentOptions" :key="opt.value">
                        <button
                            type="button"
                            @click="answer(opt.value, opt.label)"
                            class="min-h-[44px] px-5 py-2.5 rounded-full border-2 border-navy-800/10 text-navy-800 text-sm font-semibold hover:border-red-600 hover:text-red-600 hover:bg-red-50 active:scale-[0.97] transition-all duration-200"
                        >
                            <span x-text="opt.label"></span>
                        </button>
                    </template>
                </div>
            </template>

            {{-- Text / Email input --}}
            <template x-if="inputType === 'text' || inputType === 'email'">
                <div class="relative">
                    <label for="brief-input" class="sr-only" x-text="currentQuestion"></label>
                    <input
                        id="brief-input"
                        name="brief-input"
                        :type="inputType === 'email' ? 'email' : 'text'"
                        x-model="currentInput"
                        @keydown.enter="submitTextInput()"
                        @input="inputError = ''"
                        :placeholder="currentPlaceholder"
                        :autocomplete="inputType === 'email' ? 'email' : (currentQuestionId === 'name' ? 'name' : 'off')"
                        spellcheck="false"
                        :aria-describedby="inputError ? 'brief-error' : null"
                        :aria-invalid="inputError ? 'true' : null"
                        class="w-full min-h-[44px] px-5 py-3.5 pr-14 rounded-full border-2 border-navy-800/25 text-gray-800 text-[0.95rem] placeholder:text-gray-500 focus:border-red-600 transition-colors bg-white"
                        x-ref="textInput"
                    >
                    <button
                        type="button"
                        @click="submitTextInput()"
                        :disabled="!currentInput.trim()"
                        class="absolute right-2 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-red-600 text-white flex items-center justify-center disabled:opacity-30 disabled:cursor-not-allowed hover:bg-red-700 active:scale-95 transition-all duration-150"
                        aria-label="Continue"
                    >
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </button>
                </div>
            </template>

            {{-- Textarea --}}
            <template x-if="inputType === 'textarea'">
                <div>
                    <label for="brief-message" class="sr-only" x-text="currentQuestion"></label>
                    <textarea
                        id="brief-message"
                        name="brief-message"
                        x-model="currentInput"
                        @input="inputError = ''"
                        :placeholder="currentPlaceholder"
                        :aria-describedby="inputError ? 'brief-error' : null"
                        :aria-invalid="inputError ? 'true' : null"
                        rows="3"
                        class="w-full px-5 py-4 rounded-2xl border-2 border-navy-800/25 text-gray-800 text-[0.95rem] leading-relaxed placeholder:text-gray-500 focus:border-red-600 transition-colors resize-none bg-white"
                        x-ref="textareaInput"
                    ></textarea>
                    <div class="flex justify-end mt-2.5">
                        <button
                            type="button"
                            @click="submitTextInput()"
                            :disabled="!currentInput.trim()"
                            class="min-h-[44px] px-6 py-2.5 rounded-full bg-red-600 text-white text-sm font-semibold disabled:opacity-30 disabled:cursor-not-allowed hover:bg-red-700 active:scale-[0.97] transition-all duration-150"
                        >
                            Continue →
                        </button>
                    </div>
                </div>
            </template>

            {{-- Validation error, announced to assistive tech --}}
            <p
                id="brief-error"
                x-cloak
                x-show="inputError"
                x-text="inputError"
                role="alert"
                class="mt-2 text-sm font-medium text-red-700"
            ></p>
        </div>

        {{-- ── REVIEW CARD ──────────────────────────────────── --}}
        <div
            x-cloak
            x-show="showReview"
            x-transition:enter="transition ease-out duration-500"
            x-transition:enter-start="opacity-0 translate-y-6"
            x-transition:enter-end="opacity-100 translate-y-0"
            class="mt-8"
        >
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 md:px-8 pt-6 md:pt-8 pb-5 border-b border-gray-100">
                    {{-- tabindex="-1" so focusCurrentControl() can park the caret here when the
                     review card appears. Before this, answering the last
                     question left focus on <body>. --}}
                    <h2 x-ref="reviewHeading" tabindex="-1" class="font-display text-lg font-bold text-navy-800">Here's what we've got.</h2>
                    <p class="text-sm text-gray-500 mt-1">Review your details, then hit send.</p>
                </div>

                <div class="px-6 md:px-8 py-5 space-y-0 divide-y divide-gray-100">
                    {{-- Project type --}}
                    <div class="flex justify-between items-start py-3.5 gap-4">
                        <span class="text-sm font-medium text-gray-600 shrink-0">Project</span>
                        <span class="text-sm font-semibold text-navy-800 text-right" x-text="answers.project_type"></span>
                    </div>
                    {{-- Name --}}
                    <div class="flex justify-between items-start py-3.5 gap-4">
                        <span class="text-sm font-medium text-gray-600 shrink-0">Name</span>
                        <span class="text-sm font-semibold text-navy-800 text-right" x-text="answers.name"></span>
                    </div>
                    {{-- Email --}}
                    <div class="flex justify-between items-start py-3.5 gap-4">
                        <span class="text-sm font-medium text-gray-600 shrink-0">Email</span>
                        <span class="text-sm font-semibold text-navy-800 text-right" x-text="answers.email"></span>
                    </div>
                    {{-- Budget --}}
                    <div class="flex justify-between items-start py-3.5 gap-4">
                        <span class="text-sm font-medium text-gray-600 shrink-0">Budget</span>
                        <span class="text-sm font-semibold text-navy-800 text-right" x-text="answers.budget"></span>
                    </div>
                    {{-- Timeline --}}
                    <div class="flex justify-between items-start py-3.5 gap-4">
                        <span class="text-sm font-medium text-gray-600 shrink-0">Timeline</span>
                        <span class="text-sm font-semibold text-navy-800 text-right" x-text="answers.timeline"></span>
                    </div>
                    {{-- About --}}
                    <div class="py-3.5">
                        <span class="text-sm font-medium text-gray-600 block mb-1.5">About the project</span>
                        <p class="text-sm text-gray-700 leading-relaxed" x-text="answers.message"></p>
                    </div>
                </div>

                <div class="px-6 md:px-8 py-5 bg-gray-50 flex flex-col sm:flex-row gap-3">
                    <button
                        @click="goBack()"
                        class="px-5 py-3 rounded-full border-2 border-gray-200 text-gray-600 text-sm font-semibold hover:border-navy-800 hover:text-navy-800 transition-colors"
                    >
← Review again
                        </button>
                    <button
                        @click="submitForm()"
                        :disabled="isSubmitting"
                        class="btn btn-primary btn-shine rounded-full px-8 py-3 text-sm flex-1 sm:flex-none disabled:opacity-50"
                    >
                        <span x-show="!isSubmitting">Send project brief</span>
                        <span x-show="isSubmitting" class="flex items-center gap-2">
                            <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" fill="currentColor" class="opacity-75"/></svg>
                            Sending…
                        </span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Submit failure, announced to assistive tech --}}
        <p
            x-cloak
            x-show="submitError"
            x-text="submitError"
            role="alert"
            class="mt-4 text-center text-sm font-medium text-red-700"
        ></p>

        {{-- ── SUCCESS STATE ────────────────────────────────── --}}
        <div
            x-cloak
            x-show="isDone"
            x-transition:enter="transition ease-out duration-500"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="mt-12 text-center py-16"
            role="status"
        >
            <div class="w-16 h-16 rounded-full bg-green-50 border-2 border-green-200 flex items-center justify-center mx-auto mb-6">
                <svg class="w-7 h-7 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg>
            </div>
            <h2 x-ref="successHeading" tabindex="-1" class="font-display text-2xl font-bold text-navy-800 mb-2">Brief received.</h2>
            <p class="text-gray-500 max-w-sm mx-auto leading-relaxed mb-8">
                We'll review your project details and get back to you within 24 hours.
            </p>
            <a href="{{ route('home') }}" class="btn btn-secondary rounded-full px-8 py-3 text-sm">
                Back to home
            </a>
        </div>
    </div>

    {{-- ── ALPINE COMPONENT ──────────────────────────────────────────────
         The guided brief was 14 KB of inline script in this template. It now
         lives in resources/js/contact.js and is loaded as its own Vite entry,
         so it is fetched in parallel, cached across visits, and absent from
         every other page. A Vite module cannot interpolate a Blade route, so
         the endpoint is handed over here instead.
    --}}
    <script nonce="{{ csp_nonce() }}">window.CONTACT_ENDPOINT = @json(route('contact.store'));</script>

    <style nonce="{{ csp_nonce() }}">
        /* Typing dots pulse */
        .typing-dot {
            animation: typing-pulse 1.2s ease-in-out infinite;
        }
        @keyframes typing-pulse {
            0%, 60%, 100% { opacity: 0.3; transform: scale(0.85); }
            30%           { opacity: 1;   transform: scale(1); }
        }

        @media (prefers-reduced-motion: reduce) {
            .typing-dot { animation: none; opacity: 0.5; }
        }
    </style>

</x-layouts.app>
