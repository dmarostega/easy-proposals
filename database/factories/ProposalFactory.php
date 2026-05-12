<?php
namespace Database\Factories;
use App\Enums\ProposalStatus; use App\Models\Customer; use App\Models\User; use Illuminate\Database\Eloquent\Factories\Factory;
class ProposalFactory extends Factory { public function definition(): array { return ['user_id'=>User::factory(),'customer_id'=>Customer::factory(),'title'=>$this->faker->sentence(3),'description'=>$this->faker->paragraph(),'valid_until'=>now()->addDays(15),'subtotal'=>100,'discount'=>0,'total'=>100,'status'=>ProposalStatus::Draft]; } }
