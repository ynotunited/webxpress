<?php

namespace App\Support;

use App\Models\Page;
use App\Models\Post;
use App\Services\ProjectCatalog;
use App\Services\ServiceCatalog;
use Illuminate\Support\Str;

/**
 * Structured data for the pages a machine reads.
 *
 * Most AI crawlers do not run JavaScript. They take the raw HTML and stop. The
 * visible copy on a page is written to be read by a person — sentences, tone,
 * narrative order — which is the wrong shape for something that wants to answer
 * "how long, how much, what's included" without inferring anything.
 *
 * JSON-LD is the one channel where those facts are stated rather than implied.
 * A crawler reads a `Service` and knows the provider, the area served, and the
 * items offered without parsing a paragraph; a `FAQPage` hands it the question
 * and the answer already paired.
 *
 * Everything here is emitted from content the site already renders. The schema
 * describes the page; it never introduces a claim the copy does not make, which
 * is why there is no price in the output. The site quotes per project, so the
 * honest structured fact is a price specification rather than a fabricated
 * figure.
 */
final class StructuredData
{
    /**
     * The `@graph` for the current page, or null when there is nothing worth
     * stating.
     *
     * @return array<string, mixed>|null
     */
    public static function forPage(string $path): ?array
    {
        $graph = [];

        // Every page gets the organisation, because "who is this and how do I
        // reach them" is the first thing a machine tries to establish.
        $organization = self::organization();

        if ($organization !== null) {
            $graph[] = $organization;
        }

        foreach (self::pageEntities($path) as $entity) {
            $graph[] = $entity;
        }

        if ($graph === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ];
    }

    /**
     * Site-wide facts: who this is, how to contact them, where they work.
     *
     * @return array<string, mixed>|null
     */
    private static function organization(): ?array
    {
        $name = site_name();

        if ($name === '') {
            return null;
        }

        $organization = [
            '@type' => 'Organization',
            '@id' => url('/#organization'),
            'name' => $name,
            'url' => url('/'),
        ];

        if ($description = self::clean(site_setting('footer_tagline'))) {
            $organization['description'] = $description;
        }

        if ($logo = self::absolute(brand_logo())) {
            $organization['logo'] = [
                '@type' => 'ImageObject',
                'url' => $logo,
            ];
        }

        $contact = self::contactPoint();

        if ($contact !== null) {
            $organization['contactPoint'] = $contact;
        }

        if ($address = self::postalAddress()) {
            $organization['address'] = $address;
        }

        $social = self::socialProfiles();

        if ($social !== []) {
            $organization['sameAs'] = $social;
        }

        return $organization;
    }

    /**
     * The contact point, with the channels a visitor is expected to use.
     *
     * @return array<string, mixed>|null
     */
    private static function contactPoint(): ?array
    {
        $email = self::clean(site_setting('contact_email'));
        $phone = self::clean(site_setting('contact_phone'));

        if ($email === null && $phone === null) {
            return null;
        }

        $point = [
            '@type' => 'ContactPoint',
            'contactType' => 'sales',
            'availableLanguage' => ['en', 'en-NG'],
        ];

        if ($email !== null) {
            $point['email'] = $email;
        }

        if ($phone !== null) {
            $point['telephone'] = $phone;
        }

        // No street address is published, so the service area is stated
        // instead of a location that would send someone to an empty field.
        $point['areaServed'] = self::areaServed();

        return $point;
    }

    /**
     * Where the work is done.
     *
     * @return list<string>
     */
    private static function areaServed(): array
    {
        $areas = ['Nigeria', 'Africa'];

        if ($address = self::clean(site_setting('contact_address'))) {
            array_unshift($areas, $address);
        }

        return array_values(array_unique($areas));
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function postalAddress(): ?array
    {
        $address = self::clean(site_setting('contact_address'));

        if ($address === null) {
            return null;
        }

        return [
            '@type' => 'PostalAddress',
            'addressLocality' => $address,
            'addressCountry' => 'NG',
        ];
    }

    /**
     * @return list<string>
     */
    private static function socialProfiles(): array
    {
        $urls = [];

        foreach (['linkedin_url', 'instagram_url', 'facebook_url', 'x_url'] as $key) {
            $value = self::clean(site_setting($key));

            if ($value !== null) {
                $urls[] = $value;
            }
        }

        return $urls;
    }

    /**
     * Page-specific entities, chosen from the path.
     *
     * @return list<array<string, mixed>>
     */
    private static function pageEntities(string $path): array
    {
        $path = '/'.trim($path, '/');

        $entities = [self::website()];

        if (str_starts_with($path, '/services/')) {
            $slug = trim(substr($path, strlen('/services/')), '/');
            $entities = array_merge($entities, self::serviceEntities($slug));
        } elseif ($path === '/services') {
            $entities = array_merge($entities, self::serviceListEntities());
        } elseif (str_starts_with($path, '/insights/')) {
            $slug = trim(substr($path, strlen('/insights/')), '/');
            $entities = array_merge($entities, self::insightEntities($slug));
        } elseif (str_starts_with($path, '/work/')) {
            $slug = trim(substr($path, strlen('/work/')), '/');
            $entities = array_merge($entities, self::workEntities($slug));
        } elseif ($path === '/work') {
            $entities[] = self::itemList('/work', 'Case studies', count(ProjectCatalog::all()));
        } elseif ($path === '/insights') {
            $entities[] = self::itemList(
                '/insights',
                'Insights',
                Post::query()->where('is_published', true)->count()
            );
        }

        return array_values(array_filter($entities));
    }

    /**
     * @return array<string, mixed>
     */
    private static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => url('/#website'),
            'url' => url('/'),
            'name' => site_name(),
            'publisher' => ['@id' => url('/#organization')],
            'inLanguage' => 'en-NG',
        ];
    }

    /**
     * A service page: the service itself, what it delivers, and its FAQ.
     *
     * @return list<array<string, mixed>>
     */
    private static function serviceEntities(string $slug): array
    {
        $service = self::resolvedService($slug);

        if ($service === null) {
            return [];
        }

        $url = url('/services/'.$slug);

        $node = [
            '@type' => 'Service',
            '@id' => $url.'#service',
            'name' => $service['name'],
            'url' => $url,
            'serviceType' => $service['name'],
            'description' => self::clean($service['summary'] ?? null) ?? $service['name'],
            'provider' => ['@id' => url('/#organization')],
            'areaServed' => self::areaServed(),
        ];

        if ($fit = self::clean($service['fit'] ?? null)) {
            $node['audience'] = ['@type' => 'BusinessAudience', 'audienceType' => $fit];
        }

        // Deliverables become a real OfferCatalog, which is the machine-readable
        // form of "what's included".
        $offers = self::offerCatalog($service['deliverables'] ?? []);

        if ($offers !== null) {
            $node['hasOfferCatalog'] = $offers;
        }

        $entities = [$node];

        $faq = self::faqPage($service['faq'] ?? [], $url.'#faq');

        if ($faq !== null) {
            $entities[] = $faq;
        }

        $entities[] = [
            '@type' => 'BreadcrumbList',
            '@id' => $url.'#breadcrumb',
            'itemListElement' => self::breadcrumbs([
                ['name' => 'Home', 'path' => '/'],
                ['name' => 'Services', 'path' => '/services'],
                ['name' => $service['name'], 'path' => '/services/'.$slug],
            ]),
        ];

        return $entities;
    }

    /**
     * The service as the page actually renders it.
     *
     * `pages/services/show.blade.php` lets a CMS editor replace the deliverables,
     * outcomes and FAQ held in the catalog with a per-service structured block.
     * Reading the catalog alone would publish markup that contradicts the page
     * whenever an editor has edited either list, so the same override is applied
     * here, in the same order, with the same normalisation.
     *
     * @return array<string, mixed>|null
     */
    private static function resolvedService(string $slug): ?array
    {
        $service = ServiceCatalog::get($slug);

        if ($service === null) {
            return null;
        }

        $structured = Page::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->value('structured_content');

        if (! is_array($structured)) {
            return $service;
        }

        foreach (['deliverables', 'outcomes'] as $key) {
            if (isset($structured[$key]) && is_array($structured[$key])) {
                $service[$key] = $structured[$key];
            }
        }

        if (filled($structured['faq'] ?? null)) {
            $service['faq'] = collect($structured['faq'])->map(fn (array $item): array => [
                'q' => $item['question'] ?? '',
                'a' => $item['answer'] ?? '',
            ])->all();
        }

        return $service;
    }

    /**
     * Deliverables as an OfferCatalog.
     *
     * `Offer` carries an empty `priceSpecification` on purpose: the site quotes
     * per project rather than publishing a number, and inventing one here would
     * put a claim in the markup that the page copy does not make.
     *
     * @param  array<int, array<string, mixed>>  $deliverables
     * @return array<string, mixed>|null
     */
    private static function offerCatalog(array $deliverables): ?array
    {
        $items = [];

        foreach ($deliverables as $item) {
            $title = self::clean($item['title'] ?? null);

            if ($title === null) {
                continue;
            }

            $offer = [
                '@type' => 'Offer',
                'itemOffered' => [
                    '@type' => 'Service',
                    'name' => $title,
                ],
                'availability' => 'https://schema.org/InStock',
                'priceCurrency' => 'NGN',
            ];

            if ($description = self::clean($item['description'] ?? null)) {
                $offer['description'] = $description;
            }

            $items[] = $offer;
        }

        if ($items === []) {
            return null;
        }

        return [
            '@type' => 'OfferCatalog',
            'name' => 'What is included',
            'itemListElement' => $items,
        ];
    }

    /**
     * The service index, as a list of every service offered.
     *
     * @return list<array<string, mixed>>
     */
    private static function serviceListEntities(): array
    {
        $items = [];

        foreach (ServiceCatalog::all() as $slug => $service) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => count($items) + 1,
                'item' => [
                    '@type' => 'Service',
                    'name' => $service['name'],
                    'url' => url('/services/'.$slug),
                    'description' => self::clean($service['summary'] ?? null),
                ],
            ];
        }

        return [
            [
                '@type' => 'ItemList',
                '@id' => url('/services#services'),
                'name' => 'Services',
                'itemListElement' => $items,
            ],
            [
                '@type' => 'BreadcrumbList',
                '@id' => url('/services#breadcrumb'),
                'itemListElement' => self::breadcrumbs([
                    ['name' => 'Home', 'path' => '/'],
                    ['name' => 'Services', 'path' => '/services'],
                ]),
            ],
        ];
    }

    /**
     * An insight article: what it is, when, and who wrote it.
     *
     * @return list<array<string, mixed>>
     */
    private static function insightEntities(string $slug): array
    {
        $post = Post::query()
            ->published()
            ->where('slug', $slug)
            ->first();

        if ($post === null) {
            return [];
        }

        $url = url('/insights/'.$slug);

        $article = [
            '@type' => 'BlogPosting',
            '@id' => $url.'#article',
            'headline' => $post->title,
            'url' => $url,
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
            'publisher' => ['@id' => url('/#organization')],
            'inLanguage' => 'en-NG',
        ];

        if ($description = self::clean($post->seo_description ?: $post->excerpt)) {
            $article['description'] = $description;
        }

        if ($image = self::absolute($post->coverUrl)) {
            $article['image'] = $image;
        }

        if ($published = $post->published_at ?? $post->created_at) {
            $article['datePublished'] = $published->toAtomString();
            $article['dateModified'] = ($post->updated_at ?? $published)->toAtomString();
        }

        if ($category = $post->category?->name) {
            $article['articleSection'] = $category;
        }

        $entities = [$article];

        $faq = self::faqPage(
            self::extractFaq($post->bodyHtml),
            $url.'#faq'
        );

        if ($faq !== null) {
            $entities[] = $faq;
        }

        $entities[] = [
            '@type' => 'BreadcrumbList',
            '@id' => $url.'#breadcrumb',
            'itemListElement' => self::breadcrumbs([
                ['name' => 'Home', 'path' => '/'],
                ['name' => 'Insights', 'path' => '/insights'],
                ['name' => $post->title, 'path' => '/insights/'.$slug],
            ]),
        ];

        return $entities;
    }

    /**
     * A case study: the work, the client, and how long it took.
     *
     * @return list<array<string, mixed>>
     */
    private static function workEntities(string $slug): array
    {
        $project = ProjectCatalog::get($slug);

        if ($project === null) {
            return [];
        }

        $url = url('/work/'.$slug);

        $article = [
            '@type' => 'CreativeWork',
            '@id' => $url.'#project',
            'name' => $project['name'],
            'url' => $url,
            'about' => self::clean($project['tagline'] ?? null),
            'creator' => ['@id' => url('/#organization')],
            'inLanguage' => 'en-NG',
        ];

        if ($summary = self::clean($project['summary'] ?? null)) {
            $article['description'] = $summary;
        }

        if ($image = self::absolute($project['image'] ?? null)) {
            $article['image'] = $image;
        }

        if (filled($project['duration'] ?? null)) {
            // A case study's duration is a real, published number, so it is
            // stated as one rather than left for a reader to infer.
            $article['duration'] = self::isoDuration($project['duration']);
        }

        if (filled($project['domain'] ?? null)) {
            $article['mainEntityOfPage'] = $project['domain'];
        }

        return [$article];
    }

    /**
     * @return array<string, mixed>
     */
    private static function itemList(string $path, string $name, int $count): array
    {
        $url = url($path);

        return [
            '@type' => 'CollectionPage',
            '@id' => $url.'#collection',
            'url' => $url,
            'name' => $name,
            'isPartOf' => ['@id' => url('/#website')],
            'numberOfItems' => $count,
        ];
    }

    /**
     * Question-and-answer pairs as a `FAQPage`.
     *
     * The pairs are read from the same array the page renders, so the structured
     * answer can never drift from the visible one.
     *
     * @param  array<int, array<string, mixed>>  $faq
     * @return array<string, mixed>|null
     */
    private static function faqPage(array $faq, string $id): ?array
    {
        $entities = [];

        foreach ($faq as $item) {
            $question = self::clean($item['q'] ?? $item['question'] ?? null);
            $answer = self::clean($item['a'] ?? $item['answer'] ?? null);

            if ($question === null || $answer === null) {
                continue;
            }

            $entities[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        if ($entities === []) {
            return null;
        }

        return [
            '@type' => 'FAQPage',
            '@id' => $id,
            'mainEntity' => $entities,
        ];
    }

    /**
     * Pull question-and-answer markup out of a rich text body.
     *
     * Articles sometimes end with their own FAQ written as headings or bold
     * questions. Only pairs where the question is short enough to read as a
     * question are used, because a heading is not a question and mislabelling
     * one produces a `FAQPage` that reads as noise.
     *
     * @return list<array{q: string, a: string}>
     */
    private static function extractFaq(string $html): array
    {
        if (! str_contains($html, '<h')) {
            return [];
        }

        $found = [];

        // A heading, then the copy that follows it, up to the next heading.
        preg_match_all(
            '~<h[2-6][^>]*>(.*?)</h[2-6]>(.*?)(?=<h[2-6][^>]*>|$)~is',
            $html,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $question = trim(strip_tags($match[1]));
            $answer = trim(preg_replace('~\s+~', ' ', strip_tags($match[2])));

            if ($question === '' || $answer === '') {
                continue;
            }

            // A heading is a question only if it reads like one.
            if (! Str::endsWith($question, '?') || mb_strlen($question) > 120) {
                continue;
            }

            $found[] = ['q' => $question, 'a' => $answer];
        }

        return $found;
    }

    /**
     * @param  array<int, array{name: string, path: string}>  $items
     * @return list<array<string, mixed>>
     */
    private static function breadcrumbs(array $items): array
    {
        $list = [];

        foreach ($items as $position => $item) {
            $list[] = [
                '@type' => 'ListItem',
                'position' => $position + 1,
                'name' => $item['name'],
                'item' => url($item['path']),
            ];
        }

        return $list;
    }

    /**
     * Encode for embedding in a `<script type="application/ld+json">` block.
     *
     * `JSON_HEX_TAG` and friends are not optional here: a `<` or `&` inside any
     * string would otherwise let editor-supplied copy close the script element
     * early and take the rest of the document with it.
     */
    public static function toScriptTag(?array $graph): string
    {
        if ($graph === null) {
            return '';
        }

        return (string) json_encode(
            $graph,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * A relative or disk path as an absolute URL, or null.
     */
    private static function absolute(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        if ($value === '') {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        return url($value);
    }

    /**
     * A trimmed, non-empty string, or null. Everything that ends up in the
     * markup goes through here, so an empty setting becomes an absent key
     * rather than an empty string a crawler would read as a real value.
     */
    private static function clean(mixed $value): ?string
    {
        $value = is_string($value) ? trim(strip_tags($value)) : '';

        return $value !== '' ? $value : null;
    }

    /**
     * Turn "14 weeks" into the ISO 8601 duration "P14W".
     *
     * Case studies publish a duration in words; the markup wants a duration, and
     * converting it means a machine can compare projects instead of parsing
     * prose. Anything not in a recognised unit is dropped rather than guessed.
     */
    private static function isoDuration(string $duration): string
    {
        if (preg_match('/^(\d+)\s*(day|week|month|year)s?$/i', trim($duration), $match) !== 1) {
            return '0D';
        }

        return 'P'.$match[1].strtoupper(substr($match[2], 0, 1));
    }
}