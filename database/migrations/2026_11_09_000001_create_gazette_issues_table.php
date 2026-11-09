<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gazette_issues')) {
            Schema::create('gazette_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('issue_number')->unique();
            $table->string('label')->default('İlk sayı');
            $table->unsignedSmallInteger('year');
            $table->string('month', 32);
            $table->string('weekday', 32);
            $table->unsignedTinyInteger('day');
            $table->date('published_on')->nullable();
            $table->boolean('is_current')->default(false);
            $table->json('corner_columns')->nullable();
            $table->text('editorial_note')->nullable();
            $table->timestamps();
            });
        }

        if (! Schema::hasColumn('news', 'gazette_issue_id')) {
            Schema::table('news', function (Blueprint $table) {
                $table->foreignId('gazette_issue_id')->nullable()->after('news_type')->constrained('gazette_issues')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            if (Schema::hasColumn('news', 'gazette_issue_id')) {
                $table->dropConstrainedForeignId('gazette_issue_id');
            }
        });

        Schema::dropIfExists('gazette_issues');
    }
};
