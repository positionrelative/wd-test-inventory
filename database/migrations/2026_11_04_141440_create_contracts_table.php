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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);
            $table->string('contract_number', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['vendor_id', 'contract_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
