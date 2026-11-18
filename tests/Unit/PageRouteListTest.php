<?php

namespace Tests\Unit;

use App\Support\PageSectionSchema;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The offer a block link makes to an editor.
 *
 * This list is printed verbatim in the validation message when someone types a
 * route that does not exist, so what is in it is a product decision, not a
 * detail: an editor should see the public pages they can link to, and nothing
 * that would produce a broken button.
 */
class PageRouteListTest extends TestCase
{
    public function test_it_offers_the_public_pages_an_editor_can_link_to(): void
    {
        $routes = PageSectionSchema::pageRoutes();

        $this->assertContains('contact', $routes);
        $this->assertContains('work.index', $routes);
        $this->assertContains('home', $routes);
    }

    public function test_it_omits_routes_that_cannot_be_a_link_target(): void
    {
        $routes = PageSectionSchema::pageRoutes();

        // A link names a page, never one record: work.show takes a slug.
        $this->assertNotContains('work.show', $routes);

        // A button is a GET. Linking to a form's POST endpoint never works.
        $this->assertNotContains('contact.store', $routes);
        $this->assertNotContains('webhooks.paystack', $routes);

        // The admin panel is not a public destination.
        $this->assertNotContains('filament.admin.pages.dashboard', $routes);
    }

    public function test_every_offered_route_really_exists(): void
    {
        foreach (PageSectionSchema::pageRoutes() as $name) {
            $this->assertTrue(
                Route::has($name),
                "'{$name}' is offered as a link target but no such route is registered"
            );
        }
    }
}
