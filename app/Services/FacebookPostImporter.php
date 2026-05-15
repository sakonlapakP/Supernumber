<?php

namespace App\Services;

use App\Models\FacebookImportedPost;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class FacebookPostImporter
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function import(array $options = []): array
    {
        $nodeId = trim((string) ($options['node_id'] ?? ''));
        $accessToken = trim((string) ($options['access_token'] ?? ''));

        if ($nodeId === '') {
            throw new \InvalidArgumentException('Missing Facebook node id.');
        }

        if ($accessToken === '') {
            throw new \InvalidArgumentException('Missing Facebook access token.');
        }

        $apiVersion = trim((string) ($options['api_version'] ?? ''));
        if ($apiVersion === '') {
            $apiVersion = trim((string) config('services.facebook.graph_api_version', 'v20.0'));
        }

        $edge = trim((string) ($options['edge'] ?? ''));
        if ($edge === '') {
            $edge = 'feed';
        }

        $sourceNodeType = trim((string) ($options['source_node_type'] ?? ''));
        if ($sourceNodeType === '') {
            $sourceNodeType = 'page';
        }
        $limit = max(1, min(100, (int) ($options['limit'] ?? 100)));
        $maxPages = max(1, min(5000, (int) ($options['max_pages'] ?? 250)));
        $fields = trim((string) ($options['fields'] ?? 'id,message,story,created_time,permalink_url,full_picture,attachments{media_type,media,url,title,description,target,subattachments}'));

        $since = trim((string) ($options['since'] ?? ''));
        $until = trim((string) ($options['until'] ?? ''));

        $query = [
            'fields' => $fields,
            'limit' => $limit,
            'access_token' => $accessToken,
        ];

        if ($since !== '') {
            $query['since'] = $since;
        }

        if ($until !== '') {
            $query['until'] = $until;
        }

        $nextUrl = sprintf('https://graph.facebook.com/%s/%s/%s?%s', $apiVersion, $nodeId, $edge, http_build_query($query));

        $stats = [
            'inserted' => 0,
            'updated' => 0,
            'skipped' => 0,
            'pages_fetched' => 0,
            'rows_received' => 0,
            'next_url' => null,
        ];

        $seenUrls = [];

        while ($nextUrl !== null && $stats['pages_fetched'] < $maxPages) {
            if (isset($seenUrls[$nextUrl])) {
                break;
            }

            $seenUrls[$nextUrl] = true;

            $response = Http::acceptJson()
                ->timeout(30)
                ->get($nextUrl);

            if (! $response->successful()) {
                throw new \RuntimeException('Facebook API request failed: HTTP ' . $response->status() . ' - ' . $response->body());
            }

            $payload = $response->json();

            if (! is_array($payload)) {
                throw new \RuntimeException('Unexpected Facebook API response format.');
            }

            $rows = $payload['data'] ?? null;

            if (! is_array($rows)) {
                throw new \RuntimeException('Facebook API response does not contain a valid data array.');
            }

            $stats['pages_fetched']++;
            $stats['rows_received'] += count($rows);

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    $stats['skipped']++;
                    continue;
                }

                $facebookPostId = trim((string) ($row['id'] ?? ''));

                if ($facebookPostId === '') {
                    $stats['skipped']++;
                    continue;
                }

                $record = FacebookImportedPost::query()->firstOrNew([
                    'facebook_post_id' => $facebookPostId,
                ]);

                $isNewRecord = ! $record->exists;

                if ($isNewRecord) {
                    $record->imported_at = now();
                }

                $record->source_node_id = $nodeId;
                $record->source_node_type = $sourceNodeType;
                $record->message = $this->nullableString($row['message'] ?? null);
                $record->story = $this->nullableString($row['story'] ?? null);
                $record->permalink_url = $this->nullableString($row['permalink_url'] ?? null);
                $record->full_picture = $this->nullableString($row['full_picture'] ?? null);
                $record->attachments_json = isset($row['attachments']) && is_array($row['attachments']) ? $row['attachments'] : null;
                $record->raw_json = $row;
                $record->facebook_created_time = $this->parseFacebookTime($row['created_time'] ?? null);
                $record->last_synced_at = now();

                $record->save();

                if ($isNewRecord) {
                    $stats['inserted']++;
                } else {
                    $stats['updated']++;
                }
            }

            $nextUrl = null;

            if (isset($payload['paging']) && is_array($payload['paging'])) {
                $nextCandidate = $payload['paging']['next'] ?? null;
                if (is_string($nextCandidate) && trim($nextCandidate) !== '') {
                    $nextUrl = $nextCandidate;
                }
            }
        }

        $stats['next_url'] = $nextUrl;

        return $stats;
    }

    private function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function parseFacebookTime(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
