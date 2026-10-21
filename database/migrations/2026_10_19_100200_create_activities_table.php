<?php

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('source_idea_id')->nullable()->constrained('challenge_ideas')->nullOnDelete();
            $table->string('type', 32)->default(ActivityType::Event->value)->index();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('category')->nullable()->index();
            $table->string('location')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('registration_opens_at')->nullable();
            $table->timestamp('registration_deadline')->nullable();
            $table->unsignedInteger('capacity')->nullable();
            $table->string('status', 32)->default(ActivityStatus::Draft->value)->index();
            $table->unsignedInteger('min_team_size')->nullable();
            $table->unsignedInteger('max_team_size')->nullable();
            $table->boolean('cancellation_allowed')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
