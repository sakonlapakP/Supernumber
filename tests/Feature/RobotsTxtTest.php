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
        $response->assertSee("User-agent: facebookexternalhit\nAllow: /", false);
        $response->assertSee("User-agent: Facebot\nAllow: /", false);
        $response->assertSee('Sitemap: https://supernumber.co.th/sitemap.xml', false);
    }
}
