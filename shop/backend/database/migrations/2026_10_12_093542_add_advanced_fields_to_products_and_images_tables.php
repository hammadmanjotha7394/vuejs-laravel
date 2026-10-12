<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('allow_backorder')->default(false)->after('low_stock_threshold');
            $table->string('video_poster')->nullable()->after('video_path');
            $table->string('seo_social_image')->nullable()->after('seo_description');
            $table->json('related_products_json')->nullable()->after('seo_social_image');
            $table->json('frequently_bought_together_json')->nullable()->after('related_products_json');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->string('thumb_path')->nullable()->after('path');
            $table->string('medium_path')->nullable()->after('thumb_path');
            $table->string('large_path')->nullable()->after('medium_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'allow_backorder',
                'video_poster',
                'seo_social_image',
                'related_products_json',
                'frequently_bought_together_json',
            ]);
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropColumn(['thumb_path', 'medium_path', 'large_path']);
        });
    }
};
