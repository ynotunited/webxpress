<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // These are first-run defaults, not a reset button. An editor's saved
        // values must survive a deploy that runs db:seed again.
        $defaults = [
            'site_name' => 'Webxpress Technologies',
            'og_title' => 'Webxpress Technologies — Web & SaaS Built for Africa',
            'contact_email' => 'hello@webxpresstech.com.ng',
            'contact_address' => 'Lagos, Nigeria',
            'footer_tagline' => 'Ideas. Designs. Innovation.',
            'inquiries_email' => 'hello@webxpresstech.com.ng',
            'hero_headline' => 'Web & SaaS Built for Africa',
            'hero_subheadline' => "Fast, scalable digital solutions for businesses that can't wait for slow tech.",
        ];

        // No phone, WhatsApp or social profiles are seeded. A fake number in
        // structured data or a placeholder profile link is worse than an empty
        // field; those are entered by the client in Site Settings.
        foreach ($defaults as $key => $value) {
            SiteSetting::query()->firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
