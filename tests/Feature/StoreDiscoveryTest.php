<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_classify_and_feature_products_for_guided_discovery(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/produtos', [
            'name' => 'Luminária personalizada', 'type' => 'product',
            'base_price' => 89, 'production_cost' => 32,
            'active' => 1, 'store_visible' => 1, 'store_featured' => 1,
            'store_occasions' => ['presente', 'decoracao'],
        ])->assertRedirect('/produtos');

        $product = Product::where('name', 'Luminária personalizada')->firstOrFail();
        $this->assertSame(['presente', 'decoracao'], $product->store_occasions);
        $this->assertTrue($product->store_featured);
        $this->get('/')->assertOk()->assertSee('Quero presentear')->assertSee('Para se inspirar agora.');

        $this->actingAs($admin)->put("/produtos/{$product->id}", [
            'name' => $product->name, 'type' => 'product', 'base_price' => 89,
            'production_cost' => 32, 'active' => 1, 'store_visible' => 1,
            'store_featured' => 0,
        ])->assertRedirect('/produtos');
        $this->assertSame([], $product->fresh()->store_occasions);
        $this->assertFalse($product->fresh()->store_featured);
    }

    public function test_catalog_filters_search_sort_and_related_products(): void
    {
        $gift = Product::create(['name' => 'Presente madeira', 'type' => 'product', 'base_price' => 90, 'production_cost' => 30, 'active' => true, 'store_visible' => true, 'store_occasions' => ['presente'], 'store_featured' => true]);
        $anotherGift = Product::create(['name' => 'Presente gravado', 'type' => 'product', 'base_price' => 40, 'production_cost' => 10, 'active' => true, 'store_visible' => true, 'store_occasions' => ['presente'], 'allow_personalization' => true]);
        Product::create(['name' => 'Placa empresarial', 'type' => 'product', 'base_price' => 120, 'production_cost' => 50, 'active' => true, 'store_visible' => true, 'store_occasions' => ['empresa']]);
        Product::create(['name' => 'Produto oculto', 'type' => 'product', 'base_price' => 10, 'production_cost' => 2, 'active' => true, 'store_visible' => false]);

        $this->get('/?uso=presente')->assertOk()->assertSee('Presente madeira')->assertSee('Presente gravado')->assertDontSee('Placa empresarial');
        $this->get('/?uso=empresa')->assertOk()->assertSee('Placa empresarial')->assertDontSee('Presente madeira');
        $this->get('/?uso=personalizavel')->assertOk()->assertSee('Presente gravado')->assertDontSee('Presente madeira');
        $this->get('/?busca=gravado')->assertOk()->assertSee('Presente gravado')->assertDontSee('Placa empresarial');
        $this->get('/?uso=presente&ordenar=menor-preco')->assertOk()->assertSeeInOrder(['Presente gravado', 'Presente madeira']);
        $this->get("/loja/produto/{$gift->id}")->assertOk()->assertSee('Mais ideias para você.')->assertSeeInOrder([$anotherGift->name, 'Placa empresarial'])->assertDontSee('Produto oculto');
    }
}
