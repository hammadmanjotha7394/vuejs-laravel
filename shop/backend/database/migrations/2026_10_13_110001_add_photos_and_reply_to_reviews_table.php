<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('reviews', 'photos_json')) {
                $table->json('photos_json')->nullable()->after('body');
            }
            if (! Schema::hasColumn('reviews', 'admin_reply')) {
                $table->text('admin_reply')->nullable()->after('photos_json');
            }
            if (! Schema::hasColumn('reviews', 'admin_replied_at')) {
                $table->timestamp('admin_replied_at')->nullable()->after('admin_reply');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $cols = array_filter(
                ['photos_json', 'admin_reply', 'admin_replied_at'],
                fn ($c) => Schema::hasColumn('reviews', $c)
            );
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
