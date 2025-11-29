<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->id(); // primary key
            $table->bigInteger('follower_id'); // user who follows
            $table->bigInteger('following_id'); // user being followed
            $table->timestamps();

            $table->unique(['follower_id', 'following_id']);

            // foreign keys with signed BIGINT
            $table->foreign('follower_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('following_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
