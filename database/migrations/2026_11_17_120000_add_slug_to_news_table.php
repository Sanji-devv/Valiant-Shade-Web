<?php

use App\Models\News;
use App\ValiantShade\Support\NewsSlug;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('news')) {
            return;
        }

        if (! Schema::hasColumn('news', 'slug')) {
            Schema::table('news', function (Blueprint $table) {
                $table->string('slug', 120)->nullable()->unique()->after('title');
            });
        }

        News::query()
            ->whereNull('slug')
            ->orderBy('id')
            ->each(function (News $news): void {
                $news->slug = NewsSlug::assignUnique($news->title, (int) $news->id);
                $news->saveQuietly();
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('news') && Schema::hasColumn('news', 'slug')) {
            Schema::table('news', function (Blueprint $table) {
                $table->dropUnique(['slug']);
                $table->dropColumn('slug');
            });
        }
    }
};