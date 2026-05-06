<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ArticleController extends Controller
{
    public function index()
    {
        return Article::latest()->paginate(20);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'meta_description' => 'nullable|string',
            'keywords' => 'nullable|string',
            'lsi_keywords' => 'nullable|string',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
            'image_guidelines' => 'nullable|string', // รับเป็น JSON string จาก FormData
            'cover_landscape' => 'nullable|image|max:2048',
            'cover_square' => 'nullable|image|max:2048',
        ]);

        $data = $validated;
        unset($data['cover_landscape'], $data['cover_square']);
        
        if ($request->hasFile('cover_landscape')) {
            $data['cover_image_landscape_path'] = $request->file('cover_landscape')->store('articles', 'public');
            $data['cover_image_path'] = $data['cover_image_landscape_path']; // ใช้เป็นรูปหลักด้วย
        }

        if ($request->hasFile('cover_square')) {
            $data['cover_image_square_path'] = $request->file('cover_square')->store('articles', 'public');
        }
        
        if (isset($data['image_guidelines'])) {
            $data['image_guidelines'] = json_decode($data['image_guidelines'], true);
        }

        $article = Article::create([
            ...$data,
            'slug' => Str::slug($data['title']) . '-' . time(),
            'author_user_id' => $request->user()->id,
            'published_at' => $data['published_at'] ?? ($request->is_published ? now() : null),
        ]);

        return response()->json($article, 201);
    }

    public function show(Article $article)
    {
        return $article;
    }

    public function update(Request $request, Article $article)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'meta_description' => 'nullable|string',
            'keywords' => 'nullable|string',
            'lsi_keywords' => 'nullable|string',
            'is_published' => 'boolean',
            'published_at' => 'nullable|date',
            'image_guidelines' => 'nullable|string',
            'cover_landscape' => 'nullable|image|max:2048',
            'cover_square' => 'nullable|image|max:2048',
        ]);

        $data = $validated;
        unset($data['cover_landscape'], $data['cover_square']);

        if ($request->hasFile('cover_landscape')) {
            if ($article->cover_image_landscape_path) {
                Storage::disk('public')->delete($article->cover_image_landscape_path);
            }
            $data['cover_image_landscape_path'] = $request->file('cover_landscape')->store('articles', 'public');
            $data['cover_image_path'] = $data['cover_image_landscape_path'];
        }

        if ($request->hasFile('cover_square')) {
            if ($article->cover_image_square_path) {
                Storage::disk('public')->delete($article->cover_image_square_path);
            }
            $data['cover_image_square_path'] = $request->file('cover_square')->store('articles', 'public');
        }

        if (isset($data['image_guidelines'])) {
            $data['image_guidelines'] = json_decode($data['image_guidelines'], true);
        }

        if ($request->is_published && !$article->is_published && !$request->published_at) {
            $data['published_at'] = now();
        }

        $article->update($data);

        return response()->json($article);
    }

    public function destroy(Article $article)
    {
        if ($article->cover_image_landscape_path) {
            Storage::disk('public')->delete($article->cover_image_landscape_path);
        }
        if ($article->cover_image_square_path) {
            Storage::disk('public')->delete($article->cover_image_square_path);
        }
        $article->delete();
        return response()->json(['message' => 'Article deleted']);
    }
}
