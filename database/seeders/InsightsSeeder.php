<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Database\Seeder;

class InsightsSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect([
            ['slug' => 'technology', 'name' => 'Technology', 'description' => 'Web development, software engineering and digital product insights.'],
            ['slug' => 'design', 'name' => 'Design', 'description' => 'Brand, UX and content thinking for useful digital experiences.'],
            ['slug' => 'business-systems', 'name' => 'Business Systems', 'description' => 'Practical guidance for operations, automation and digital transformation.'],
            ['slug' => 'ai-and-search', 'name' => 'AI and Search', 'description' => 'Human-centred guidance for AI, search and modern content.'],
        ])->mapWithKeys(fn (array $category) => [
            $category['slug'] => Category::updateOrCreate(['slug' => $category['slug']], $category),
        ]);

        $posts = [
            [
                'slug' => 'your-website-is-not-a-billboard',
                'category' => 'design',
                'title' => 'Your Website Is Not a Billboard: The 15-Minute Homepage Audit',
                'excerpt' => 'A simple homepage audit for the moment a visitor arrives curious, busy and one tap away from leaving.',
                'body' => <<<'HTML'
<p>Your homepage has one job before it does anything clever: help a stranger understand what you do, who it is for and what to do next. Many business websites fail because they treat the first screen like a billboard. There is a logo, a slogan, three paragraphs and a beautiful photograph, but no clear answer to the visitor's actual question: “Can you help me?”</p>
<h2>The 15-minute test</h2>
<p>Open the page on your phone and give yourself 15 seconds. Write down the service you think the company sells, the type of customer it serves and the next action you would take. If you cannot answer all three, the page needs work. Ask someone outside the business to repeat the exercise. Their confusion is more useful than an internal team's familiarity.</p>
<ul><li>Replace vague headlines with a plain-language promise.</li><li>Put one primary action near the headline.</li><li>Show proof close to the decision: results, clients, reviews or a useful example.</li><li>Remove any sentence that only explains how proud the team is.</li></ul>
<p>Do not redesign everything first. Change the headline, first paragraph and primary button, then watch enquiries and calls for two weeks. Clarity is often the cheapest conversion improvement available.</p>
HTML,
                'days' => 1, 'featured' => true,
            ],
            [
                'slug' => 'the-real-reason-your-website-feels-slow',
                'category' => 'technology',
                'title' => 'The Real Reason Your Website Feels Slow (And What to Fix First)',
                'excerpt' => 'Speed problems are rarely solved by one magic plugin. Start with the heavy things your visitors actually have to download.',
                'body' => <<<'HTML'
<p>“The website is slow” is a useful complaint, but it is not yet a diagnosis. A page can feel slow because the hero image is enormous, because the browser is waiting for too many scripts, because the server is taking too long to respond, or because the page appears blank while everything loads below the fold.</p>
<h2>Fix the first useful moment</h2>
<p>Test the homepage on a mid-range phone and an ordinary mobile connection. Look at the first meaningful content, not just the score from a tool. Compress and resize images to the size they are displayed. Remove tracking scripts that no one reviews. Delay chat widgets and video players until a visitor needs them. Cache pages that do not change every minute.</p>
<p>For a growing product, measure three things on every release: server response time, the largest visible element and how quickly the page responds to a tap. Keep a small performance budget in the project brief. A beautiful page that makes customers wait is an expensive brochure.</p>
<ul><li>Beginner: optimise the largest images and remove unused plugins.</li><li>Growing team: inspect third-party scripts and caching.</li><li>Advanced team: set real-user monitoring and performance budgets in CI.</li></ul>
HTML,
                'days' => 2, 'featured' => false,
            ],
            [
                'slug' => 'ai-search-will-not-save-a-boring-website',
                'category' => 'ai-and-search',
                'title' => 'AI Search Won’t Save a Boring Website',
                'excerpt' => 'Search is becoming more conversational, but useful, specific and trustworthy content still wins the human decision.',
                'body' => <<<'HTML'
<p>AI answers have changed how people discover businesses, but they have not changed why people trust one. A generated summary may introduce your company. It cannot repair a vague offer, a missing case study or a contact form that never replies.</p>
<h2>Become the useful answer</h2>
<p>Write pages around real questions customers ask before they buy. Explain the difference between your services, show the process, name the constraints and include examples. Use headings that describe the answer rather than trying to repeat a keyword. Keep important information in text, connect related pages with sensible internal links and make sure the business details are consistent everywhere.</p>
<p>There is no shortcut file that makes weak content authoritative. The practical opportunity is to create material that a person would happily send to a colleague. A beginner needs definitions and next steps. An experienced buyer needs trade-offs, evidence and implementation detail. Your strongest content should help both without pretending every problem has the same solution.</p>
<ul><li>List ten questions your sales team answers repeatedly.</li><li>Publish the clearest answers first.</li><li>Add a named example or a useful checklist to each one.</li><li>Review old pages when your offer changes.</li></ul>
HTML,
                'days' => 3, 'featured' => true,
            ],
            [
                'slug' => 'stop-writing-for-robots',
                'category' => 'ai-and-search',
                'title' => 'Stop Writing for Robots: Make Content Useful to Humans First',
                'excerpt' => 'A practical way to turn polished-sounding copy into guidance people can understand, remember and act on.',
                'body' => <<<'HTML'
<p>Content becomes forgettable when it is assembled from safe phrases: leverage, unlock, seamless, innovative and end-to-end. Those words are not always wrong, but they rarely tell a reader what will change in their week.</p>
<h2>Write from the problem outward</h2>
<p>Start with a person and a moment. A finance manager is reconciling three spreadsheets at 7 p.m. A founder is deciding whether to hire a developer. A customer is trying to complete a form on a phone with poor network coverage. Describe the friction in plain language, then explain the decision that removes it.</p>
<p>Good content makes a promise, earns it with specifics and gives the reader a next move. Replace “we deliver scalable solutions” with the actual workflow you improve. Replace a long feature list with a short explanation of when each feature matters. Keep the tone warm, but let the details do the convincing.</p>
<ul><li>Cut the first paragraph in half.</li><li>Use examples from the customer's world.</li><li>Explain one trade-off instead of hiding every limitation.</li><li>End with a useful action, not a generic invitation.</li></ul>
HTML,
                'days' => 4, 'featured' => false,
            ],
            [
                'slug' => 'the-small-business-ai-starter-kit',
                'category' => 'business-systems',
                'title' => 'The Small Business AI Starter Kit: Five Automations Worth Building First',
                'excerpt' => 'You do not need an AI department to remove repetitive work. Start with the tasks that are frequent, boring and easy to check.',
                'body' => <<<'HTML'
<p>AI is most useful in a small business when it removes a queue, not when it becomes a new project to manage. The right first automation is predictable, happens often and has a human who can review the result.</p>
<h2>Five sensible starting points</h2>
<ul><li>Turn enquiry emails into structured leads for a human to qualify.</li><li>Summarise meeting notes into owners, dates and open questions.</li><li>Draft first replies from an approved knowledge base.</li><li>Classify support requests before they reach the right team.</li><li>Turn a finished project into a case study outline for editing.</li></ul>
<p>Keep private information out of tools that have not been approved. Give every workflow a clear failure path: if the confidence is low, send it to a person. Measure time saved and error rate, not the number of prompts written. Automation that creates a second checking job is not progress.</p>
<p>For a first experiment, choose one workflow, define the current steps, run it in parallel for two weeks and compare the results. Once it is reliable, connect it to your systems. The boring discipline is what makes the exciting technology useful.</p>
HTML,
                'days' => 5, 'featured' => false,
            ],
            [
                'slug' => 'your-chatbot-needs-an-exit',
                'category' => 'design',
                'title' => 'Your Chatbot Needs an Exit Door',
                'excerpt' => 'A helpful assistant knows when to stop guessing and hand a person the right context.',
                'body' => <<<'HTML'
<p>A chatbot is not helpful because it replies quickly. It is helpful when the customer reaches the right answer with less effort. The fastest way to lose trust is to make someone repeat a problem to a bot that cannot solve it, then repeat it again to a person.</p>
<h2>Design the handoff before the greeting</h2>
<p>Decide which questions the assistant can answer confidently and which require a human. Show the boundary. Add a visible route to contact a person, and pass the conversation, customer details and attempted steps along with the handoff. “Please explain your issue again” is a product failure disguised as customer service.</p>
<p>Use short answers, links to the full explanation and a confirmation after an action. Do not let the assistant invent policy, pricing or delivery promises. Keep a log of unanswered questions; that list is a better roadmap than a collection of clever demo conversations.</p>
<ul><li>Give the bot a narrow first job.</li><li>Test confused, angry and incomplete questions.</li><li>Make escalation easy on mobile.</li><li>Review transcripts for unsafe or misleading answers.</li></ul>
HTML,
                'days' => 6, 'featured' => false,
            ],
            [
                'slug' => 'before-you-build-a-client-portal',
                'category' => 'business-systems',
                'title' => 'Before You Build a Client Portal, Answer These Seven Questions',
                'excerpt' => 'A portal succeeds when it removes uncertainty for both sides. These questions expose the workflow before development starts.',
                'body' => <<<'HTML'
<p>Client portals are attractive because they promise one calm place for files, updates and requests. They become expensive when a team builds screens before deciding what the customer should actually be able to finish there.</p>
<h2>Ask the workflow questions</h2>
<ul><li>What do clients ask us for repeatedly?</li><li>Which information can they safely see?</li><li>What must they submit, and who approves it?</li><li>What happens when a request is incomplete?</li><li>Which status changes should trigger a notification?</li><li>What does a staff member do after every submission?</li><li>How will we know the portal is saving time?</li></ul>
<p>Map one complete journey from login to resolution. Include the awkward cases: forgotten passwords, duplicate uploads, rejected documents and a customer who starts on a phone and finishes on a laptop. Those details are the product.</p>
<p>Start with the smallest useful version. A focused portal that handles two high-volume tasks is better than a large dashboard that sends customers back to email. Build permissions and audit history into the foundation, because adding them after launch is painful and risky.</p>
HTML,
                'days' => 7, 'featured' => false,
            ],
            [
                'slug' => 'when-spreadsheets-become-a-business-risk',
                'category' => 'business-systems',
                'title' => 'When Spreadsheets Become a Business Risk',
                'excerpt' => 'Spreadsheets are excellent tools until the business starts depending on memory, duplicated files and one person who knows the formula.',
                'body' => <<<'HTML'
<p>There is nothing wrong with starting in a spreadsheet. The warning sign is not the file itself; it is the number of decisions that depend on a file no one can confidently explain. If two teams keep different versions, if a formula is copied by hand or if a report takes days to reconcile, the spreadsheet has become an operational risk.</p>
<h2>Find the moment to graduate</h2>
<p>Document the current process before buying software. Note who enters data, who checks it, where it goes next and what happens when something is missing. This often reveals that the real problem is an unclear rule, not a missing application.</p>
<p>Then choose the lightest improvement that removes the risk: a shared database, a form with validation, an approval queue or a small internal application. Preserve the familiar report where it helps, but give the underlying data one source of truth.</p>
<ul><li>Track duplicated entry and correction time.</li><li>Define owners for important data.</li><li>Keep a history of changes.</li><li>Give staff a recovery process when something goes wrong.</li></ul>
HTML,
                'days' => 8, 'featured' => false,
            ],
            [
                'slug' => 'the-2026-security-checklist',
                'category' => 'technology',
                'title' => 'The 2026 Web Security Checklist for a Growing Business',
                'excerpt' => 'Security is a set of habits, permissions and recovery plans. Use this checklist before a small issue becomes an expensive interruption.',
                'body' => <<<'HTML'
<p>Security advice can feel abstract until someone loses access to an account or a customer asks where their data went. A growing business does not need a giant security department to improve its position. It needs to close the obvious doors and know what to do when one is opened.</p>
<h2>Start with the basics that compound</h2>
<ul><li>Use multi-factor authentication for admin, email, hosting and payment accounts.</li><li>Remove access when people leave and review permissions quarterly.</li><li>Keep frameworks, packages and plugins patched.</li><li>Back up important data and test restoring it.</li><li>Separate development, staging and production credentials.</li><li>Log sensitive actions and decide who reviews unusual activity.</li></ul>
<p>Do not confuse a security product with a security process. A scanner may find a problem, but someone still has to prioritise it. Start with accounts that can change money, customer data or the public website. Write a one-page incident plan with contacts, containment steps and a customer communication owner.</p>
<p>For advanced teams, threat-model the most important workflows and add security checks to delivery. The goal is not to promise that nothing will ever happen. The goal is to make mistakes harder, detection faster and recovery less chaotic.</p>
HTML,
                'days' => 9, 'featured' => false,
            ],
            [
                'slug' => 'passwords-are-not-a-security-strategy',
                'category' => 'technology',
                'title' => 'Passwords Are Not a Security Strategy',
                'excerpt' => 'A practical guide to replacing shared passwords with stronger access habits your team can actually maintain.',
                'body' => <<<'HTML'
<p>Shared passwords feel convenient because they hide the question of who should have access. They also make every departure, lost laptop and suspicious login harder to investigate. A password copied into a group chat is not a permission system.</p>
<h2>Make access personal and boring</h2>
<p>Give each person their own account, use a password manager and turn on multi-factor authentication wherever the service supports it. Keep administrator access limited. A person who only needs to publish content should not also be able to export every customer record.</p>
<p>Create an offboarding checklist and test it. Remove access to email, hosting, repositories, analytics, payment tools and shared storage. Review who can reset passwords and who receives security alerts. The process should still work when the person responsible for it is on leave.</p>
<ul><li>Beginner: stop sharing admin passwords.</li><li>Growing team: use roles and an access register.</li><li>Advanced team: use central identity, single sign-on and automated provisioning.</li></ul>
<p>Good security feels uneventful. That is the point. The team gets its work done, customers are protected and no one is relying on one person's memory to keep the business safe.</p>
HTML,
                'days' => 10, 'featured' => false,
            ],
            [
                'slug' => 'accessibility-is-a-growth-feature',
                'category' => 'design',
                'title' => 'Accessibility Is a Growth Feature, Not a Compliance Chore',
                'excerpt' => 'Clear structure, keyboard support and readable interfaces help more people finish the task your product exists to support.',
                'body' => <<<'HTML'
<p>Accessibility is often discussed as a special layer added after the design is finished. In practice, many accessible decisions are simply good product decisions: clear labels, visible focus, readable contrast, captions, predictable navigation and forms that explain what went wrong.</p>
<h2>Test the task, not just the page</h2>
<p>Choose a real journey such as booking, applying, paying or requesting support. Try it with only a keyboard. Zoom the page. Use a screen reader for the key steps. Test on a small screen and with a slow connection. You will find problems that a visual review misses.</p>
<p>Do not rely on placeholder text as a label. Do not communicate errors with colour alone. Make buttons describe the action and keep the focus position sensible after a modal or validation error. These details reduce abandonment for everyone, including tired users and people working in bright sunlight.</p>
<ul><li>Use semantic headings and landmarks.</li><li>Give images meaningful alternative text.</li><li>Keep touch targets comfortable.</li><li>Include accessibility in acceptance criteria.</li></ul>
<p>Accessibility becomes affordable when it is part of the design system and definition of done. It becomes expensive when the business has already built a confusing workflow and asks someone to patch it at the end.</p>
HTML,
                'days' => 11, 'featured' => false,
            ],
            [
                'slug' => 'designing-for-nigerian-mobile-data',
                'category' => 'design',
                'title' => 'Designing for Nigerian Mobile Data: Build for the Phone People Actually Use',
                'excerpt' => "A fast, readable experience respects the visitor's data, battery and attention. That is good business, not a technical compromise.",
                'body' => <<<'HTML'
<p>Your customer may be using a mid-range Android phone, a browser with several tabs open and a mobile plan they are actively watching. Designing for that reality means the page must communicate before every image, animation and tracking script has finished loading.</p>
<h2>Make the important path light</h2>
<p>Put the offer, price range, location and next action in text. Compress images and use responsive sizes. Avoid autoplay video. Keep forms short and preserve entered data if the connection drops. Make tap targets comfortable and do not hide navigation behind an interaction that fails on an older browser.</p>
<p>Test with network throttling and a real phone, not only a fast office connection. Ask someone to complete the most valuable task while you watch where they hesitate. A lightweight page also helps search visibility, accessibility and conversion, so the benefit is larger than a faster score.</p>
<ul><li>Measure the first useful content.</li><li>Load below-the-fold media only when needed.</li><li>Use clear empty and offline states.</li><li>Keep the page usable when JavaScript is slow.</li></ul>
HTML,
                'days' => 12, 'featured' => false,
            ],
            [
                'slug' => 'website-web-app-or-mobile-app',
                'category' => 'technology',
                'title' => 'Website, Web App, or Mobile App? A Decision Guide for Real Businesses',
                'excerpt' => 'Choose the product shape by the job it must do, not by the technology that sounds most impressive.',
                'body' => <<<'HTML'
<p>The wrong first question is “Should we build an app?” The better question is “What should a customer or staff member be able to finish, and how often?” A public website explains and earns trust. A web app manages an authenticated workflow. A mobile app earns its place when people return frequently or need capabilities the browser cannot provide well.</p>
<h2>Use the smallest surface that works</h2>
<p>Choose a website when discovery, content and enquiries are the main jobs. Choose a web app when the workflow needs accounts, data, approvals or dashboards. Consider native mobile when offline use, device hardware, notifications or a high-frequency habit are central to the value.</p>
<p>Prototype the workflow before committing to three platforms. Count the steps, roles, integrations and support burden. A product that works beautifully on the web is better than an ambitious app that has no repeatable reason to be opened.</p>
<ul><li>Write the primary user journey in ten steps or fewer.</li><li>List the device capability that is genuinely required.</li><li>Estimate maintenance, not only launch cost.</li><li>Choose a success metric tied to the business outcome.</li></ul>
HTML,
                'days' => 13, 'featured' => false,
            ],
            [
                'slug' => 'the-hidden-cost-of-custom-software',
                'category' => 'technology',
                'title' => 'The Hidden Cost of Custom Software (And How to Avoid It)',
                'excerpt' => 'Custom software can be the right investment, but only when the business is ready to own the decisions around it.',
                'body' => <<<'HTML'
<p>Custom software is often priced as a build and forgotten as an operating system. The hidden costs arrive later: unclear ownership, manual data migration, staff training, support, security updates and the small changes that keep the tool aligned with the business.</p>
<h2>Budget for ownership</h2>
<p>Before development, name the person who owns the product outcome. Document the workflows, integrations and permissions that cannot break. Decide how incidents are handled and how changes are prioritised. Ask what happens if the original developer is unavailable.</p>
<p>Build in thin releases. Launch one complete workflow, learn from real users and keep the foundation maintainable. Avoid customising every exception on day one; a clear rule is often more valuable than a feature that preserves a broken process.</p>
<ul><li>Reserve budget for maintenance and security.</li><li>Keep documentation close to the code and the workflow.</li><li>Export your data in a usable format.</li><li>Measure time, errors and customer experience after launch.</li></ul>
<p>The best custom system is not the one with the most screens. It is the one that makes the right work easier without making the organisation dependent on mystery.</p>
HTML,
                'days' => 14, 'featured' => false,
            ],
            [
                'slug' => 'digital-transformation-without-the-big-bang',
                'category' => 'business-systems',
                'title' => 'Digital Transformation Without the Big-Bang Project',
                'excerpt' => 'Transformation becomes manageable when it starts with one painful workflow and proves value before expanding.',
                'body' => <<<'HTML'
<p>Digital transformation does not have to begin with a giant platform, a new department and a twelve-month promise. It can begin with one workflow everyone agrees is painful. The goal is to create a better way of working that people trust, then use the lesson to choose the next improvement.</p>
<h2>Make progress visible</h2>
<p>Pick a process with a clear cost: delayed approvals, repeated data entry, missed follow-ups or reports that arrive too late to guide decisions. Map it with the people who do the work. Remove unnecessary steps before automating the rest.</p>
<p>Define a baseline and a target. If the project cannot say what will become faster, clearer or safer, it is still a technology wish. Release a small version, train the team and gather feedback while the context is fresh. Celebrate adoption, not just deployment.</p>
<ul><li>Start with a workflow, not a vendor demo.</li><li>Give one person decision ownership.</li><li>Keep a manual fallback during rollout.</li><li>Review the result after 30 and 90 days.</li></ul>
HTML,
                'days' => 15, 'featured' => false,
            ],
            [
                'slug' => 'the-brief-that-saves-weeks',
                'category' => 'business-systems',
                'title' => 'The Brief That Saves Weeks: What to Give Your Web Team',
                'excerpt' => 'A good brief does not need technical language. It needs a shared definition of the problem, the people and the proof of success.',
                'body' => <<<'HTML'
<p>The fastest web projects are not the ones with the longest requirements documents. They are the ones where the team understands the decision behind each important requirement. A useful brief gives a developer and designer something better than a list of pages: it gives them a problem to solve.</p>
<h2>Include the things people usually leave out</h2>
<ul><li>Who is the primary user and what are they trying to do?</li><li>What is failing in the current experience?</li><li>What must be true at launch?</li><li>What can wait?</li><li>Who approves content and design?</li><li>What systems, data and permissions are involved?</li><li>How will success be measured?</li></ul>
<p>Share real examples of customer questions, current reports, brand references and awkward edge cases. Say what is out of scope. A clear constraint creates better decisions than an open-ended request to “make it modern.”</p>
<p>End with a small list of assumptions to validate. The brief is not a contract with the future; it is a tool for making uncertainty visible early, while changing direction is still affordable.</p>
HTML,
                'days' => 16, 'featured' => false,
            ],
            [
                'slug' => 'why-your-admin-panel-matters',
                'category' => 'business-systems',
                'title' => 'The Part of Your Product Customers Never See: Your Admin Panel',
                'excerpt' => 'A calm back office creates a better customer experience because staff can find, fix and explain what is happening.',
                'body' => <<<'HTML'
<p>Customers experience your admin panel indirectly. They feel it when a support agent finds an order quickly, when an approval does not disappear into a queue and when a team can correct a mistake without asking an engineer to run a script.</p>
<h2>Design for the people carrying the work</h2>
<p>Admin users need search, filters, clear statuses, useful history and safe actions. They need to know whether a change succeeded and what it changed. Destructive actions should be deliberate. Bulk actions should explain their scope. Empty states should tell the operator what to do next.</p>
<p>Interview the person who uses the panel under pressure. Watch how they reconstruct context from emails and spreadsheets. That is the gap the product should close. Do not copy the public design blindly; operational tools need density, speed and strong information hierarchy.</p>
<ul><li>Show the next action, not only the current status.</li><li>Keep an audit trail for sensitive changes.</li><li>Make common tasks keyboard-friendly.</li><li>Measure handling time and correction rate.</li></ul>
HTML,
                'days' => 17, 'featured' => false,
            ],
            [
                'slug' => 'analytics-that-answer-real-questions',
                'category' => 'technology',
                'title' => 'Analytics That Answer Questions, Not Just Count Visitors',
                'excerpt' => 'A smaller set of trusted events is more useful than a dashboard full of numbers no one knows how to act on.',
                'body' => <<<'HTML'
<p>Analytics becomes noise when every click is tracked but no one can say what decision the data supports. Start with the questions your team needs to answer: where do qualified enquiries come from, which step causes people to abandon, and how long does a request take to resolve?</p>
<h2>Build a measurement habit</h2>
<p>Define a small event vocabulary before installing more tools. Give events names that describe outcomes, not implementation details. Record useful context, but avoid collecting sensitive information you do not need. Make sure consent, retention and access are understood by the team.</p>
<p>Review the numbers with an owner and a decision. If the conversion rate falls, someone should know what will be investigated. If a page receives traffic but no action, ask whether the offer, audience or next step is wrong. Data does not replace judgement; it makes judgement less lonely.</p>
<ul><li>Choose one north-star outcome per journey.</li><li>Track the steps leading to it.</li><li>Annotate major releases and campaigns.</li><li>Delete metrics that never change a decision.</li></ul>
HTML,
                'days' => 18, 'featured' => false,
            ],
            [
                'slug' => 'a-brand-refresh-is-not-a-new-logo',
                'category' => 'design',
                'title' => 'A Brand Refresh Is Not a New Logo',
                'excerpt' => 'A stronger brand gives people a clearer expectation of the experience they are about to have.',
                'body' => <<<'HTML'
<p>A new logo can be part of a refresh, but it cannot carry the whole business. If the website still speaks differently from the sales deck, the service experience contradicts the promise and the team improvises every social post, the identity has not really changed.</p>
<h2>Refresh the whole expectation</h2>
<p>Start with the customers you want to keep and the position you want to own. Clarify the promise, personality and proof. Then translate that thinking into a visual system, writing principles, templates and service behaviours. A brand becomes believable through repetition.</p>
<p>Test the refresh in real materials before announcing it. Update the homepage, proposal, email signature and one customer touchpoint. Ask whether the new system is easier for the team to use and easier for customers to recognise. If it only looks good in a presentation, it is not finished.</p>
<ul><li>Define the words you want customers to use about you.</li><li>Show what the promise means in practice.</li><li>Give the team reusable components.</li><li>Measure recognition, trust and qualified demand.</li></ul>
HTML,
                'days' => 19, 'featured' => false,
            ],
            [
                'slug' => 'ready-to-launch-better-checklist',
                'category' => 'technology',
                'title' => 'Ready to Launch? A Better Checklist Than “It Looks Good”',
                'excerpt' => 'A launch is ready when the important journeys work for real people, on real devices, with a clear plan for what happens next.',
                'body' => <<<'HTML'
<p>“It looks good” is a design opinion, not a launch decision. A product is ready when a real person can complete the important task, the business can operate what happens afterward and the team knows how to notice when something breaks.</p>
<h2>Check the whole experience</h2>
<ul><li>Test the main journey on a phone, tablet and desktop.</li><li>Submit every important form and confirm the notification arrives.</li><li>Check empty, error, loading and offline states.</li><li>Verify permissions with both an ordinary user and an administrator.</li><li>Review headings, labels, contrast, keyboard focus and image alternatives.</li><li>Confirm analytics, backups, redirects and search metadata.</li><li>Write the first support and rollback steps.</li></ul>
<p>Invite someone unfamiliar with the project to complete the task without coaching. Watch where they pause. That pause is a launch note. After release, keep a short observation window with clear owners for bugs and customer questions. A launch is a beginning of learning, not the end of responsibility.</p>
<p>The most confident teams are not the ones that predict everything. They are the ones that have made the important unknowns visible and know how to respond.</p>
HTML,
                'days' => 20, 'featured' => false,
            ],
        ];

        // Covers are committed under public/images/insights rather than
        // hotlinked: a third-party image host is an availability dependency
        // the site does not control, and these are placeholder art anyway.
        $imagePaths = [
            'images/insights/1497366811353-6870744d04b2.jpg',
            'images/insights/1556761175-b413da4baf72.jpg',
            'images/insights/1553877522-43269d4ea984.jpg',
            'images/insights/1551434678-e076c223a692.jpg',
            'images/insights/1521737711867-e3b97375f902.jpg',
            'images/insights/1497366216548-37526070297c.jpg',
            'images/insights/1552664730-d307ca884978.jpg',
            'images/insights/1524758631624-e2822e304c36.jpg',
            'images/insights/1517245386807-bb43f82c33c4.jpg',
            'images/insights/1531482615713-2afd69097998.jpg',
            'images/insights/1542744173-8e7e53415bb0.jpg',
            'images/insights/1551836022-d5d88e9218df.jpg',
            'images/insights/1519389950473-47ba0277781c.jpg',
            'images/insights/1522071820081-009f0129c71c.jpg',
            'images/insights/1556761175-5973dc0f32e7.jpg',
            'images/insights/1523966211575-eb4a01e7dd51.jpg',
            'images/insights/1550751827-4bd374c3f58b.jpg',
            'images/insights/1563013544-824ae1b704d3.jpg',
            'images/insights/1454165804606-c3d57bc86b40.jpg',
            'images/insights/1516321318423-f06f85e504b3.jpg',
            'images/insights/1556761175-4b46a572b786.jpg',
            'images/insights/1516321497487-e288fb19713f.jpg',
            'images/insights/1524178232363-1fb2b075b655.jpg',
        ];
        $imageIndex = 0;

        foreach ($posts as $post) {
            $record = Post::withTrashed()->firstOrNew(['slug' => $post['slug']]);
            $isNew = ! $record->exists;

            if ($record->trashed()) {
                $record->restore();
            }

            if ($isNew || blank($record->body)) {
                $record->fill([
                    'title' => $post['title'],
                    'excerpt' => $post['excerpt'],
                    'body' => $post['body'],
                    'seo_title' => $post['title'],
                    'seo_description' => $post['excerpt'],
                    'published_at' => now()->subDays($post['days']),
                    'is_published' => true,
                    'featured' => $post['featured'],
                ]);
            }

            $record->category_id = $categories[$post['category']]->id;

            // Only supply a cover when the post has none, or when it still
            // points at the old hotlinked placeholder host, so re-seeding
            // migrates an existing install onto the local copies. A cover an
            // editor uploaded through the admin is left alone.
            $cover = $record->cover_image;
            if (blank($cover) || str_starts_with($cover, 'https://images.unsplash.com/')) {
                $record->cover_image = $imagePaths[$imageIndex % count($imagePaths)];
            }

            $imageIndex++;
            $record->save();
        }

        // Catch any other published post that still has no cover, and any
        // that is still hotlinked, without touching local or uploaded images.
        Post::query()
            ->published()
            ->where(fn ($query) => $query
                ->whereNull('cover_image')
                ->orWhere('cover_image', '')
                ->orWhere('cover_image', 'like', 'https://images.unsplash.com/%'))
            ->get()
            ->each(function (Post $record) use (&$imageIndex, $imagePaths): void {
                $record->cover_image = $imagePaths[$imageIndex % count($imagePaths)];
                $imageIndex++;
                $record->save();
            });
    }
}
