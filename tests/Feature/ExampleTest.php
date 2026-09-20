<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Order;
use App\Models\Machine;
use App\Models\Material;
use App\Models\OrderItem;
use App\Models\Supplier;
use App\Models\FinanceEntry;
use App\Services\ProductionWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $this->get('/painel')->assertRedirect('/entrar');
    }

    public function test_authenticated_user_can_create_customer(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/clientes', [
            'type' => 'PJ', 'name' => 'Alfarei Teste', 'document' => '12.345.678/0001-01',
            'email' => 'teste@alfarei.local', 'phone' => '(65) 99999-0000', 'city' => 'Cuiabá',
            'state' => 'MT', 'customer_group' => 'final', 'active' => 1,
        ])->assertRedirect('/clientes');

        $this->assertDatabaseHas('customers', ['name' => 'Alfarei Teste', 'customer_group' => 'final']);
    }

    public function test_quote_uses_product_cost_when_calculating_margin(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['type' => 'PF', 'name' => 'Cliente Teste', 'customer_group' => 'final']);
        $product = Product::create(['name' => 'Produto Teste', 'type' => 'product', 'base_price' => 100, 'production_cost' => 40]);

        $this->actingAs($user)->post('/orcamentos', [
            'customer_id' => $customer->id, 'status' => 'draft', 'valid_until' => '2026-10-01', 'discount' => 10,
            'items' => [[
                'product_id' => $product->id, 'description' => 'Produto Teste', 'quantity' => 2,
                'unit_price' => 100, 'unit_cost' => 1,
            ]],
        ])->assertRedirect();

        $this->assertDatabaseHas('quotes', ['subtotal' => 200, 'cost_total' => 80, 'total' => 190]);
        $this->assertDatabaseHas('quote_items', ['unit_cost' => 40, 'total_cost' => 80]);
    }

    public function test_approved_quote_converts_to_order_and_requires_deposit(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['type' => 'PF', 'name' => 'Cliente Pedido', 'customer_group' => 'final']);
        $product = Product::create(['name' => 'Produto Pedido', 'type' => 'product', 'base_price' => 200, 'production_cost' => 80, 'made_to_order' => true]);
        $quote = Quote::create(['number' => 'ORC-2026-9999', 'customer_id' => $customer->id, 'created_by' => $user->id, 'version' => 1, 'status' => 'approved', 'subtotal' => 200, 'cost_total' => 80, 'total' => 200]);
        QuoteItem::create(['quote_id' => $quote->id, 'product_id' => $product->id, 'description' => 'Produto Pedido', 'type' => 'product', 'quantity' => 1, 'unit_price' => 200, 'unit_cost' => 80, 'total' => 200, 'total_cost' => 80]);

        $this->actingAs($user)->post("/orcamentos/{$quote->id}/converter-em-pedido")->assertRedirect();

        $this->assertDatabaseHas('orders', ['quote_id' => $quote->id, 'status' => 'awaiting_deposit', 'deposit_amount' => 100]);
        $this->assertDatabaseHas('payments', ['type' => 'deposit', 'status' => 'pending', 'amount' => 100]);
        $this->assertDatabaseHas('finance_entries', ['type' => 'receivable', 'source_type' => 'order_balance', 'source_id' => Order::firstOrFail()->id, 'amount' => 100, 'status' => 'pending']);
    }

    public function test_released_order_creates_production_order_and_tracks_time(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['type' => 'PF', 'name' => 'Cliente Produção', 'customer_group' => 'final']);
        Machine::create(['name' => 'Laser teste', 'code' => 'LASER-TESTE', 'type' => 'laser_co2', 'hourly_cost' => 50, 'status' => 'available', 'active' => true]);
        $order = Order::create(['number' => 'PED-2026-9999', 'customer_id' => $customer->id, 'created_by' => $user->id, 'status' => 'ready_for_production', 'source' => 'quote', 'total' => 200, 'cost_total' => 80, 'deposit_amount' => 100]);

        $workflow = app(ProductionWorkflowService::class);
        $production = $workflow->createFromOrder($order, $user->id);
        $production = $workflow->advance($production, $user->id, 35);

        $this->assertDatabaseHas('production_orders', ['order_id' => $order->id, 'status' => 'preparation', 'actual_minutes' => 35]);
        $this->assertDatabaseHas('production_events', ['production_order_id' => $production->id, 'type' => 'status', 'to_status' => 'preparation', 'minutes' => 35]);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'in_production']);
    }

    public function test_bom_material_is_reserved_then_consumed_when_cutting_starts(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['type' => 'PF', 'name' => 'Cliente Estoque', 'customer_group' => 'final']);
        Machine::create(['name' => 'Laser estoque', 'code' => 'LASER-ESTOQUE', 'type' => 'laser_co2', 'hourly_cost' => 50, 'status' => 'available', 'active' => true]);
        $material = Material::create(['code' => 'MDF-TESTE', 'name' => 'MDF teste', 'category' => 'MDF', 'unit' => 'chapa', 'cost_per_unit' => 100, 'stock_quantity' => 5, 'minimum_stock' => 1]);
        $product = Product::create(['name' => 'Produto com ficha', 'type' => 'product', 'base_price' => 200, 'production_cost' => 80]);
        $product->materials()->attach($material->id, ['quantity' => 2, 'loss_percent' => 0]);
        $order = Order::create(['number' => 'PED-2026-ESTOQUE', 'customer_id' => $customer->id, 'created_by' => $user->id, 'status' => 'ready_for_production', 'source' => 'quote', 'total' => 200, 'cost_total' => 80, 'deposit_amount' => 100]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'description' => 'Produto com ficha', 'type' => 'product', 'quantity' => 1, 'unit_price' => 200, 'unit_cost' => 80, 'total' => 200, 'total_cost' => 80]);

        $workflow = app(ProductionWorkflowService::class);
        $production = $workflow->createFromOrder($order, $user->id);
        $this->assertDatabaseHas('materials', ['id' => $material->id, 'stock_quantity' => 5, 'reserved_quantity' => 2]);
        $this->assertDatabaseHas('inventory_movements', ['production_order_id' => $production->id, 'type' => 'reserve', 'status' => 'active', 'quantity' => 2]);

        $workflow->advance($production, $user->id);
        $workflow->advance($production->fresh(), $user->id);

        $this->assertDatabaseHas('materials', ['id' => $material->id, 'stock_quantity' => 3, 'reserved_quantity' => 0]);
        $this->assertDatabaseHas('inventory_movements', ['production_order_id' => $production->id, 'type' => 'reserve', 'status' => 'consumed']);
        $this->assertDatabaseHas('inventory_movements', ['production_order_id' => $production->id, 'type' => 'consume', 'status' => 'posted', 'quantity' => 2]);
    }

    public function test_receiving_purchase_increases_material_stock_once(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::create(['name' => 'Fornecedor teste', 'active' => true]);
        $material = Material::create(['code' => 'COMPRA-TESTE', 'name' => 'Material compra', 'category' => 'Insumo', 'unit' => 'un', 'cost_per_unit' => 10, 'stock_quantity' => 1, 'minimum_stock' => 2]);

        $this->actingAs($user)->post('/compras', ['supplier_id' => $supplier->id, 'status' => 'ordered', 'expected_at' => '2026-10-01', 'items' => [['material_id' => $material->id, 'quantity' => 4, 'unit_cost' => 12.5]]])->assertRedirect();

        $purchase = \App\Models\PurchaseOrder::firstOrFail();
        $this->actingAs($user)->post("/compras/{$purchase->id}/receber")->assertRedirect();
        $this->actingAs($user)->post("/compras/{$purchase->id}/receber")->assertRedirect();

        $this->assertDatabaseHas('materials', ['id' => $material->id, 'stock_quantity' => 5, 'cost_per_unit' => 12.5]);
        $this->assertDatabaseHas('inventory_movements', ['material_id' => $material->id, 'type' => 'purchase', 'status' => 'posted', 'quantity' => 4]);
        $this->assertDatabaseCount('inventory_movements', 1);
        $this->assertDatabaseHas('finance_entries', ['type' => 'payable', 'source_type' => 'purchase', 'source_id' => $purchase->id, 'amount' => 50, 'status' => 'pending']);
    }

    public function test_finance_entry_can_be_settled(): void
    {
        $user = User::factory()->create();
        $entry = FinanceEntry::create(['type' => 'receivable', 'source_type' => 'manual_test', 'source_id' => 999, 'description' => 'Receita teste', 'amount' => 120, 'status' => 'pending']);

        $this->actingAs($user)->post("/financeiro/{$entry->id}/baixar", ['payment_method' => 'pix'])->assertRedirect();

        $this->assertDatabaseHas('finance_entries', ['id' => $entry->id, 'status' => 'paid', 'payment_method' => 'pix']);
    }

    public function test_store_checkout_creates_order_and_financial_entries(): void
    {
        User::factory()->create(['active' => true]);
        $product = Product::create(['name' => 'Peça da loja', 'type' => 'product', 'base_price' => 150, 'production_cost' => 60, 'made_to_order' => true, 'active' => true, 'store_visible' => true, 'allow_personalization' => true]);
        $this->assertTrue($product->fresh()->store_visible);

        $cart = ["{$product->id}|Nome da cliente" => ['product_id' => $product->id, 'quantity' => 2, 'personalization' => 'Nome da cliente']];
        $this->withSession(['store_cart' => $cart])->get('/loja/carrinho')->assertSee('Peça da loja');
        $response = $this->withSession(['store_cart' => $cart])
            ->post('/loja/finalizar', ['name' => 'Cliente Loja', 'email' => 'loja@cliente.test', 'phone' => '(65) 99999-0000', 'city' => 'Cuiabá', 'state' => 'MT']);
        $response->assertRedirect();

        $this->assertDatabaseHas('orders', ['source' => 'ecommerce', 'total' => 300, 'deposit_amount' => 150, 'status' => 'awaiting_deposit']);
        $this->assertDatabaseHas('order_items', ['description' => 'Peça da loja · Personalização: Nome da cliente', 'quantity' => 2]);
        $this->assertDatabaseCount('finance_entries', 2);
    }
}
