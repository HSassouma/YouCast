<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->changeUserForeignKey('challenge_ideas', 'author_id', true);
        $this->changeUserForeignKey('activities', 'created_by', true);
        $this->changeUserForeignKey('teams', 'leader_id', true);
        $this->changeUserForeignKey('conversations', 'student_id', true);
        $this->changeUserForeignKey('messages', 'user_id', true);
    }

    public function down(): void
    {
        $this->changeUserForeignKey('challenge_ideas', 'author_id', false);
        $this->changeUserForeignKey('activities', 'created_by', false);
        $this->changeUserForeignKey('teams', 'leader_id', false);
        $this->changeUserForeignKey('conversations', 'student_id', false);
        $this->changeUserForeignKey('messages', 'user_id', false);
    }

    private function changeUserForeignKey(string $tableName, string $columnName, bool $anonymize): void
    {
        Schema::table($tableName, function (Blueprint $table) use ($columnName): void {
            $table->dropForeign([$columnName]);
        });

        Schema::table($tableName, function (Blueprint $table) use ($columnName, $anonymize): void {
            $table->unsignedBigInteger($columnName)->nullable()->change();

            $foreign = $table->foreign($columnName)->references('id')->on('users');

            if ($anonymize) {
                $foreign->nullOnDelete();
            } else {
                $foreign->restrictOnDelete();
            }
        });
    }
};
