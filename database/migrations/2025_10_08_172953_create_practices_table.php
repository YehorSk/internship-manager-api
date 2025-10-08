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
        Schema::create('practices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('company_id');
            $table->enum('semester', ['summer', 'winter'])->nullable();
            $table->string('academic_year', 9)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
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
            $table->unsignedBigInteger('study_program_id');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('study_program_id')->references('id')->on('study_programs')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practices');
    }
};
