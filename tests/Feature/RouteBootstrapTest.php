<?php

namespace Tests\Feature;

use Tests\TestCase;

class RouteBootstrapTest extends TestCase
{
    public function test_route_list_builds_successfully(): void
    {
        // Bat lỗi bootstrap route som nếu import/controller bị vo.
        $this->artisan('route:list')
            ->assertExitCode(0);
    }
}
