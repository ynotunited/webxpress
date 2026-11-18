<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // No test user here. A known-password account has no place on a live
        // box, and the panel is provisioned from ADMIN_EMAIL by AdminUserSeeder.
        //
        // ClientReviewSeeder is deliberately not called. Its quotes are layout
        // placeholders, not approved client feedback. Real reviews are added
        // in Admin -> Site -> Client Reviews.
        //
        // Order matters: PageSectionSeeder promotes blocks onto the "home"
        // page record that SitePageSeeder creates.
        $this->call([
            AdminUserSeeder::class,
            SiteSettingsSeeder::class,
            SitePageSeeder::class,
            PageSectionSeeder::class,
            InsightsSeeder::class,
        ]);
    }
}
