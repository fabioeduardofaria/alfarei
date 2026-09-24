<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_a_category_and_case_or_accent_variants_select_the_existing_one(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->get('/materiais/create')->assertOk()->assertSee('Selecione a categoria')->assertSee('Acrílico');

        $this->postJson('/materiais/categorias', ['name' => '  Madeira   maciça  '])
            ->assertCreated()
            ->assertJsonPath('name', 'Madeira maciça')
            ->assertJsonPath('already_exists', false);
        $this->postJson('/materiais/categorias', ['name' => 'MADEIRA MACICA'])
            ->assertOk()
            ->assertJsonPath('name', 'Madeira maciça')
            ->assertJsonPath('already_exists', true);
        $this->postJson('/materiais/categorias', ['name' => 'AcriliCO'])
            ->assertOk()
            ->assertJsonPath('name', 'Acrílico')
            ->assertJsonPath('already_exists', true);

        $this->assertSame(1, MaterialCategory::where('key', 'madeira macica')->count());
        $this->assertSame(1, MaterialCategory::where('key', 'acrilico')->count());
    }

    public function test_material_uses_registered_category_even_if_a_different_text_is_submitted(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $category = MaterialCategory::where('key', 'acrilico')->firstOrFail();

        $this->post('/materiais', [
            'code' => 'ACR-3', 'name' => 'Acrílico cristal 3 mm', 'category_id' => $category->id,
            'category' => 'AcriliCO', 'unit' => 'chapa', 'cost_per_unit' => 100,
            'stock_quantity' => 2, 'minimum_stock' => 1, 'active' => 1,
        ])->assertRedirect(route('materiais.index'));
        $material = Material::where('code', 'ACR-3')->firstOrFail();
        $this->assertSame('Acrílico', $material->category);
        $this->get(route('materiais.edit', $material))
            ->assertOk()
            ->assertSee('value="'.$category->id.'" selected', false);

        $this->post('/materiais', [
            'code' => 'ACR-4', 'name' => 'Outro acrílico', 'category' => 'AcriliCO',
            'unit' => 'chapa', 'cost_per_unit' => 100, 'stock_quantity' => 0, 'minimum_stock' => 0,
        ])->assertSessionHasErrors('category_id');
    }

    public function test_users_without_material_access_cannot_create_categories(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'finance']));
        $this->postJson('/materiais/categorias', ['name' => 'Madeira'])->assertForbidden();
        $this->assertDatabaseMissing('material_categories', ['key' => 'madeira']);
    }

    public function test_correcting_a_category_updates_linked_materials_and_rejects_duplicate_names(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $category = MaterialCategory::create(['name' => 'Acrilicoo', 'key' => 'acrilicoo']);
        $first = Material::create(['code' => 'ACR-A', 'name' => 'Acrílico A', 'category' => 'Acrilicoo', 'unit' => 'chapa', 'cost_per_unit' => 50]);
        $second = Material::create(['code' => 'ACR-B', 'name' => 'Acrílico B', 'category' => 'Acrilicoo', 'unit' => 'chapa', 'cost_per_unit' => 60]);

        $this->putJson(route('materiais.categories.update', $category), ['name' => 'Acrílico'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
        $this->assertSame('Acrilicoo', $first->fresh()->category);

        $this->putJson(route('materiais.categories.update', $category), ['name' => 'Acrílico especial'])
            ->assertOk()
            ->assertJsonPath('name', 'Acrílico especial');
        $this->assertSame('Acrílico especial', $first->fresh()->category);
        $this->assertSame('Acrílico especial', $second->fresh()->category);
        $this->assertSame('acrilico especial', $category->fresh()->key);
    }
}
