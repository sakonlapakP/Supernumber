<?php

namespace Tests\Feature;

use Tests\TestCase;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SEOValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A list of titles found during the test to check for uniqueness.
     *
     * @var array
     */
    protected static $observedTitles = [];

    /**
     * Test SEO elements for the Homepage.
     */
    public function test_homepage_seo()
    {
        $url = route('home');
        $response = $this->get($url);
        $response->assertStatus(200);

        $this->validateSEO($response, $url);
    }

    /**
     * Test SEO elements for the Numbers Index page.
     */
    public function test_numbers_page_seo()
    {
        $url = route('numbers.index');
        $response = $this->get($url);
        $response->assertStatus(200);

        $this->validateSEO($response, $url);
    }

    /**
     * Test SEO elements for the Articles Index page.
     */
    public function test_articles_index_seo()
    {
        // Articles page might be empty but table should exist
        $url = route('articles.index');
        $response = $this->get($url);
        $response->assertStatus(200);

        $this->validateSEO($response, $url);
    }

    /**
     * Test SEO elements for the Evaluate page.
     */
    public function test_evaluate_page_seo()
    {
        // Provide a sample phone number to avoid redirect
        $url = route('evaluate', ['phone' => '0812345678']);
        $response = $this->get($url);
        $response->assertStatus(200);

        $this->validateSEO($response, $url);
    }

    /**
     * Test SEO elements for the Contact page.
     */
    public function test_contact_page_seo()
    {
        $url = route('contact');
        $response = $this->get($url);
        $response->assertStatus(200);

        $this->validateSEO($response, $url);
    }

    /**
     * Helper method to validate SEO requirements.
     */
    protected function validateSEO($response, $expectedUrl)
    {
        $html = $response->getContent();
        $crawler = new Crawler($html);

        // 1. Title Tag: Exists, and unique
        $titleElement = $crawler->filter('title');
        $this->assertTrue($titleElement->count() > 0, "Missing <title> tag on $expectedUrl");
        
        $titleText = $titleElement->text();
        
        // Dynamic Title Length Check: Evaluate page can be longer (up to 75)
        $limit = str_contains($expectedUrl, '/evaluate') ? 75 : 60;
        $this->assertLessThanOrEqual($limit, mb_strlen($titleText), "Title tag too long (> $limit chars) on $expectedUrl: '$titleText'");
        
        $this->assertNotContains($titleText, self::$observedTitles, "Duplicate Title Tag found: '$titleText' on $expectedUrl");
        self::$observedTitles[] = $titleText;

        // 2. Robots Meta
        $robotsElement = $crawler->filter('meta[name="robots"]');
        $robots = $robotsElement->count() > 0 ? $robotsElement->attr('content') : 'index, follow';
        $isNoIndex = str_contains($robots, 'noindex');

        // 3. H1 (Heading 1): Exactly one
        $h1Count = $crawler->filter('h1')->count();
        $this->assertEquals(1, $h1Count, "Page $expectedUrl MUST have exactly one <h1>, found $h1Count");

        // 4. Alt Text: Every <img> must have an alt attribute
        $crawler->filter('img')->each(function (Crawler $node) use ($expectedUrl) {
            $alt = $node->attr('alt');
            $src = $node->attr('src');
            $this->assertNotNull($alt, "Missing 'alt' attribute for image ($src) on $expectedUrl");
        });

        // 5. Canonical URL: Must exist
        $canonical = $crawler->filter('link[rel="canonical"]');
        $this->assertTrue($canonical->count() > 0, "Missing <link rel=\"canonical\"> on $expectedUrl");
        
        $canonicalHref = $canonical->attr('href');
        
        if ($isNoIndex) {
            // If noindex, canonical should point to the base tool page to consolidate equity
            // (Assuming base tool page is route('evaluate') without parameters)
            $baseUrl = strtok($expectedUrl, '?');
            $this->assertEquals($baseUrl, $canonicalHref, "Noindex page $expectedUrl should canonicalize to base URL $baseUrl. Found: $canonicalHref");
        } else {
            // If indexed, canonical should match the specific URL
            $this->assertEquals($expectedUrl, $canonicalHref, "Canonical URL mismatch on $expectedUrl. Found: $canonicalHref");
        }
    }
}
