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
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->string('hostname');
            $table->foreignId('created_by')->nullable()->index()->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['hostname', 'created_by']);
            $table->unsignedInteger('interval')->default(60);
            $table->unsignedInteger('timeout')->default(30);
            $table->enum('method', ['GET', 'HEAD'])->default('GET');
            $table->json('body')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
