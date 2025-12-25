<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('practices', function (Blueprint $table) {
            $table->index(['student_id', 'id'], 'idx_practices_student_id_id');
            $table->index(['company_id', 'id'], 'idx_practices_company_id_id');
        });
    }

    public function down(): void
    {
        Schema::table('practices', function (Blueprint $table) {
            $table->dropIndex('idx_practices_company_id_id');
            $table->dropIndex('idx_practices_student_id_id');
        });
    }
};
