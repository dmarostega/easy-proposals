<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;
class PlanFactory extends Factory { public function definition(): array { return ['name'=>$this->faker->word(),'slug'=>$this->faker->unique()->slug(),'monthly_price_cents'=>0,'monthly_proposal_limit'=>3,'customer_limit'=>10,'allows_pdf'=>false,'allows_custom_logo'=>false,'is_active'=>true]; } public function unlimited(): static { return $this->state(fn()=>['monthly_proposal_limit'=>null,'customer_limit'=>null,'allows_pdf'=>true]); } }
