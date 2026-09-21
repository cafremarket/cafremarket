<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Tests\TestCase;

class AdminDashboardRouteTest extends TestCase
{
    public function test_admin_dashboard_is_not_captured_by_category_browse(): void
    {
        $route = app('router')->getRoutes()->match(Request::create('/admin/dashboard', 'GET'));

        $this->assertSame('admin.admin.dashboard', $route->getName());
    }

    public function test_merchant_dashboard_is_not_captured_by_category_browse(): void
    {
        $route = app('router')->getRoutes()->match(Request::create('/merchant/dashboard', 'GET'));

        $this->assertSame('merchant.dashboard', $route->getName());
    }

    public function test_category_browse_still_matches_two_segment_catalog_urls(): void
    {
        $route = app('router')->getRoutes()->match(Request::create('/electronics/phones', 'GET'));

        $this->assertSame('category.browse', $route->getName());
    }
}
