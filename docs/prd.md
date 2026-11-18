PRD: Webxpress Technologies Corporate Website & CMS

Product: Webxpress Technologies Corporate Website
Domain: webxpresstech.com.ng
Product Type: Corporate Website + Content Management System
Status: Production Specification
Primary Goal: Build a premium, cinematic corporate website for Webxpress Technologies with a structured CMS that allows non-developers to manage website content, portfolio, blog, navigation, SEO, media and homepage sections from an admin panel.

1. Product Overview

Webxpress Technologies is an established Nigerian technology company operating since 2010.

The new website should position Webxpress as a mature technology company, digital product studio and creative technology partner capable of delivering:

Web Development
Mobile Development
Branding
Digital Strategy
Visuals
Motion Design
SEO
AI Solutions

The website must feel like a serious technology company with years of execution behind it, not a generic agency template or AI-generated landing page.

The public website should combine:

Strong editorial design
Cinematic project presentation
High-quality portfolio storytelling
Clear business positioning
Conversion-focused calls to action
A CMS-driven content architecture
Excellent performance
Strong SEO
Mobile-first responsiveness
Easy administration on shared hosting
2. Product Vision

Build a digital home for Webxpress that demonstrates what the company can build before the visitor even contacts the company.

The website itself must act as a portfolio piece.

A visitor should immediately understand:

Who Webxpress is
What Webxpress builds
How long the company has been operating
The quality of its work
The industries it serves
The services it provides
Why a business should trust Webxpress
How to start a project
3. Core Design Direction
3.1 Design Personality

The visual identity should communicate:

Established
Intelligent
Premium
Confident
Modern
Technical
Creative
Human
Strategic
Reliable
3.2 Design References

The quality bar should be comparable to the sophistication of modern companies such as:

Stripe
Linear
Figma
Framer
Notion
Pentagram
Instrument
Locomotive
Apple

These are references for quality and execution, not designs to copy.

4. Explicit Design Restrictions

The website must NOT look like generic AI-generated web design.

Avoid:

Purple AI gradients
Neon gradients
Random blue/purple gradient backgrounds
AI sparkles
Floating 3D blobs
Excessive glassmorphism
Excessive rounded cards
Glowing buttons
Excessive shadows
Generic SaaS dashboard visuals
Emoji as decorative elements
Huge decorative icons everywhere
Fake statistics
Stock-photo-heavy layouts
Template-like three-card sections everywhere
Excessive pill-shaped UI
Random abstract shapes
Over-animation
Scroll hijacking
"AI" visual clichés
Excessive social media icons
One-page-only architecture

The site should feel designed by an experienced digital product/design team.

5. Technical Architecture
5.1 Required Stack
Backend
Laravel 12
PHP 8.2+
MySQL
Eloquent ORM
Laravel Authentication
Laravel Validation
Laravel Filesystem
Laravel Scheduler where required
Laravel Cache
Frontend
Blade
Livewire
Alpine.js
Tailwind CSS
Vanilla JavaScript where appropriate
CMS / Admin
Filament 5
Filament Forms
Filament Tables
Filament Actions
Filament Rich Editor
Filament File Uploads
Filament Notifications
Filament Authorization
Infrastructure
Apache
MySQL
SSL
Standard PHP shared hosting
Composer
Optional SSH
Cron where available
6. Hosting Requirements

The application must be deployable to standard shared hosting environments such as Hostinger.

Production must NOT require:

Node.js runtime
Redis
Elasticsearch
Kubernetes
Docker
WebSockets
Persistent workers
PostgreSQL
Separate frontend hosting
Separate headless CMS
Dedicated server

Node.js may be used during development to compile frontend assets if required.

Compiled assets must be deployable to production as static files.

7. Architecture Principle

The project should use:

Custom-designed frontend + structured Laravel CMS + Filament administration

Do NOT build a generic drag-and-drop website builder.

The CMS controls content, not the visual integrity of the website.

Administrators should be able to modify:

Text
Images
Projects
Services
Blog posts
Testimonials
Logos
Navigation
SEO
Homepage content
Contact information
CTA content

Administrators should NOT be able to arbitrarily destroy the frontend layout.

8. User Types
8.1 Website Visitor

Can:

Browse website
View services
View portfolio
Read blog posts
Search/read insights
Submit contact form
Navigate to external links
Visit MadeIT Codes
8.2 Content Manager

Can:

Create blog posts
Edit blog posts
Publish/unpublish posts
Manage portfolio
Manage services
Manage testimonials
Manage client logos
Update homepage content
Manage media
Update SEO fields

Cannot:

Manage users
Modify system configuration
Delete critical system data
8.3 Super Administrator

Can:

Manage all content
Manage users
Manage navigation
Manage site settings
Manage SEO
Manage media
Manage contact submissions
Publish/unpublish content
Configure homepage sections
9. Website Information Architecture

The public website must contain:

/
├── /work
├── /work/{slug}
├── /services
├── /services/{slug}
├── /about
├── /industries
├── /insights
├── /insights/{slug}
├── /contact
├── /privacy
└── /terms

Admin:

The base path is not `/admin` fixed — it comes from `ADMIN_PATH` in `.env` and is
read by `admin_path()`. Set it to something a scanner cannot guess. Every URL below
is `admin_path()` + the suffix shown, and the admin-facing emails build their links
from `admin_url()` rather than hardcoding the base.

Planned (the live slugs in `routes/web.php` and the Filament resources differ for
several of these — `route:list` is authoritative):

/admin
/admin/posts
/admin/categories
/admin/projects
/admin/services
/admin/clients
/admin/testimonials
/admin/homepage
/admin/navigation
/admin/media
/admin/seo
/admin/settings
/admin/contact-messages
/admin/users
10. Global Header
Objective

Create a clean navigation system that remains useful throughout the website.

Desktop Navigation

Logo:

Webxpress Technologies

Navigation:

Work
Services
About
Insights
Contact
MadeIT Codes

Primary CTA:

Start a Project

MadeIT Codes

The header must contain a clearly identifiable link to MadeIT Codes.

It should be visually distinct but not overpower the primary navigation.

External destination:

https://madeitcodes.online

11. Header Behaviour
Desktop
Transparent or visually integrated header over hero where appropriate
Transitions to solid/light background after scrolling
Sticky positioning
Smooth state transition
No excessive blur effects
Mobile

Use:

Logo
Menu button
Full-screen or large mobile navigation
Clear CTA
MadeIT Codes link

Mobile menu must be keyboard accessible.

12. Homepage

The homepage is the primary conversion and brand experience.

Required order:

HEADER
↓
HERO / INTERACTIVE PROJECT SHOWCASE
↓
TRUSTED BY
↓
WHO WE ARE
↓
SERVICES
↓
SELECTED WORK
↓
HOW WE WORK
↓
INDUSTRIES
↓
TESTIMONIALS / PROOF
↓
MAJOR CTA
↓
INSIGHTS
↓
FOOTER
13. Hero Section
Objective

Immediately demonstrate the quality of Webxpress work.

The hero must be visually driven rather than relying only on marketing copy.

13.1 Hero Structure

Desktop layout:

------------------------------------------------
HEADER
------------------------------------------------

                  HERO

  Large Featured Project
  ----------------------
  |                    |
  |                    |
  |   PROJECT IMAGE    |
  |                    |
  |                    |
  ----------------------

  Project information        Project
                              Selector
                              ┌──────┐
                              │ 01   │
                              └──────┘
                              ┌──────┐
                              │ 02   │
                              └──────┘
                              ┌──────┐
                              │ 03   │
                              └──────┘
                              ┌──────┐
                              │ 04   │
                              └──────┘
14. Hero Project Selector

The right-side project selectors are CMS-driven.

Each selector must contain:

Project thumbnail
Project number
Project name
Optional category
Active state

Example:

01
Corporate Website

02
E-commerce Platform

03
Financial Platform

04
Hospitality Platform
15. Hero Interaction

The hero must support:

Automatic Rotation

Projects automatically transition every configurable number of seconds.

Default:

5 seconds

Admin can change this.

Manual Selection

Clicking a project selector immediately changes:

Main image
Project name
Category
Year
Description
CTA
Optional background treatment
Transition

Use smooth transitions:

Image crossfade
Slight image movement
Text fade/slide
Selector active-state transition

No excessive animation.

16. Hero CMS Fields
Hero Headline
Hero Supporting Text
Primary CTA Label
Primary CTA URL
Secondary CTA Label
Secondary CTA URL
Auto Rotation Enabled
Rotation Interval
Featured Projects
Project Order

Hero project data should reference portfolio records rather than duplicate project data.

17. Hero Content Example

Headline:

We build digital experiences that move businesses forward.

Supporting copy:

Webxpress Technologies designs and builds websites, digital products and business systems that help organizations operate, communicate and grow.

CTA:

Start a Project

Secondary CTA:

View Our Work

18. Trusted By Section
Objective

Immediately establish credibility.

Display logos of selected clients/organizations.

The section should remain visually restrained.

Possible presentation:

TRUSTED BY

[LOGO] [LOGO] [LOGO] [LOGO] [LOGO]

Avoid an aggressive infinite marquee unless it genuinely improves the design.

19. Client Logo CMS

Each client logo contains:

Name
Logo
Website URL
Display Order
Active

Admin can:

Add logo
Replace logo
Reorder logo
Disable logo
Delete logo
20. Who We Are
Objective

Explain Webxpress beyond services.

This section should feel editorial rather than corporate-template-like.

Content
Large statement
Company story
Supporting copy
Years of experience
Projects delivered
Optional industries served
Supporting visual

Example positioning:

Technology should solve real business problems, not simply look impressive.

Supporting content should explain the company's experience since 2010.

21. About Page

Route:

/about

Content:

Introduction

Who Webxpress is.

Story

Company history and evolution.

Experience

Operating since 2010.

Philosophy

How Webxpress approaches technology and business.

Capabilities
Product Development
Web Development
Mobile
Branding
Digital Strategy
AI Solutions
CTA

Start a project.

22. About CMS

Fields:

Page Title
Intro Heading
Intro Content
Story Heading
Story Content
Experience Heading
Experience Content
Primary Image
Secondary Image
Stats
CTA Heading
CTA Description
SEO
23. Services Section

The homepage should present the eight primary services:

Web Development
Mobile Development
Branding
Digital Strategy
Visuals
Motion Design
SEO
AI Solutions

The design should NOT simply show eight identical icon cards.

Use a more editorial layout.

Possible interaction:

01 Web Development
   description
   →
   
02 Mobile Development
   description
   →
   
03 Branding
   description
   →

Hovering or selecting a service can reveal a visual.

24. Services Page

Route:

/services

Should include:

Introduction
Service list
Detailed descriptions
Relevant work
Industries served
CTA
25. Individual Service Pages

Route:

/services/{slug}

Each service page should contain:

Hero
↓
Service Overview
↓
Capabilities
↓
What We Deliver
↓
Selected Work
↓
Process
↓
FAQ
↓
CTA
26. Service CMS

Fields:

Name
Slug
Short Description
Long Description
Hero Image
Supporting Images
Capabilities
Deliverables
Process
FAQ
Featured Projects
Display Order
Active
SEO Title
SEO Description
OG Image
27. Selected Work
Objective

Portfolio must be one of the strongest parts of the website.

The site should demonstrate actual execution.

Avoid generic mockups wherever real project screenshots/assets are available.

28. Portfolio Page

Route:

/work

Content:

Featured projects
Filter by industry
Filter by service/category
Year
Project grid
Project detail pages
29. Portfolio Grid

Use an asymmetric editorial layout.

Example:

┌───────────────────────────────┐
│                               │
│       LARGE PROJECT           │
│                               │
└───────────────────────────────┘

┌───────────────┐ ┌───────────────┐
│               │ │               │
│    PROJECT    │ │    PROJECT    │
│               │ │               │
└───────────────┘ └───────────────┘

Not every project should look identical.

30. Portfolio Detail Page

Route:

/work/{slug}

Content:

Hero
Project name
Client
Industry
Year
Category
Hero image
Challenge

What problem existed?

Approach

How Webxpress approached it.

Solution

What was built?

Results

Business outcomes where real data exists.

Gallery

Multiple project images.

Technology

Optional technology list.

Related Work

Other relevant projects.

CTA

Start a project.

31. Portfolio CMS

Each project must support:

Project Name
Slug
Client
Industry
Category
Year
Short Description
Challenge
Approach
Solution
Results
Hero Image
Gallery
Project URL
Technology
Featured
Hero Featured
Display Order
Status
Published At
SEO Title
SEO Description
OG Image
32. Project Status

Supported states:

Draft
Published
Archived

Only published projects appear publicly.

33. How We Work

The website should communicate a simple professional process.

Recommended process:

01 Discover
02 Define
03 Build
04 Launch & Grow

Each stage includes:

Number
Title
Description
Optional image

The section should visually communicate progression without becoming a generic timeline.

34. Process CMS

Fields:

Step Number
Title
Description
Image
Display Order
Active
35. Industries

Present the business environments Webxpress understands.

Recommended:

Corporate & Enterprise
Healthcare
Finance
Hospitality
Retail & E-commerce
Education
Professional Services
Startups

The section should focus on business problems and outcomes, not simply industry labels.

36. Industry CMS

Fields:

Name
Slug
Description
Business Problems
Image
Related Services
Related Projects
Display Order
Active
SEO
37. Testimonials / Proof

The website should include genuine client testimonials where available.

Each testimonial:

Client Name
Company
Role
Testimonial
Photo
Company Logo
Featured
Display Order
Active

Do not generate fake testimonials.

If testimonials are unavailable during launch, the section should be hidden rather than populated with fabricated content.

38. Major CTA

This should be one of the strongest sections on the site.

Example:

Have a business problem worth solving?

Supporting copy:

Let's turn it into a digital solution built to work.

CTA:

Start a Project

Secondary:

Talk to Webxpress

The section should visually break the page without relying on flashy gradients.

39. Contact Page

Route:

/contact

Content:

Contact introduction
Email
Phone
Location
Project form
Social links
Business information
40. Contact Form

Fields:

Name
Email
Phone
Company
Service
Budget
Message

Optional:

Preferred Contact Method
Project Timeline
Website URL
41. Budget Options

Recommended configurable options:

Under ₦500k
₦500k - ₦1m
₦1m - ₦3m
₦3m - ₦5m
₦5m+
Not Sure Yet

These must be editable from the CMS if required.

42. Contact Submission Workflow
Visitor submits form
        ↓
Validate fields
        ↓
Rate-limit request
        ↓
Store submission
        ↓
Status = New
        ↓
Admin receives notification
        ↓
Admin reviews submission
        ↓
Status updated

Statuses:

New
Contacted
In Progress
Converted
Closed
43. Contact Messages Admin

Admin should be able to:

View messages
Search messages
Filter by status
Filter by date
View complete submission
Change status
Add internal notes
Delete/archive submissions
44. Insights / Blog

Route:

/insights

Individual post:

/insights/{slug}

The blog should feel like an editorial publication, not a generic WordPress blog.

45. Blog Homepage

Layout:

FEATURED ARTICLE

        ↓

Latest Insights

[Article] [Article] [Article]

        ↓

Categories

Use large typography and high-quality featured imagery.

46. Blog CMS

Administrators must be able to:

Create posts
Edit posts
Save drafts
Publish posts
Unpublish posts
Archive posts
Delete posts
Schedule publication where supported
Upload featured images
Add categories
Add tags
Set author
Configure SEO
Configure social preview
47. Blog Fields
Title
Slug
Excerpt
Featured Image
Content
Category
Tags
Author
Published At
Reading Time
Status
SEO Title
SEO Description
Canonical URL
OG Image
48. Blog Editor

The rich text editor should support:

Headings
Paragraphs
Bold
Italic
Links
Ordered lists
Unordered lists
Blockquotes
Images
Image captions
Tables
Code blocks where necessary

Ordinary content managers should not need to write raw HTML.

49. Blog SEO

Each article should automatically generate:

<title>
<meta name="description">
<link rel="canonical">
Open Graph metadata
Twitter/X metadata
Article schema
Breadcrumb schema
50. Blog Categories

Admin can create:

Category Name
Slug
Description
SEO Title
SEO Description

Examples:

Technology
Business
Design
Digital Strategy
AI
Web Development
Company News
51. Media Management

All uploaded media must be managed through Laravel's filesystem.

Supported:

JPG
JPEG
PNG
WebP
SVG where appropriate

The CMS should validate:

MIME type
File size
Image dimensions where appropriate
52. Image Optimisation

Images should be optimized before or during upload where practical.

Frontend:

Responsive image sizes
Lazy loading
Width/height attributes
Modern image formats
Proper alt text

Do not load huge original images when a smaller variant is sufficient.

53. Media Metadata

Each image should support:

File
Alt Text
Caption
Title
Description

Alt text should be mandatory for important content images.

54. Navigation CMS

Admin should be able to manage navigation items.

Fields:

Label
URL
Type
Display Order
Active
Open in New Tab

Types:

Internal
External

The MadeIT Codes link must remain available as an external navigation option.

55. Footer

Footer should contain:

Company

Short description of Webxpress.

Navigation
Work
Services
About
Insights
Contact
Company
Privacy
Terms
MadeIT Codes

Clear link to MadeIT Codes.

Contact
Email
Phone
Location
Social

Only relevant social channels.

Copyright

Dynamic current year.

56. Site Settings CMS

Global settings:

Company Name
Company Description
Logo
Footer Logo
Favicon
Email
Phone
Address
Google Maps URL
Facebook
Instagram
LinkedIn
X
YouTube
WhatsApp
Default SEO Title
Default SEO Description
Default OG Image
57. SEO Management

Every major page must support:

SEO Title
SEO Description
Canonical URL
OG Title
OG Description
OG Image
Robots Setting
58. Global SEO

Implement:

XML sitemap
robots.txt
Canonical URLs
Open Graph
Twitter/X cards
Organization schema
Website schema
Article schema
Breadcrumb schema
Service schema where appropriate
59. Structured Data

The system should generate JSON-LD dynamically.

At minimum:

Organization
WebSite
BreadcrumbList
Article

Relevant service/project pages should receive appropriate structured data.

60. Homepage CMS Architecture

The homepage should consist of structured sections.

Recommended model:

HomepageSection

Fields:

Section Type
Section Name
Display Order
Enabled
Configuration/Data

However, avoid putting every piece of data into one unstructured JSON field.

Where content represents a reusable entity, use proper relational models.

For example:

Homepage
    ↓
Featured Projects → Projects
Featured Services → Services
Client Logos → Clients
Testimonials → Testimonials
Insights → Posts
61. Homepage Admin

The admin should provide a dedicated homepage management area.

Example:

Homepage
 ├── Hero
 ├── Client Logos
 ├── About
 ├── Services
 ├── Selected Work
 ├── Process
 ├── Industries
 ├── Testimonials
 ├── CTA
 └── Insights

Each section can be:

Edited
Reordered where appropriate
Enabled/disabled
Previewed
62. Content Preview

Administrators should be able to preview content before publishing.

For example:

Draft
   ↓
Preview
   ↓
Publish

Where possible, preview should use the actual frontend layout.

63. Admin Dashboard

The Filament dashboard should provide useful operational information.

Example:

CONTACT MESSAGES
12 New

BLOG POSTS
8 Published
3 Draft

PROJECTS
24 Published

SERVICES
8 Active

RECENT CONTACTS
----------------
John Doe
ABC Ltd
Web Development
2 hours ago

Avoid unnecessary decorative dashboard widgets.

64. Admin Permissions
Super Admin
Resource	Create	Read	Update	Delete	Publish
Posts	✓	✓	✓	✓	✓
Categories	✓	✓	✓	✓	✓
Projects	✓	✓	✓	✓	✓
Services	✓	✓	✓	✓	✓
Clients	✓	✓	✓	✓	✓
Testimonials	✓	✓	✓	✓	✓
Homepage	✓	✓	✓	✓	✓
Navigation	✓	✓	✓	✓	✓
Settings	✓	✓	✓	✓	✓
Users	✓	✓	✓	✓	N/A
Messages	N/A	✓	✓	✓	N/A
Content Manager

Can manage content resources but cannot:

Manage users
Modify system configuration
Modify security settings
65. Database Architecture

Recommended core tables:

users

posts
categories
post_category
tags
post_tag

projects
project_images
project_technologies
project_industries

services
service_faqs
service_capabilities

clients

testimonials

industries
industry_service
industry_project

process_steps

homepage_sections
homepage_settings

navigation_items

media

contact_messages

site_settings

seo_metadata
66. Posts Table

Recommended fields:

id
author_id
category_id
title
slug
excerpt
content
featured_image
status
published_at
reading_time
seo_title
seo_description
canonical_url
og_image
created_at
updated_at

Indexes:

slug
status
published_at
category_id
author_id
67. Projects Table

Recommended fields:

id
name
slug
client_id
industry_id
category
year
short_description
challenge
approach
solution
results
hero_image
project_url
featured
hero_featured
display_order
status
published_at
seo_title
seo_description
og_image
created_at
updated_at
68. Services Table
id
name
slug
short_description
description
hero_image
display_order
active
seo_title
seo_description
og_image
created_at
updated_at
69. Contact Messages Table
id
name
email
phone
company
service
budget
message
status
internal_notes
created_at
updated_at

Indexes:

status
created_at
email
70. URL Rules

URLs must be:

Human readable
SEO friendly
Lowercase
Hyphen separated

Examples:

/services/web-development
/services/ai-solutions
/work/buildledger
/insights/how-ai-is-changing-business
71. Slug Management

Slugs should automatically generate from titles/names.

Admins must be able to manually edit slugs.

Changing a published slug should ideally create a redirect from the old URL.

72. 404 Page

Custom 404 page required.

Should include:

Looks like this page took a wrong turn.

CTA:

Back to Home

Secondary:

View Our Work

Do not use generic server errors.

73. Loading States

Use subtle loading states for:

Hero project transitions
Livewire interactions
Form submission
Mobile navigation
Content filtering

Avoid large full-screen loading animations.

74. Animation System

Animations should communicate hierarchy and polish.

Use:

Fade
Translate
Scale
Image reveal
Clip-path reveals where appropriate
Hover transitions
Scroll-triggered reveals

Animation duration should generally remain restrained.

Example:

150ms
250ms
400ms
600ms

Avoid animations that delay access to content.

75. Hero Animation

The hero is the primary animated component.

Transition sequence:

User selects project
        ↓
Current image begins exit
        ↓
New image enters
        ↓
Project metadata updates
        ↓
Selector active state changes

The transition must feel cinematic but fast.

76. Accessibility

Must meet WCAG-oriented accessibility standards.

Requirements:

Semantic HTML
Keyboard navigation
Visible focus states
Proper heading hierarchy
Accessible forms
Labels for inputs
Alt text
ARIA only where necessary
Sufficient color contrast
Reduced motion support

If user has prefers-reduced-motion, nonessential animation should be reduced or disabled.

77. Responsive Design

Required breakpoints:

Mobile
Tablet
Desktop
Large Desktop

Do not simply shrink the desktop layout.

Mobile should be intentionally designed.

78. Mobile Hero

The desktop project selector on the right should transform into a horizontal or compact selector.

Possible structure:

Featured Project
        ↓
Project information
        ↓
Horizontal project selector
[01] [02] [03] [04]

The selector must remain easy to interact with using touch.

79. Performance Requirements

Target:

Fast initial render
Minimal JavaScript
Optimized images
Lazy loading
Cached configuration
Cached routes
Cached views
Database indexes
Eager loading
Pagination

Avoid unnecessary frontend frameworks.

80. Laravel Production Optimisation

Production deployment should include appropriate Laravel optimizations:

php artisan config:cache
php artisan route:cache
php artisan view:cache

Do not run development-only tooling in production.

81. Database Performance

Avoid:

N+1 queries
Unnecessary database queries
Large unpaginated admin tables
Loading all portfolio images at once

Use:

Eager loading
Pagination
Proper indexes
Query scopes
Cached settings where appropriate
82. Security

Required:

CSRF protection
Authentication
Authorization
Password hashing
Mass-assignment protection
Input validation
Output escaping
Upload validation
Rate limiting
Secure cookies
Session security
Admin route protection

Contact forms must be protected from spam and abuse.

83. Form Security

Contact form should include:

Laravel validation
Rate limiting
Honeypot or equivalent anti-spam mechanism
CSRF
Email validation
Maximum input lengths

Never trust user-submitted HTML.

84. Authentication

Admin authentication must support:

Secure login
Password hashing
Session management
Logout
Password reset where configured

Optional future support:

Two-factor authentication
85. Email Notifications

When a contact form is submitted:

Admin should receive an email notification.

Email should contain:

New Website Inquiry

Name:
Email:
Phone:
Company:
Service:
Budget:

Message:

Email sending should use configurable SMTP credentials.

86. Error Handling

User-facing errors should be human friendly.

Never expose:

Stack traces
Database credentials
SQL queries
Server paths
Environment variables

Production must have debugging disabled.

87. Caching Strategy

Cache suitable low-change content such as:

Site settings
Navigation
Services
Client logos
Homepage configuration

Cache must be invalidated when administrators modify relevant content.

88. Search Engine Requirements

The website must be crawlable.

Ensure:

robots.txt
sitemap.xml
Canonical URLs
Proper heading hierarchy
Metadata
Structured data
Internal linking
Clean URLs
89. Social Sharing

Blog and project pages should generate proper social cards.

CMS fields:

OG Title
OG Description
OG Image

Fallback:

Page-specific OG image
        ↓
Site default OG image
90. Analytics

Architecture should support adding analytics without redesign.

Recommended implementation:

Google Analytics / Google Tag Manager

Analytics IDs must be configurable through Site Settings rather than hardcoded throughout templates.

91. Cookie / Privacy Considerations

If analytics or other tracking requires consent based on applicable regulations, provide an appropriate consent mechanism.

The system should not hardcode unnecessary tracking scripts.

92. Admin Media UX

Uploading an image should provide:

File selection
Upload progress
Preview
Alt text
Caption
Validation errors

Admins should easily reuse uploaded images where appropriate.

93. Admin Content UX

Forms should be organized into logical sections.

Example Blog form:

CONTENT
 ├── Title
 ├── Slug
 ├── Excerpt
 └── Content

PUBLISHING
 ├── Status
 ├── Author
 └── Published Date

MEDIA
 └── Featured Image

TAXONOMY
 ├── Category
 └── Tags

SEO
 ├── SEO Title
 ├── Description
 ├── Canonical
 └── OG Image

Avoid giant unstructured admin forms.

94. Publishing Workflow
Create
 ↓
Draft
 ↓
Preview
 ↓
Publish
 ↓
Public Website

Unpublishing:

Published
 ↓
Unpublish
 ↓
Draft/Archived
95. Content Relationships

The CMS must allow content to connect.

Examples:

Service
   ↓
Related Projects

Industry
   ↓
Related Services
   ↓
Related Projects

Project
   ↓
Related Services
   ↓
Related Industry

Blog Post
   ↓
Category
   ↓
Tags

This allows the frontend to dynamically create relevant content relationships.

96. Related Content

Project detail pages should show related work.

Service pages should show relevant projects.

Industry pages should show relevant services and projects.

Blog posts should show related articles.

This should be database-driven.

97. Industry Pages

If implemented as dedicated pages:

/industries/{slug}

Page structure:

Hero
↓
Industry Challenges
↓
How Webxpress Helps
↓
Relevant Services
↓
Relevant Projects
↓
CTA
98. Content Rules

CMS content should never break the frontend.

Frontend must account for:

Long titles
Short titles
Missing optional images
Missing descriptions
Long project names
Empty testimonials
No featured posts
No portfolio items
99. Empty States

Examples:

If no blog posts:

Insights are coming soon.

If no projects:

Our latest work is being prepared.

Do not display broken layouts.

100. Admin Validation

Required fields must be clearly marked.

Examples:

Project:

Name *
Slug *
Description *
Hero Image *
Status *

Optional:

Results
Project URL
Gallery
Technology

Validation errors should appear directly beside relevant fields.

101. Content Deletion

Destructive actions must require confirmation.

Examples:

Delete Project?
Delete Post?
Delete Client?
Delete Media?

Where content has dependencies, the CMS should prevent unsafe deletion or clearly explain what will happen.

102. Soft Deletes

Use soft deletion where appropriate for:

Posts
Projects
Clients
Testimonials
Contact Messages

This reduces accidental permanent data loss.

103. Auditability

For important admin changes, consider recording:

User
Action
Resource
Timestamp

Examples:

Tony published "New Webxpress Project"
Admin changed site phone number
Content Manager updated homepage hero

This can be implemented using an activity/audit package or a lightweight internal activity system.

104. Contact Management

Dashboard should make new inquiries obvious.

Example:

NEW INQUIRIES

5

Admin should be able to click through directly to the messages.

105. Admin Search

Search should exist for:

Blog posts
Projects
Services
Clients
Testimonials
Messages
Media
106. Admin Filters

Useful filters:

Posts
Status
Category
Author
Published date
Projects
Status
Industry
Category
Featured
Messages
Status
Service
Date
107. Admin Sorting

Tables should support sorting by:

Name
Created date
Updated date
Published date
Display order
Status
108. Ordering

Where visual order matters, admin should be able to reorder items.

Examples:

Client logos
Services
Projects
Testimonials
Process steps
Industries
Navigation

Use drag-and-drop ordering where useful.

109. Frontend Component Architecture

Blade components should be reusable.

Example:

components/
├── header
├── footer
├── button
├── container
├── section-heading
├── project-card
├── service-item
├── blog-card
├── testimonial
├── client-logo
├── image
├── breadcrumb
├── seo
└── cta

Page sections should be composed from reusable components rather than giant Blade files.

110. Livewire Usage

Use Livewire where server-driven interactivity provides value.

Examples:

Contact form
Portfolio filters
Blog filters
Search
Admin-facing interactive functionality

Do not use Livewire unnecessarily for purely visual animations.

111. Alpine.js Usage

Use Alpine.js for lightweight browser-side interaction.

Examples:

Mobile menu
Dropdowns
Hero selector state
Accordions
Modal interactions
Small UI states
112. JavaScript Rules

JavaScript must remain lightweight.

Do not introduce:

React
Vue
Angular
Next.js
Nuxt
Large animation frameworks unless genuinely justified
113. Tailwind Architecture

Use a consistent design token system.

Define:

Typography
Spacing
Container widths
Breakpoints
Border styles
Radius
Shadows
Brand colors

Avoid arbitrary styling scattered throughout templates.

114. Typography

Typography should feel editorial and premium.

Use:

Strong display typeface
Highly readable body typeface

Avoid:

Overused "AI startup" fonts
Excessive futuristic fonts
Decorative fonts for body copy

Font selection should support Webxpress's mature technology positioning.

115. Color System

The color system should be based on Webxpress's actual branding.

Use:

White/light backgrounds
Dark typography
Controlled brand accents

Do not create an unrelated visual identity.

The website should not depend on gradients to feel premium.

116. Photography / Visual Assets

Where imagery is used:

Prioritize:

Real Webxpress projects
Real client/project screenshots
Real company photography
Carefully selected editorial imagery
Abstract graphics only when they genuinely communicate something

Do not fill empty sections with generic AI images.

117. Portfolio Hero Visuals

Hero portfolio images should be:

High resolution
Properly cropped
Consistent enough to work within the layout
Optimized for web

CMS should allow a dedicated hero image instead of automatically using any random gallery image.

118. SEO-Friendly Content Structure

Every page should contain:

One H1
Logical H2 sections
Descriptive links
Alt text
Metadata
Internal links
Canonical URL

Avoid using typography styles as substitutes for semantic headings.

119. Accessibility of Hero

The hero selector must support:

Keyboard selection
Focus state
Screen-reader labels
Pause/stop auto rotation where appropriate

Auto-rotation must not make content inaccessible.

120. Reduced Motion

When:

prefers-reduced-motion: reduce

The website should:

Disable unnecessary transitions
Reduce hero animation
Avoid parallax
Avoid rapid movement
121. Performance Targets

Target:

Core Web Vitals

Aim for:

LCP: ≤ 2.5s
INP: ≤ 200ms
CLS: ≤ 0.1

These are targets, not guarantees, and must be measured on real deployment.

122. Asset Strategy

Use:

WebP/modern formats
Responsive image sizing
Lazy loading
Preload only critical assets
Minified CSS
Minified JavaScript

Do not preload everything.

123. Deployment Structure

Recommended Laravel deployment:

/home/account/
    app/
    bootstrap/
    config/
    database/
    resources/
    routes/
    storage/
    vendor/

public_html/
    index.php
    build/
    assets/

The web server must point to Laravel's public directory.

If the shared host requires a different arrangement, use a secure deployment structure that preserves Laravel's intended public/private separation.

124. Environment Configuration

Production .env must contain:

APP_ENV=production
APP_DEBUG=false
APP_URL=https://webxpresstech.com.ng

DB_CONNECTION=mysql
DB_HOST=
DB_PORT=
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

MAIL_MAILER=
MAIL_HOST=
MAIL_PORT=
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=

Secrets must never be committed to Git.

125. Backup Strategy

Production must have:

Database backups
Uploaded media backups
Code repository backup

Recommended:

Daily database backup
Regular media backup
Git repository
126. Migration Strategy

All database changes must be represented through Laravel migrations.

Never manually alter production schema without documenting the change.

Deployment:

git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

Exact deployment commands should be adapted to the hosting environment.

127. Cron

If scheduled publishing or recurring maintenance is implemented, use Laravel's scheduler.

Shared hosting cron can run the scheduler.

Example:

php artisan schedule:run

Do not require persistent workers.

128. Testing Requirements
Feature Tests

Test:

Contact submission
Blog publishing
Blog visibility
Project publishing
Service pages
Navigation
SEO metadata
Authorization
Admin permissions
Validation Tests

Test:

Invalid email
Missing required fields
Oversized uploads
Invalid file types
Duplicate slugs
129. Browser Testing

Test latest versions of:

Chrome
Safari
Firefox
Edge

Mobile:

Android Chrome
iOS Safari
130. Responsive QA

Test at minimum:

360px
390px
430px
768px
1024px
1280px
1440px
1920px
131. Security Testing

Verify:

Admin cannot access unauthorized resources
CSRF works
File upload restrictions work
Contact endpoint cannot be spammed easily
SQL injection protections
XSS protection
Authentication protections
Production debug mode disabled
132. CMS Acceptance Criteria

The CMS is considered complete when an administrator can independently:

Homepage
Edit hero
Change featured projects
Reorder hero projects
Change hero rotation interval
Update client logos
Edit About section
Manage services
Manage featured projects
Manage process
Manage industries
Manage testimonials
Edit CTA
Control featured insights
Blog
Create post
Save draft
Preview
Publish
Unpublish
Edit
Delete
Add categories
Add tags
Upload featured image
Configure SEO
Portfolio
Create project
Add gallery
Set hero image
Assign client
Assign industry
Assign category
Publish/unpublish
Feature project
Configure SEO
Global
Edit navigation
Edit company information
Edit contact information
Edit social links
Edit SEO defaults
Manage media
View contact submissions
133. Frontend Acceptance Criteria

The website is considered complete when:

Homepage loads correctly
Hero automatically rotates
Hero selectors work
Hero content updates correctly
Mobile hero works
Navigation works
MadeIT Codes link works
Portfolio filtering works
Project pages render correctly
Service pages render correctly
Blog works
Blog detail pages work
Contact form works
Footer works
404 page works
SEO metadata is generated
Sitemap exists
Responsive layouts work
Accessibility requirements are met
No placeholder content remains
134. CMS Acceptance Criteria

A non-developer should be able to:

Add a new project → upload images → publish it → have it appear on the Work page and, if marked featured, appear in the homepage/project showcase.

A non-developer should be able to:

Write a blog post → add image → select category → configure SEO → publish → have it appear on Insights.

A non-developer should be able to:

Update the company phone number once in Site Settings → have the updated number appear everywhere it is used.

135. Hero Acceptance Criteria

Given multiple featured projects:

Project A
Project B
Project C
Project D

When the homepage loads:

Project A is displayed
Project A selector is active

After the configured interval:

Project B becomes active

When the visitor selects Project C:

Project C becomes active immediately
Main image changes
Project metadata changes
Active selector changes

The system must not reload the entire page.

136. Blog Acceptance Criteria

Given a post with:

status = Draft

It must not appear publicly.

Given:

status = Published
published_at <= now

It must appear publicly.

Unpublished content must never appear through public listing queries.

137. Portfolio Acceptance Criteria

Projects marked:

featured = true

can appear in:

Homepage
Selected Work

Projects marked:

hero_featured = true

can appear in the homepage hero selector.

The number of hero projects should be configurable.

138. SEO Acceptance Criteria

Every public content page must generate:

Unique title
Unique description
Canonical URL
Open Graph metadata

Noindex must be applied where appropriate to:

Admin
Draft content
Private content
139. Contact Acceptance Criteria

A valid submission must:

Pass validation
Pass spam/rate limiting
Be saved to database
Set status to New
Trigger configured notification
Show a success message to the visitor

Example:

Thanks. Your project details have been received. We'll be in touch.

140. Error Acceptance Criteria

If contact submission fails:

The visitor must see a useful message.

Example:

Something went wrong while sending your message. Please try again.

Technical details must only be logged server-side.

141. Admin UX Principle

The CMS should feel like a content operating system, not a developer control panel.

The administrator should immediately understand:

What needs attention?
What can I edit?
Where do I publish?
What content exists?
What inquiries came in?
142. Content Governance

CMS should prevent common content mistakes.

Examples:

Require featured image for published blog posts
Require SEO title for important pages
Require project name and hero image before publishing
Prevent duplicate slugs
Warn before deleting referenced content
143. Future Extensibility

The architecture should allow future additions without rebuilding the application.

Potential future features:

Careers
Team members
Case study pages
Newsletter
Client portal
Events
FAQs
Knowledge base
Lead pipeline
Multi-language support
Advanced analytics
CRM integration

These should not be built into V1 unless required.

144. V1 Scope
Must Have
Website
Homepage
Header
Interactive hero
Client logos
About
Services
Work
Process
Industries
Testimonials
CTA
Insights
Contact
Footer
404
Privacy
Terms
CMS
Authentication
Dashboard
Posts
Categories
Projects
Services
Clients
Testimonials
Industries
Process
Homepage
Navigation
Media
Site Settings
SEO
Contact Messages
User Management
Technical
Laravel
Blade
Livewire
Alpine
Tailwind
Filament
MySQL
SEO
Security
Responsive design
Shared hosting compatibility
145. V1 Exclusions

Do NOT build:

Generic drag-and-drop page builder
Full CRM
Marketing automation platform
Client portal
E-commerce
Membership system
Multi-tenancy
Complex analytics dashboard
AI chatbot
Newsletter automation
Complex workflow engine
React frontend
Next.js frontend
Headless CMS

These can be future products/features.

146. Recommended Development Phases
Phase 1: Foundation
Laravel installation
Database
Authentication
Filament
Tailwind
Blade architecture
Base layout
Design system
Deployment structure
Phase 2: CMS

Build:

Posts
Categories
Projects
Services
Clients
Testimonials
Industries
Process
Navigation
Settings
Media
SEO
Contact Messages
Phase 3: Homepage

Build:

Header
Hero
Client Logos
About
Services
Selected Work
Process
Industries
Testimonials
CTA
Insights
Footer
Phase 4: Internal Pages

Build:

Work
Project Details
Services
Service Details
About
Industries
Insights
Blog Details
Contact
Privacy
Terms
404
Phase 5: Interactions

Implement:

Hero transitions
Mobile menu
Portfolio filtering
Blog filtering
Accordions
Scroll animations
Hover interactions
Form interactions
Phase 6: SEO & Performance

Implement:

Sitemap
Metadata
Structured data
Image optimization
Caching
Lazy loading
Core Web Vitals optimization
Phase 7: Security & QA

Test:

Authentication
Authorization
Forms
Uploads
XSS
CSRF
Rate limiting
Responsive design
Browser compatibility
Accessibility
Phase 8: Production
Configure production .env
Database
SSL
SMTP
Storage
Cron
Cache
Backups
Analytics
Final QA
Launch
147. Definition of Done

The project is complete when:

Brand
Website visually represents Webxpress as an established technology company
Design does not look like an AI-generated template
Actual company/project assets are prioritized
Frontend
All primary pages exist
Responsive behaviour works
Hero interaction works
Animations are polished
Mobile experience is intentionally designed
CMS
All required content is manageable through Filament
Blog publishing works
Portfolio management works
Homepage management works
Site settings work
Navigation management works
Contact messages work
Technical
Laravel production configuration works
MySQL works
Shared hosting deployment works
No production Node.js requirement
No Redis requirement
No Docker requirement
Security controls are implemented
SEO
Sitemap works
Metadata works
Structured data works
Canonical URLs work
Social previews work
Performance
Images optimized
CSS/JS optimized
Database queries optimized
Caching configured
Core Web Vitals tested
Deployment
SSL active
Production environment configured
Backups configured
Error handling configured
Debug mode disabled
148. Final Product Principle

The most important architectural rule for this project is:

The CMS should make Webxpress easy to manage without making the website easy to ruin.

The frontend should remain a carefully art-directed digital experience.

The CMS should provide structured control over the content behind that experience.

The final system should therefore be:

                WEBXPRESS WEBSITE
                       │
          ┌────────────┴────────────┐
          │                         │
      FRONTEND                    CMS
          │                         │
      Blade                       Filament
      Livewire                    Laravel
      Alpine                      MySQL
      Tailwind                    Storage
          │                         │
          └────────────┬────────────┘
                       │
                 Shared Hosting

Result: a premium Webxpress corporate website that can be deployed on conventional PHP/MySQL shared hosting today, managed by a non-developer through Filament, and later moved to a VPS without requiring a fundamental architectural rewrite.