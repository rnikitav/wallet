<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['deposit', 'withdrawal', 'fee']);
            $table->enum('status', ['pending', 'confirmed', 'failed', 'cancelled'])->default('pending');
            $table->decimal('amount', 36, 18);
            $table->decimal('fee', 36, 18)->default(0);
            $table->string('tx_hash')->nullable()->unique();
            $table->string('network')->nullable();
            $table->string('from_address')->nullable();
            $table->string('to_address')->nullable();
            $table->integer('confirmations')->default(0);
            $table->integer('required_confirmations')->default(6);
            $table->json('meta')->nullable();
            $table->string('idempotency_key')->unique();
            $table->timestamps();

            $table->index(['wallet_id', 'status']);
            $table->index('tx_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
