<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained('rooms')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type')->default('text'); // text, image, audio, video, file
            $table->text('content')->nullable();
            $table->string('media_url')->nullable();
            $table->integer('media_duration')->nullable();
            $table->string('media_thumbnail')->nullable();
            $table->json('reactions')->nullable();
            $table->foreignId('reply_to')->nullable()->constrained('messages')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
