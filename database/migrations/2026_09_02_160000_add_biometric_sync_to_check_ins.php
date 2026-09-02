<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            $table->enum('checkin_type', ['huella', 'facial'])->nullable()->after('tipo');
            $table->enum('sync_status', ['normal', 'pendiente'])->default('normal')->after('checkin_type');
            $table->timestamp('pending_checkin_datetime')->nullable()->after('sync_status');
            $table->timestamp('synced_at')->nullable()->after('pending_checkin_datetime');
            $table->char('client_uuid', 36)->nullable()->unique()->after('synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('check_ins', function (Blueprint $table) {
            $table->dropUnique(['client_uuid']);
            $table->dropColumn([
                'checkin_type',
                'sync_status',
                'pending_checkin_datetime',
                'synced_at',
                'client_uuid',
            ]);
        });
    }
};
