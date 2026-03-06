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
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->string('post_title');
            $table->longText('post_content');
            $table->string('post_image')->nullable();
           
            
            $table->string('meta_description');
            $table->string('meta_title');
            $table->string('meta_keywords');
            $table->string('post_title_slug');
            $table->string('category_id');
            $table->string('post_status')->default('published');
            $table->dateTime('scheduled_at')->nullable();
            $table->foreignId('user_id')->constrained('users')->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
