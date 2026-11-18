<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Promotes the pages this project ships as Blade templates into editable
 * Site Page records (Admin → Site → Site Pages), so every public page is
 * listed, searched, and manageable from the admin.
 *
 * Idempotent and safe to re-run — keyed per record on slug (updateOrCreate).
 *
 * Rendering contract:
 *  - privacy + terms are fully CMS-backed: the public pages render DB
 *    content end-to-end through PageController.
 *  - The bespoke Blade pages (home, about, services + the 8 service
 *    details, work, insights, industries, contact) stay Blade-rendered
 *    by design — their site_pages records carry editable SEO, hero
 *    metadata, and publish state, and they are editable + toggleable
 *    from the admin even though the bespoke layout stays in Blade.
 *  - `content` is left null for bespoke pages (their body lives in the
 *    Blade template, or in page_sections once promoted); it is filled
 *    only for the two CMS-rendered pages.
 *
 * Content safety: `content` is never written over an existing value. A
 * bespoke page's null body must not clobber copy that has been promoted
 * into page_sections or edited in the admin.
 */
class SitePageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            // ---- CMS-rendered prose pages (privacy + terms) ----
            [
                'slug' => 'privacy',
                'title' => 'Privacy Policy',
                'seo_title' => 'Privacy Policy — Webxpress Technologies',
                'seo_description' => 'How Webxpress Technologies collects, uses, stores, and protects personal data across our websites, portals, and client projects.',
                'content' => '<h2>1. Introduction</h2><p>At Webxpress Technologies, we are committed to protecting the privacy of everyone who uses our website and services. This policy explains what information we collect, why we collect it, and how we use it. By using our site you agree to the practices described here.</p><h2>2. What we collect</h2><p>We collect information you give us directly, such as your name, email address, and the contents of any enquiry you send through our contact forms. We also collect limited technical data automatically, including the pages you visit, your browser type, and your approximate location. We use this to improve performance and understand how visitors engage with our content.</p><h2>3. How we use your information</h2><p>We use your information only to respond to your enquiries, deliver the services you request, and improve our products. We do not sell your personal data to anyone.</p><h2>4. Cookies and analytics</h2><p>We use cookies and similar technologies to operate the site, remember your preferences, and measure traffic. You can control cookies through your browser settings, though some site features may not work as a result.</p><h2>5. How we share your information</h2><p>We share information only with the trusted service providers who help us host and operate our platform, and only to the extent necessary to provide our services. We will disclose information where required by law or where necessary to protect our rights and the safety of our users.</p><h2>6. Data retention and security</h2><p>We keep personal data only for as long as it is needed for the purposes described in this policy, or as required by law. All data is transmitted over encrypted connections and access to it is restricted to the people who need it.</p><h2>7. Your rights</h2><p>You have the right to access, correct, or delete the personal information we hold about you at any time. To make a request, or if you have any question about how your data is handled, contact us and we will respond promptly.</p><h2>8. Changes to this policy</h2><p>We may update this policy as our services evolve, so we encourage you to check back from time to time. The date of the latest revision appears with the published policy.</p><h2>9. Contact</h2><p>For any question about this policy or your data, email <strong>hello@webxpresstech.com.ng</strong> or use the contact page on this website.</p>',
                'is_published' => true,
            ],
            [
                'slug' => 'terms',
                'title' => 'Terms of Service',
                'seo_title' => 'Terms of Service — Webxpress Technologies',
                'seo_description' => 'The terms that govern use of the Webxpress Technologies website and the agreements behind project engagements.',
                'content' => '<h2>1. Acceptance of terms</h2><p>These terms govern your use of the Webxpress Technologies website and the services we provide. By accessing our site or engaging our services, you agree to be bound by these terms. If you do not agree with any part of them, please do not use our services.</p><h2>2. About this website</h2><p>You may browse this site to learn about our services and our work. We aim to keep the information on it accurate and current, but we do not warrant that it is complete or error free.</p><h2>3. Intellectual property</h2><p>All content on this website, including text, graphics, logos and code, is the property of Webxpress Technologies or its licensors and is protected by applicable intellectual property laws. You may view and interact with the content for your own business purposes, but you may not reproduce, redistribute, or use it commercially without our written permission.</p><h2>4. Project engagements</h2><p>When you engage us to build a product, the specific scope, pricing and timeline will be agreed in a written proposal or contract that governs that engagement. We deliver our work to the agreed specification and provide reasonable support after launch. If circumstances outside our control delay delivery, we will communicate openly and agree a revised timeline with you.</p><h2>5. Acceptable use</h2><p>You may not use this website to transmit unlawful or harmful material, to attempt to gain unauthorised access to any system, or to interfere with the normal operation of the site.</p><h2>6. Availability and liability</h2><p>We make every effort to keep our website secure and available, but we cannot guarantee uninterrupted access. We are not liable for damages arising from your use of, or inability to use, our website or services, to the maximum extent permitted by law.</p><h2>7. Governing law</h2><p>These terms are governed by the laws of the Federal Republic of Nigeria, and any dispute will be subject to the exclusive jurisdiction of the Nigerian courts.</p><h2>8. Contact</h2><p>Questions about these terms? Email <strong>hello@webxpresstech.com.ng</strong>.</p>',
                'is_published' => true,
            ],
            // ---- Bespoke Blade pages: SEO metadata + publish state ----
            //
            // `eyebrow` / `headline` / `intro` here are the same strings the
            // Blade template falls back to. Seeding them means the record starts
            // out saying what the page already says, and the editor takes it
            // over from there - PageHero::for() prefers the record, and falls
            // back to the template when the record is silent.
            ['slug' => 'home', 'title' => 'Home — Webxpress Technologies', 'seo_title' => 'Webxpress Technologies — Web & SaaS for Nigerian Business', 'seo_description' => 'Webxpress Technologies builds web, mobile, and SaaS products for Nigerian businesses. Since 2010. Digital product studio.', 'content' => null, 'is_published' => true],
            ['slug' => 'about', 'title' => 'About — Webxpress Technologies', 'seo_title' => 'About Webxpress Technologies — Digital Product Studio Since 2010', 'seo_description' => 'We build web, mobile, and SaaS solutions for Nigerian businesses — strategy, design, engineering, and growth since 2010.', 'content' => null, 'is_published' => true,
                'eyebrow' => 'About Webxpress',
                'headline' => 'We build the digital backbone for ambitious teams.',
                'intro' => 'Webxpress Technologies designs and engineers websites, SaaS platforms, client portals, and business systems that help organizations sell, operate, and scale with confidence.'],
            ['slug' => 'services', 'title' => 'Services — Webxpress Technologies', 'seo_title' => 'Services — Webxpress Technologies', 'seo_description' => 'Web, mobile, branding, strategy, visuals, motion, SEO, and AI services from Webxpress Technologies for Nigerian business.', 'content' => null, 'is_published' => true,
                'eyebrow' => 'Services',
                'headline' => 'Digital services for teams that need more than a good-looking website.',
                'intro' => 'We plan, design, build, and improve the systems that sit behind modern businesses: websites, portals, apps, automations, search foundations, and product workflows.'],
            ['slug' => 'web-development', 'title' => 'Web Development', 'seo_title' => 'Web Development — Webxpress Technologies', 'seo_description' => 'High-performance websites, Laravel applications, and SaaS platforms built by Webxpress Technologies for Nigerian business.', 'content' => null, 'is_published' => true],
            ['slug' => 'mobile-development', 'title' => 'Mobile Development', 'seo_title' => 'Mobile Development — Webxpress Technologies', 'seo_description' => 'Product-ready Android and iOS apps built by Webxpress Technologies for Nigerian business.', 'content' => null, 'is_published' => true],
            ['slug' => 'branding', 'title' => 'Branding', 'seo_title' => 'Branding — Webxpress Technologies', 'seo_description' => 'Identity systems and brand guidelines that give Nigerian businesses a durable, ownable presence.', 'content' => null, 'is_published' => true],
            ['slug' => 'digital-strategy', 'title' => 'Digital Strategy', 'seo_title' => 'Digital Strategy — Webxpress Technologies', 'seo_description' => 'Product strategy, content strategy, and technical strategy for Nigerian businesses scoped by Webxpress Technologies.', 'content' => null, 'is_published' => true],
            ['slug' => 'visuals', 'title' => 'Visuals', 'seo_title' => 'Visuals — Webxpress Technologies', 'seo_description' => 'Interfaces, campaign creatives, and art direction produced by Webxpress Technologies.', 'content' => null, 'is_published' => true],
            ['slug' => 'motion-design', 'title' => 'Motion Design', 'seo_title' => 'Motion Design — Webxpress Technologies', 'seo_description' => 'Product explainers and brand motion crafted by Webxpress Technologies.', 'content' => null, 'is_published' => true],
            ['slug' => 'seo', 'title' => 'SEO', 'seo_title' => 'SEO — Webxpress Technologies', 'seo_description' => 'Technical SEO and content architecture that gives Nigerian businesses durable, compounding visibility.', 'content' => null, 'is_published' => true],
            ['slug' => 'ai-solutions', 'title' => 'AI Solutions', 'seo_title' => 'AI Solutions — Webxpress Technologies', 'seo_description' => 'Practical AI applications for Nigerian business — documents, automation, and product intelligence.', 'content' => null, 'is_published' => true],
            ['slug' => 'insights', 'title' => 'Insights — Webxpress Technologies', 'seo_title' => 'Insights — Webxpress Technologies', 'seo_description' => 'Practitioner writing on building, launching, and growing digital products for Nigerian business.', 'content' => null, 'is_published' => true],
            ['slug' => 'industries', 'title' => 'Industries — Webxpress Technologies', 'seo_title' => 'Industries — Webxpress Technologies', 'seo_description' => 'Digital products for education, finance, healthcare, retail, and professional services in Nigeria.', 'content' => null, 'is_published' => true,
                'eyebrow' => 'Industries',
                'headline' => 'Sectors we know well.',
                'intro' => 'Different industries fail in different ways. Tell us the sector you work in and we will show you what we have built for businesses in it.'],
            ['slug' => 'contact', 'title' => 'Contact — Webxpress Technologies', 'seo_title' => 'Contact Webxpress Technologies', 'seo_description' => 'Start a project or ask a question. Contact the Webxpress Technologies team about web, mobile, branding, and strategy.', 'content' => null, 'is_published' => true],
            ['slug' => 'work', 'title' => 'Work — Webxpress Technologies', 'seo_title' => 'Work — Webxpress Technologies', 'seo_description' => 'A selection of products and engagements shipped by Webxpress Technologies for Nigerian and international clients.', 'content' => null, 'is_published' => true,
                'eyebrow' => 'Work',
                'headline' => 'Work that runs the business, not just the brief.',
                'intro' => 'Every build below started as an operational problem — stock that disagreed with itself, status chased by phone call, engineers writing job cards from memory. Here is what shipped, and what changed afterwards.'],
        ];

        foreach ($pages as $page) {
            $record = Page::query()->firstOrNew(['slug' => $page['slug']]);
            $isNew = ! $record->exists;

            // SEO + publish state are canonical here and always synced.
            $record->title = $page['title'];
            $record->seo_title = $page['seo_title'];
            $record->seo_description = $page['seo_description'];
            $record->is_published = true;

            // Body copy is written only when the record is first created.
            // Re-seeding must never overwrite copy that has been promoted
            // into page_sections or edited in the admin.
            if ($isNew && filled($page['content'] ?? null)) {
                $record->content = $page['content'];
            }

            // Hero copy is backfilled only where there is none. Writing on
            // first create alone is not enough: this seeder may run for the
            // first time against a database where the pages already exist, and
            // then the hero would stay permanently empty. Filling only the
            // blanks is both idempotent and non-destructive, so an editor's
            // copy is never reverted by re-running db:seed on a deployed site.
            foreach (['eyebrow', 'headline', 'intro'] as $field) {
                if (array_key_exists($field, $page)
                    && blank($record->{$field})
                    && filled($page[$field])) {
                    $record->{$field} = $page[$field];
                }
            }

            // Navigation is a structural setting, not copy, so it is synced
            // every time. An editor who wants a page out of the menu can edit
            // it in the admin; a page added to this list below is declaring
            // where it belongs, and re-running the seeder should not leave two
            // different answers to that question in two places.
            if (array_key_exists('show_in_nav', $page)) {
                $record->show_in_nav = $page['show_in_nav'];
                $record->nav_label = $page['nav_label'] ?? null;
                $record->nav_position = $page['nav_position'] ?? 0;
            }

            $record->save();
        }
    }
}
