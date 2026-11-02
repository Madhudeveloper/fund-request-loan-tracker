<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('loan_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->decimal('amount', 12, 2);
            $t->unsignedSmallInteger('tenure_months');
            $t->string('purpose');
            $t->enum('status', ['pending', 'approved', 'rejected', 'closed'])->default('pending')->index();
            $t->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('approved_at')->nullable();
            $t->text('remarks')->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('loan_requests'); }
};
