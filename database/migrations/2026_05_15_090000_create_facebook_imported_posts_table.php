<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facebook_imported_posts', function (Blueprint $table) {
            $table->id();
            $table->string('facebook_post_id')->unique();
            $table->string('source_node_id')->nullable()->index();
            $table->string('source_node_type', 32)->default('page');
            $table->longText('message')->nullable();
            $table->text('story')->nullable();
            $table->text('permalink_url')->nullable();
            $table->text('full_picture')->nullable();
            $table->json('attachments_json')->nullable();
            $table->json('raw_json')->nullable();
            $table->timestamp('facebook_created_time')->nullable()->index();
            $table->timestamp('imported_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['source_node_type', 'facebook_created_time'], 'fb_imported_posts_source_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facebook_imported_posts');
    }
};
