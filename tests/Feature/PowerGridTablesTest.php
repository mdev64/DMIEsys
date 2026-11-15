<?php

namespace Tests\Feature;

use App\Http\Livewire\InventoryTable;
use App\Http\Livewire\PostgraduateRegistrationTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PowerGridTablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_table_component_renders(): void
    {
        $user = $this->createUser('see notification');

        Livewire::actingAs($user)
            ->test(InventoryTable::class)
            ->assertStatus(200);
    }

    public function test_postgraduate_registration_table_component_renders(): void
    {
        $user = $this->createUser('see notification');

        Livewire::actingAs($user)
            ->test(PostgraduateRegistrationTable::class)
            ->assertStatus(200);
    }
}
