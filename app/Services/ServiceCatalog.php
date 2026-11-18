<?php

namespace App\Services;

/**
 * Service catalogue used by the individual service pages.
 *
 * Every entry drives a full page (hero, deliverables, outcomes, FAQ).
 * Copy is written to match the Webxpress voice: direct, outcome-first,
 * specific — no empty superlatives.
 */
class ServiceCatalog
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'web-development' => [
                'name' => 'Web Development',
                'tagline' => 'Sites and systems that carry real business weight.',
                'summary' => 'High-performance websites, Laravel applications, portals, dashboards, and digital platforms built around real business workflows.',
                'fit' => 'Best for companies that need a serious web presence connected to operations.',
                'longSummary' => 'We design and build web products that hold up under real traffic and real users — marketing sites, client portals, self-service dashboards, and internal tools. Each project starts with the workflow behind it, so what ships is a system your team actually runs on, not just a page they visit.',
                'deliverables' => [
                    ['title' => 'Marketing & company websites', 'description' => 'Conversion-focused structure, brand-correct design, and pages that load fast on the networks your customers actually use.'],
                    ['title' => 'Client portals & dashboards', 'description' => 'Members, orders, documents, invoices, and support in one login — replacing scattered email threads and shared spreadsheets.'],
                    ['title' => 'SaaS & web applications', 'description' => 'Laravel backends with authentication, role-based access, payments, and reporting engineered for scale rather than demo screens.'],
                    ['title' => 'E-commerce', 'description' => 'Catalog, checkout, and local payment gateways wired end-to-end — Paystack, Flutterwave, and bank transfer flows included.'],
                    ['title' => 'Performance & SEO foundations', 'description' => 'Core Web Vitals, structured data, metadata, and crawl logic built in from day one instead of bolted on after launch.'],
                    ['title' => 'Deployment & maintenance', 'description' => 'Managed hosting, monitoring, backups, and security updates so the site stays healthy long after launch week.'],
                ],
                'outcomes' => [
                    ['title' => 'A site that earns its place', 'description' => 'Fast, credible, and structured to convert — so your web budget shows up in numbers, not just opinions.'],
                    ['title' => 'Operations off the spreadsheet', 'description' => 'Orders, clients, documents, and data handled by one system instead of chase-your-tail email threads.'],
                    ['title' => 'Built to grow, not to rebuild', 'description' => 'Clean code and real documentation mean the next feature costs a fraction of a ground-up build.'],
                ],
                'faq' => [
                    ['q' => 'How long does a web project take?', 'a' => 'A marketing site typically takes 3–6 weeks; portals and applications run 6–12 weeks in set phases. You get a written timeline and weekly progress notes before we start, and you approve each phase.'],
                    ['q' => 'Do you use WordPress or a framework?', 'a' => 'For anything tied to operations, payments, or data, we build custom Laravel applications — not themes. Simple marketing pages are still hand-crafted, so nothing you launch is someone else\'s template.'],
                    ['q' => 'Can you take over an existing site?', 'a' => 'Yes. We start with a technical audit of the current build, then recommend either a focused rebuild or extending the code that is worth keeping.'],
                    ['q' => 'Who owns the code and content?', 'a' => 'You do. On full and final payment, we transfer the codebase, documentation, credentials, and all assets. Everything is deployable in your own hosting if you prefer to move.'],
                ],
            ],

            'mobile-development' => [
                'name' => 'Mobile Development',
                'tagline' => 'Product-ready apps your users stay on.',
                'summary' => 'Cross-platform and product-ready mobile apps with clean journeys, reliable backend integration, and launch support.',
                'fit' => 'Best for teams bringing a service, marketplace, or internal workflow to phones.',
                'longSummary' => 'We take products from a validated idea to a store-ready app: single codebase across iOS and Android, a backend that behaves the same on every network, and an onboarding flow your users complete without a manual. From marketplace to field operations, the app is built to be used daily.',
                'deliverables' => [
                    ['title' => 'Product & journey design', 'description' => 'Screens mapped around how people actually use the app day to day — not around every feature you could dream up.'],
                    ['title' => 'Cross-platform engineering', 'description' => 'One polished codebase that ships to both iOS and Android at the same time, on the same feature set.'],
                    ['title' => 'Backend & API integration', 'description' => 'Accounts, payments, notifications, and data sync wired to a backend we build or one you already run.'],
                    ['title' => 'Offline-first behaviour', 'description' => 'Forms and flows that work on slow or patchy connections, then sync when the network returns.'],
                    ['title' => 'App Store & Play launch', 'description' => 'Listing copy, screenshots, compliance, and submission handled for both stores — including review callbacks.'],
                    ['title' => 'Post-launch support', 'description' => 'Crash monitoring, analytics, and a maintenance window so launch is the start of the product, not the end.'],
                ],
                'outcomes' => [
                    ['title' => 'Users complete the journey', 'description' => 'Onboarding and core flows tested against real usage, so activation doesn\'t depend on luck.'],
                    ['title' => 'Works where your users are', 'description' => 'Stable behaviour on Android devices and patchy networks — the reality in most of the markets you serve.'],
                    ['title' => 'One team, two stores', 'description' => 'Same features, same schedule, same codebase — no version drift between platforms.'],
                ],
                'faq' => [
                    ['q' => 'Do you build for both iOS and Android?', 'a' => 'Yes — always. We use a single codebase so both stores receive the same features on the same timeline, without a second budget.'],
                    ['q' => 'Do we need an existing backend?', 'a' => 'No. We build the backend if you need one, or integrate with an existing API, existing database, or third-party platform you already use.'],
                    ['q' => 'Do we need dev accounts before starting?', 'a' => 'Not to start. We guide you through Apple Developer and Google Play accounts early in the build so submissions go smoothly at the end.'],
                    ['q' => 'Can you take an existing app over?', 'a' => 'We can — after an honest audit. If the current codebase is worth extending, we extend it; if it\'s fighting you every sprint, we\'ll tell you and rebuild it properly.'],
                ],
            ],

            'branding' => [
                'name' => 'Branding',
                'tagline' => 'An identity your team actually uses.',
                'summary' => 'Identity systems, positioning, interface-ready visual rules, and launch assets that make the product feel coherent.',
                'fit' => 'Best for new businesses or established brands ready for a sharper market signal.',
                'longSummary' => 'We build identities that work in the places they actually appear — a website, a deck, a signboard, an invoice. Starting from positioning and naming, we land on a visual system with clear rules, so every asset your team produces afterwards looks like it came from the same brand.',
                'deliverables' => [
                    ['title' => 'Positioning & messaging', 'description' => 'The one thing your brand stands for, the audience it serves, and the words used to say it consistently.'],
                    ['title' => 'Visual identity system', 'description' => 'Logo system, colour palette, typography, and layout rules designed to work across print, screen, and motion.'],
                    ['title' => 'Interface-ready rules', 'description' => 'A brand documented so the same system feeds directly into UI work — navigation, buttons, forms, states.'],
                    ['title' => 'Launch asset pack', 'description' => 'Business cards, letterhead, social templates, deck layouts, and a one-page brand guideline your team can follow.'],
                    ['title' => 'Brand voice & tone guide', 'description' => 'How the brand talks — written, not guessed, so enquiries, emails, and website copy stay on character.'],
                    ['title' => 'Implementation support', 'description' => 'We stay on for the first rollout — website, signage, packaging, or the launch campaign — so the identity survives contact with reality.'],
                ],
                'outcomes' => [
                    ['title' => 'Recognisable in five seconds', 'description' => 'The brand reads as one thing across Instagram, the website, and the office — not a collection of experiments.'],
                    ['title' => 'Looks the part, feels the part', 'description' => 'Credibility built visually, which matters most in the first impressions with partners and customers.'],
                    ['title' => 'Your team can run it', 'description' => 'Clear rules and templates mean your marketing team produces on-brand work without an agency phone call.'],
                ],
                'faq' => [
                    ['q' => 'Do we need a name first?', 'a' => 'No. We can work in either order — brand naming is part of positioning, and it\'s cheaper to fix here than after it\'s printed on everything.'],
                    ['q' => 'How long does a brand project take?', 'a' => 'The core identity usually lands in 4–6 weeks. Naming and positioning add another 1–2 weeks. Launch assets follow the core system at a pace that fits your launch date.'],
                    ['q' => 'Can we keep elements of our current brand?', 'a' => 'Yes, if they carry equity. We audit what exists, keep what works, and replace what\'s holding you back — you won\'t lose a customer because the logo changed.'],
                    ['q' => 'What do we actually receive?', 'a' => 'Source files for every logo and asset, brand guidelines, templates, and all fonts and colours — plus a short session with your team so nothing sits unused in a folder.'],
                ],
            ],

            'digital-strategy' => [
                'name' => 'Digital Strategy',
                'tagline' => 'Decide less in the dark, build with more certainty.',
                'summary' => 'Roadmaps, content structure, technical planning, analytics thinking, and product decisions before major build work begins.',
                'fit' => 'Best when the goal is clear but the right digital path still needs shaping.',
                'longSummary' => 'Strategy work is where the certainty comes from. We map your goals against what your market actually does, decide what gets built and in what order, and produce the plan that makes the build phase predictable — timelines, costs, and the metrics that will tell you it worked.',
                'deliverables' => [
                    ['title' => 'Goals & evidence review', 'description' => 'Your business targets turned into concrete digital objectives with the data that will measure them.'],
                    ['title' => 'Customer & journey mapping', 'description' => 'Who you serve, how they discover you, and the paths that turn attention into revenue.'],
                    ['title' => 'Content & IA structure', 'description' => 'Site maps, page purposes, and content structure that make the right information easy to find.'],
                    ['title' => 'Technical direction', 'description' => 'Platform, architecture, and integration decisions — with the trade-offs and costs made visible, not hidden.'],
                    ['title' => 'Prioritised roadmap', 'description' => 'What ships first, what waits, and why — sequenced so early work funds and de-risks later work.'],
                    ['title' => 'Measurement plan', 'description' => 'The dashboards, events, and reporting rhythms that turn the project into a system you manage going forward.'],
                ],
                'outcomes' => [
                    ['title' => 'A plan that protects budget', 'description' => 'Decisions made once, on purpose — so build phases don\'t eat money on features that don\'t serve the goal.'],
                    ['title' => 'Teams move with direction', 'description' => 'Designers, developers, and stakeholders work from one roadmap instead of competing priorities.'],
                    ['title' => 'Success has a definition', 'description' => 'Clear metrics agreed up front, so "did it work?" has an answer and not a feeling.'],
                ],
                'faq' => [
                    ['q' => 'Is this consulting or a deliverable?', 'a' => 'Both. You get written documents you can act on and present — the roadmap, structure, and metrics — not just a workshop with nothing to show.'],
                    ['q' => 'Can you execute the plan too?', 'a' => 'Yes. Most clients hand the roadmap to us to build, because the design and engineering teams are already in place. If you take it elsewhere, it\'s written to be executable by anyone competent.'],
                    ['q' => 'How long does strategy take?', 'a' => 'Most engagements run 2–4 weeks depending on audience research depth. Shorter if you already know your customer and just need decisions.'],
                    ['q' => 'What if we\'ve already started building?', 'a' => 'We audit what exists and re-plan from there. A good strategy fixes direction without throwing away work that\'s worth keeping.'],
                ],
            ],

            'visuals' => [
                'name' => 'Visuals',
                'tagline' => 'Presentation that earns the second look.',
                'summary' => 'Art direction, interface visuals, campaign assets, and production guidance for polished digital presentation.',
                'fit' => 'Best for teams that need their product or brand to feel more credible on sight.',
                'longSummary' => 'Before a prospect reads a word, they decide whether to keep reading. We direct the visual layer — product interfaces, campaign creatives, photo treatments, and launch visuals — so everything your audience sees is coherent, intentional, and worth their attention.',
                'deliverables' => [
                    ['title' => 'Art direction', 'description' => 'A visual concept for your product or campaign — mood, light, palette, and composition — applied consistently.'],
                    ['title' => 'Interface & product visuals', 'description' => 'Marketing screens, product shots, and app store visuals that look as considered as the product they represent.'],
                    ['title' => 'Campaign creatives', 'description' => 'Instagram, LinkedIn, and web ad assets designed in a system, so a single campaign reads as one voice.'],
                    ['title' => 'Photo & illustration direction', 'description' => 'Shooting guidance, stock curation, or custom illustration that fits the brand instead of watering it down.'],
                    ['title' => 'Asset library', 'description' => 'Organised, documented assets your team can reuse across channels without decoding old files.'],
                    ['title' => 'Production QA', 'description' => 'Review passes across devices and placements so nothing ships cropped, low-res, or off-colour.'],
                ],
                'outcomes' => [
                    ['title' => 'Credibility on sight', 'description' => 'A polished visual layer that makes partners and customers take the product seriously before the pitch.'],
                    ['title' => 'One voice across channels', 'description' => 'Website, store listings, and social creative all share a visual language — no accidental rebrand every week.'],
                    ['title' => 'Launch-ready asset packs', 'description' => 'Everything you need for a campaign pulled together, checked, and formatted for each platform.'],
                ],
                'faq' => [
                    ['q' => 'Can you work with our existing brand?', 'a' => 'Yes. Visuals sit on top of the brand — we apply, extend, and translate your existing identity across channels without redesigning your logo.'],
                    ['q' => 'What files will we receive?', 'a' => 'Final visuals in every required format — web, print, social, and store — plus the source files and a short guide on how to request future assets.'],
                    ['q' => 'How fast can we get campaign creatives?', 'a' => 'A standard campaign set (6–10 visuals) typically lands in one to two weeks once the art direction is approved.'],
                    ['q' => 'Do you handle photography or video?', 'a' => 'We direct and art-manage shoots with partners; if the campaign includes photo or video, we produce the visual direction and assets with a trusted production team.'],
                ],
            ],

            'motion-design' => [
                'name' => 'Motion Design',
                'tagline' => 'When a still image can\'t explain the value.',
                'summary' => 'Purposeful animation, product explainers, interface motion, and video assets that improve clarity and recall.',
                'fit' => 'Best when a static page cannot explain the value fast enough.',
                'longSummary' => 'Motion is the fastest way to explain how something works. We produce explainer films, interface animation, and brand motion — with a storyboard approved before we animate, so you always know exactly what you\'re paying for and what it will say.',
                'deliverables' => [
                    ['title' => 'Product explainers', 'description' => '60–90 second films that walk a prospect through the problem and your answer in their language.'],
                    ['title' => 'Interface & app motion', 'description' => 'Micro-interactions and transitions that guide attention and make the product feel considered.'],
                    ['title' => 'Brand animation', 'description' => 'Logo stamps, intro/outro sequences, and launch reveals built from your existing identity.'],
                    ['title' => 'Storyboards & scripting', 'description' => 'Every deliverable starts with an approved script and frame-by-frame plan — no surprises mid-project.'],
                    ['title' => 'Platform-ready exports', 'description' => 'Formats, captions, and aspect ratios prepared for Instagram, TikTok, LinkedIn, and your website.'],
                    ['title' => 'Motion guidelines', 'description' => 'Timing, easing, and usage rules if you plan to animate your own content with the brand look intact.'],
                ],
                'outcomes' => [
                    ['title' => 'Hard-to-say things get said', 'description' => 'Complex products explained in seconds, so sales and marketing stop sending paragraphs.'],
                    ['title' => 'Views turn into holds', 'description' => 'Motion that keeps attention through the moment that matters — the value proposition, not the title screen.'],
                    ['title' => 'The brand moves as one', 'description' => 'Every animation follows the same timing and personality, across product and marketing.'],
                ],
                'faq' => [
                    ['q' => 'How do we brief a motion project?', 'a' => 'A short call where you explain the product or message. We turn that into a script and storyboard for approval — animation only starts once the plan makes sense to you.'],
                    ['q' => 'Can you animate our existing brand?', 'a' => 'Yes. We work from your identity system and motion guidelines (or create them) so the animation extends the brand instead of fighting it.'],
                    ['q' => 'What\'s the typical turnaround?', 'a' => 'A 60-second explainer is usually 2–4 weeks. Interface motion runs in parallel with the design sprint that produces the screens.'],
                    ['q' => 'Will we get editable project files?', 'a' => 'Yes — source files and assets on final payment, so your team can produce future motions or brief extensions without restarting.'],
                ],
            ],

            'seo' => [
                'name' => 'SEO',
                'tagline' => 'Visibility you keep, not rent.',
                'summary' => 'Technical SEO, content architecture, page performance, structured metadata, and search-focused publishing foundations.',
                'fit' => 'Best for organizations that want durable visibility instead of short campaign spikes.',
                'longSummary' => 'We build the foundations that let Google understand and trust your site — then improve it season after season. Technical fixes, content structure, performance, and the measurement that proves what\'s working. No tricks, no black-hat promises: just defensible work on pages already worth ranking.',
                'deliverables' => [
                    ['title' => 'Technical audit & fixes', 'description' => 'Crawlability, indexation, redirects, and errors found and fixed where it matters — not reports that die in a PDF.'],
                    ['title' => 'Keyword & content mapping', 'description' => 'The terms your customers actually search, mapped to the pages that should win them.'],
                    ['title' => 'On-page optimisation', 'description' => 'Titles, meta descriptions, structured data, and content structure rewritten for clicks and clarity.'],
                    ['title' => 'Performance (Core Web Vitals)', 'description' => 'Speed fixes that both rankings and real users reward — on mobile networks and mid-range devices.'],
                    ['title' => 'Site architecture & linking', 'description' => 'Internal linking and category structure that spread authority where it converts.'],
                    ['title' => 'Search Console & GA4 setup', 'description' => 'Measurement wired in so rankings, clicks, and conversions are visible — and we both know what\'s working.'],
                ],
                'outcomes' => [
                    ['title' => 'Durable organic growth', 'description' => 'Position growth that compounds quarter over quarter instead of evaporating when the campaign budget ends.'],
                    ['title' => 'Less paid dependence', 'description' => 'Money shifts from renting every click to earning them — at a cost per acquisition that drops over time.'],
                    ['title' => 'Content that earns its place', 'description' => 'Publishing guided by data, so new pages rank instead of vanishing into the archive.'],
                ],
                'faq' => [
                    ['q' => 'How long before we see results?', 'a' => 'Technical wins show in 1–2 months; meaningful ranking movement typically takes 3–6 months. Anyone who promises faster is selling you something that won\'t last.'],
                    ['q' => 'SEO or paid ads?', 'a' => 'They serve different jobs. Paid gets you traffic this week; SEO builds an asset. We\'ll tell you honestly which you need — and many clients run both.'],
                    ['q' => 'Do you write the content?', 'a' => 'We structure and optimise. If writing capacity is the gap, we can draft or co-write with your subject matter experts — the tech is ours, the credibility stays yours.'],
                    ['q' => 'What do we need from you?', 'a' => 'Access to your site, Search Console (or the ability to create one), and answers to a one-page questionnaire. We handle everything else.'],
                ],
            ],

            'ai-solutions' => [
                'name' => 'AI Solutions',
                'tagline' => 'Repetitive work, done by machines. Decisions stay with you.',
                'summary' => 'AI-assisted workflows, internal automations, response systems, document handling, and intelligent product features.',
                'fit' => 'Best for teams with repetitive work that can be made faster and more consistent.',
                'longSummary' => 'We put AI to work where it\'s actually reliable: routing, summarising, drafting, extracting, and answering from your own data. We scope each use case around your workflows, keep a human in every decision loop, and only ship models where the accuracy is good enough to trust.',
                'deliverables' => [
                    ['title' => 'Workflow mapping', 'description' => 'The repetitive tasks worth automating found and quantified — hours, error rate, and cost per cycle.'],
                    ['title' => 'AI response systems', 'description' => 'Chat and email assistants trained on your products and tone, with escalation to a human the moment it matters.'],
                    ['title' => 'Document processing', 'description' => 'Extraction and classification of contracts, invoices, and forms — with a human review queue built in.'],
                    ['title' => 'Workflow automation', 'description' => 'Forms, approvals, and notifications moving themselves across the tools your team already uses.'],
                    ['title' => 'Intelligent product features', 'description' => 'Search, recommendations, and assistants inside your product that make it better without making it standoffish.'],
                    ['title' => 'Evaluation & guardrails', 'description' => 'Accuracy testing, fallbacks, cost caps, and data protections so AI never runs your business unattended.'],
                ],
                'outcomes' => [
                    ['title' => 'Hours returned to the team', 'description' => 'Using budget on judgement instead of copy-paste work — the effect is measurable in the first quarter.'],
                    ['title' => 'Answers that stay on brand', 'description' => 'Customer-facing AI that holds your tone, your facts, and your boundaries — with handover when it can\'t.'],
                    ['title' => 'Scale without headcount', 'description' => 'Handling more volume with the same team, because the boring 80% stopped needing a human.'],
                ],
                'faq' => [
                    ['q' => 'Do we need an existing product?', 'a' => 'No. Workflows can be automated without a product in place. If you have software already, we integrate into it; if you don\'t, we build what\'s needed on top of a clean backend.'],
                    ['q' => 'How secure is our data?', 'a' => 'We work with providers and configurations that keep your data inside your region and your controls. Anything sensitive stays out of public models, and access is scoped per role.'],
                    ['q' => 'Is this replacing our staff?', 'a' => 'No. AI here removes repetitive volume so your team does more judgement-heavy work. Every output keeps a human decision point — that\'s a design requirement, not a safety setting.'],
                    ['q' => 'How is it priced?', 'a' => 'A discovery sprint to scope the use cases, then build in phases you approve. Running costs (model usage) are metered and capped transparently — no surprise invoices.'],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }
}
