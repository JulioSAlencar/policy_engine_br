<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_identifier');
            $table->text('input_text');
            $table->text('output_text');
            $table->string('url_source');
            $table->timestamp('captured_at');

            // Status do processamento assíncrono
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');

            // Resultado da análise do Gemini
            $table->boolean('has_sensitive_data')->nullable();
            $table->enum('risk_level', ['low', 'medium', 'high', 'critical'])->nullable();
            $table->text('gemini_justification')->nullable();
            $table->json('gemini_raw_response')->nullable();

            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_identifier', 'risk_level']);
            $table->index('risk_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
