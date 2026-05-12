<?php
namespace Database\Factories;
use App\Models\User; use Illuminate\Database\Eloquent\Factories\Factory;
class CustomerFactory extends Factory { public function definition(): array { return ['user_id'=>User::factory(),'name'=>$this->faker->name(),'email'=>$this->faker->safeEmail(),'phone'=>$this->faker->phoneNumber()]; } }
