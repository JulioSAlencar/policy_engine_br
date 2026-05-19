<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Adiciona coluna error_reason para armazenar o motivo de falhas
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->text('error_reason')->nullable()->after('status');
        });

        // 2) Migra registros 'processing' (status legado) para 'in_analysis'
        DB::table('audit_logs')
            ->where('status', 'processing')
            ->update(['status' => 'in_analysis']);

        // 3) Altera o ENUM para refletir o novo ciclo de vida:
        //    pending → in_analysis → completed | failed
        DB::statement(
            "ALTER TABLE audit_logs
             MODIFY COLUMN status
             ENUM('pending','in_analysis','completed','failed')
             NOT NULL DEFAULT 'pending'"
        );
    }

    public function down(): void
    {
        // Reverte ENUM — mapeia in_analysis de volta para processing
        DB::table('audit_logs')
            ->where('status', 'in_analysis')
            ->update(['status' => 'processing']);

        DB::statement(
            "ALTER TABLE audit_logs
             MODIFY COLUMN status
             ENUM('pending','processing','completed','failed')
             NOT NULL DEFAULT 'pending'"
        );

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('error_reason');
        });
    }
};
