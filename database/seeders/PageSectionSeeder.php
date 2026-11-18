<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Promotes the hardcoded homepage Blade sections into editable, typed
 * page_sections blocks.
 *
 * Two-tier contract: Blade keeps the design shell, the DB keeps the copy.
 *
 * Create-only and safe to re-run: each block is keyed on (page_id, key) and is
 * never rewritten once it exists, so a re-seed cannot discard admin edits.
 * Re-promoting changed Blade copy is a deliberate delete-then-seed.
 *
 * Section copy below is lifted verbatim from the Blade it replaces, so the
 * rendered design is byte-identical to the pre-promotion output.
 */
class PageSectionSeeder extends Seeder
{
    public function run(): void
    {
        $page = Page::query()->where('slug', 'home')->first();

        if (! $page) {
            throw new RuntimeException(
                'PageSectionSeeder requires the "home" page record. Run SitePageSeeder first.'
            );
        }

        foreach ($this->blocks() as $sortOrder => $block) {
            $section = PageSection::query()->firstOrNew([
                'page_id' => $page->id,
                'key' => $block['key'],
            ]);

            // Create-only. Once the block exists it is owned by the admin.
            if ($section->exists) {
                continue;
            }

            $section->type = $block['type'];
            $section->sort_order = $sortOrder;
            $section->data = $block['data'];
            $section->save();
        }
    }

    /**
     * Homepage blocks in page order.
     *
     * @return array<int, array{key: string, type: string, data: array<string, mixed>}>
     */
    private function blocks(): array
    {
        return [
            20 => [
                'key' => 'trusted-by',
                'type' => PageSection::TYPE_LOGO_STRIP,
                'data' => $this->logoStripBlock(),
            ],
            30 => [
                'key' => 'who-we-are',
                'type' => PageSection::TYPE_BIO,
                'data' => $this->bioBlock(),
            ],
            40 => [
                'key' => 'services-preview',
                'type' => PageSection::TYPE_SECTION_HEAD,
                'data' => $this->sectionHead('Services', 'What we build'),
            ],
            60 => [
                'key' => 'how-we-work',
                'type' => PageSection::TYPE_STEPS,
                'data' => $this->stepsBlock(),
            ],
            70 => [
                'key' => 'industries-preview',
                'type' => PageSection::TYPE_CARD_GRID,
                'data' => $this->cardGridBlock(),
            ],
            80 => [
                'key' => 'testimonials',
                'type' => PageSection::TYPE_SECTION_HEAD,
                'data' => $this->sectionHead(
                    'Client Voices',
                    'What our clients say',
                    "Real outcomes from the teams we've built with — and the work we're proud to stand behind.",
                ),
            ],
            90 => [
                'key' => 'major-cta',
                'type' => PageSection::TYPE_CTA,
                'data' => $this->ctaBlock(),
            ],
            100 => [
                'key' => 'insights-preview',
                'type' => PageSection::TYPE_SECTION_HEAD,
                'data' => $this->sectionHead('Insights', 'Thinking out loud'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sectionHead(string $label, string $heading, ?string $subheading = null): array
    {
        return array_filter([
            'label' => $label,
            'heading' => $heading,
            'subheading' => $subheading,
        ], fn (mixed $value): bool => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private function logoStripBlock(): array
    {
        return [
            'label' => 'Trusted by',
            'items' => [
                ['name' => 'University of Lagos', 'abbr' => 'UNILAG'],
                ['name' => 'Access Bank', 'abbr' => 'Access'],
                ['name' => 'Guarantee Trust Bank', 'abbr' => 'GTBank'],
                ['name' => 'National University Commission', 'abbr' => 'NUC'],
                ['name' => 'Hilton Hotels', 'abbr' => 'Hilton'],
                ['name' => 'SunTrust Bank', 'abbr' => 'SunTrust'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function stepsBlock(): array
    {
        return [
            'label' => 'How We Work',
            'heading' => 'A process built for results',
            'items' => [
                [
                    'number' => '01',
                    'title' => 'Discover',
                    'description' => 'We start by understanding your business, goals, users, and constraints. No assumptions — we ask the right questions and listen carefully.',
                ],
                [
                    'number' => '02',
                    'title' => 'Define',
                    'description' => 'We turn research into a clear brief: objectives, deliverables, timeline, and success metrics. Everyone knows what we\'re building and why.',
                ],
                [
                    'number' => '03',
                    'title' => 'Build',
                    'description' => 'We design and develop with precision. Regular progress reviews keep you informed and aligned throughout the build phase.',
                ],
                [
                    'number' => '04',
                    'title' => 'Launch & Grow',
                    'description' => 'We deploy carefully, test thoroughly, and stay available. After launch, we measure results and support continued growth.',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cardGridBlock(): array
    {
        return [
            'label' => 'Industries',
            'heading' => 'We understand your market',
            'items' => [
                ['name' => 'Corporate & Enterprise', 'description' => 'Scalable platforms for large organizations.'],
                ['name' => 'Healthcare', 'description' => 'Digital tools for patient care and administration.'],
                ['name' => 'Finance & FinTech', 'description' => 'Secure, compliant financial technology.'],
                ['name' => 'Hospitality', 'description' => 'Booking, management, and guest experience platforms.'],
                ['name' => 'Retail & E-commerce', 'description' => 'High-performance stores and inventory systems.'],
                ['name' => 'Education', 'description' => 'LMS platforms, portals, and student systems.'],
                ['name' => 'Professional Services', 'description' => 'CRM, workflow, and client management tools.'],
                ['name' => 'Startups', 'description' => 'Fast-moving MVPs and scalable product foundations.'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function ctaBlock(): array
    {
        return [
            'label' => 'Start a Project',
            'heading' => 'Have a business problem',
            'heading_accent' => 'worth solving?',
            'body' => 'Let\'s turn it into a digital solution built to work. We partner with businesses that take execution seriously.',
            'buttons' => [
                ['label' => 'Start a Project', 'route' => 'contact', 'style' => 'primary'],
                ['label' => 'View Our Work', 'route' => 'work.index', 'style' => 'ghost'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function bioBlock(): array
    {
        return [
            'label' => 'Who We Are',

            'quote' => 'Technology should solve real business problems, not simply look impressive.',

            'paragraphs' => [
                'Webxpress Technologies has been at the intersection of technology and business since 2010. We partner with organizations across Africa to design, build, and scale digital products that actually work.',
                'We combine technical depth with a clear understanding of the African business landscape — from fintech to hospitality, from startups to enterprise. Our work spans the full digital lifecycle: strategy, design, development, and growth.',
            ],

            'cta' => [
                'label' => 'Our Story',
                'route' => 'about',
            ],

            'stats' => [
                [
                    'variant' => 'plain',
                    'value' => '15+',
                    'label' => 'Years of Experience',
                ],
                [
                    'variant' => 'plain',
                    'value' => '200+',
                    'label' => 'Projects Delivered',
                ],
                [
                    'variant' => 'inverted',
                    'value' => '8',
                    'label' => 'Service Specialties',
                ],
            ],
        ];
    }
}
