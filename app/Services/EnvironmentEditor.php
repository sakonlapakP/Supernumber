<?php

namespace App\Services;

use RuntimeException;

class EnvironmentEditor
{
    public function __construct(
        private readonly ?string $path = null,
    ) {
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, string>
     */
    public function getMany(array $keys): array
    {
        $values = $this->parseFile();
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = (string) ($values[$key] ?? '');
        }

        return $result;
    }

    /**
     * @param  array<string, string>  $values
     */
    public function setMany(array $values): void
    {
        $path = $this->resolvePath();
        $contents = file_exists($path) ? (string) file_get_contents($path) : '';

        if ($contents === '' && ! file_exists($path)) {
            throw new RuntimeException('.env file was not found.');
        }

        foreach ($values as $key => $value) {
            $line = $key . '=' . $this->formatValue($value);
            $pattern = '/^' . preg_quote($key, '/') . '=.*$/m';

            if (preg_match($pattern, $contents) === 1) {
                $contents = (string) preg_replace($pattern, $line, $contents, 1);
                continue;
            }

            $contents = rtrim($contents, "\r\n") . PHP_EOL . $line . PHP_EOL;
        }

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to write updated values to .env.');
        }
    }

    /**
     * @return array<string, string>
     */
    private function parseFile(): array
    {
        $path = $this->resolvePath();

        if (! file_exists($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            return [];
        }

        $values = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '' || str_starts_with($trimmed, '#') || ! str_contains($line, '=')) {
                continue;
            }

            [$key, $rawValue] = explode('=', $line, 2);
            $values[trim($key)] = $this->parseValue($rawValue);
        }

        return $values;
    }

    private function parseValue(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $quote = $value[0];
            $inner = substr($value, 1, -1);

            if ($quote === '"') {
                return stripcslashes($inner);
            }

            return str_replace(["\\'", '\\\\'], ["'", '\\'], $inner);
        }

        return $value;
    }

    private function formatValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/\s|#|=|["\']/', $value) === 1) {
            return '"' . addcslashes($value, "\\\"") . '"';
        }

        return $value;
    }

    private function resolvePath(): string
    {
        return $this->path ?? base_path('.env');
    }
}
