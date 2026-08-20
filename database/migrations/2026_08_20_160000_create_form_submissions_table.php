<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('form_type', 64)->index();
            $table->string('name');
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('country')->nullable();
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->string('status', 32)->default('new')->index();
            $table->text('admin_notes')->nullable();
            $table->timestamp('submitted_at')->index();
            $table->timestamps();

            $table->index(['form_type', 'status']);
            $table->index(['status', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
