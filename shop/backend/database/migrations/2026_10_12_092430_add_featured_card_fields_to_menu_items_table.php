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
        Schema::table('menu_items', function (Blueprint $table) {
            $table->string('featured_title')->nullable()->after('image');
            $table->string('featured_button_text')->nullable()->after('featured_title');
            $table->string('featured_button_url')->nullable()->after('featured_button_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropColumn(['featured_title', 'featured_button_text', 'featured_button_url']);
        });
    }
};
