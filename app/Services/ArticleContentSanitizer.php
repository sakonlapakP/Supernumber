<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

class ArticleContentSanitizer
{
    /**
     * @var array<int, string>
     */
    private const ALLOWED_TAGS = [
        'a',
        'b',
        'blockquote',
        'br',
        'code',
        'div',
        'em',
        'figcaption',
        'figure',
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
        'hr',
        'i',
        'img',
        'li',
        'ol',
        'p',
        'pre',
        'small',
        'span',
        'strong',
        'sub',
        'sup',
        'table',
        'tbody',
        'td',
        'th',
        'thead',
        'tr',
        'u',
        'ul',
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading', 'decoding'],
        'table' => ['border', 'cellpadding', 'cellspacing'],
        'td' => ['colspan', 'rowspan'],
        'th' => ['colspan', 'rowspan', 'scope'],
    ];

    /**
     * @var array<int, string>
     */
    private const STRIP_TAGS = [
        'script',
        'style',
        'iframe',
        'object',
        'embed',
        'form',
        'input',
        'button',
        'textarea',
        'select',
        'option',
        'svg',
        'math',
        'meta',
        'link',
        'noscript',
        'template',
    ];

    public function sanitize(string $html): string
    {
        $html = trim($html);

        if ($html === '') {
            return '';
        }

        $previousState = libxml_use_internal_errors(true);

        try {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $wrappedHtml = '<!DOCTYPE html><html><body><div id="article-content-root">' . $this->prepareForDom($html) . '</div></body></html>';
            $dom->loadHTML($wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

            $container = $dom->getElementById('article-content-root');

            if (! $container instanceof DOMElement) {
                return '';
            }

            $this->sanitizeChildren($container);

            $output = '';

            foreach (iterator_to_array($container->childNodes) as $child) {
                $output .= $dom->saveHTML($child) ?: '';
            }

            return trim($output);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousState);
        }
    }

    private function prepareForDom(string $html): string
    {
        if (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        }

        return htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    private function sanitizeChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);

                if (in_array($tag, self::STRIP_TAGS, true)) {
                    $child->parentNode?->removeChild($child);
                    continue;
                }

                if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                    $this->sanitizeChildren($child);
                    $this->unwrapElement($child);
                    continue;
                }

                $this->sanitizeAttributes($child, $tag);
                $this->sanitizeChildren($child);
                continue;
            }

            if ($child instanceof DOMText) {
                continue;
            }

            if ($child->hasChildNodes()) {
                $this->sanitizeChildren($child);
            }
        }
    }

    private function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $allowed = self::ALLOWED_ATTRIBUTES[$tag] ?? [];

        foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
            $name = strtolower($attribute->name);

            if (str_starts_with($name, 'on') || $name === 'style') {
                $element->removeAttributeNode($attribute);
                continue;
            }

            if (! in_array($name, $allowed, true)) {
                $element->removeAttributeNode($attribute);
            }
        }

        if ($tag === 'a') {
            $this->sanitizeLink($element);
        }

        if ($tag === 'img') {
            $this->sanitizeImage($element);
        }
    }

    private function sanitizeLink(DOMElement $element): void
    {
        $href = trim((string) $element->getAttribute('href'));

        if ($href === '' || ! $this->isSafeUrl($href, ['http', 'https', 'mailto', 'tel'])) {
            $element->removeAttribute('href');
        } else {
            $element->setAttribute('href', $href);
        }

        $target = strtolower(trim((string) $element->getAttribute('target')));

        if ($target === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
            $element->setAttribute('target', '_blank');
            return;
        }

        $element->removeAttribute('target');
        $element->removeAttribute('rel');
    }

    private function sanitizeImage(DOMElement $element): void
    {
        $src = trim((string) $element->getAttribute('src'));

        if ($src === '' || ! $this->isSafeUrl($src, ['http', 'https'])) {
            $this->unwrapElement($element);

            return;
        }

        $element->setAttribute('src', $src);

        foreach (['alt', 'title'] as $attribute) {
            if ($element->hasAttribute($attribute)) {
                $element->setAttribute($attribute, trim((string) $element->getAttribute($attribute)));
            }
        }

        foreach (['width', 'height'] as $attribute) {
            if (! $element->hasAttribute($attribute)) {
                continue;
            }

            $value = preg_replace('/[^\d]/', '', (string) $element->getAttribute($attribute));

            if ($value === '') {
                $element->removeAttribute($attribute);
                continue;
            }

            $element->setAttribute($attribute, $value);
        }

        $element->setAttribute('loading', 'lazy');
        $element->setAttribute('decoding', 'async');
    }

    /**
     * @param array<int, string> $allowedSchemes
     */
    private function isSafeUrl(string $value, array $allowedSchemes): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        if (
            str_starts_with($value, '/')
            || str_starts_with($value, '#')
            || str_starts_with($value, '?')
            || str_starts_with($value, '//')
        ) {
            return true;
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        if ($scheme === '') {
            return true;
        }

        return in_array($scheme, $allowedSchemes, true);
    }

    private function unwrapElement(DOMElement $element): void
    {
        $parent = $element->parentNode;

        if (! $parent) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }

        $parent->removeChild($element);
    }
}
