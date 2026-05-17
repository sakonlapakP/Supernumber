<?php

namespace Tests\Feature;

use Tests\TestCase;

class RobotsTxtTest extends TestCase
{
    public function test_robots_txt_allows_facebook_scrapers(): void
    {
        $response = $this->get('/robots.txt');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
        $response->assertSee("User-agent: facebookexternalhit\nDisallow:", false);
        $response->assertSee("User-agent: Facebot\nDisallow:", false);
        $response->assertSee('Sitemap: https://supernumber.co.th/sitemap.xml', false);
    }
}
