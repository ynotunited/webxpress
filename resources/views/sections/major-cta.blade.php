{{--
    MAJOR CTA — Strong conversion section
    Breaks the page visually. No flashy gradients.
--}}
<section class="section-padding wx-major-cta" aria-labelledby="cta-heading">
    <div class="container-wx">
        <div class="max-w-3xl animate-on-scroll">
            <div class="section-label"><span class="accent-bar"></span><span class="label-sm">Your next move</span></div>
            <h2 id="cta-heading" class="heading-display mb-6">
                Stop working around<br>
                <span style="color:#E31937">the problem.</span>
            </h2>
            <p class="body-lg text-gray-500 mb-10 max-w-xl">
                Bring us the messy workflow, the slow website or the idea that is ready to become real. We will help you find the clearest next step and build what earns its place in the business.
            </p>
            <div class="flex flex-wrap gap-4">
                <a href="{{ route('contact') }}" class="btn btn-primary text-lg px-8 py-4">Tell us what is stuck <span aria-hidden="true">↗</span></a>
                <a href="{{ route('work.index') }}" class="btn btn-ghost text-lg px-8 py-4">See what we have built</a>
            </div>
            <p class="wx-major-cta-note">No hard sell. Just a useful first conversation.</p>
        </div>
    </div>
</section>
