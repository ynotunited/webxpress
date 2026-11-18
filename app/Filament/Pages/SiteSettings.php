<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class SiteSettings extends Page
{
    protected string $view = 'filament.pages.site-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Site Settings';

    protected static ?int $navigationSort = 11;

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'site_name' => SiteSetting::value('site_name', config('app.name')),
            'og_title' => SiteSetting::value('og_title', 'Webxpress Technologies — Web & SaaS Built for Africa'),
            'og_image' => SiteSetting::value('og_image'),
            'logo_colored' => SiteSetting::value('logo_colored'),
            'logo_white' => SiteSetting::value('logo_white'),
            'favicon' => SiteSetting::value('favicon'),
            'contact_email' => SiteSetting::value('contact_email'),
            'contact_phone' => SiteSetting::value('contact_phone'),
            'contact_address' => SiteSetting::value('contact_address'),
            'whatsapp_number' => SiteSetting::value('whatsapp_number'),
            'inquiries_email' => SiteSetting::value('inquiries_email', config('mail.inquiries_to')),
            'linkedin_url' => SiteSetting::value('linkedin_url'),
            'instagram_url' => SiteSetting::value('instagram_url'),
            'x_url' => SiteSetting::value('x_url'),
            'hero_headline' => SiteSetting::value('hero_headline', 'Web & SaaS Built for Africa'),
            'hero_subheadline' => SiteSetting::value('hero_subheadline', 'Fast, scalable digital solutions for businesses that can\'t wait for slow tech.'),
            'footer_tagline' => SiteSetting::value('footer_tagline', 'Ideas. Designs. Innovation.'),
        ]);
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Branding')
                ->description('Applied everywhere at once — header, footer, contact page, browser tab and the admin panel. Leave a field empty to keep the shipped default.')
                ->columns(3)
                ->schema([
                    TextInput::make('site_name')
                        ->label('Site name')
                        ->required()
                        ->maxLength(120)
                        ->helperText('Used in logo alt text, page titles and aria labels.'),
                    TextInput::make('og_title')
                        ->label('Open Graph title')
                        ->maxLength(70)
                        ->helperText('Headline shown when a page is shared. Pages with their own SEO title keep it.'),
                    FileUpload::make('og_image')
                        ->label('Open Graph image')
                        ->image()
                        ->disk('public')
                        ->directory('branding')
                        ->visibility('public')
                        ->helperText('1200 × 630 px. Currently: '.og_image_url()),
                    FileUpload::make('logo_colored')
                        ->label('Logo (light backgrounds)')
                        ->image()
                        ->disk('public')
                        ->directory('branding')
                        ->visibility('public')
                        ->imageResizeTargetWidth('600')
                        ->helperText('Currently: '.brand_logo('colored')),
                    FileUpload::make('logo_white')
                        ->label('Logo (dark backgrounds)')
                        ->image()
                        ->disk('public')
                        ->directory('branding')
                        ->visibility('public')
                        ->imageResizeTargetWidth('600')
                        ->helperText('Currently: '.brand_logo('white')),
                    FileUpload::make('favicon')
                        ->label('Favicon')
                        ->image()
                        ->disk('public')
                        ->directory('branding')
                        ->visibility('public')
                        ->imageResizeTargetWidth('512')
                        ->helperText('Currently: '.brand_favicon()),
                ]),
            Section::make('Contact Information')->columns(2)->schema([
                TextInput::make('contact_email')->label('Contact email')->email()->required(),
                TextInput::make('contact_phone')->label('Phone number'),
                Textarea::make('contact_address')->label('Address')->rows(2),
                TextInput::make('whatsapp_number')->label('WhatsApp number'),
                TextInput::make('inquiries_email')->label('Inquiries email')->email()->hint('Where contact-form leads are sent'),
            ]),
            Section::make('Homepage Hero')->columns(2)->schema([
                TextInput::make('hero_headline')->label('Headline'),
                Textarea::make('hero_subheadline')->label('Sub-headline')->rows(2),
            ]),
            Section::make('Social Links')->columns(3)->schema([
                TextInput::make('linkedin_url')->label('LinkedIn')->url(),
                TextInput::make('instagram_url')->label('Instagram')->url(),
                TextInput::make('x_url')->label('X (Twitter)')->url(),
            ]),
            Section::make('Footer')->schema([
                Textarea::make('footer_tagline')->label('Tagline')->rows(2),
            ]),
        ]);
    }

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            SiteSetting::setValue($key, $value);
        }

        Notification::make()->success()->title('Settings saved.')->send();
    }
}
