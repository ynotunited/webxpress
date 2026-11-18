{{--
    GLOBAL HEADER — Webxpress Technologies
    Floating glass capsule. Staggered entrance. Hover choreography.
    Mobile: full-screen overlay menu with numbered oversized links.
--}}

@php
    use App\Support\SiteNavigation;
    use App\Services\ServiceCatalog;
    use Illuminate\Support\Str;

    $navItems = SiteNavigation::items()->all();
    $mobileItems = SiteNavigation::mobileItems()->all();
    $services = ServiceCatalog::all();
@endphp

{{-- Sentinel for scroll-state detection (moves out of view once scrolled) --}}
<span id="nav-sentinel" class="absolute top-0 left-0 w-px h-px" aria-hidden="true"></span>

<header
    x-data="{
        open: false,
        servicesOpen: false,
        scrolled: false,
        _onScroll: null,
        init() {
            this._onScroll = () => { this.scrolled = window.scrollY > 8; };
            this._onScroll();
            window.addEventListener('scroll', this._onScroll, { passive: true });
        },
        destroy() {
            if (this._onScroll) {
                window.removeEventListener('scroll', this._onScroll);
                this._onScroll = null;
            }
        },
        toggle() {
            this.open ? this.close() : this.openMenu();
        },
        openMenu() {
            this.open = true;
            document.body.classList.add('overflow-hidden');
        },
        /**
         * @param {boolean} restoreFocus Send focus back to the button that
         *   opened the menu. Only for dismissal (Escape, close button): after
         *   following a link the page is unloading, so moving focus would be
         *   pointless. Without it, dismissing the overlay left focus on the
         *   <body>, which drops a keyboard user at the top of the document.
         */
        close(restoreFocus = false) {
            const wasOpen = this.open;
            this.open = false;
            this.servicesOpen = false;
            document.body.classList.remove('overflow-hidden');

            if (restoreFocus && wasOpen) {
                this.$nextTick(() => this.$refs.menuToggle?.focus());
            }
        }
    }"
    @keydown.escape="close(true)"
    class="fixed top-0 inset-x-0 z-50 pointer-events-none"
>

    {{-- Scroll progress hairline (hidden until user scrolls) --}}
    <div
        class="nav-progress absolute top-0 left-0 right-0 z-10 pointer-events-none transition-opacity duration-300"
        style="background: linear-gradient(90deg, #E31937, #1B2D5F);"
        :class="scrolled ? 'opacity-100' : 'opacity-0'"
        aria-hidden="true"
    ></div>

    {{-- Capsule --}}
    <div class="container-wx pointer-events-none">
        <nav
            aria-label="Primary navigation"
            class="nav-capsule pointer-events-auto relative z-20 mt-3 md:mt-5 flex items-center justify-between gap-4 pl-4 pr-2 md:pl-6 md:pr-3 h-16 md:h-[72px] rounded-2xl transition-all duration-500 ease-out"
            :class="scrolled ? 'nav-capsule-scrolled' : ''"
        >
            {{-- Logo --}}
            <a
                href="{{ route('home') }}"
                class="nav-enter flex items-center shrink-0 transition-transform duration-300 hover:scale-[1.03]"
                style="--stage: 0"
                aria-label="{{ site_name() }} — Home"
            >
                <span class="relative">
                    <img src="{{ brand_logo('colored') }}" alt="{{ site_name() }}" class="h-7 w-auto md:h-8" width="150" height="38" fetchpriority="high" decoding="async" />
                    <span class="absolute -bottom-1 -right-1 w-1.5 h-1.5 rounded-full bg-red-600 nav-live-dot" aria-hidden="true"></span>
                </span>
            </a>

            {{-- Desktop links --}}
            <div class="hidden lg:flex items-center gap-1">
                @foreach ($navItems as $i => $item)
                    @if (strtolower($item['label']) === 'services')
                        {{-- x-ref="servicesTrigger" + the Escape handler below return focus to this
                                 button. Closing a disclosure and leaving focus on its
                                 removed/now-collapsed panel is a keyboard trap at the
                                 end of the panel. --}}
                        <div class="relative nav-enter" style="--stage: {{ $i + 1 }}">
                            <button
                                type="button"
                                aria-controls="services-menu"
                                data-services-trigger
                                aria-expanded="false"
                                class="nav-pill-link relative flex items-center gap-1.5 rounded-full px-3.5 py-2 text-[0.9rem] font-semibold tracking-tight {{ $item['active'] ? 'active' : '' }}"
                                {{ $item['active'] ? 'aria-current="page"' : '' }}
                            >
                                Services
                                <svg class="h-3.5 w-3.5 transition-transform duration-200" :class="servicesOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>
                            </button>
                            {{-- role="menu" is deliberately NOT used here. This is a list of links that
                             opens on click, not an application menu, so the expected
                             keyboard model is plain Tab, not arrow keys. --}}
                        <div
                            id="services-menu"
                            hidden
                            data-services-menu
                            class="wx-services-menu absolute left-1/2 top-full mt-3 -translate-x-1/2 rounded-2xl border border-navy/10 bg-white p-2 shadow-2xl"
                        >
                                <a href="{{ route('services.index') }}" class="mb-1 flex items-center justify-between rounded-xl px-4 py-3 text-sm font-bold text-navy hover:bg-gray-50">
                                    All services <span class="text-red-600" aria-hidden="true">↗</span>
                                </a>
                                <div class="wx-services-menu-grid">
                                    @foreach ($services as $slug => $service)
                                        <a href="{{ route('services.show', $slug) }}" class="block rounded-xl px-3 py-3 hover:bg-gray-50">
                                            <span class="block text-sm font-semibold leading-tight text-navy">{{ $service['name'] }}</span>
                                            <span class="mt-1 block text-[0.68rem] leading-snug text-gray-500">{{ Str::limit($service['summary'], 78) }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- aria-current tells assistive tech which page you are on. The
                             red underline styling said it visually to sighted users only. --}}
                        <a href="{{ $item['href'] }}" class="nav-pill-link nav-enter relative rounded-full px-3.5 py-2 text-[0.9rem] font-semibold tracking-tight {{ $item['active'] ? 'active' : '' }}" style="--stage: {{ $i + 1 }}" {{ $item['active'] ? 'aria-current="page"' : '' }}>{{ $item['label'] }}</a>
                    @endif
                @endforeach
            </div>

            {{-- Right side --}}
            <div class="flex items-center gap-2 md:gap-3">
                {{-- MadeIT badge --}}
                <a
                    href="https://madeitcodes.online"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="nav-enter hidden md:inline-flex items-center gap-1.5 px-3 py-2 rounded-full text-[0.8rem] font-semibold transition-colors duration-300"
                    {{-- teal-700, not the brand teal: #0FA99D is 2.92:1 on white and this label
                     is text. #0A8078 is 4.80:1. --}}
                    style="--stage: 6; color:#0A8078;"
                >
                    <span class="w-1.5 h-1.5 rounded-full bg-teal-500 animate-pulse" aria-hidden="true"></span>
                    MadeIT
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M7 7h10v10"/></svg>
                </a>

                {{-- Desktop CTA --}}
                <a
                    href="{{ route('contact') }}"
                    class="nav-enter btn btn-primary btn-shine hidden md:inline-flex rounded-full px-5 py-2.5 text-sm"
                    style="--stage: 7"
                >
                    Start a Project
                </a>

                {{-- Mobile menu toggle --}}
                <button
                    x-ref="menuToggle"
                    @click="toggle()"
                    :aria-expanded="open"
                    aria-controls="mobile-menu"
                    :aria-label="open ? 'Close navigation menu' : 'Open navigation menu'"
                    class="nav-enter lg:hidden flex items-center justify-center w-11 h-11 rounded-full border border-navy-800/15 transition-colors duration-300"
                    style="--stage: 7"
                    :class="open ? 'bg-navy-800 text-white border-navy-800' : 'text-navy-800 hover:bg-navy-800/5'"
                >
                    <span class="relative block w-5 h-3.5" aria-hidden="true">
                        <span
                            class="absolute left-0 top-0 w-5 h-[2px] rounded-full bg-current transition-all duration-300"
                            :class="open ? 'top-[6px] rotate-45' : ''"
                        ></span>
                        <span
                            class="absolute left-0 top-[6px] w-5 h-[2px] rounded-full bg-current transition-all duration-300"
                            :class="open ? 'opacity-0 w-0' : ''"
                        ></span>
                        <span
                            class="absolute left-0 bottom-0 w-5 h-[2px] rounded-full bg-current transition-all duration-300"
                            :class="open ? 'bottom-[6px] -rotate-45' : ''"
                        ></span>
                    </span>
                </button>
            </div>
        </nav>
    </div>

    {{-- Mobile full-screen overlay --}}
    <div
        id="mobile-menu"
        x-cloak
        x-show="open"
        x-trap.noscroll="open"
        x-transition:enter="transition ease-out duration-500"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="lg:hidden fixed inset-0 top-0 z-10 bg-navy-900 overflow-y-auto pointer-events-auto"
        role="dialog"
        aria-modal="true"
        aria-label="Mobile navigation"
        style="background: linear-gradient(160deg, #0F1A35 0%, #142148 55%, #1B2D5F 100%);"
    >
        <div class="container-wx min-h-full flex flex-col pt-28 pb-10" :class="open ? 'menu-open' : ''">

            {{-- Close control lives inside the focus trap so keyboard users
                 are never trapped with no way out. --}}
            <button
                type="button"
                @click="close(true)"
                class="absolute top-5 right-5 z-10 flex h-11 w-11 items-center justify-center rounded-full border border-white/25 text-white transition-colors hover:bg-white/10"
            >
                <span class="sr-only">Close menu</span>
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true">
                    <path d="M5 5l10 10M15 5L5 15" />
                </svg>
            </button>

            <p class="text-white/70 text-[0.7rem] font-semibold uppercase tracking-[0.25em] mb-6 menu-item" style="--i:0">Menu</p>

            <nav class="flex flex-col" aria-label="Mobile navigation links">
                @foreach ($mobileItems as $i => $item)
                    <a
                        href="{{ $item['href'] }}"
                        {{-- close() without restoreFocus: the browser is navigating, so focus
                         does not need to come back to the toggle. --}}
                        @click="close()"
                        class="menu-item group flex items-baseline gap-4 py-3.5 border-b border-white/10"
                        style="--i: {{ $i + 1 }}"
                        {{ $item['active'] ? 'aria-current="page"' : '' }}
                    >
                        <span class="text-[0.7rem] font-mono font-semibold text-white/60 tracking-widest w-7 shrink-0" aria-hidden="true">{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="text-3xl font-bold text-white tracking-tight group-hover:text-red-400 transition-colors duration-300 {{ $item['active'] ? 'text-red-400' : '' }}">{{ $item['label'] }}</span>
                        <svg class="w-5 h-5 mt-1 ml-auto text-white/70 group-hover:text-red-400 group-hover:translate-x-1 transition-all duration-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                @endforeach
            </nav>

            <div class="menu-item mt-10 flex flex-wrap items-center gap-3" style="--i: 7">
                <a href="{{ route('contact') }}" @click="close()" class="btn btn-primary btn-shine rounded-full flex-1 sm:flex-none">Start a Project</a>
                <a href="https://madeitcodes.online" target="_blank" rel="noopener noreferrer" class="btn btn-ghost-white rounded-full flex-1 sm:flex-none">MadeIT Codes ↗</a>
            </div>
        </div>
    </div>

</header>

{{-- Spacer to offset fixed header --}}
<div class="h-[76px] md:h-[92px]" aria-hidden="true" id="header-spacer"></div>
