<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

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
            'contact_position' => $this->faker->randomElement([
                'Generálny riaditeľ',
                'Výkonný riaditeľ',
                'Finančný riaditeľ',
                'Technický riaditeľ',
                'Manažér',
                'Projektový manažér',
                'Obchodný manažér',
                'Účtovník',
                'Personálny manažér',
                'Vývojár',
                'Dizajnér',
                'Administrátor',
                'Právnik',
                'Marketingový špecialista',
                'Asistent'
            ]),
            'ico' => $this->faker->numerify('########'),
        ];
    }
}
