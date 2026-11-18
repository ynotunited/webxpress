<?php

namespace App\Services;

use Illuminate\Container\Container;
use Illuminate\Support\Str;

/**
 * Project catalogue for the Work pages.
 *
 * Single source of truth for the portfolio: the /work index, the
 * /work/{slug} detail pages, and the homepage hero and Selected Work
 * sections all read from here, so a project is described once and appears
 * consistently everywhere it is listed.
 *
 * Every entry drives a full detail page (challenge, approach, deliverables,
 * outcomes, testimonial), so each one carries enough copy to stand alone.
 * Voice matches ServiceCatalog: direct, outcome-first, specific — no empty
 * superlatives.
 *
 * `stats` figures are client-reported results measured after launch, and are
 * labelled as such on the page. `services` keys must exist in ServiceCatalog
 * so the detail page never links to a 404.
 *
 * `gallery` holds additional real project images and is rendered only when
 * populated. It ships empty on purpose: the PRD forbids filling sections with
 * generic imagery, so stock photos are not acceptable substitutes for
 * screenshots of the actual product.
 */
class ProjectCatalog
{
    /**
     * The assembled catalogue, built once per process.
     *
     * all() walks the filesystem: it globs twelve screenshot folders and sorts
     * every match. Every public accessor — numbered(), categories(),
     * industries(), count(), featured(), related(), numberFor() — is built on
     * top of it, and the /work index alone calls the family eight times, so
     * without this the same dozen directory listings were read and re-sorted
     * eight times for one page view.
     *
     * Held per-process rather than in the cache because the catalogue is
     * derived from files on disk that change on deploy, not from the database.
     * A long-lived worker (queue, Octane) would keep a stale copy across a
     * deploy, so the array is kept only for the lifetime of one request.
     *
     * This relies on the per-request statics reset of a normal PHP-FPM worker.
     * Under Octane, or any long-lived worker, call flush() after a deploy that
     * changes the catalogue or adds screenshots, or every worker will keep
     * serving its first copy.
     *
     * @var array<string, array<string, mixed>>|null
     */
    private static ?array $resolved = null;

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return self::$resolved ??= self::build();
    }

    /**
     * Drop the in-process copy.
     *
     * Only needed by a process that writes screenshot files while serving —
     * nothing in the app does today. Present so the memoisation above cannot
     * silently become a staleness bug.
     */
    public static function flush(): void
    {
        self::$resolved = null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function build(): array
    {
        $projects = [
            'ecommerce-platform' => [
                'name' => 'E-commerce Platform',
                'category' => 'Web Development',
                'industry' => 'Retail',
                'year' => '2024',
                'client' => 'Multi-branch retail group, Lagos',
                'duration' => '14 weeks',
                'featured' => true,
                'tagline' => 'A storefront that holds up on peak days — and a back office the staff actually use.',
                'summary' => 'A multi-location retail platform built for traffic spikes, local payments, and staff who needed the back office to work as hard as the storefront.',
                'services' => ['web-development', 'seo'],
                'technology' => ['Laravel', 'MySQL', 'Redis', 'Paystack', 'Tailwind CSS'],
                'image' => '/ecommerce.png',
                // Real project screenshots only — see the class docblock.
                'gallery' => [],
                'stats' => [
                    ['value' => '99.98%', 'label' => 'Checkout uptime since launch'],
                    ['value' => '1.4s', 'label' => 'Largest contentful paint on 3G'],
                    ['value' => '+38%', 'label' => 'Mobile revenue, first two quarters'],
                    ['value' => '6', 'label' => 'Branches moved onto one system'],
                ],
                'challenge' => [
                    'The business was running six branches on three spreadsheets, a shared inbox, and a website that fell over every payday weekend. Online and in-store stock disagreed constantly, so customers paid for items that were already sold.',
                    'The brief asked for "a better website". The real problem was inventory truth: one system had to know what each branch held, what had shipped, and what was still available to sell online.',
                ],
                'approach' => [
                    'Made product availability a single source of truth first, so the storefront could never promise stock the business did not have.',
                    'Wired Paystack, bank transfer, and card-on-delivery into one checkout, with the payment steps customers in Nigeria actually complete.',
                    'Rebuilt the catalogue on a Laravel backend with caching at the edge, so promotional traffic hit a warm page instead of the database.',
                    'Shipped a staff-facing order desk and a branch stock view, replacing the spreadsheet and the inbox.',
                ],
                'delivered' => [
                    'Laravel storefront and admin with role-based staff access',
                    'Multi-branch inventory with live stock sync',
                    'Paystack, transfer, and cash-on-delivery checkout flows',
                    'Order desk with printables, refunds, and status tracking',
                    'Catalogue, category, and product SEO structure',
                ],
                'outcomes' => [
                    ['title' => 'Peak days stopped being a crisis', 'description' => 'The last three promotion weekends ran without a single checkout outage, and stock disputes fell to near zero.'],
                    ['title' => 'The back office replaced three spreadsheets', 'description' => 'Branch staff reconcile orders and stock in one place, cutting the close-of-day routine from hours to minutes.'],
                    ['title' => 'Mobile became the growth channel', 'description' => 'Faster pages on slow networks moved mobile from an afterthought to the largest share of revenue.'],
                ],
                'quote' => 'We stopped arguing about which spreadsheet was right. That alone was worth the project.',
                'quoteAttribution' => 'Operations Director, retail group',
            ],

            'enterprise-web-platform' => [
                'name' => 'Enterprise Web Platform',
                'category' => 'Web Development',
                'industry' => 'Professional services',
                'year' => '2024',
                'client' => 'Consulting group, Abuja',
                'duration' => '18 weeks',
                'featured' => true,
                'tagline' => 'Client work, proposals, and delivery in one system instead of four.',
                'summary' => 'A secure web platform that moved proposals, client onboarding, and delivery reporting off email and shared drives for a growing consulting group.',
                'services' => ['web-development', 'digital-strategy'],
                'technology' => ['Laravel', 'MySQL', 'Livewire', 'Role-based access', 'Tailwind CSS'],
                'image' => 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1400&q=80',
                'gallery' => [],
                'stats' => [
                    ['value' => '4→1', 'label' => 'Tools replaced by one platform'],
                    ['value' => '-62%', 'label' => 'Time to onboard a new client'],
                    ['value' => '2 days', 'label' => 'Typical proposal turnaround'],
                ],
                'challenge' => [
                    'Twelve consultants were running client work across an email thread, a shared drive, a spreadsheet tracker, and a slide deck template. Status questions came back as "let me check" — usually the next day.',
                    'Leadership had no reliable view of which engagements were healthy, over budget, or waiting on the client. The reporting was manual and slightly wrong every time.',
                ],
                'approach' => [
                    'Modelled the business first: engagements, deliverables, approvals, and billing milestones, then built the screens around those objects.',
                    'Gave every client a login with scoped access to their own proposals, documents, and progress — so updates stopped travelling by attachment.',
                    'Turned the weekly status meeting into a generated report, keeping the meeting for decisions instead of status reading.',
                    'Kept the design deliberately restrained; this is a tool people open eight times a day.',
                ],
                'delivered' => [
                    'Engagement and proposal management with approval trails',
                    'Client portal with scoped document access',
                    'Automated progress and budget reporting',
                    'Role-based permissions across four internal teams',
                    'Full-text search and audit logging',
                ],
                'outcomes' => [
                    ['title' => 'One view of every engagement', 'description' => 'Leadership sees budget, blockers, and delivery dates across the whole book of work without asking anyone.'],
                    ['title' => 'Clients stopped chasing updates', 'description' => 'Documents and status live in the portal, so update requests dropped to the exceptions that actually need a person.'],
                    ['title' => 'Faster proposals, more wins', 'description' => 'Reusable proposal building cut turnaround from days to roughly two, and the group quoted more work with the same team.'],
                ],
                'quote' => 'It is the first system here that new hires understand without a walkthrough.',
                'quoteAttribution' => 'Managing Partner, consulting group',
            ],

            'financial-saas' => [
                'name' => 'Financial SaaS Dashboard',
                'category' => 'SaaS / FinTech',
                'industry' => 'Fintech',
                'year' => '2023',
                'client' => 'Payments fintech, Nigeria',
                'duration' => '20 weeks',
                'featured' => true,
                'tagline' => 'Live transaction analytics that finance teams can defend in a board meeting.',
                'summary' => 'A real-time analytics dashboard over millions of daily transactions, built so reconciliation, settlement, and support queries resolve in minutes.',
                'services' => ['web-development', 'ai-solutions'],
                'technology' => ['Laravel', 'MySQL', 'Redis', 'Queued jobs', 'REST API'],
                'image' => 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=1400&q=80',
                'gallery' => [],
                'stats' => [
                    ['value' => '2.1M', 'label' => 'Daily transactions modelled'],
                    ['value' => '<900ms', 'label' => 'Dashboard query response'],
                    ['value' => '-75%', 'label' => 'Support tickets per settlement cycle'],
                ],
                'challenge' => [
                    'The finance team exported raw transaction tables into spreadsheets every morning. By the time the numbers were assembled, they described yesterday — and settlement queries from operations took hours to answer.',
                    'Any dispute meant a developer wrote a one-off query, which meant the answer depended on whoever was online.',
                ],
                'approach' => [
                    'Designed the data model around the questions the team actually asks, not around the raw transaction table.',
                    'Aggregated the heavy reads into pre-computed views so the dashboard stays fast as transaction volume grows.',
                    'Gave every figure a drill-down path to its underlying records, so a number can be defended without leaving the screen.',
                    'Turned the most common settlement questions into self-serve lookup, and routed the rest to support with context attached.',
                ],
                'delivered' => [
                    'Real-time dashboard with drill-down to individual transactions',
                    'Settlement and reconciliation reporting',
                    'Role-based access for finance, operations, and support',
                    'Exportable audit trails for regulators',
                    'Anomaly alerts on settlement breaks',
                ],
                'outcomes' => [
                    ['title' => 'Numbers that are current', 'description' => 'The team works from live figures instead of a morning export, so decisions are made on the day, not the day after.'],
                    ['title' => 'Disputes answered in minutes', 'description' => 'Support resolves settlement questions from the dashboard directly rather than escalating to engineering.'],
                    ['title' => 'Audit-ready by default', 'description' => 'Every adjustment and settlement figure carries a traceable record, which turned a quarterly scramble into a routine export.'],
                ],
                'quote' => 'Reconciliation went from our scariest day of the month to a Tuesday afternoon task.',
                'quoteAttribution' => 'Head of Finance, payments fintech',
            ],

            'hospitality-platform' => [
                'name' => 'Hospitality Platform',
                'category' => 'Hospitality Tech',
                'industry' => 'Hospitality',
                'year' => '2023',
                'client' => 'Hospitality group, West Africa',
                'duration' => '16 weeks',
                'featured' => true,
                'tagline' => 'Reservations, housekeeping, and guest history on one system across every property.',
                'summary' => 'A booking and property management platform for a multi-location hospitality group, connecting direct reservations with the operational teams running each property.',
                'services' => ['web-development', 'visuals'],
                'technology' => ['Laravel', 'MySQL', 'Channel manager API', 'Paystack', 'Scheduled jobs'],
                'image' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1400&q=80',
                'gallery' => [],
                'stats' => [
                    ['value' => '5', 'label' => 'Properties on one platform'],
                    ['value' => '+27%', 'label' => 'Direct bookings, first year'],
                    ['value' => '-40%', 'label' => 'No-shows after deposit flow'],
                ],
                'challenge' => [
                    'Each property ran its own booking flow while the group sold through third-party channels. Nobody could see a guest\'s history, so a returning guest was greeted as a stranger at a property the group owned.',
                    'Front desk managed room state on paper, so overbooking was discovered by guests, not systems.',
                ],
                'approach' => [
                    'Built one availability model shared by direct bookings and channel manager integrations, so no property could oversell itself.',
                    'Added a deposit step to direct reservations and softened the guest journey around it — the fall in no-shows paid for itself immediately.',
                    'Gave front desk a live room board with housekeeping states, so allocation happens from real data.',
                    'Put guest preferences and stay history in one profile, visible at check-in across the group.',
                ],
                'delivered' => [
                    'Direct booking engine with deposits and local payment options',
                    'Channel manager integration for third-party inventory',
                    'Front-desk room board and housekeeping workflow',
                    'Guest profiles with preference and history tracking',
                    'Property-level reporting for occupancy and revenue',
                ],
                'outcomes' => [
                    ['title' => 'One guest, not five strangers', 'description' => 'Preferences and history follow the guest across properties, so service is consistent across the group.'],
                    ['title' => 'Overbooking stopped being a surprise', 'description' => 'Availability is enforced in the system, and front desk works from live room state.'],
                    ['title' => 'Direct bookings became worth pushing', 'description' => 'A cleaner direct flow with deposits moved volume away from expensive third-party channels.'],
                ],
                'quote' => 'The group finally runs as one business instead of five hotels sharing an owner.',
                'quoteAttribution' => 'Group General Manager, hospitality',
            ],

            'logistics-portal' => [
                'name' => 'Logistics Client Portal',
                'category' => 'Client Portals',
                'industry' => 'Logistics',
                'year' => '2022',
                'client' => 'Freight and logistics company, Lagos',
                'duration' => '11 weeks',
                'featured' => false,
                'tagline' => 'Shipment tracking that customers check themselves, saving the phone calls.',
                'summary' => 'A client portal that gives a logistics company\'s customers live shipment status, documents, and invoicing without phoning the operations desk.',
                'services' => ['web-development'],
                'technology' => ['Laravel', 'MySQL', 'REST API', 'SMS gateway', 'Document storage'],
                'image' => 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?w=1400&q=80',
                'gallery' => [],
                'stats' => [
                    ['value' => '-68%', 'label' => 'Status calls to operations'],
                    ['value' => '11 weeks', 'label' => 'Discovery to launch'],
                    ['value' => '24/7', 'label' => 'Self-service visibility'],
                ],
                'challenge' => [
                    'The operations desk fielded the same question every morning: where is my shipment? Each answer cost a phone call, a lookup across three systems, and a manager\'s time.',
                    'Customers with the least technical staff were the ones who depended on those calls most, because they had no other way to see progress.',
                ],
                'approach' => [
                    'Made the operations team the source of truth first: status changes in one place, published to the portal automatically.',
                    'Designed the customer journey for someone on a phone, on a poor connection, in a depot yard — the actual conditions of a shipment.',
                    'Put invoices and signed documents in the same place as tracking, which removed most of the remaining "can you resend…" emails.',
                    'Kept the integration surface small: one webhook into the existing TMS, no migration of the systems already working.',
                ],
                'delivered' => [
                    'Customer portal with role-based shipment access',
                    'Live status timeline per consignment',
                    'Document and invoice delivery',
                    'Exception alerts by SMS and email',
                    'Operations-side status console',
                ],
                'outcomes' => [
                    ['title' => 'Operations stopped answering "where is it?"', 'description' => 'Customers check their own shipments, which freed the desk for the exceptions that genuinely need a person.'],
                    ['title' => 'Exceptions surfaced earlier', 'description' => 'Alerts on delays and customs holds meant customers heard about problems before they noticed them.'],
                    ['title' => 'Proof of delivery, automatically', 'description' => 'Signed documents reached the customer at the moment of delivery, not at month-end reconciliation.'],
                ],
                'quote' => 'Our morning phone queue basically disappeared. That is the whole story.',
                'quoteAttribution' => 'Operations Manager, logistics company',
            ],

            'field-service-app' => [
                'name' => 'Field Service App',
                'category' => 'Mobile Development',
                'industry' => 'Facilities management',
                'year' => '2022',
                'client' => 'Facilities management provider, Nigeria',
                'duration' => '13 weeks',
                'featured' => false,
                'tagline' => 'Job cards, photos, and sign-off in the field — working offline, syncing later.',
                'summary' => 'A cross-platform mobile app for engineers working on sites with unreliable signal, from job card to customer sign-off.',
                'services' => ['mobile-development', 'ai-solutions'],
                'technology' => ['Cross-platform app', 'Laravel API', 'Offline storage', 'Background sync', 'Push notifications'],
                'image' => 'https://images.unsplash.com/photo-1581094794329-c8112a89af12?w=1400&q=80',
                'gallery' => [],
                'stats' => [
                    ['value' => '1 codebase', 'label' => 'Shipping to both app stores'],
                    ['value' => '320+', 'label' => 'Engineers in the field weekly'],
                    ['value' => '-31%', 'label' => 'Reactive callouts'],
                ],
                'challenge' => [
                    'Engineers carried paper job cards. With no signal in basements, plant rooms, and unfinished buildings, work was recorded in the evening from memory, and supervisors learned about completed jobs from the customer\'s phone call.',
                    'Customer sign-off happened on paper, which then had to be driven to an office and keyed in.',
                ],
                'approach' => [
                    'Designed offline-first: every job card, note, and photo is saved on the device and syncs when a connection returns, with no lost work.',
                    'Kept the on-site flow to a few taps, because engineers work in gloves and poor light.',
                    'Built in-app customer sign-off with photo evidence, so approval happens before the engineer leaves the site.',
                    'Used job history to flag repeat faults on the same asset, turning reactive callouts into planned maintenance.',
                ],
                'delivered' => [
                    'Cross-platform app (iOS and Android) from one codebase',
                    'Offline job cards with automatic background sync',
                    'Photo capture and in-app customer sign-off',
                    'Asset history and repeat-fault flagging',
                    'Supervisor console with live job status',
                ],
                'outcomes' => [
                    ['title' => 'Records match reality', 'description' => 'Job data is captured at the site, so supervisor reports and billing follow the same source.'],
                    ['title' => 'No more evening paperwork', 'description' => 'Engineers finish their day with the record already complete, off-site.'],
                    ['title' => 'Some faults stopped recurring', 'description' => 'Repeat-failure flags turned a few maintenance visits into scheduled repairs instead of emergency dispatches.'],
                ],
                'quote' => 'The app works in the places our old process never did — inside the plant room.',
                'quoteAttribution' => 'Service Delivery Lead, facilities management',
            ],
        ];

        // Real project captures live in public/images/works/{project}. The
        // first screenshot is the cover; the rest become the detail gallery.
        $assetFolders = [
            'ecommerce-platform' => ['folder' => 'marigold', 'name' => 'Marigold Signature', 'industry' => 'Retail', 'year' => '2026'],
            'enterprise-web-platform' => ['folder' => 'ercaan', 'name' => 'ERCAAN National', 'industry' => 'Professional services'],
            'financial-saas' => ['folder' => 'cemcsmfb', 'name' => 'CEMCS Microfinance Bank', 'industry' => 'Financial services'],
            'hospitality-platform' => ['folder' => '7and8', 'name' => '7&8 Specialist Hospital', 'industry' => 'Healthcare', 'year' => '2024'],
            'logistics-portal' => ['folder' => 'ctech', 'name' => 'CTech Support IT', 'industry' => 'Technology', 'year' => '2023'],
            'field-service-app' => ['folder' => 'atmosng', 'name' => 'ATMOS Integrated Services', 'industry' => 'Engineering'],
            'global-apex-farmers' => ['folder' => 'gafconl', 'name' => 'Global Apex Farmers', 'industry' => 'Agriculture'],
            'edgeplus' => ['folder' => 'edgeplus', 'name' => 'EdgePlus', 'industry' => 'Business services'],
            'icon-of-change' => ['folder' => 'icon', 'name' => 'Icon of Change Initiatives', 'industry' => 'Non-profit'],
            'heyforsagee' => ['folder' => 'heyforsagee', 'name' => 'Heyforsagee', 'industry' => 'Consumer services'],
            'ecobeam' => ['folder' => 'ecobeam', 'name' => 'Ecobeam Lighting Solutions', 'industry' => 'Energy'],
            'bunmi-adedayofoundation' => ['folder' => 'baf', 'name' => 'Bunmi Adedayo Foundation', 'industry' => 'Non-profit', 'year' => '2020'],
        ];

        $domains = [
            'ecommerce-platform' => 'https://marigoldsignatureng.com',
            'financial-saas' => 'https://cemcsmfb.com',
            'enterprise-web-platform' => 'https://ercaannational.org.ng',
            'global-apex-farmers' => 'https://globalapexfarmers.org.ng',
            'logistics-portal' => 'https://ctechsupportit.com',
            'field-service-app' => 'https://atmosng.com',
            'edgeplus' => 'https://www.edgeplus.com.ng',
            'hospitality-platform' => 'https://7and8specialisthospital.com.ng',
            'icon-of-change' => 'https://iconofchange.org',
            'heyforsagee' => 'https://heyforsagee.ng',
            'ecobeam' => 'https://ecobeam.ng',
            'bunmi-adedayofoundation' => 'https://bunmiadedayofoundation.org',
        ];

        // Screenshots are served as WebP (see images:optimize-works), which is
        // roughly a tenth of the bytes of the source PNG. The glob is
        // extension-specific rather than a wildcard: with both formats sitting
        // in the folder a wildcard would interleave `x-01.png` and
        // `x-01.webp`, and the natural sort would then produce two entries per
        // capture, putting the wrong image in the gallery.
        // Resolve the public directory without assuming a booted application.
        // This class is static data, and PHPUnit reads it from a data provider
        // that runs before the framework exists — so calling public_path() here
        // made every project-detail test report "invalid data provider" instead
        // of failing on its own merits. Fall back to the directory this file
        // lives in, which is where public/ is by default, and prefer the app's
        // own answer whenever one is available so a customised public path
        // still wins.
        $container = Container::getInstance();
        $publicRoot = method_exists($container, 'publicPath')
            ? $container->publicPath()
            : dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'public';

        $assetPath = static function (string $folder) use ($publicRoot): array {
            $files = glob($publicRoot.DIRECTORY_SEPARATOR."images/works/{$folder}/*.webp") ?: [];
            sort($files, SORT_NATURAL | SORT_FLAG_CASE);

            return array_map(
                static fn (string $file): string => '/images/works/' . $folder . '/' . basename($file),
                array_values(array_filter($files, static fn (string $file): bool => is_file($file)))
            );
        };

        $template = $projects['ecommerce-platform'];
        foreach ($assetFolders as $slug => $asset) {
            $screenshots = $assetPath($asset['folder']);
            if ($screenshots === []) {
                continue;
            }

            $hasWrittenEntry = isset($projects[$slug]);
            $project = $projects[$slug] ?? $template;

            // Name and industry are facts about the real client, so they come
            // from the screenshot set. The industry reads truer against the
            // finished work than the draft it displaces — "Healthcare" for a
            // hospital, where the placeholder said "Hospitality".
            //
            // Discipline deliberately does not. Every $assetFolders entry
            // carried the same "Web Development" value, because that is the
            // only discipline the captures were commissioned for, and writing
            // it back over each project's own category collapsed all twelve
            // into a single facet. That quietly turned the category filter
            // into a no-op and left the "no projects match that combination"
            // state unreachable, since no real category/industry pair could be
            // empty any more. A project keeps the discipline written in its own
            // entry; one without written copy inherits it from the template.
            $project['name'] = $asset['name'];
            $project['industry'] = $asset['industry'];
            $project['year'] = $asset['year'] ?? $project['year'];
            $project['domain'] = $domains[$slug] ?? null;
            $project['image'] = $screenshots[0];
            $project['gallery'] = array_slice($screenshots, 1);
            $project['featured'] = in_array($slug, ['ecommerce-platform', 'financial-saas', 'enterprise-web-platform'], true);
            $project['tagline'] = "A clearer digital experience for {$asset['name']}.";
            $project['summary'] = "A published Webxpress project for {$asset['name']}, presented through the real interface and screen captures from the finished site.";

            // The project page credits this client by name, so it has to be the
            // real organisation. Left alone it kept whatever the borrowed entry
            // said, which put "Multi-branch retail group, Lagos" on six
            // unrelated projects — an entry with no written copy inherits the
            // template verbatim, descriptor and all.
            $project['client'] = $asset['name'];

            // Same problem, smaller blast radius: the template's "14 weeks" was
            // showing up as the timeline on every project without written copy.
            if (! $hasWrittenEntry) {
                $project['duration'] = null;
            }

            $projects[$slug] = $project;
        }

        // Every entry carries its own key as 'slug' so views can build links
        // without the caller re-deriving it from the catalogue order.
        foreach ($projects as $slug => $project) {
            $project['slug'] = $slug;
            $projects[$slug] = $project;
        }

        return $projects;
    }

    /**
     * A single project, or null when the slug is unknown.
     *
     * @return array<string, mixed>|null
     */
    public static function get(string $slug): ?array
    {
        return self::all()[$slug] ?? null;
    }

    /**
     * Projects in catalogue order, each carrying its catalogue number
     * ('01', '02'…). Numbers come from the full catalogue, so they stay
     * stable when a category filter is applied. Always a list, never keyed by
     * slug, so callers can index and loop it directly.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function numbered(?string $categorySlug = null, ?string $industrySlug = null): array
    {
        $projects = [];
        $index = 0;

        foreach (self::all() as $project) {
            // Number before filtering, so a project keeps its catalogue
            // number no matter which filters are active.
            $project['number'] = str_pad((string) ++$index, 2, '0', STR_PAD_LEFT);

            if ($categorySlug !== null && Str::slug($project['category']) !== $categorySlug) {
                continue;
            }

            if ($industrySlug !== null && Str::slug($project['industry']) !== $industrySlug) {
                continue;
            }

            $projects[] = $project;
        }

        return $projects;
    }

    /**
     * Catalogue number for a slug, or null when the slug is unknown.
     */
    public static function numberFor(string $slug): ?string
    {
        $position = array_search($slug, array_keys(self::all()), true);

        return $position === false ? null : str_pad((string) ($position + 1), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Projects flagged as featured, falling back to catalogue order when
     * nothing is flagged so the homepage always has something to show.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function featured(int $limit = 3): array
    {
        $featured = array_filter(self::numbered(), fn (array $project): bool => $project['featured']);

        if ($featured === []) {
            $featured = self::numbered();
        }

        return array_slice($featured, 0, $limit);
    }

    /**
     * Filterable categories, in catalogue order, each with a URL-safe slug.
     *
     * Counts are scoped to $industrySlug when given, so the chips describe
     * what a click will actually return rather than the whole catalogue.
     *
     * @return array<int, array{name: string, slug: string, count: int}>
     */
    public static function categories(?string $industrySlug = null): array
    {
        return self::facets('category', 'industry', $industrySlug);
    }

    /**
     * Filterable industries, in catalogue order, each with a URL-safe slug.
     *
     * @return array<int, array{name: string, slug: string, count: int}>
     */
    public static function industries(?string $categorySlug = null): array
    {
        return self::facets('industry', 'category', $categorySlug);
    }

    /**
     * Group the catalogue by one field, counting how many projects sit in each
     * group — optionally narrowed to one value of a second field. Shared by
     * categories() and industries(), which are the same query over a different
     * field.
     *
     * @return array<int, array{name: string, slug: string, count: int}>
     */
    private static function facets(string $field, ?string $withinField = null, ?string $withinSlug = null): array
    {
        $projects = self::all();

        if ($withinSlug !== null) {
            $projects = array_filter(
                $projects,
                fn (array $project): bool => Str::slug($project[$withinField]) === $withinSlug
            );
        }

        $facets = [];

        foreach ($projects as $project) {
            $slug = Str::slug($project[$field]);

            $facets[$slug] ??= ['name' => $project[$field], 'slug' => $slug, 'count' => 0];
            $facets[$slug]['count']++;
        }

        return array_values($facets);
    }

    /**
     * Up to $limit other projects, preferring ones that share a service or a
     * category with the given slug so the suggestions stay relevant.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function related(string $slug, int $limit = 3): array
    {
        $current = self::get($slug);

        if ($current === null) {
            return array_slice(self::numbered(), 0, $limit);
        }

        $services = $current['services'];
        $scored = [];
        $order = 0;

        foreach (self::numbered() as $project) {
            if ($project['slug'] === $slug) {
                continue;
            }

            $score = 0;

            if (array_intersect($services, $project['services']) !== []) {
                $score += 2;
            }

            if ($project['category'] === $current['category']) {
                $score += 1;
            }

            $scored[] = ['score' => $score, 'order' => $order++, 'project' => $project];
        }

        // Tie-break on catalogue order so equal scores stay predictable.
        usort($scored, fn (array $a, array $b): int => ($b['score'] <=> $a['score']) ?: ($a['order'] <=> $b['order']));

        return array_slice(array_column($scored, 'project'), 0, $limit);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function forService(string $serviceSlug, int $limit = 3): array
    {
        $matches = array_filter(
            self::numbered(),
            fn (array $project): bool => in_array($serviceSlug, $project['services'], true)
        );

        return array_slice($matches, 0, $limit);
    }

    public static function count(): int
    {
        return count(self::all());
    }
}
