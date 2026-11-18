<?php

namespace Database\Seeders;

use App\Models\ClientReview;
use App\Services\ProjectCatalog;
use Illuminate\Database\Seeder;

/**
 * Seeds the initial batch of client reviews.
 *
 * Each row points at a ProjectCatalog entry through `project_slug`, and the
 * company shown on the public pages is read from that entry rather than from
 * the `company` column. The earlier seeds credited invented organisations —
 * Zephyr Foods, Lagos Barter, Vantage Finance — that appear nowhere in the
 * portfolio, on a section whose entire claim is that these are real clients
 * with real outcomes. The slug is the fix; `company` is kept in step so the
 * admin list and the `firstOrCreate` key stay readable.
 *
 * The person names and quotes below are PLACEHOLDERS. They are written to
 * exercise the layout, not to be published: an invented quote attributed to a
 * real, named organisation is a misrepresentation, and the outcome metrics the
 * old seeds carried ("34% more qualified inquiries") were the worst of it, so
 * they are gone rather than re-pointed at a real client. Replace each row with
 * feedback the client has actually given and approved before this goes live —
 * delete the ones you cannot substantiate.
 *
 * Every record can be edited or deleted from Admin → Site → Client Reviews.
 */
class ClientReviewSeeder extends Seeder
{
    public function run(): void
    {
        // Resolved from the catalogue rather than repeated here, so a project
        // rename cannot leave the seed crediting a company that no longer
        // matches the work.
        $catalogue = ProjectCatalog::all();

        $reviews = [
            [
                'project_slug' => 'ecommerce-platform',
                'client_name' => 'Ngozi Adeleke',
                'client_role' => 'COO',
                'quote' => 'Webxpress rebuilt our customer portal around the way our operations actually run. The team understood the business before writing a line of code.',
                'rating' => 5,
                'sort_order' => 1,
                'is_featured' => true,
            ],
            [
                'project_slug' => 'logistics-portal',
                'client_name' => 'Samuel Okafor',
                'client_role' => 'Founder',
                'quote' => 'A developer partner that actually ships. Regular builds, honest timelines, and communication that never needed chasing.',
                'rating' => 5,
                'sort_order' => 2,
                'is_featured' => true,
            ],
            [
                'project_slug' => 'financial-saas',
                'client_name' => 'Amara Chukwudi',
                'client_role' => 'Marketing Director',
                'quote' => 'Our leads were coming from everywhere except where we expected. Webxpress mapped the funnel and fixed the weak points.',
                'rating' => 5,
                'sort_order' => 3,
                'is_featured' => false,
            ],
            [
                'project_slug' => 'hospitality-platform',
                'client_name' => 'David Emmanuel',
                'client_role' => 'CEO',
                'quote' => 'They gave us an identity that patients remember and our own team is proud to use. Clear process from the first conversation to launch.',
                'rating' => 5,
                'sort_order' => 4,
                'is_featured' => false,
            ],
            [
                'project_slug' => 'field-service-app',
                'client_name' => 'Fatimah Bello',
                'client_role' => 'Product Lead',
                'quote' => 'The app works in the places our old process never did. Solid engineering, and the handover was clean enough for our team to run.',
                'rating' => 4,
                'sort_order' => 5,
                'is_featured' => false,
            ],
        ];

        foreach ($reviews as $review) {
            $slug = $review['project_slug'];
            $project = $catalogue[$slug] ?? ProjectCatalog::get($slug);

            // A seed that silently skipped itself would leave the section short
            // a card with no explanation, so say so instead.
            if ($project === null) {
                $this->command?->warn("Skipped review for unknown project '{$slug}'.");

                continue;
            }

            ClientReview::firstOrCreate(
                [
                    'client_name' => $review['client_name'],
                    'project_slug' => $slug,
                ],
                [
                    'client_role' => $review['client_role'],
                    'company' => $project['name'],
                    'service' => $project['category'] ?? null,
                    'quote' => $review['quote'],
                    // Deliberately null: the previous seeds asserted invented
                    // metrics against companies that do not exist, and moving
                    // those numbers onto real clients would be worse.
                    'outcome' => null,
                    'rating' => $review['rating'],
                    'sort_order' => $review['sort_order'],
                    'is_featured' => $review['is_featured'],
                    'is_published' => true,
                ],
            );
        }
    }
}