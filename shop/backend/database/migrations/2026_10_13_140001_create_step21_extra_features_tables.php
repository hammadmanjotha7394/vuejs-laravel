<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('back_in_stock_subscriptions')) {
            Schema::create('back_in_stock_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
                $table->string('email')->index();
                $table->timestamp('notified_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('url_redirects')) {
            Schema::create('url_redirects', function (Blueprint $table) {
                $table->id();
                $table->string('source_path')->unique()->index();
                $table->string('target_path');
                $table->smallInteger('status_code')->default(301);
                $table->unsignedInteger('hits_count')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('carts', function (Blueprint $table) {
            if (! Schema::hasColumn('carts', 'guest_email')) {
                $table->string('guest_email')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('carts', 'abandoned_reminder_sent_at')) {
                $table->timestamp('abandoned_reminder_sent_at')->nullable()->after('coupon_code');
            }
        });

        Schema::table('coupons', function (Blueprint $table) {
            if (! Schema::hasColumn('coupons', 'min_qty')) {
                $table->unsignedInteger('min_qty')->nullable()->after('min_subtotal');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('back_in_stock_subscriptions');
        Schema::dropIfExists('url_redirects');

        Schema::table('carts', function (Blueprint $table) {
            $cols = array_filter(['guest_email', 'abandoned_reminder_sent_at'], fn ($c) => Schema::hasColumn('carts', $c));
            if ($cols) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('coupons', function (Blueprint $table) {
            if (Schema::hasColumn('coupons', 'min_qty')) {
                $table->dropColumn('min_qty');
            }
        });
    }
};
