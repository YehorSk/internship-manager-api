<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/*
        Schema::create('practice_companies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('practice_id');
            $table->string('name');
            $table->string('address');
            $table->string('company_email');
            $table->string('contact_phone', 50);
            $table->string('contact_email');
            $table->string('contact_name');
            $table->foreign('practice_id')->references('id')->on('practices')->
                onDelete('cascade');
            $table->timestamps();
        });
 */

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PracticeCompany>
 */
class PracticeCompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
//            'practice_id' => $this->faker->numberBetween(1, 20),
            'name' => $this->faker->company(),
            'address' => $this->faker->address(),
            'company_email' => $this->faker->unique()->companyEmail(),
            'contact_phone' => $this->faker->phoneNumber(),
            'contact_email' => $this->faker->unique()->safeEmail(),
            'contact_name' => $this->faker->name(),
        ];
    }
}
