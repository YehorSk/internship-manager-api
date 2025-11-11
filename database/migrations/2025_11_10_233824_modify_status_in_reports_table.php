<?php

use App\Enums\ReportStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->enum('status', array_map(fn($case) => $case->value, ReportStatusEnum::cases()))->default(ReportStatusEnum::PENDING->value)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('status')->nullable()->change();
        });
    }
};
