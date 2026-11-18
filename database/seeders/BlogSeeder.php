<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $technology = Category::updateOrCreate(
            ['slug' => 'technology'],
            ['name' => 'Technology', 'description' => 'Web development, software engineering and digital product insights for Nigerian businesses.']
        );

        $design = Category::updateOrCreate(
            ['slug' => 'design'],
            ['name' => 'Design', 'description' => 'Branding, UI/UX and visual identity thinking built around the African market.']
        );

        Post::updateOrCreate(
            ['slug' => 'why-your-business-website-is-losing-customers'],
            [
                'category_id' => $technology->id,
                'title' => 'Why Your Business Website Is Losing Customers',
                'excerpt' => 'Most Nigerian businesses do not know their website is repelling customers until the money is already gone. Here is how to fix the leaks before your competitors do.',
                'body' => '<p>If your website takes more than three seconds to load, you are already losing visitors. In Nigeria, where a large share of your customers browse on prepaid mobile data and mid-range Android phones, a heavy, image-stuffed homepage is a silent sales killer. Your potential customer does not wait around, and they certainly do not come back to try again.</p><p>Beyond speed, trust is the real currency. When a visitor lands on a site with an outdated look, broken contact form or no clear way to reach you, they assume the worst: that your business is out of touch or, worse, not real. Every business you lose here is lost to a competitor who simply looked more professional.</p><p>At Webxpress, we help businesses diagnose these leaks with honest website audits and rebuilds that prioritise speed, clarity and conversion. If your enquiries have dried up while your ads still cost money, your website is likely the problem.</p>',
                'cover_image' => null,
                'published_at' => now()->subDays(6),
                'is_published' => true,
                'featured' => true,
                'seo_title' => 'Why Your Business Website Is Losing Customers',
                'seo_description' => 'Slow loading speeds, trust signals and conversion mistakes are costing Nigerian businesses customers every day. Learn how to fix your website.',
            ]
        );

        Post::updateOrCreate(
            ['slug' => '5-signs-you-need-a-custom-web-application'],
            [
                'category_id' => $technology->id,
                'title' => '5 Signs You Need a Custom Web Application',
                'excerpt' => 'Off-the-shelf tools stop scaling at some point. These are the five signals that your business has outgrown generic software.',
                'body' => '<p>You keep forcing your workflow into a tool that was never built for it. If you are juggling spreadsheets, copy-pasting customer data between platforms, or paying monthly for software that still requires manual fixes, your operations have outgrown off-the-shelf products. Custom web applications exist to remove exactly this friction.</p><p>Here are five signs you are ready: your staff manually reconciles data every week; your customers complain about turnaround time while your team is overworked; your reporting takes days to compile; you pay licensing fees that grow faster than the value you receive; and your competitors ship features you cannot match without owning your software.</p><p>A custom application is an investment in your operations, not just a development project. Whether it is a client portal, an internal dashboard or an order management system, the right build pays for itself by reclaiming hours and removing manual errors.</p>',
                'cover_image' => null,
                'published_at' => now()->subDays(13),
                'is_published' => true,
                'featured' => false,
                'seo_title' => '5 Signs You Need a Custom Web Application',
                'seo_description' => 'Spreadsheet juggling, duplicate data entry and slow reporting are symptoms of software that no longer fits. These five signs mean it is time to build custom.',
            ]
        );

        Post::updateOrCreate(
            ['slug' => 'the-roi-of-professional-branding-for-nigerian-startups'],
            [
                'category_id' => $design->id,
                'title' => 'The ROI of Professional Branding for Nigerian Startups',
                'excerpt' => 'Branding is not a logo you print on a mug. For Nigerian startups, it is the difference between being priced as a commodity and being valued as a product.',
                'body' => '<p>Investors and customers both judge faster than you think. A poorly designed brand tells a promising founder to present to stakeholders that this business is not ready for serious money. Professional branding signals discipline, attention to detail and a team that cares about the experience from first impression to final delivery.</p><p>For customers, branding does durable work that marketing spend cannot. When your identity is consistent across your website, packaging and social presence, you can command premium prices because you look like the premium option. In a Nigerian market crowded with look-alike competitors, clarity of brand is often the only differentiator a customer can articulate.</p><p>Professional branding is not vanity. It is the cheapest long-term marketing asset you will ever buy, because it makes every later pound you spend on ads more effective.</p>',
                'cover_image' => null,
                'published_at' => now()->subDays(23),
                'is_published' => true,
                'featured' => false,
                'seo_title' => 'The ROI of Professional Branding for Nigerian Startups',
                'seo_description' => 'Branding is the cheapest long-term marketing asset Nigerian startups can buy. See how professional identity builds trust, premium pricing and investor confidence.',
            ]
        );
    }
}
