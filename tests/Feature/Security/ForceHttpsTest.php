<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\ForceHttps;
use App\Providers\TrustedProxyServiceProvider;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ForceHttpsTest extends TestCase
{
    public function test_plain_http_is_redirected_to_https(): void
    {
        $request = Request::create('http://webxpresstech.com.ng/contact', 'GET');

        $response = (new ForceHttps)->handle($request, fn () => new Response('ok'));

        $this->assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
        $this->assertSame('https://webxpresstech.com.ng/contact', $response->headers->get('Location'));
    }

    public function test_https_requests_pass_through(): void
    {
        $request = Request::create('https://webxpresstech.com.ng/contact', 'GET');

        $response = (new ForceHttps)->handle($request, fn () => new Response('ok'));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }

    public function test_provider_registers_force_https_when_the_kernel_is_already_resolved(): void
    {
        config(['app.force_https' => true]);

        $kernel = $this->app->make(HttpKernel::class);

        (new TrustedProxyServiceProvider($this->app))->boot();

        $this->assertContains(ForceHttps::class, $kernel->getGlobalMiddleware());
    }
}
