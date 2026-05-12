<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('tickettailor_event_id')->nullable()->after('status');
            $table->string('tickettailor_ticket_type_id')->nullable()->after('tickettailor_event_id');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->string('tickettailor_ticket_id')->nullable()->after('admin_notes');
            $table->string('tickettailor_ticket_reference')->nullable()->after('tickettailor_ticket_id');
            $table->timestamp('tickettailor_issued_at')->nullable()->after('tickettailor_ticket_reference');
            $table->text('tickettailor_last_error')->nullable()->after('tickettailor_issued_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['tickettailor_event_id', 'tickettailor_ticket_type_id']);
        });
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'tickettailor_ticket_id',
                'tickettailor_ticket_reference',
                'tickettailor_issued_at',
                'tickettailor_last_error',
            ]);
        });
    }
};
