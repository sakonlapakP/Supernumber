<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('line_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 80)->nullable();
            $table->string('source_type', 40)->nullable();
            $table->string('group_id', 255)->nullable();
            $table->string('room_id', 255)->nullable();
            $table->string('user_id', 255)->nullable();
            $table->string('message_type', 80)->nullable();
            $table->string('destination', 255)->nullable();
            $table->boolean('signature_valid')->nullable();
            $table->json('headers')->nullable();
            $table->json('payload')->nullable();
            $table->longText('raw_body')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index(['group_id', 'received_at']);
            $table->index(['event_type', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('line_webhook_events');
    }
};
