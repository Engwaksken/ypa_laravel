<?php

namespace Tests\Unit;

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Middleware\TrustHosts;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ProductionConfigurationTest extends TestCase
{
    protected function tearDown(): void
    {
        Request::setTrustedProxies([], -1);
        Request::setTrustedHosts([]);
        TrustProxies::flushState();
        TrustHosts::flushState();
        parent::tearDown();
    }

    public function test_explicit_proxy_configuration_is_read_from_config_and_accepts_forwarded_https(): void
    {
        config(['trustedproxy.proxies' => ['10.0.0.5']]);
        $request = Request::create('http://ypa.test/login', 'GET', [], [], [], [
            'REMOTE_ADDR' => '10.0.0.5', 'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_FOR' => '203.0.113.8',
        ]);
        (new TrustProxies)->handle($request, fn ($request) => new Response('OK'));
        $this->assertTrue($request->isSecure());
        $this->assertSame('203.0.113.8', $request->ip());
    }

    public function test_an_untrusted_client_cannot_spoof_forwarded_https_or_client_ip(): void
    {
        config(['trustedproxy.proxies' => []]);
        $request = Request::create('http://ypa.test/login', 'GET', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.10', 'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_FOR' => '127.0.0.1',
        ]);
        (new TrustProxies)->handle($request, fn ($request) => new Response('OK'));
        $this->assertFalse($request->isSecure());
        $this->assertSame('203.0.113.10', $request->ip());
    }

    public function test_trusted_hosts_are_exact_and_resolved_from_runtime_configuration(): void
    {
        // Resolve the HTTP kernel so bootstrap registers the lazy host callback.
        app(\Illuminate\Contracts\Http\Kernel::class);
        config(['app.url' => 'https://ypa.example.com', 'deployment.hosts' => ['admin.example.com']]);
        $patterns = (new TrustHosts(app()))->hosts();
        $matches = fn ($host) => collect($patterns)->contains(fn ($pattern) => preg_match('/'.$pattern.'/i', $host) === 1);
        $this->assertTrue($matches('ypa.example.com'));
        $this->assertTrue($matches('admin.example.com'));
        $this->assertFalse($matches('evil.ypa.example.com'));
        $this->assertFalse($matches('ypa.example.com.attacker.test'));
    }

    public function test_authenticated_responses_receive_browser_security_headers_and_no_store(): void
    {
        $request = Request::create('https://ypa.test/members');
        $request->setUserResolver(fn () => (object) ['id' => 1]);
        $response = (new SecurityHeaders)->handle($request, fn () => new Response('Private'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
    }

    public function test_production_check_fails_when_debug_mode_is_enabled(): void
    {
        app()->instance('env', 'production');
        config([
            'app.debug' => true, 'app.url' => 'https://ypa.test',
            'app.key' => 'base64:'.base64_encode(str_repeat('x', 32)),
            'session.secure' => true, 'session.http_only' => true,
            'session.same_site' => 'lax', 'session.driver' => 'file',
            'mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.test',
            'mail.from.address' => 'support@ypa.test', 'mail.mailers.smtp.timeout' => 15,
            'trustedproxy.proxies' => [],
        ]);
        $this->artisan('ypa:production-check', ['--skip-database' => true])
            ->expectsOutput('[FAIL] Debug output is disabled')->assertExitCode(1);
    }

    public function test_production_check_can_validate_configuration_without_database_writes(): void
    {
        app()->instance('env', 'production');
        config([
            'app.debug' => false, 'app.url' => 'https://ypa.test',
            'app.key' => 'base64:'.base64_encode(str_repeat('x', 32)),
            'session.secure' => true, 'session.http_only' => true,
            'session.same_site' => 'lax', 'session.driver' => 'file',
            'mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.test',
            'mail.from.address' => 'support@ypa.test', 'mail.mailers.smtp.timeout' => 15,
            'trustedproxy.proxies' => [],
        ]);
        $this->artisan('ypa:production-check', ['--skip-database' => true])
            ->expectsOutput('Database checks skipped. This result does not establish database readiness.')
            ->assertExitCode(0);
    }

    public function test_production_check_identifies_database_failures_without_disclosing_exception_details(): void
    {
        $connection = \Mockery::mock(\Illuminate\Database\Connection::class);
        DB::shouldReceive('connection')->andReturn($connection);
        foreach ([
            1045 => 'Database authentication was rejected.',
            1044 => 'The database account cannot access the configured database.',
            1049 => 'The configured database does not exist on this server.',
            2002 => 'The database server is unavailable.',
        ] as $code => $expected) {
            $exception = new \PDOException('private-password-and-connection-details');
            $exception->errorInfo = ['HY000', $code, 'private-password-and-connection-details'];
            $connection->shouldReceive('select')->with('SELECT 1')->once()->andThrow($exception);
            $this->assertSame(1, Artisan::call('ypa:production-check'));
            $output = Artisan::output();
            $this->assertStringContainsString($expected, $output);
            $this->assertStringNotContainsString('private-password-and-connection-details', $output);
        }
    }
}
