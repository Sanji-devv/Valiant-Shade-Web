<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table) {
            if (! Schema::hasColumn('news', 'status')) {
                $table->string('status', 32)->default('published')->after('is_active');
            }
            if (! Schema::hasColumn('news', 'news_type')) {
                $table->string('news_type', 64)->nullable()->after('status');
            }
            if (! Schema::hasColumn('news', 'full_story')) {
                $table->text('full_story')->nullable()->after('description');
            }
            if (! Schema::hasColumn('news', 'testimony')) {
                $table->text('testimony')->nullable()->after('full_story');
            }
            if (! Schema::hasColumn('news', 'author')) {
                $table->string('author')->nullable()->after('testimony');
            }
            if (! Schema::hasColumn('news', 'author_role')) {
                $table->string('author_role')->nullable()->after('author');
            }
            if (! Schema::hasColumn('news', 'author_avatar')) {
                $table->string('author_avatar')->nullable()->after('author_role');
            }
            if (! Schema::hasColumn('news', 'read_time')) {
                $table->string('read_time', 64)->nullable()->after('date_text');
            }
            if (! Schema::hasColumn('news', 'published_date')) {
                $table->string('published_date', 128)->nullable()->after('read_time');
            }
            if (! Schema::hasColumn('news', 'is_breaking')) {
                $table->boolean('is_breaking')->default(false)->after('published_date');
            }
            if (! Schema::hasColumn('news', 'views_count')) {
                $table->unsignedInteger('views_count')->default(0)->after('is_breaking');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $columns = [
                'status',
                'news_type',
                'full_story',
                'testimony',
                'author',
                'author_role',
                'author_avatar',
                'read_time',
                'published_date',
                'is_breaking',
                'views_count',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('news', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
