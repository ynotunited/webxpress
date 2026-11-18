{{-- INDUSTRIES PREVIEW --}}
@php
    $industries = [
        ['name' => 'Corporate & Enterprise', 'description' => 'Scalable platforms for large organizations.'],
        ['name' => 'Healthcare', 'description' => 'Digital tools for patient care and administration.'],
        ['name' => 'Finance & FinTech', 'description' => 'Secure, compliant financial technology.'],
        ['name' => 'Hospitality', 'description' => 'Booking, management, and guest experience platforms.'],
        ['name' => 'Retail & E-commerce', 'description' => 'High-performance stores and inventory systems.'],
        ['name' => 'Education', 'description' => 'LMS platforms, portals, and student systems.'],
        ['name' => 'Professional Services', 'description' => 'CRM, workflow, and client management tools.'],
        ['name' => 'Startups', 'description' => 'Fast-moving MVPs and scalable product foundations.'],
    ];
@endphp
<section class="section-padding bg-gray-50" aria-labelledby="industries-heading">
    <div class="container-wx">
        <div class="mb-12 animate-on-scroll">
            <div class="section-label"><span class="accent-bar"></span><span class="label-sm">Industries</span></div>
            <h2 id="industries-heading" class="heading-1">We understand your market</h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($industries as $i => $industry)
            <div class="card hover:border-navy animate-on-scroll" data-delay="{{ min($i+1,4) }}" style="--hover-border:#1B2D5F">
                <h3 class="text-base font-bold text-navy mb-2" style="color:#1B2D5F">{{ $industry['name'] }}</h3>
                <p class="text-sm text-gray-500 leading-relaxed">{{ $industry['description'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>
