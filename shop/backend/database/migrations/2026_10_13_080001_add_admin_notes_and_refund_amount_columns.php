<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('last_login_at');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'admin_notes')) {
                $table->text('admin_notes')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('orders', 'refunded_amount')) {
                $table->decimal('refunded_amount', 10, 2)->default(0)->after('grand_total');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'admin_notes')) {
                $table->dropColumn('admin_notes');
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            $cols = array_filter(['admin_notes', 'refunded_amount'], fn ($c) => Schema::hasColumn('orders', $c));
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
