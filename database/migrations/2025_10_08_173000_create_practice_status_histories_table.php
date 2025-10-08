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
        Schema::create('practice_status_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('practice_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('document_id');
            $table->enum('status', [
                'created',
                'agreement_uploaded',
                'agreement_confirmed',
                'agreement_approved',
                'agreement_rejected',
                'agreement_removed',
                'report_uploaded',
                'defended_by_company',
                'defended_by_guarantor',
                'report_rejected',
                'report_removed',
                'rejected'
            ])->nullable();
            $table->text('comment')->nullable();
            $table->foreign('practice_id')->references('id')->on('practices')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('document_id')->references('id')->on('documents')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_status_histories');
    }
};
