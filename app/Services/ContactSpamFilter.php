<?php

namespace App\Services;

class ContactSpamFilter
{
    private const URL_PATTERN = '/(?:https?:\/\/|www\.|[a-z0-9][a-z0-9-]{1,62}\.(?:com|net|org|io|co|info|biz|xyz|top|site|online|blog|link|me|cc|ly|work|shop|pro|ru|cn|th)\b)/iu';

    private const ADULT_KEYWORDS = [
        'anal',
        'blowjob',
        'cock',
        'escort',
        'gangbang',
        'nude',
        'porn',
        'pussy',
        'xxx',
    ];

    private const MARKETING_KEYWORDS = [
        'backlink',
        'classified',
        'free traffic',
        'guest post',
        'link building',
        'promote your site',
        'ranking',
        'search engine',
        'seo',
        'submitter',
        'traffic',
    ];

    private const AUTOMATION_KEYWORDS = [
        'affiliate offers',
        'ai employee',
        'autopilot',
        'command center',
        'infinite prompt loop',
        'prompt loop',
        'setup chaos',
        'twitter growth',
        'world talks about the ai revolution',
    ];

    private const SERVICE_SPAM_KEYWORDS = [
        'qr code',
        'link shortener',
        'short link',
        'instagram link',
        'dynamic qr',
        'anonymous link',
        'anonymous instagram',
        'link generator',
        'url shortener',
        'bit.ly',
        'tinyurl',
        'shorturl',
    ];

    private const CRYPTO_PAYMENT_KEYWORDS = [
        'payment processor',
        'payment gateway',
        'online payment',
        'payment processing',
        'crypto',
        'bitcoin',
        'ethereum',
        'blockchain',
    ];

    /**
     * @return array{blocked: bool, score: int, reasons: array<int, string>}
     */
    public function inspect(string $name, string $phone, string $message): array
    {
        $normalizedMessage = $this->normalize($message);
        $score = 0;
        $reasons = [];

        $urlCount = $this->countMatches(self::URL_PATTERN, $normalizedMessage);
        if ($urlCount >= 2) {
            $score += 4;
            $reasons[] = 'multiple_links';
        } elseif ($urlCount === 1) {
            $score += 2;
            $reasons[] = 'contains_link';
        }

        $adultHits = $this->countKeywordHits($normalizedMessage, self::ADULT_KEYWORDS);
        if ($adultHits > 0) {
            $score += 6;
            $reasons[] = 'adult_keywords';
        }

        $marketingHits = $this->countKeywordHits($normalizedMessage, self::MARKETING_KEYWORDS);
        if ($marketingHits >= 2) {
            $score += 4;
            $reasons[] = 'marketing_pitch';
        } elseif ($marketingHits === 1) {
            $score += 2;
            $reasons[] = 'marketing_keyword';
        }

        $automationHits = $this->countKeywordHits($normalizedMessage, self::AUTOMATION_KEYWORDS);
        if ($automationHits >= 2) {
            $score += 4;
            $reasons[] = 'automation_pitch';
        } elseif ($automationHits === 1) {
            $score += 2;
            $reasons[] = 'automation_keyword';
        }

        $serviceSpamHits = $this->countKeywordHits($normalizedMessage, self::SERVICE_SPAM_KEYWORDS);
        if ($serviceSpamHits >= 3) {
            $score += 5;
            $reasons[] = 'service_spam_pitch';
        } elseif ($serviceSpamHits >= 2) {
            $score += 3;
            $reasons[] = 'service_spam_keywords';
        } elseif ($serviceSpamHits === 1) {
            $score += 1;
            $reasons[] = 'service_spam_mention';
        }

        $cryptoHits = $this->countKeywordHits($normalizedMessage, self::CRYPTO_PAYMENT_KEYWORDS);
        if ($cryptoHits >= 2) {
            $score += 4;
            $reasons[] = 'crypto_payment_pitch';
        } elseif ($cryptoHits === 1) {
            $score += 2;
            $reasons[] = 'crypto_payment_mention';
        }

        if (! preg_match('/\p{Thai}/u', $message) && mb_strlen($message) >= 280) {
            $score += 2;
            $reasons[] = 'long_non_thai_message';
        }

        if ($this->hasExcessiveCapitals($message)) {
            $score += 2;
            $reasons[] = 'excessive_capitals';
        }

        if ($this->looksGeneratedName($name)) {
            $score += 2;
            $reasons[] = 'generated_name';
        }

        if ($this->hasSuspiciousPhone($phone)) {
            $score += 1;
            $reasons[] = 'suspicious_phone';
        }

        return [
            'blocked' => $adultHits > 0 || $score >= 5,
            'score' => $score,
            'reasons' => array_values(array_unique($reasons)),
        ];
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');

        return preg_replace('/\s+/u', ' ', trim($value)) ?? '';
    }

    /**
     * @param  array<int, string>  $keywords
     */
    private function countKeywordHits(string $message, array $keywords): int
    {
        $hits = 0;

        foreach ($keywords as $keyword) {
            if (str_contains($message, $keyword)) {
                $hits++;
            }
        }

        return $hits;
    }

    private function countMatches(string $pattern, string $message): int
    {
        $count = preg_match_all($pattern, $message, $matches);

        return $count === false ? 0 : $count;
    }

    private function looksGeneratedName(string $name): bool
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            return false;
        }

        // Check for consecutive repeated digits (like 676374)
        if (preg_match('/\d{4,}/', $trimmed)) {
            return true;
        }

        // Check for 2+ consecutive digits scattered in name
        if (preg_match('/\d{2,}/', $trimmed)) {
            return true;
        }

        // Check for gibberish pattern: no spaces, mix of alpha and numbers
        if (! preg_match('/\s/u', $trimmed) && preg_match('/[a-z]/iu', $trimmed) && preg_match('/\d/u', $trimmed)) {
            return true;
        }


        return false;
    }

    private function hasExcessiveCapitals(string $message): bool
    {
        $letters = preg_replace('/[^a-z]/iu', '', $message) ?? '';
        if (mb_strlen($letters) < 50) {
            return false;
        }

        $capitals = mb_strlen(preg_replace('/[^A-Z]/', '', $message));
        $ratio = $capitals / mb_strlen($letters);

        return $ratio > 0.4;
    }

    private function hasSuspiciousPhone(string $phone): bool
    {
        return preg_match('/(\d)\1{6,}/', $phone) === 1;
    }
}
