<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('documents', function (Blueprint $t) {
            $t->id();
            $t->foreignId('loan_request_id')->constrained()->cascadeOnDelete();
            $t->string('file_path');
            $t->string('original_name');
            $t->json('summary')->nullable();
            $t->enum('summary_status', ['pending', 'completed', 'failed'])->default('pending');
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('documents'); }
};
