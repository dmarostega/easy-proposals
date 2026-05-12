<?php
namespace Database\Factories;
use App\Models\Proposal; use Illuminate\Database\Eloquent\Factories\Factory;
class ProposalItemFactory extends Factory { public function definition(): array { return ['proposal_id'=>Proposal::factory(),'description'=>$this->faker->sentence(3),'quantity'=>1,'unit_price'=>100,'total'=>100]; } }
