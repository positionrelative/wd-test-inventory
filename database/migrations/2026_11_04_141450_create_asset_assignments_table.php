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
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('assign_date');
            $table->date('return_date')->nullable();
            $table->timestamps();

            // An asset can have only one open (not returned) assignment at a time:
            // the column holds asset_id while open and NULL after return; the unique index ignores NULLs
            $table->unsignedBigInteger('open_asset_id')
                ->nullable()
                ->virtualAs('case when return_date is null then asset_id end');
            $table->unique('open_asset_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};
