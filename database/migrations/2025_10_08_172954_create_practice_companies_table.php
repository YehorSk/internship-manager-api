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
        Schema::create('practice_companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('practice_id');
            $table->string('name');
            $table->string('address');
            $table->string('company_email');
            $table->string('contact_phone', 50);
            $table->string('contact_email');
            $table->string('contact_name');
            $table->string('ico', 8);
            $table->foreign('practice_id')->references('id')->on('practices')->
                onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_companies');
    }
};
