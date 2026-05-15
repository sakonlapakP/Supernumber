<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FacebookImportedPost;
use Illuminate\Http\Request;

class FacebookImportedPostController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $search = trim((string) ($validated['q'] ?? ''));
        $fromDate = trim((string) ($validated['from'] ?? ''));
        $toDate = trim((string) ($validated['to'] ?? ''));
        $perPage = (int) ($validated['per_page'] ?? 30);

        $paginator = FacebookImportedPost::query()
            ->when($search !== '', function ($query) use ($search) {
                $searchTerm = mb_strtolower($search);
                $query->where(function ($innerQuery) use ($search, $searchTerm) {
                    $innerQuery->whereRaw('LOWER(COALESCE(message, "")) like ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw('LOWER(COALESCE(story, "")) like ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw('LOWER(facebook_post_id) like ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw('LOWER(COALESCE(permalink_url, "")) like ?', ['%' . $searchTerm . '%'])
                        ->orWhereRaw('CAST(id AS CHAR) like ?', ['%' . $search . '%']);
                });
            })
            ->when($fromDate !== '', function ($query) use ($fromDate) {
                $query->whereDate('facebook_created_time', '>=', $fromDate);
            })
            ->when($toDate !== '', function ($query) use ($toDate) {
                $query->whereDate('facebook_created_time', '<=', $toDate);
            })
            ->orderByDesc('facebook_created_time')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->query());

        $paginator->getCollection()->transform(function (FacebookImportedPost $post) {
            return [
                'id' => $post->id,
                'facebook_post_id' => $post->facebook_post_id,
                'message' => $post->message,
                'story' => $post->story,
                'permalink_url' => $post->permalink_url,
                'facebook_created_time' => optional($post->facebook_created_time)?->toIso8601String(),
                'last_synced_at' => optional($post->last_synced_at)?->toIso8601String(),
                'image_url' => $this->resolveImageUrl($post),
            ];
        });

        return response()->json($paginator);
    }

    private function resolveImageUrl(FacebookImportedPost $post): ?string
    {
        $imageUrl = trim((string) ($post->full_picture ?? ''));

        if ($imageUrl !== '') {
            return $imageUrl;
        }

        $attachments = is_array($post->attachments_json) ? $post->attachments_json : [];

        if (! isset($attachments['data']) || ! is_array($attachments['data'])) {
            return null;
        }

        foreach ($attachments['data'] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $candidate = trim((string) data_get($item, 'media.image.src', ''));
            if ($candidate === '') {
                $candidate = trim((string) data_get($item, 'media.source', ''));
            }
            if ($candidate === '') {
                $candidate = trim((string) data_get($item, 'url', ''));
            }

            if ($candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }
}
