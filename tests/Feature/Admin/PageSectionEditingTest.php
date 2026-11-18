<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Site\PageSectionResource\Pages\CreatePageSection;
use App\Filament\Resources\Site\PageSectionResource\Pages\EditPageSection;
use App\Filament\Resources\Site\PageSectionResource\Pages\ListPageSections;
use App\Filament\Resources\Site\SitePageResource\Pages\CreateSitePage;
use App\Filament\Resources\Site\SitePageResource\Pages\EditSitePage;
use App\Filament\Resources\Site\SitePageResource\Pages\ListSitePages;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\User;
use App\Support\PageSectionSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The page-block editor, exercised through the real Filament page components.
 *
 * These guard the one thing an editor cannot check for themselves: that saving
 * a block changes only what they edited, and that every field the schema
 * declares is actually editable.
 *
 * A block type owns a fixed set of data keys, and the model drops any key a
 * block does not own. A form missing one of a block's fields is therefore not
 * merely incomplete - the next unrelated save erases that copy from the live
 * site. The round-trip test below is the guard: every key the schema declares
 * for a type must survive a load/save cycle untouched.
 */
class PageSectionEditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_key_the_schema_declares_survives_a_load_and_save(): void
    {
        $this->signInAsAdmin();

        $page = Page::create(['title' => 'Home', 'slug' => 'home', 'is_published' => true]);

        // One block of every registered type, each fully populated, so a save
        // that silently drops a field shows up as a concrete diff.
        $blocks = [];

        foreach (array_keys(PageSectionSchema::types()) as $type) {
            $blocks[$type] = PageSection::create([
                'page_id' => $page->id,
                'key' => 'block-'.$type,
                'type' => $type,
                'sort_order' => 0,
                'data' => $this->payloadFor($type),
            ]);
        }

        $before = array_map(fn (PageSection $block): array => $block->fresh()->data, $blocks);

        foreach ($blocks as $block) {
            Livewire::test(EditPageSection::class, ['record' => $block->getKey()])
                ->assertSuccessful()
                ->call('save')
                ->assertHasNoFormErrors();
        }

        foreach ($blocks as $type => $block) {
            $this->assertSame(
                $before[$type],
                $block->fresh()->data,
                "the '{$type}' form does not expose every key the schema declares, so saving erases live copy"
            );
        }
    }

    public function test_editing_a_block_does_not_disturb_its_siblings(): void
    {
        $this->signInAsAdmin();

        $page = Page::create(['title' => 'Home', 'slug' => 'home', 'is_published' => true]);

        $logoStrip = PageSection::create([
            'page_id' => $page->id,
            'key' => 'trusted-by',
            'type' => PageSection::TYPE_LOGO_STRIP,
            'sort_order' => 0,
            'data' => [
                'label' => 'Trusted by',
                'items' => [
                    ['name' => 'Interswitch', 'abbr' => 'IS'],
                    ['name' => 'Moniepoint', 'abbr' => 'MP'],
                ],
            ],
        ]);

        $cta = PageSection::create([
            'page_id' => $page->id,
            'key' => 'major-cta',
            'type' => PageSection::TYPE_CTA,
            'sort_order' => 1,
            'data' => [
                'heading' => 'Let us build it',
                'heading_accent' => 'properly',
                'buttons' => [['label' => 'Start a project', 'route' => 'contact', 'style' => 'primary']],
            ],
        ]);

        // The realistic case: an editor opens the CTA and fixes a typo.
        Livewire::test(EditPageSection::class, ['record' => $cta->getKey()])
            ->assertSuccessful()
            ->fillForm(['data.heading' => 'Let us build it properly'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Let us build it properly', $cta->fresh()->get('heading'));
        $this->assertSame('properly', $cta->fresh()->get('heading_accent'));
        $this->assertSame(
            [['label' => 'Start a project', 'route' => 'contact', 'style' => 'primary']],
            $cta->fresh()->get('buttons'),
            'editing one field of a block discarded its other fields'
        );

        $this->assertSame(
            ['label' => 'Trusted by', 'items' => [['name' => 'Interswitch', 'abbr' => 'IS'], ['name' => 'Moniepoint', 'abbr' => 'MP']]],
            $logoStrip->fresh()->data,
            'editing one block altered another'
        );
    }

    public function test_a_block_key_is_required(): void
    {
        $this->signInAsAdmin();

        $page = Page::create(['title' => 'Home', 'slug' => 'home', 'is_published' => true]);

        $block = PageSection::create([
            'page_id' => $page->id,
            'key' => 'trusted-by',
            'type' => PageSection::TYPE_LOGO_STRIP,
            'sort_order' => 0,
            'data' => ['label' => 'Trusted by'],
        ]);

        Livewire::test(EditPageSection::class, ['record' => $block->getKey()])
            ->fillForm(['key' => ''])
            ->call('save')
            ->assertHasFormErrors(['key']);
    }

    public function test_a_block_key_cannot_be_used_twice_on_one_page(): void
    {
        $this->signInAsAdmin();

        $page = Page::create(['title' => 'Home', 'slug' => 'home', 'is_published' => true]);

        PageSection::create([
            'page_id' => $page->id,
            'key' => 'trusted-by',
            'type' => PageSection::TYPE_LOGO_STRIP,
            'sort_order' => 0,
            'data' => ['label' => 'Trusted by'],
        ]);

        // Two blocks sharing a key would silently fight over one section at
        // render time, so the write is rejected wherever it comes from.
        $this->expectException(ValidationException::class);

        PageSection::create([
            'page_id' => $page->id,
            'key' => 'trusted-by',
            'type' => PageSection::TYPE_LOGO_STRIP,
            'sort_order' => 1,
            'data' => ['label' => 'Also trusted by'],
        ]);
    }

    public function test_the_same_block_key_may_be_reused_on_a_different_page(): void
    {
        $this->signInAsAdmin();

        $home = Page::create(['title' => 'Home', 'slug' => 'home', 'is_published' => true]);
        $about = Page::create(['title' => 'About', 'slug' => 'about', 'is_published' => true]);

        $shared = ['type' => PageSection::TYPE_LOGO_STRIP, 'data' => ['label' => 'Trusted by']];

        $this->assertSame('trusted-by', PageSection::create([
            'page_id' => $home->id,
            'key' => 'trusted-by',
            'sort_order' => 0,
            ...$shared,
        ])->key);

        $this->assertSame('trusted-by', PageSection::create([
            'page_id' => $about->id,
            'key' => 'trusted-by',
            'sort_order' => 0,
            ...$shared,
        ])->key);
    }

    public function test_a_saving_a_block_clears_a_key_the_schema_does_not_own(): void
    {
        $this->signInAsAdmin();

        $page = Page::create(['title' => 'Home', 'slug' => 'home', 'is_published' => true]);

        $block = PageSection::create([
            'page_id' => $page->id,
            'key' => 'trusted-by',
            'type' => PageSection::TYPE_LOGO_STRIP,
            'sort_order' => 0,
            'data' => ['label' => 'Trusted by', 'items' => [['name' => 'Interswitch', 'abbr' => 'IS']]],
        ]);

        $block->data = ['label' => 'Trusted by', 'quote' => 'A quote this block cannot own'];
        $block->save();

        $this->assertArrayNotHasKey('quote', $block->fresh()->data);
    }

    public function test_a_new_block_can_be_created_with_its_own_type_fields(): void
    {
        $this->signInAsAdmin();

        $page = Page::create(['title' => 'Home', 'slug' => 'home', 'is_published' => true]);

        Livewire::test(CreatePageSection::class)
            ->assertSuccessful()
            ->fillForm([
                'page_id' => $page->id,
                'key' => 'how-we-work',
                'type' => PageSection::TYPE_STEPS,
                'sort_order' => 3,
                'data' => [
                    'label' => 'How we work',
                    'heading' => 'From brief to build',
                    'items' => [
                        ['number' => '01', 'title' => 'Discover', 'description' => 'We map the problem.'],
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $block = $page->sections()->where('key', 'how-we-work')->firstOrFail();

        $this->assertSame(PageSection::TYPE_STEPS, $block->type);
        $this->assertSame('From brief to build', $block->get('heading'));
        $this->assertSame('Discover', $block->getItems()[0]['title'] ?? null);
    }

    public function test_a_mistyped_route_name_is_rejected(): void
    {
        $this->signInAsAdmin();

        $page = Page::create(['title' => 'Home', 'slug' => 'home', 'is_published' => true]);

        $block = PageSection::create([
            'page_id' => $page->id,
            'key' => 'major-cta',
            'type' => PageSection::TYPE_CTA,
            'sort_order' => 0,
            'data' => ['heading' => 'Let us build it'],
        ]);

        // A bad route name is the one copy mistake that fails silently: the
        // renderer skips the link, so the button just disappears.
        Livewire::test(EditPageSection::class, ['record' => $block->getKey()])
            ->fillForm(['data.buttons' => [['label' => 'Start a project', 'route' => 'contact-page', 'style' => 'primary']]])
            ->call('save')
            ->assertHasFormErrors(['data.buttons.0.route']);
    }

    public function test_changing_a_blocks_type_replaces_its_copy_with_that_types_fields(): void
    {
        $this->signInAsAdmin();

        $page = Page::create(['title' => 'Home', 'slug' => 'home', 'is_published' => true]);

        $block = PageSection::create([
            'page_id' => $page->id,
            'key' => 'hero-copy',
            'type' => PageSection::TYPE_BIO,
            'sort_order' => 0,
            'data' => ['label' => 'Who we are', 'quote' => 'An original quote'],
        ]);

        // Switching type must not leave the old type's keys behind on a block
        // that no longer has fields for them.
        Livewire::test(EditPageSection::class, ['record' => $block->getKey()])
            ->fillForm([
                'type' => PageSection::TYPE_CTA,
                'data.heading' => 'Let us build it',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $data = $block->fresh()->data;

        $this->assertSame(PageSection::TYPE_CTA, $block->fresh()->type);
        $this->assertSame('Let us build it', $data['heading']);
        $this->assertArrayNotHasKey('quote', $data);
    }

    public function test_every_block_admin_screen_renders(): void
    {
        $this->signInAsAdmin();

        $page = Page::create(['title' => 'Home', 'slug' => 'home', 'is_published' => true]);

        $block = PageSection::create([
            'page_id' => $page->id,
            'key' => 'trusted-by',
            'type' => PageSection::TYPE_LOGO_STRIP,
            'sort_order' => 0,
            'data' => ['label' => 'Trusted by', 'items' => [['name' => 'Interswitch', 'abbr' => 'IS']]],
        ]);

        // A table column, filter or action that does not exist is only ever
        // reported when the screen renders, so each screen is rendered here.
        Livewire::test(ListPageSections::class)->assertSuccessful();
        Livewire::test(CreatePageSection::class)->assertSuccessful();
        Livewire::test(EditPageSection::class, ['record' => $block->getKey()])->assertSuccessful();
    }

    public function test_the_block_list_shows_which_page_and_type_a_block_belongs_to(): void
    {
        $this->signInAsAdmin();

        $page = Page::create(['title' => 'Home', 'slug' => 'home', 'is_published' => true]);

        PageSection::create([
            'page_id' => $page->id,
            'key' => 'trusted-by',
            'type' => PageSection::TYPE_LOGO_STRIP,
            'sort_order' => 0,
            'data' => ['label' => 'Trusted by'],
        ]);

        Livewire::test(ListPageSections::class)
            ->assertCanSeeTableRecords(PageSection::all())
            ->assertTableColumnStateSet('key', 'trusted-by', PageSection::first())
            ->assertTableColumnStateSet('type', 'logo_strip', PageSection::first());
    }

    public function test_every_page_admin_screen_renders(): void
    {
        $this->signInAsAdmin();

        $page = Page::create(['title' => 'Home', 'slug' => 'home', 'is_published' => true]);

        // The page screens were changed in step with the block editor (the
        // inline repeater is gone, replaced by a link to the block list), and
        // nothing else renders them.
        Livewire::test(ListSitePages::class)->assertSuccessful();
        Livewire::test(CreateSitePage::class)->assertSuccessful();
        Livewire::test(EditSitePage::class, ['record' => $page->getKey()])
            ->assertSuccessful()
            ->assertActionExists('blocks');
    }

    /**
     * A payload that fills every control the schema declares for a block type.
     *
     * @return array<string, mixed>
     */
    private function payloadFor(string $type): array
    {
        $data = [];

        foreach (PageSectionSchema::controls($type) as $key => $control) {
            $data[$key] = match (true) {
                // Route fields are validated against real route names, so a
                // marker string would (correctly) be rejected.
                ($control['format'] ?? null) === 'route' => 'contact',
                // Option fields are selects, so a marker string is not a legal
                // value; the first declared option stands in for a real one.
                // Same rule as the nested controls below, applied here too so
                // a top-level select can never be saved with an illegal value.
                filled($control['options'] ?? null) => (string) array_key_first($control['options']),
                $control['control'] === 'items' => [$this->childFor($control['item'] ?? [], $key)],
                $control['control'] === 'item' => $this->childFor($control['item'] ?? [], $key),
                // Rich text is echoed unescaped, so it is sanitised. A marker
                // has to be real markup to prove the round trip preserves it.
                $control['control'] === 'html' => '<p>'.$this->marker($key).'</p>',
                // The uploader resolves its state against the real disk, so a
                // path with no file behind it is legitimately cleared on save.
                // The test therefore writes an actual file, which is also the
                // only way to prove a real upload survives the round trip.
                $control['control'] === 'image' => $this->realImage($key),
                default => $this->marker($key),
            };
        }

        return $data;
    }

    /**
     * @param  array<string, array<string, mixed>>  $controls
     * @return array<string, string>
     */
    private function childFor(array $controls, string $prefix): array
    {
        $child = [];

        foreach ($controls as $key => $control) {
            $child[$key] = match (true) {
                // Route fields are validated against real route names, so a
                // marker string would (correctly) be rejected.
                ($control['format'] ?? null) === 'route' => 'contact',
                // Option fields are selects, so a marker string is not a legal
                // value; the first declared option stands in for a real one.
                filled($control['options'] ?? null) => (string) array_key_first($control['options']),
                $control['control'] === 'html' => '<p>'.$this->marker($prefix.'.'.$key).'</p>',
                default => $this->marker($prefix.'.'.$key),
            };
        }

        return $child;
    }

    private function marker(string $key): string
    {
        return str_replace('.', ' ', ucfirst($key)).' marker';
    }

    /**
     * Write an actual file to the public disk and return its relative path.
     *
     * Filament's uploader resolves stored state against the disk, so a block
     * pointing at a path with no file behind it is correctly cleared on save.
     * Using a real file is what makes this a test of the round trip rather
     * than a test of a missing-file edge case.
     */
    private function realImage(string $key): string
    {
        $path = 'page-sections/'.md5($key).'.png';

        Storage::disk('public')->put($path, 'not-really-a-png');

        return $path;
    }

    private function signInAsAdmin(): void
    {
        // An empty allow-list opens the panel to any signed-in user, which
        // keeps these tests focused on editing rather than on access control.
        config(['admin.email' => '']);

        $this->actingAs(User::factory()->create());
    }
}
