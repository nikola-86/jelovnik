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
        Schema::create('slack_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_choice_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slack_notifications');
    }
};
