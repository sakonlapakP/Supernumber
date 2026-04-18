<?php

namespace Tests\Unit;

use App\Models\PhoneNumber;
use PHPUnit\Framework\TestCase;

class PhoneNumberSupportedTopicIconsTest extends TestCase
{
    public function test_it_returns_supported_topic_icons_for_a_phone_number(): void
    {
        $icons = PhoneNumber::buildSupportedTopicIcons('0812345678');

        $this->assertIsArray($icons);
        $this->assertNotEmpty($icons);
        $this->assertContains([
            'topic' => 'การงาน/ความก้าวหน้า',
            'icon' => '💼',
        ], $icons);
    }

    public function test_it_returns_empty_array_for_invalid_phone_number(): void
    {
        $this->assertSame([], PhoneNumber::buildSupportedTopicIcons('123'));
    }
}
