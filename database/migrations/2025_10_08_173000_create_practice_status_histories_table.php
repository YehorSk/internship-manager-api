<?php

use App\Enums\PracticeStatusEnum;
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
            $table->enum('status', array_map(fn($case) => $case->value, PracticeStatusEnum::cases()))->nullable();
            $table->text('comment')->nullable();
            $table->foreign('practice_id')->references('id')->on('practices')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
