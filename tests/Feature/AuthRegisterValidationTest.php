<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\AuthController;
use App\Http\Requests\RegisterUserRequest;
use App\Services\AuthService;
use Mockery;
use Tests\TestCase;

class AuthRegisterValidationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_register_only_passes_public_fields_to_the_repository(): void
    {
        $service = Mockery::mock(AuthService::class);
        $service->shouldReceive('register')
            ->once()
            ->with([
                'name' => 'Demo User',
                'email' => 'demo@example.com',
                'password' => 'secret123',
            ])
            ->andReturn([
                'access_token' => 'token',
                'token_type' => 'bearer',
                'expires_in' => 7200,
            ]);

        $request = Mockery::mock(RegisterUserRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn([
                'name' => 'Demo User',
                'email' => 'demo@example.com',
                'password' => 'secret123',
            ]);

        $controller = new AuthController($service);
        $response = $controller->register($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('token', $response->getData(true)['data']['access_token']);
    }
}
