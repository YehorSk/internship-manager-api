<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Change report_type column to enum with values from ReportTypeEnum
            $table->enum('report_type', [
                'practices_status_summary',
                'practices_funnel',
                'missing_documents',
                'students_without_practice',
                'practices_by_company'
            ])->change();
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Revert report_type column back to string
            $table->string('report_type')->change();
        });
    }
};
