<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Machine;
use App\Models\Material;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Quote;
use App\Models\StoreSetting;
use App\Models\TextCutoutConfigurator;
use App\Models\User;
use App\Services\TextCutoutPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TextCutoutConfiguratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_stays_hidden_until_text_configurator_is_complete_and_enabled(): void
    {
        $this->get('/loja/nome-personalizado')->assertNotFound();
        $admin = User::factory()->create(['role' => 'admin']);
        $response = $this->actingAs($admin)->get('/administracao/nomes-textos')->assertOk()->assertSee('Configurador de nomes e textos');
        $this->assertSame(21, substr_count($response->getContent(), 'class="field-tip"'));
        $response->assertSee('Ajuda: Tempo estimado para cortar um caractere', false);
        $this->put('/administracao/nomes-textos', $this->settings(['enabled' => 1]))->assertSessionHasErrors('enabled');
        $this->get('/loja/nome-personalizado')->assertNotFound();
    }

    public function test_price_uses_registered_mdf_or_acrylic_and_finish_costs(): void
    {
        [$configurator, $mdf, $acrylic] = $this->configured();
        $pricing = app(TextCutoutPricingService::class);
        $natural = $pricing->quote($configurator, $mdf, 'Aurora', 10, 30, 'natural', null, 10);
        $white = $pricing->quote($configurator, $mdf, 'Aurora', 10, 30, 'white', null, 10);
        $painted = $pricing->quote($configurator, $mdf, 'Aurora', 10, 30, 'painted', 'rosa', 10);
        $acrylicQuote = $pricing->quote($configurator, $acrylic, 'Aurora', 10, 30, 'natural', null, 10);
        $this->assertEquals(11.65, $white['unit_cost']);
        $this->assertEquals(16.65, $white['unit_price']);
        $this->assertLessThan($white['unit_cost'], $natural['unit_cost']);
        $this->assertGreaterThan($white['unit_cost'], $painted['unit_cost']);
        $this->assertGreaterThan($natural['unit_cost'], $acrylicQuote['unit_cost']);
        $this->assertSame('rosa', $painted['color']);
        $this->assertCount(1, $white['material_usage']);
    }

    public function test_admin_can_update_text_costs_without_changing_material_catalog(): void
    {
        [$configurator, $mdf] = $this->configured();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->put('/administracao/nomes-textos', $this->settings([
            'product_id' => $configurator->product_id,
            'laser_machine_id' => $configurator->laser_machine_id,
            'laser_minutes_per_character_10cm' => 0.75,
            'enabled' => 1,
        ]))->assertRedirect();
        $this->assertEquals(0.75, $configurator->fresh()->laser_minutes_per_character_10cm);
        $this->assertTrue($configurator->fresh()->enabled);
        $this->assertEquals(50, $mdf->fresh()->cost_per_unit);
        $this->get('/loja/nome-personalizado')->assertOk();
    }

    public function test_quote_selector_calculates_server_price_and_preserves_text_material_and_finish_in_order(): void
    {
        [$configurator, $mdf] = $this->configured();
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['type' => 'PF', 'name' => 'Cliente Aurora', 'email' => 'aurora@teste.com', 'customer_group' => 'final', 'active' => true]);
        $this->actingAs($admin)->get('/orcamentos/create')->assertOk()->assertSee('Nome ou texto recortado');
        $this->postJson('/orcamentos/nome-texto/preco', [
            'text_content' => 'Aurora', 'material_id' => $mdf->id, 'finish' => 'white',
            'width_cm' => 30, 'height_cm' => 10, 'quantity' => 10, 'customer_id' => $customer->id,
        ])->assertOk()->assertJsonPath('unit_price', 16.65);
        $this->post('/orcamentos', [
            'customer_id' => $customer->id, 'valid_until' => today()->addWeek()->toDateString(), 'discount' => 0,
            'items' => [[
                'kind' => 'text_cutout', 'text_content' => 'Aurora', 'text_material_id' => $mdf->id,
                'text_finish' => 'white', 'text_width_cm' => 30, 'text_height_cm' => 10,
                'quantity' => 10, 'unit_price' => 0.01, 'unit_cost' => 0.01,
            ]],
        ])->assertRedirect();
        $quote = Quote::firstOrFail();
        $item = $quote->items()->firstOrFail();
        $this->assertSame('text_cutout', $item->type);
        $this->assertSame('Nome/texto "Aurora" · MDF cru 3 mm · branco · 30 × 10 cm', $item->description);
        $this->assertEquals(16.65, $item->unit_price);
        $this->assertEquals(11.65, $item->unit_cost);
        $this->assertSame($mdf->id, $item->configuration_snapshot['material_id']);
        $this->post("/orcamentos/{$quote->id}/enviar")->assertRedirect();
        $quote->refresh();
        $this->get('/proposta/'.$quote->approval_token)->assertOk()->assertSee('Aurora');
        $this->post('/proposta/'.$quote->approval_token.'/resposta', ['decision' => 'approved', 'response' => 'Aprovado.'])->assertRedirect();
        $this->post("/orcamentos/{$quote->id}/converter-em-pedido")->assertRedirect();
        $this->assertSame('Aurora', OrderItem::firstOrFail()->configuration_snapshot['text']);
    }

    public function test_store_estimates_width_quotes_and_keeps_price_snapshot_in_cart(): void
    {
        [$configurator, $mdf] = $this->configured();
        User::factory()->create(['role' => 'admin', 'active' => true]);
        StoreSetting::create(['store_open' => true, 'pickup_enabled' => true]);
        $this->get('/')->assertOk()->assertSee('Criar meu nome');
        $this->get('/loja/nome-personalizado')->assertOk()->assertSee('Acrílico');
        $this->get('/loja/produto/'.$configurator->product_id)->assertRedirect('/loja/nome-personalizado');
        $response = $this->postJson('/loja/nome-personalizado/preco', [
            'text' => 'Aurora', 'material_id' => $mdf->id, 'finish' => 'natural',
            'height_cm' => 10, 'quantity' => 1,
        ])->assertOk()->assertJsonPath('width_estimated', true)->assertJsonPath('width_cm', 36);
        $this->post('/loja/nome-personalizado/carrinho', ['quote_token' => $response->json('token')])->assertRedirect('/loja/carrinho');
        $this->get('/loja/carrinho')->assertOk()->assertSee('Aurora');
        $cart = session('store_cart');
        $snapshot = collect($cart)->first()['configuration_snapshot'];
        $this->assertSame('Aurora', $snapshot['text']);
        $this->assertSame($mdf->id, $snapshot['material_id']);
        $configurator->update(['max_width_cm' => 30]);
        $this->get('/loja/carrinho')->assertOk();
        $this->get('/loja/finalizar')->assertOk()->assertSee('Aurora');
        $this->post('/loja/finalizar', [
            'name' => 'Cliente Nome', 'email' => 'nome@teste.com', 'phone' => '65999990000',
            'city' => 'Cuiabá', 'state' => 'MT', 'delivery_method' => 'pickup',
        ])->assertRedirect();
        $orderItem = OrderItem::firstOrFail();
        $this->assertSame('Aurora', $orderItem->configuration_snapshot['text']);
        $this->assertStringContainsString('Aurora', $orderItem->description);
    }

    public function test_text_quote_rejects_invalid_material_and_painted_finish_without_color(): void
    {
        [, $mdf] = $this->configured();
        $metal = Material::create(['code' => 'METAL', 'name' => 'Metal', 'category' => 'Metal', 'unit' => 'chapa', 'thickness_mm' => 3, 'width_mm' => 1000, 'height_mm' => 1000, 'cost_per_unit' => 50, 'active' => true]);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->postJson('/orcamentos/nome-texto/preco', [
            'text_content' => 'Aurora', 'material_id' => $metal->id, 'finish' => 'natural', 'height_cm' => 10, 'quantity' => 1,
        ])->assertUnprocessable();
        $this->postJson('/orcamentos/nome-texto/preco', [
            'text_content' => 'Aurora', 'material_id' => $mdf->id, 'finish' => 'painted', 'height_cm' => 10, 'quantity' => 1,
        ])->assertUnprocessable();
        $this->postJson('/orcamentos/nome-texto/preco', [
            'text_content' => 'Aurora', 'material_id' => $mdf->id, 'finish' => 'natural', 'width_cm' => 90, 'height_cm' => 10, 'quantity' => 1,
        ])->assertUnprocessable();
        $this->postJson('/orcamentos/nome-texto/preco', [
            'text_content' => 'Aurora', 'material_id' => $mdf->id, 'finish' => 'natural', 'width_cm' => 1, 'height_cm' => 10, 'quantity' => 1,
        ])->assertUnprocessable();
    }

    private function settings(array $override = []): array
    {
        return array_merge([
            'max_width_cm' => 80, 'max_height_cm' => 90, 'average_character_width_ratio' => 0.6,
            'minimum_width_percent' => 50,
            'laser_minutes_per_character_10cm' => 0.5, 'minimum_laser_minutes' => 1,
            'material_loss_percent' => 10, 'white_finish_cost_per_m2' => 100,
            'painted_finish_cost_per_m2' => 200, 'base_cost_per_unit' => 2,
            'packaging_cost_per_unit' => 1, 'setup_cost_per_order' => 10,
            'selling_fee_percent' => 5, 'target_margin_percent' => 25,
            'reseller_min_quantity' => 10, 'wholesale_min_quantity' => 50,
        ], $override);
    }

    private function configured(): array
    {
        $product = Product::create(['name' => 'Nome recortado', 'type' => 'product', 'base_price' => 1, 'active' => true, 'store_visible' => true]);
        $mdf = Material::create(['code' => 'MDF-CRU', 'name' => 'MDF cru', 'category' => 'MDF', 'unit' => 'chapa', 'thickness_mm' => 3, 'width_mm' => 1000, 'height_mm' => 1000, 'cost_per_unit' => 50, 'active' => true]);
        $acrylic = Material::create(['code' => 'ACRILICO', 'name' => 'Acrílico cristal', 'category' => 'Acrílico', 'unit' => 'chapa', 'thickness_mm' => 3, 'width_mm' => 1000, 'height_mm' => 1000, 'cost_per_unit' => 100, 'active' => true]);
        $machine = Machine::create(['name' => 'Laser', 'code' => 'LASER-TEXT', 'type' => 'laser', 'hourly_cost' => 60, 'active' => true]);
        $configurator = TextCutoutConfigurator::create($this->settings(['product_id' => $product->id, 'laser_machine_id' => $machine->id, 'enabled' => true]));

        return [$configurator, $mdf, $acrylic];
    }
}
