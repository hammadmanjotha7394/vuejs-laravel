<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            if (! Schema::hasColumn('chat_conversations', 'current_page')) {
                $table->string('current_page', 500)->nullable()->after('visitor_email');
            }
            if (! Schema::hasColumn('chat_conversations', 'internal_notes')) {
                $table->text('internal_notes')->nullable()->after('unread_visitor_count');
            }
            if (! Schema::hasColumn('chat_conversations', 'visitor_last_seen_at')) {
                $table->timestamp('visitor_last_seen_at')->nullable()->after('last_message_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $cols = array_filter(
                ['current_page', 'internal_notes', 'visitor_last_seen_at'],
                fn ($c) => Schema::hasColumn('chat_conversations', $c)
            );
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
