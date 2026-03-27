<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

class AdminLogViewer
{
    private const DEFAULT_FILE = 'laravel.log';
    private const MAX_READ_BYTES = 256000;
    private const DISPLAY_TIMEZONE = 'Asia/Bangkok';

    /**
     * @return array<int, array{name: string, path: string, size: int, modified_at: int|null}>
     */
    public function availableFiles(): array
    {
        $directory = storage_path('logs');

        if (! File::exists($directory)) {
            return [];
        }

        return collect(File::files($directory))
            ->filter(fn (\SplFileInfo $file) => strtolower($file->getExtension()) === 'log')
            ->map(fn (\SplFileInfo $file) => [
                'name' => $file->getFilename(),
                'path' => $file->getPathname(),
                'size' => $file->getSize() ?: 0,
                'modified_at' => $file->getMTime() ?: null,
            ])
            ->sortByDesc('modified_at')
            ->values()
            ->all();
    }

    /**
     * @return array{name: string, path: string, size: int, modified_at: int|null, exists: bool, readable: bool}
     */
    public function resolveFile(?string $selectedFile): array
    {
        $name = trim((string) $selectedFile);
        $name = basename($name);

        if ($name === '' || preg_match('/^[A-Za-z0-9._-]+\.log$/', $name) !== 1) {
            $name = self::DEFAULT_FILE;
        }

        $path = storage_path('logs/' . $name);
        $exists = File::exists($path);
        $readable = $exists && File::isReadable($path);

        return [
            'name' => $name,
            'path' => $path,
            'size' => $exists ? (int) File::size($path) : 0,
            'modified_at' => $exists ? (@filemtime($path) ?: null) : null,
            'exists' => $exists,
            'readable' => $readable,
        ];
    }

    public function readTail(string $path, int $maxBytes = self::MAX_READ_BYTES): string
    {
        if (! File::exists($path) || ! File::isReadable($path)) {
            return '';
        }

        $size = (int) File::size($path);
        $readBytes = min($size, $maxBytes);

        if ($readBytes <= 0) {
            return '';
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return '';
        }

        if ($readBytes < $size) {
            fseek($handle, -$readBytes, SEEK_END);
        }

        $content = (string) fread($handle, $readBytes);
        fclose($handle);

        if ($readBytes < $size) {
            $firstNewline = strpos($content, "\n");
            $content = $firstNewline === false
                ? $content
                : substr($content, $firstNewline + 1);
        }

        return $content;
    }

    /**
     * @return array<int, array{timestamp: string|null, date: string|null, environment: string|null, level: string|null, message: string, raw: string, raw_display: string}>
     */
    public function parseEntries(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $entries = [];
        $current = null;

        foreach ($lines as $line) {
            if (preg_match('/^\[(?<timestamp>[^\]]+)\]\s+(?<environment>[A-Za-z0-9_.-]+)\.(?<level>[A-Z]+):\s*(?<message>.*)$/', $line, $matches) === 1) {
                if ($current !== null) {
                    $entries[] = $current;
                }

                $timestamp = trim((string) ($matches['timestamp'] ?? ''));
                $normalizedTimestamp = $this->normalizeTimestamp($timestamp);

                $current = [
                    'timestamp' => $normalizedTimestamp['timestamp'],
                    'date' => $normalizedTimestamp['date'],
                    'environment' => trim((string) ($matches['environment'] ?? '')) ?: null,
                    'level' => trim((string) ($matches['level'] ?? '')) ?: null,
                    'message' => trim((string) ($matches['message'] ?? '')),
                    'raw' => $line,
                    'raw_display' => sprintf(
                        '[%s] %s.%s: %s',
                        $normalizedTimestamp['timestamp'] ?? $timestamp,
                        trim((string) ($matches['environment'] ?? '')),
                        trim((string) ($matches['level'] ?? '')),
                        trim((string) ($matches['message'] ?? ''))
                    ),
                ];

                continue;
            }

            if ($current === null) {
                $current = [
                    'timestamp' => null,
                    'date' => null,
                    'environment' => null,
                    'level' => null,
                    'message' => trim($line),
                    'raw' => $line,
                    'raw_display' => $line,
                ];
                continue;
            }

            $current['raw'] .= PHP_EOL . $line;
            $current['raw_display'] .= PHP_EOL . $line;
        }

        if ($current !== null) {
            $entries[] = $current;
        }

        return array_reverse($entries);
    }

    /**
     * @param  array<int, array{timestamp: string|null, date: string|null, environment: string|null, level: string|null, message: string, raw: string, raw_display: string}>  $entries
     * @return array<int, array{timestamp: string|null, date: string|null, environment: string|null, level: string|null, message: string, raw: string, raw_display: string}>
     */
    public function filterEntries(array $entries, ?string $level, ?string $date, ?string $search): array
    {
        $normalizedLevel = trim(strtoupper((string) $level));
        $normalizedDate = trim((string) $date);
        $normalizedSearch = mb_strtolower(trim((string) $search));

        return array_values(array_filter($entries, function (array $entry) use ($normalizedLevel, $normalizedDate, $normalizedSearch): bool {
            if ($normalizedLevel !== '' && strtoupper((string) ($entry['level'] ?? '')) !== $normalizedLevel) {
                return false;
            }

            if ($normalizedDate !== '' && ($entry['date'] ?? null) !== $normalizedDate) {
                return false;
            }

            if ($normalizedSearch !== '' && ! str_contains(mb_strtolower($entry['raw']), $normalizedSearch)) {
                return false;
            }

            return true;
        }));
    }

    /**
     * @param  array<int, array{timestamp: string|null, date: string|null, environment: string|null, level: string|null, message: string, raw: string, raw_display: string}>  $entries
     * @return array<int, string>
     */
    public function availableLevels(array $entries): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn (array $entry) => $entry['level'] ?? null,
            $entries
        ))));
    }

    /**
     * @param  array<int, array{timestamp: string|null, date: string|null, environment: string|null, level: string|null, message: string, raw: string, raw_display: string}>  $entries
     * @return array<int, string>
     */
    public function availableDates(array $entries): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn (array $entry) => $entry['date'] ?? null,
            $entries
        ))));
    }

    public function clearFile(string $path): void
    {
        File::put($path, '');
    }

    /**
     * @return array{timestamp: string|null, date: string|null}
     */
    private function normalizeTimestamp(string $timestamp): array
    {
        if ($timestamp === '') {
            return ['timestamp' => null, 'date' => null];
        }

        try {
            $dateTime = Carbon::parse($timestamp, 'UTC')
                ->setTimezone(self::DISPLAY_TIMEZONE);

            return [
                'timestamp' => $dateTime->format('Y-m-d H:i:s') . ' ICT',
                'date' => $dateTime->format('Y-m-d'),
            ];
        } catch (\Throwable) {
            return [
                'timestamp' => $timestamp,
                'date' => preg_match('/^\d{4}-\d{2}-\d{2}/', $timestamp) === 1 ? substr($timestamp, 0, 10) : null,
            ];
        }
    }
}
