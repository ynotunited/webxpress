<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $about = [
            'principles' => [
                ['title' => 'Clarity before code', 'body' => 'We shape the business problem first, then choose the simplest technical path that can scale.'],
                ['title' => 'Systems over pages', 'body' => 'A website, CRM, payment flow, and admin panel should feel like one connected machine.'],
                ['title' => 'Design with restraint', 'body' => 'Interfaces should guide decisions, reduce friction, and make important actions obvious.'],
                ['title' => 'Launch is a checkpoint', 'body' => 'We build with maintenance, iteration, SEO, analytics, and future product moves in mind.'],
            ],
            'process' => [
                ['title' => 'Discover', 'body' => 'We map the business goal, users, risks, integrations, and launch constraints.'],
                ['title' => 'Shape', 'body' => 'We turn the brief into content structure, UX flows, technical scope, and delivery milestones.'],
                ['title' => 'Build', 'body' => 'Design and engineering move together so the interface and system logic stay aligned.'],
                ['title' => 'Launch', 'body' => 'We test, deploy, hand over, and help the team understand how to run the system.'],
            ],
        ];

        $services = ['faq' => [
            ['question' => 'Can we hire you for one service only?', 'answer' => 'Yes. You can bring us in for a focused website build, SEO sprint, automation workflow, AI feature, brand refresh, or strategy engagement.'],
            ['question' => 'Do you handle both design and development?', 'answer' => 'Yes. We connect product thinking, interface design, Laravel engineering, content structure, integrations, testing, and launch support.'],
            ['question' => 'How do you price service projects?', 'answer' => 'Pricing depends on scope, complexity, integrations, timeline, content readiness, and support needs. We confirm this after discovery instead of guessing.'],
            ['question' => 'Can you improve an existing website or app?', 'answer' => 'Yes. We can audit what exists, identify the weak points, and run a focused improvement sprint without rebuilding everything.'],
            ['question' => 'What happens after launch?', 'answer' => 'We can hand over cleanly, provide support, continue with improvements, or help your internal team maintain the system with confidence.'],
        ]];

        DB::table('pages')->where('slug', 'about')->whereNull('structured_content')->update(['structured_content' => json_encode($about)]);
        DB::table('pages')->where('slug', 'services')->whereNull('structured_content')->update(['structured_content' => json_encode($services)]);
    }

    public function down(): void
    {
        DB::table('pages')->whereIn('slug', ['about', 'services'])->update(['structured_content' => null]);
    }
};
