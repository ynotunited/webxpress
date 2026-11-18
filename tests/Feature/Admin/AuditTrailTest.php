<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Models\ClientReview;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Tests\TestCase;

/**
 * The audit trail records what an admin changed.
 *
 * These cover the properties the table exists to provide: that a change is
 * captured, that it records who and what rather than only that something
 * happened, that a login is recorded, and that the record cannot itself be
 * edited.
 */
class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create(['email' => 'boss@webxpresstech.com.ng']);

        Auth::login($admin);

        return $admin;
    }

    public function test_creating_a_record_is_audited_with_the_actor(): void
    {
        $admin = $this->actingAsAdmin();

        $page = Page::create(['title' => 'Privacy Policy', 'slug' => 'privacy', 'content' => 'x']);

        $log = AuditLog::forSubject($page)->first();

        $this->assertNotNull($log, 'creating a page wrote no audit entry');
        $this->assertSame('created', $log->event);
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($admin->email, $log->user_email);
        $this->assertStringContainsString('Privacy Policy', $log->summary);
    }

    public function test_an_edit_stores_the_before_and_after_values(): void
    {
        $admin = $this->actingAsAdmin();

        $page = Page::create(['title' => 'Privacy Policy', 'slug' => 'privacy', 'content' => 'x']);
        $page->update(['title' => 'Privacy Notice']);

        $log = AuditLog::forSubject($page)->where('event', 'updated')->firstOrFail();

        $this->assertSame('Privacy Policy', $log->changes['title']['old']);
        $this->assertSame('Privacy Notice', $log->changes['title']['new']);
        $this->assertSame($admin->id, $log->user_id);
    }

    public function test_an_edit_records_only_the_fields_that_changed(): void
    {
        $this->actingAsAdmin();

        $post = Post::create([
            'title' => 'Original',
            'slug' => 'original',
            'excerpt' => 'x',
            'body' => 'x',
        ]);
        $post->update(['title' => 'Revised']);

        $log = AuditLog::forSubject($post)->where('event', 'updated')->firstOrFail();

        // A post has a dozen columns. Storing all of them would bury the one
        // field that actually moved.
        $this->assertSame(['title'], array_keys($log->changes));
    }

    public function test_deletion_is_audited_even_though_the_record_is_gone(): void
    {
        $this->actingAsAdmin();

        $page = Page::create(['title' => 'Scratch', 'slug' => 'scratch', 'content' => 'x']);
        $page->delete();

        $log = AuditLog::query()
            ->where('event', 'deleted')
            ->where('subject_type', (new Page)->getMorphClass())
            ->firstOrFail();

        $this->assertSame($page->id, $log->subject_id);
        $this->assertStringContainsString('Scratch', $log->summary);
    }

    public function test_long_values_are_truncated_so_the_table_does_not_become_a_second_copy_of_the_site(): void
    {
        $this->actingAsAdmin();

        $page = Page::create(['title' => 'Terms', 'slug' => 'terms', 'content' => 'short']);
        $page->update(['content' => str_repeat('a', 5000)]);

        $log = AuditLog::forSubject($page)->where('event', 'updated')->firstOrFail();

        $this->assertLessThan(400, mb_strlen($log->changes['content']['new']));
        $this->assertStringContainsString('truncated', $log->changes['content']['new']);
    }

    public function test_every_audited_model_is_covered(): void
    {
        $this->actingAsAdmin();

        $parent = Page::create(['title' => 'Parent', 'slug' => 'parent', 'content' => 'x']);

        // If a model is added to the admin panel but not to the audit list,
        // its edits vanish silently. These are the resources an admin can edit.
        $writes = [
            'page' => fn () => Page::create(['title' => 'Child', 'slug' => 'child', 'content' => 'x']),
            'page section' => fn () => PageSection::create([
                'page_id' => $parent->id,
                'type' => 'text',
                'key' => 'intro',
                'data' => [],
            ]),
            'post' => fn () => Post::create([
                'title' => 'A post',
                'slug' => 'a-post',
                'excerpt' => 'x',
                'body' => 'x',
            ]),
            'client review' => fn () => ClientReview::create([
                'client_name' => 'Acme',
                'quote' => 'q',
                'rating' => 5,
            ]),
        ];

        foreach ($writes as $label => $write) {
            $before = AuditLog::count();
            $write();

            $this->assertGreaterThan($before, AuditLog::count(), "a {$label} write produced no audit entry");
        }
    }

    public function test_admin_sign_in_is_audited(): void
    {
        $admin = User::factory()->create(['email' => 'boss@webxpresstech.com.ng']);

        event(new Login('web', $admin, false));

        $log = AuditLog::where('event', 'logged_in')->firstOrFail();

        $this->assertSame($admin->email, $log->user_email);
        $this->assertSame($admin->id, $log->user_id);
    }

    public function test_a_failed_sign_in_is_audited_without_storing_the_password(): void
    {
        event(new Failed('web', $admin = User::factory()->create(), ['email' => 'boss@x.com', 'password' => 'hunter2']));

        $log = AuditLog::where('event', 'login_failed')->firstOrFail();

        $this->assertStringContainsString('boss@x.com', $log->summary);
        // Writing the attempted password anywhere would make this table a
        // password history, including for typos on successful sign-ins.
        $this->assertStringNotContainsString('hunter2', $log->summary);
        $this->assertStringNotContainsString('hunter2', json_encode($log->changes ?? []));
    }

    public function test_entries_survive_the_deletion_of_the_admin_who_made_them(): void
    {
        $admin = $this->actingAsAdmin();

        $page = Page::create(['title' => 'Privacy Policy', 'slug' => 'privacy', 'content' => 'x']);
        $admin->delete();

        $log = AuditLog::forSubject($page)->firstOrFail();

        // user_id nulls on delete, so the name has to survive as a snapshot or
        // the trail silently loses the only answer to "who did this".
        $this->assertNull($log->user_id);
        $this->assertSame('boss@webxpresstech.com.ng', $log->user_email);
    }

    public function test_an_audit_entry_cannot_be_edited(): void
    {
        $this->actingAsAdmin();

        Page::create(['title' => 'Privacy Policy', 'slug' => 'privacy', 'content' => 'x']);

        $log = AuditLog::firstOrFail();

        $this->expectException(\LogicException::class);

        $log->update(['summary' => 'Nothing happened']);
    }

    public function test_an_audit_entry_cannot_be_deleted(): void
    {
        $this->actingAsAdmin();

        Page::create(['title' => 'Privacy Policy', 'slug' => 'privacy', 'content' => 'x']);

        $log = AuditLog::firstOrFail();

        $this->expectException(\LogicException::class);

        $log->delete();
    }

    public function test_a_failed_audit_write_does_not_block_the_content_edit(): void
    {
        $this->actingAsAdmin();

        // Make the audit insert itself fail, which is the condition being
        // defended against. Simulated with a model hook rather than by dropping
        // the table, so the test does not have to rebuild the schema afterwards.
        AuditLog::creating(fn () => throw new RuntimeException('audit storage unavailable'));

        // Relative, not zero: signing in is already recorded above the hook.
        $before = AuditLog::count();

        // Losing a content edit because the log table was unavailable is a far
        // worse outcome than losing the record of the edit, so the observer
        // reports and swallows.
        $page = Page::create(['title' => 'Still Saved', 'slug' => 'still-saved', 'content' => 'x']);

        $this->assertDatabaseHas('pages', ['id' => $page->id, 'title' => 'Still Saved']);
        $this->assertSame($before, AuditLog::count(), 'the page edit was recorded despite the audit insert failing');
    }

    public function test_login_events_are_wired_up(): void
    {
        // Guards the explicit Event::listen registration. Auto-discovery only
        // finds a single type-hinted handle(), so dropping these listeners would
        // be silent: sign-ins would simply stop being recorded.
        $listeners = app('events')->getRawListeners();

        $this->assertArrayHasKey(Login::class, $listeners);
        $this->assertArrayHasKey(Failed::class, $listeners);
    }

    public function test_the_admin_can_read_the_trail(): void
    {
        config(['admin.email' => 'boss@webxpresstech.com.ng']);
        $admin = User::factory()->create(['email' => 'boss@webxpresstech.com.ng']);

        Auth::login($admin);
        Page::create(['title' => 'Privacy Policy', 'slug' => 'privacy', 'content' => 'x']);

        $this->actingAs($admin)->get('/'.admin_path().'/audit-logs')
            ->assertOk()
            ->assertSee('Audit Trail')
            ->assertSee('Privacy Policy');
    }

    public function test_the_diff_view_renders_before_and_after(): void
    {
        $this->actingAsAdmin();

        $page = Page::create(['title' => 'Privacy Policy', 'slug' => 'privacy', 'content' => 'x']);
        $page->update(['title' => 'Privacy Notice']);

        $log = AuditLog::forSubject($page)->where('event', 'updated')->firstOrFail();

        // The modal content is rendered lazily when the action is invoked, so
        // loading the index page above does not exercise this template at all.
        $html = view('filament.audit-log-changes', ['record' => $log])->render();

        $this->assertStringContainsString('Privacy Policy', $html);
        $this->assertStringContainsString('Privacy Notice', $html);
        $this->assertStringContainsString('Page', $html);
    }

    public function test_the_trail_offers_no_way_to_write_to_it(): void
    {
        $this->actingAsAdmin();

        Page::create(['title' => 'Privacy Policy', 'slug' => 'privacy', 'content' => 'x']);
        $log = AuditLog::firstOrFail();

        // Closed at both layers: the model refuses, and so do the routes.
        // Only the routes being open would still be a problem via raw query
        // builder, which is why neither layer is left to chance.
        $this->assertFalse(AuditLogResource::canCreate());
        $this->assertFalse(AuditLogResource::canEdit($log));
        $this->assertFalse(AuditLogResource::canDelete($log));
        $this->assertFalse(AuditLogResource::canDeleteAny());
    }
}