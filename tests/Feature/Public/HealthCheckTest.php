<?php

namespace Tests\Feature\Public;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_endpoint_is_available_for_deployment_checks(): void
    {
        $this->get('/up')
            ->assertOk()
            ->assertSee('ok');
    }
}
