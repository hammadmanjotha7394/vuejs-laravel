<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('popups', function (Blueprint $table) {
            if (! Schema::hasColumn('popups', 'views_count')) {
                $table->unsignedInteger('views_count')->default(0)->after('sort_order');
            }
            if (! Schema::hasColumn('popups', 'clicks_count')) {
                $table->unsignedInteger('clicks_count')->default(0)->after('views_count');
            }
            if (! Schema::hasColumn('popups', 'signups_count')) {
                $table->unsignedInteger('signups_count')->default(0)->after('clicks_count');
            }
        });
    }

    public function down(): void
    {
        Schema::table('popups', function (Blueprint $table) {
            $cols = array_filter(['views_count', 'clicks_count', 'signups_count'], fn ($c) => Schema::hasColumn('popups', $c));
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
