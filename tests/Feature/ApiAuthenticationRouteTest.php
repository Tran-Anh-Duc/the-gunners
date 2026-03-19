<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Tests\TestCase;

class ApiAuthenticationRouteTest extends TestCase
{
    public function test_every_business_api_route_requires_jwt_except_explicit_public_routes(): void
    {
        $publicRoutes = [
            'api/auth/login',
            'api/auth/register',
            'api/test',
        ];

        /** @var array<int, Route> $routes */
        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn (Route $route) => str_starts_with($route->uri(), 'api/'))
            ->values()
            ->all();

        foreach ($routes as $route) {
            if (in_array($route->uri(), $publicRoutes, true)) {
                continue;
            }

            $middleware = $route->gatherMiddleware();

            $this->assertContains(
                'jwt',
                $middleware,
                sprintf('Route [%s] %s phải có middleware jwt.', implode('|', $route->methods()), $route->uri()),
            );
        }
    }
}
