<?php

namespace Tests\Feature;

use Tests\TestCase;

class RouteBootstrapTest extends TestCase
{
    public function test_route_list_builds_successfully(): void
    {
        $this->artisan('route:list')
            ->assertExitCode(0);
    }
}
