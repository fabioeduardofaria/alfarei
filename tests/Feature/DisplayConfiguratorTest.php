<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DisplayConfigurator;
use App\Models\Machine;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Quote;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\DisplayPricingService;
use App\Services\InventoryReservationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisplayConfiguratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_remains_hidden_until_admin_configures_and_enables_display(): void
    {
        $this->get('/loja/display')->assertNotFound();
        $this->get('/administracao/displays')->assertRedirect('/entrar');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/administracao/displays')->assertOk()->assertSee('3 minutos');
        $this->actingAs($admin)->put('/administracao/displays', $this->settingsData(['enabled' => 1]))->assertSessionHasErrors('enabled');
        $this->get('/loja/display')->assertNotFound();
    }

    public function test_price_uses_free_dimensions_materials_and_adjustable_laser_minutes(): void
    {
        $configurator = $this->configuredDisplay();
        $pricing = app(DisplayPricingService::class);
        $quote = $pricing->quote($configurator, 30, 40, 10);
        $this->assertSame(3.0, $quote['laser_minutes']);
        $this->assertEqualsWithDelta(19.14, $quote['unit_cost'], 0.01);
        $this->assertEqualsWithDelta(27.35, $quote['unit_price'], 0.01);

        $configurator->update(['laser_minutes_per_unit' => 4]);
        $quote = $pricing->quote($configurator->fresh(), 30, 40, 10);
        $this->assertSame(4.0, $quote['laser_minutes']);
        $this->assertEqualsWithDelta(20.14, $quote['unit_cost'], 0.01);
    }

    public function test_linked_product_cannot_be_bought_at_catalog_base_price_while_configurator_is_off(): void
    {
        $configurator = $this->configuredDisplay();
        $configurator->update(['enabled' => false]);
        $this->get('/')->assertOk()->assertDontSee('Display personalizado');
        $this->get('/loja/display')->assertNotFound();
        $this->get('/loja/produto/'.$configurator->product_id)->assertNotFound();
        $this->post('/loja/produto/'.$configurator->product_id.'/carrinho', ['quantity' => 1])->assertNotFound();
    }

    public function test_admin_can_set_maximum_dimensions_and_laser_time_from_interface(): void
    {
        $configurator = $this->configuredDisplay();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/administracao/displays')->assertOk()->assertSee('Limites de medida')->assertSee('Simular orçamento sob medida');
        $this->actingAs($admin)->put('/administracao/displays', $this->settingsData([
            'product_id' => $configurator->product_id,
            'mdf_material_id' => $configurator->mdf_material_id,
            'adhesive_material_id' => $configurator->adhesive_material_id,
            'laser_machine_id' => $configurator->laser_machine_id,
            'laser_minutes_per_unit' => 4,
            'max_width_cm' => 60,
            'max_height_cm' => 80,
            'enabled' => 1,
        ]))->assertRedirect();
        $this->assertEquals(4, $configurator->fresh()->laser_minutes_per_unit);
        $this->assertEquals(60, $configurator->fresh()->max_width_cm);
        $this->actingAs($admin)->post('/administracao/displays/simular', ['width_cm' => 45.5, 'height_cm' => 52.3, 'quantity' => 10])->assertSessionHas('display_simulation');
        $this->get('/loja/display')->assertOk();
    }

    public function test_reseller_price_only_activates_for_approved_account_and_minimum_quantity(): void
    {
        $configurator = $this->configuredDisplay();
        $configurator->update(['reseller_margin_percent' => 15, 'reseller_min_quantity' => 10]);
        $customer = Customer::create(['type' => 'PJ', 'name' => 'Revenda', 'email' => 'revenda@teste.com', 'customer_group' => 'reseller', 'reseller_status' => 'approved', 'password' => 'senha-forte-123', 'active' => true]);
        $pricing = app(DisplayPricingService::class);
        $this->assertSame('final', $pricing->quote($configurator, 30, 40, 1, $customer)['customer_group']);
        $this->assertSame('reseller', $pricing->quote($configurator, 30, 40, 10, $customer)['customer_group']);
        $this->post('/loja/entrar', ['email' => $customer->email, 'password' => 'senha-forte-123']);
        $response = $this->postJson('/loja/display/preco', ['width_cm' => 30, 'height_cm' => 40, 'quantity' => 10])->assertOk();
        $this->assertLessThan(27.35, $response->json('unit_price'));
        $customer->update(['reseller_status' => 'suspended']);
        $this->post('/loja/display/carrinho', ['quote_token' => $response->json('token')])->assertSessionHasErrors('quote_token');
    }

    public function test_store_shows_price_before_cart_and_preserves_snapshot_after_cost_change(): void
    {
        User::factory()->create(['role' => 'admin', 'active' => true]);
        StoreSetting::create(['store_open' => true, 'pickup_enabled' => true]);
        $configurator = $this->configuredDisplay();
        $this->get('/')->assertOk()->assertSee('Montar meu display');
        $this->get('/loja/produto/'.$configurator->product_id)->assertRedirect('/loja/display');
        $this->post('/loja/produto/'.$configurator->product_id.'/carrinho', ['quantity' => 1])->assertRedirect('/loja/display');
        $this->get('/loja/display')->assertOk()->assertSee('3 mm')->assertSee('Largura (cm)')->assertSee('Altura (cm)');

        $response = $this->postJson('/loja/display/preco', ['width_cm' => 30, 'height_cm' => 40, 'quantity' => 10])->assertOk()->assertJsonPath('unit_price', 27.35);
        $token = $response->json('token');
        $this->post('/loja/display/carrinho', ['quote_token' => $token, 'personalization' => 'Tema futebol'])->assertRedirect('/loja/carrinho');
        $this->get('/loja/carrinho')->assertOk()->assertSee('R$ 273,50')->assertSee('30 × 40 cm');

        $configurator->mdfMaterial->update(['cost_per_unit' => 100]);
        $configurator->update(['laser_minutes_per_unit' => 5]);
        $this->get('/loja/carrinho')->assertOk()->assertSee('R$ 273,50');
        $this->get('/loja/finalizar')->assertOk()->assertSee('R$ 273,50');
        $this->post('/loja/finalizar', [
            'name' => 'Cliente Display', 'email' => 'display@teste.com', 'phone' => '65999990000',
            'city' => 'Cuiabá', 'state' => 'MT', 'delivery_method' => 'pickup',
        ])->assertRedirect();
        $this->assertDatabaseHas('order_items', ['product_id' => $configurator->product_id, 'unit_price' => 27.35, 'quantity' => 10, 'total' => 273.50]);
        $snapshot = OrderItem::firstOrFail()->configuration_snapshot;
        $this->assertEquals(3.0, $snapshot['laser_minutes']);
        $this->assertSame('30 × 40 cm', $snapshot['size_label']);
        $this->assertEquals(30, $snapshot['width_cm']);
        $this->assertEquals(40, $snapshot['height_cm']);
        $this->assertCount(2, $snapshot['material_usage']);

        $configurator->mdfMaterial->update(['stock_quantity' => 10]);
        $configurator->adhesiveMaterial->update(['stock_quantity' => 10]);
        $production = ProductionOrder::create(['number' => 'OP-TESTE-1', 'order_id' => Order::firstOrFail()->id, 'status' => 'awaiting']);
        app(InventoryReservationService::class)->reserveFor($production);
        $this->assertDatabaseHas('inventory_movements', ['production_order_id' => $production->id, 'material_id' => $configurator->mdf_material_id, 'quantity' => 1.32, 'type' => 'reserve']);
        $this->assertDatabaseHas('inventory_movements', ['production_order_id' => $production->id, 'material_id' => $configurator->adhesive_material_id, 'quantity' => 1.32, 'type' => 'reserve']);
    }

    public function test_store_accepts_arbitrary_decimal_dimensions_but_rejects_over_limit_or_invalid_precision(): void
    {
        $this->configuredDisplay();
        $response = $this->postJson('/loja/display/preco', ['width_cm' => 30.5, 'height_cm' => 40.2, 'quantity' => 1])->assertOk();
        $this->assertGreaterThan(0, $response->json('unit_price'));
        $this->postJson('/loja/display/preco', ['width_cm' => 80.1, 'height_cm' => 40, 'quantity' => 1])->assertUnprocessable();
        $this->postJson('/loja/display/preco', ['width_cm' => 30, 'height_cm' => 90.1, 'quantity' => 1])->assertUnprocessable();
        $this->postJson('/loja/display/preco', ['width_cm' => 30.55, 'height_cm' => 40, 'quantity' => 1])->assertUnprocessable();
    }

    public function test_quote_cannot_be_added_after_admin_reduces_maximum_dimensions(): void
    {
        $configurator = $this->configuredDisplay();
        $response = $this->postJson('/loja/display/preco', ['width_cm' => 70, 'height_cm' => 80, 'quantity' => 1])->assertOk();
        $configurator->update(['max_width_cm' => 60]);
        $this->post('/loja/display/carrinho', ['quote_token' => $response->json('token')])->assertSessionHasErrors('quote_token');
    }

    public function test_admin_display_selector_prices_quote_on_server_and_preserves_dimensions_in_public_proposal(): void
    {
        $this->configuredDisplay();
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['type' => 'PF', 'name' => 'Cliente Display', 'email' => 'display-cotacao@teste.com', 'customer_group' => 'final', 'active' => true]);
        $this->actingAs($admin)->get('/orcamentos/create')->assertOk()->assertSee('Display adesivado sob medida');
        $this->postJson('/orcamentos/display/preco', ['width_cm' => 30, 'height_cm' => 40, 'quantity' => 10, 'customer_id' => $customer->id])->assertOk()->assertJsonPath('unit_price', 27.35);
        $this->post('/orcamentos', [
            'customer_id' => $customer->id, 'valid_until' => today()->addWeek()->toDateString(), 'discount' => 0,
            'items' => [[
                'kind' => 'display', 'width_cm' => 30, 'height_cm' => 40, 'quantity' => 10,
                'reference' => '  Turma   da Mônica  ',
                'description' => 'Preço adulterado', 'unit_price' => 0.01, 'unit_cost' => 0.01,
            ]],
        ])->assertRedirect();
        $quote = Quote::firstOrFail();
        $item = $quote->items()->firstOrFail();
        $this->assertSame('display', $item->type);
        $this->assertSame('Display adesivado Turma da Mônica · MDF 3 mm · 30 × 40 cm', $item->description);
        $this->assertEquals(27.35, $item->unit_price);
        $this->assertEquals(19.14, $item->unit_cost);
        $this->assertEquals(30, $item->configuration_snapshot['width_cm']);
        $this->assertSame('Turma da Mônica', $item->configuration_snapshot['reference']);
        $this->assertEquals(3, $item->configuration_snapshot['mdf_thickness_mm']);
        $this->get("/orcamentos/{$quote->id}/edit")->assertOk()->assertSee('Turma da Mônica');
        $this->post("/orcamentos/{$quote->id}/enviar")->assertRedirect();
        $quote->refresh();
        $this->get('/proposta/'.$quote->approval_token)->assertOk()->assertSee('Display adesivado Turma da Mônica');
        $this->post('/proposta/'.$quote->approval_token.'/resposta', ['decision' => 'approved', 'response' => 'Aprovado.'])->assertRedirect();
        $this->post("/orcamentos/{$quote->id}/converter-em-pedido")->assertRedirect();
        $this->assertEquals(30, OrderItem::firstOrFail()->configuration_snapshot['width_cm']);
        $this->assertSame('Turma da Mônica', OrderItem::firstOrFail()->configuration_snapshot['reference']);
        $this->assertEquals(3, OrderItem::firstOrFail()->configuration_snapshot['mdf_thickness_mm']);
        $this->post("/orcamentos/{$quote->id}/nova-versao")->assertRedirect();
        $revision = Quote::where('parent_quote_id', $quote->id)->firstOrFail();
        $this->assertEquals(30, $revision->items()->firstOrFail()->configuration_snapshot['width_cm']);
        $this->assertSame('Turma da Mônica', $revision->items()->firstOrFail()->configuration_snapshot['reference']);
        $this->assertEquals(3, $revision->items()->firstOrFail()->configuration_snapshot['mdf_thickness_mm']);
    }

    public function test_admin_display_quote_rejects_dimensions_above_configured_limit(): void
    {
        $this->configuredDisplay();
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['type' => 'PF', 'name' => 'Cliente Limite', 'customer_group' => 'final', 'active' => true]);
        $this->actingAs($admin)->postJson('/orcamentos/display/preco', ['width_cm' => 80.1, 'height_cm' => 40, 'quantity' => 1])->assertUnprocessable();
        $this->post('/orcamentos', [
            'customer_id' => $customer->id, 'discount' => 0,
            'items' => [['kind' => 'display', 'reference' => 'Tema teste', 'width_cm' => 80.1, 'height_cm' => 40, 'quantity' => 1]],
        ])->assertSessionHasErrors('display');
        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_admin_display_quote_requires_a_reference(): void
    {
        $this->configuredDisplay();
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['type' => 'PF', 'name' => 'Cliente Referência', 'customer_group' => 'final', 'active' => true]);
        $this->actingAs($admin)->post('/orcamentos', [
            'customer_id' => $customer->id, 'discount' => 0,
            'items' => [['kind' => 'display', 'width_cm' => 30, 'height_cm' => 40, 'quantity' => 1]],
        ])->assertSessionHasErrors('items.0.reference');
        $this->assertDatabaseCount('quotes', 0);
    }

    public function test_display_quote_uses_the_registered_mdf_thickness_in_description(): void
    {
        $configurator = $this->configuredDisplay();
        $configurator->mdfMaterial->update(['thickness_mm' => 3.1]);
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = Customer::create(['type' => 'PF', 'name' => 'Cliente Espessura', 'customer_group' => 'final', 'active' => true]);
        $this->actingAs($admin)->post('/orcamentos', [
            'customer_id' => $customer->id, 'discount' => 0,
            'items' => [['kind' => 'display', 'reference' => 'Tema teste', 'width_cm' => 30, 'height_cm' => 40, 'quantity' => 1]],
        ])->assertRedirect();
        $item = Quote::firstOrFail()->items()->firstOrFail();
        $this->assertSame('Display adesivado Tema teste · MDF 3,1 mm · 30 × 40 cm', $item->description);
        $this->assertEquals(3.1, $item->configuration_snapshot['mdf_thickness_mm']);
    }

    private function settingsData(array $override = []): array
    {
        return array_merge([
            'laser_minutes_per_unit' => 3, 'max_width_cm' => 80, 'max_height_cm' => 90,
            'mdf_loss_percent' => 10, 'adhesive_loss_percent' => 10,
            'printing_cost_per_m2' => 10, 'application_cost_per_m2' => 10,
            'base_cost_per_unit' => 2, 'assembly_cost_per_unit' => 1,
            'packaging_cost_per_unit' => .5, 'artwork_setup_cost_per_order' => 10,
            'selling_fee_percent' => 5, 'target_margin_percent' => 25,
            'reseller_min_quantity' => 10, 'wholesale_min_quantity' => 50,
        ], $override);
    }

    private function configuredDisplay(): DisplayConfigurator
    {
        $product = Product::create(['name' => 'Display personalizado', 'type' => 'product', 'base_price' => 1, 'active' => true, 'store_visible' => true]);
        $mdf = Material::create(['code' => 'MDF-3', 'name' => 'MDF 3 mm', 'category' => 'MDF', 'unit' => 'chapa', 'thickness_mm' => 3, 'width_mm' => 1000, 'height_mm' => 1000, 'cost_per_unit' => 50, 'active' => true]);
        $adhesive = Material::create(['code' => 'ADS-1', 'name' => 'Adesivo impresso', 'category' => 'Adesivo', 'unit' => 'm2', 'cost_per_unit' => 20, 'active' => true]);
        $machine = Machine::create(['name' => 'Laser', 'code' => 'LASER-1', 'type' => 'laser', 'hourly_cost' => 60, 'active' => true]);
        $configurator = DisplayConfigurator::create($this->settingsData([
            'product_id' => $product->id, 'mdf_material_id' => $mdf->id,
            'adhesive_material_id' => $adhesive->id, 'laser_machine_id' => $machine->id,
            'enabled' => true,
        ]));

        return $configurator;
    }
}
