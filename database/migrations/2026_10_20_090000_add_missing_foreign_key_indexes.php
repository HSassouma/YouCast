<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the indexes that SQLite does not create automatically.
 *
 * MySQL and PostgreSQL create an index for every foreign key column, but
 * SQLite does not. These foreign keys are used to filter activities, ideas,
 * teams, registrations and audit entries, so the missing indexes turn every
 * one of those lookups into a full table scan.
 *
 * Only indexes are added here: no column is altered and no data is touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table): void {
            $table->index('created_by');
            $table->index('source_idea_id');
        });

        Schema::table('challenge_ideas', function (Blueprint $table): void {
            $table->index('author_id');
            $table->index('reviewer_id');
        });

        Schema::table('teams', function (Blueprint $table): void {
            $table->index('leader_id');
        });

        Schema::table('team_members', function (Blueprint $table): void {
            $table->index('user_id');
        });

        Schema::table('registrations', function (Blueprint $table): void {
            $table->index('team_id');
        });

        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
        });

        Schema::table('registrations', function (Blueprint $table): void {
            $table->dropIndex(['team_id']);
        });

        Schema::table('team_members', function (Blueprint $table): void {
            $table->dropIndex(['user_id']);
        });

        Schema::table('teams', function (Blueprint $table): void {
            $table->dropIndex(['leader_id']);
        });

        Schema::table('challenge_ideas', function (Blueprint $table): void {
            $table->dropIndex(['reviewer_id']);
            $table->dropIndex(['author_id']);
        });

        Schema::table('activities', function (Blueprint $table): void {
            $table->dropIndex(['source_idea_id']);
            $table->dropIndex(['created_by']);
        });
    }
};
