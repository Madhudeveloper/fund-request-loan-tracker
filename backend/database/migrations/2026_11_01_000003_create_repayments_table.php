<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('repayments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('loan_request_id')->constrained()->cascadeOnDelete();
            $t->decimal('amount', 12, 2);
            $t->date('paid_on');
            $t->string('reference_no')->unique();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('repayments'); }
};
