<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleShare extends Model
{
    protected $fillable = ['article_id', 'platform', 'shared_at'];

    protected $casts = [
        'shared_at' => 'datetime',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
