<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('log_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_id')->index();
            $table->string('level', 20)->index();
            $table->text('message');
            $table->json('context')->nullable();
            $table->json('properties')->nullable();
            $table->string('controller', 100)->nullable();
            $table->string('method', 100)->nullable();
            $table->integer('call_depth')->default(1);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('url')->nullable();
            $table->string('http_method', 10)->nullable();
            $table->integer('status_code')->nullable();
            $table->integer('duration')->nullable();
            $table->bigInteger('memory_usage')->nullable();
            $table->timestamp('created_at')->index();

            // Indexes for better performance
            $table->index(['level', 'created_at']);
            $table->index(['request_id', 'created_at']);
            $table->index(['controller', 'created_at']);
            $table->index(['method', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_entries');
    }
};
