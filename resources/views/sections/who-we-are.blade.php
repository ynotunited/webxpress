{{--
    WHO WE ARE — Editorial company intro section
    Editorial layout, not a generic "about us" corporate block
--}}

<section class="section-padding bg-white" aria-labelledby="who-we-are-heading">
    <div class="container-wx">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-center">

            {{-- Left: Editorial text --}}
            <div class="animate-on-scroll">
                <div class="section-label">
                    <span class="accent-bar"></span>
                    <span class="label-sm" id="who-we-are-heading">Who We Are</span>
                </div>

                <blockquote class="text-2xl md:text-3xl font-bold text-navy leading-tight mb-8" style="color:#1B2D5F">
                    &ldquo;Technology should solve real business problems, not simply look impressive.&rdquo;
                </blockquote>

                <p class="body-lg mb-5">
                    Webxpress Technologies has been at the intersection of technology and business since 2010. We partner with organizations across Africa to design, build, and scale digital products that actually work.
                </p>
                <p class="body-base text-gray-500 mb-8">
                    We combine technical depth with a clear understanding of the African business landscape — from fintech to hospitality, from startups to enterprise. Our work spans the full digital lifecycle: strategy, design, development, and growth.
                </p>

                <a href="{{ route('about') }}" class="btn btn-secondary">Our Story</a>
            </div>

            {{-- Right: Stats grid --}}
            <div class="animate-on-scroll" data-delay="2">
                <div class="grid grid-cols-2 gap-6">
                    <div class="p-6 bg-gray-50 rounded-lg border border-gray-100">
                        <p class="text-4xl md:text-5xl font-bold text-navy mb-1" style="color:#1B2D5F">15+</p>
                        <p class="text-sm text-gray-500 font-medium">Years of Experience</p>
                    </div>
                    <div class="p-6 bg-gray-50 rounded-lg border border-gray-100">
                        <p class="text-4xl md:text-5xl font-bold text-navy mb-1" style="color:#1B2D5F">200+</p>
                        <p class="text-sm text-gray-500 font-medium">Projects Delivered</p>
                    </div>
                    <div class="p-6" style="background-color:#1B2D5F; border-radius:8px">
                        <p class="text-4xl md:text-5xl font-bold text-white mb-1">8</p>
                        <p class="text-sm text-white/70 font-medium">Service Specialties</p>
                    </div>
                    <div class="p-6 border-2 border-dashed border-gray-200 rounded-lg flex items-center justify-center">
                        <div class="text-center">
                            <p class="text-2xl font-bold text-gray-700 mb-1">Since</p>
                            <p class="text-4xl font-bold" style="color:#E31937">2010</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
