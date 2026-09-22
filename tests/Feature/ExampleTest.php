<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FinanceEntry;
use App\Models\Lead;
use App\Models\Machine;
use App\Models\MachineMaintenance;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\StoreSetting;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ProductionWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $this->get('/painel')->assertRedirect('/entrar');
        $this->get('/entrar')->assertOk()->assertSee('images/alfarei-logo.png');
    }

    public function test_user_permissions_block_direct_access_to_restricted_modules(): void
    {
        $user = User::factory()->create(['role' => 'commercial', 'permissions' => ['dashboard']]);

        $this->actingAs($user)->get('/painel')->assertOk()->assertSee('images/alfarei-logo.png')->assertDontSee('Financeiro')->assertDontSee('Usuários e acessos');
        $this->actingAs($user)->get('/financeiro')->assertForbidden();
        $this->actingAs($user)->get('/usuarios')->assertForbidden();
    }

    public function test_administrator_can_configure_complete_storefront_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->put('/administracao/loja', [
            'store_name' => 'Alfarei Online', 'hero_title' => 'Sua ideia em madeira', 'hero_cta_label' => 'Conhecer catálogo',
            'primary_color' => '#123456', 'accent_color' => '#abcdef', 'deposit_percent' => 40,
            'announcement_active' => true, 'announcement_text' => 'Frete especial nesta semana.',
            'pickup_enabled' => true, 'shipping_enabled' => true, 'pix_enabled' => true, 'store_open' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('store_settings', ['store_name' => 'Alfarei Online', 'announcement_active' => true, 'deposit_percent' => 40]);
        $this->get('/')->assertOk()->assertSee('Frete especial nesta semana.');
        $this->assertSame('#123456', StoreSetting::firstOrFail()->primary_color);
    }

    public function test_administrator_can_create_user_with_specific_modules(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/usuarios', [
            'name' => 'Operador de Produção', 'email' => 'producao@alfarei.local', 'password' => 'segredo123',
            'role' => 'production', 'active' => 1, 'permissions' => ['dashboard', 'production', 'materials'],
        ])->assertRedirect('/usuarios');

        $this->assertDatabaseHas('users', ['email' => 'producao@alfarei.local', 'role' => 'production', 'active' => true]);
        $this->assertSame(['dashboard', 'production', 'materials'], User::where('email', 'producao@alfarei.local')->firstOrFail()->permissions);
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

    public function test_product_photo_upload_is_saved_for_the_store(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/produtos', [
            'name' => 'Produto com foto', 'type' => 'product', 'base_price' => 100, 'production_cost' => 40,
            'active' => true, 'image' => UploadedFile::fake()->image('produto.jpg'),
        ])->assertRedirect('/produtos');

        $product = Product::where('name', 'Produto com foto')->firstOrFail();
        $this->assertStringStartsWith('/storage/products/', $product->image_url);
        Storage::disk('public')->assertExists(Str::after($product->image_url, '/storage/'));
        $this->actingAs($user)->put("/produtos/{$product->id}", [
            'name' => $product->name, 'type' => 'product', 'base_price' => 100, 'production_cost' => 40,
            'image_url' => $product->image_url,
        ])->assertRedirect('/produtos');
        $this->actingAs($user)->delete("/produtos/{$product->id}/foto-principal")->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'image_url' => null]);
    }

    public function test_product_can_receive_and_remove_gallery_photos(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $product = Product::create(['name' => 'Galeria de produto', 'type' => 'product', 'base_price' => 100, 'production_cost' => 40]);

        $this->actingAs($user)->post("/produtos/{$product->id}/fotos", [
            'images' => [UploadedFile::fake()->image('detalhe-1.jpg'), UploadedFile::fake()->image('detalhe-2.png')],
        ])->assertRedirect();

        $image = $product->images()->firstOrFail();
        $this->assertDatabaseCount('product_images', 2);
        $this->actingAs($user)->delete("/produtos/{$product->id}/fotos/{$image->id}")->assertRedirect();
        $this->assertDatabaseCount('product_images', 1);
    }

    public function test_commercial_user_can_create_lead_and_register_activity(): void
    {
        $user = User::factory()->create(['role' => 'commercial', 'permissions' => ['crm']]);

        $this->actingAs($user)->post('/crm', ['name' => 'Contato CRM', 'email' => 'crm@cliente.test', 'stage' => 'new', 'estimated_value' => 1250, 'probability' => 25])->assertRedirect();

        $lead = Lead::firstOrFail();
        $this->assertDatabaseHas('lead_activities', ['lead_id' => $lead->id, 'type' => 'created']);
        $this->actingAs($user)->post("/crm/{$lead->id}/atividades", ['type' => 'call', 'description' => 'Ligação inicial realizada.'])->assertRedirect();
        $this->assertDatabaseHas('lead_activities', ['lead_id' => $lead->id, 'type' => 'call']);
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

    public function test_quote_records_advanced_pricing_delivery_time_and_versioned_technical_attachment(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $customer = Customer::create(['type' => 'PF', 'name' => 'Cliente Técnico', 'customer_group' => 'final']);
        $product = Product::create(['name' => 'Peça técnica', 'type' => 'product', 'base_price' => 100, 'production_cost' => 40]);

        $this->actingAs($user)->post('/orcamentos', [
            'customer_id' => $customer->id, 'status' => 'draft', 'discount' => 0, 'discount_percent' => 10,
            'tax_percent' => 10, 'commission_percent' => 0, 'fee_percent' => 0, 'target_margin_percent' => 20,
            'production_lead_days' => 3, 'delivery_lead_days' => 2,
            'items' => [['product_id' => $product->id, 'description' => 'Peça técnica', 'quantity' => 1, 'unit_price' => 100, 'unit_cost' => 0]],
        ])->assertRedirect();

        $quote = Quote::firstOrFail();
        $this->assertDatabaseHas('quotes', ['id' => $quote->id, 'total' => 90, 'delivery_lead_days' => 2, 'tax_percent' => 10, 'target_margin_percent' => 20]);
        $this->assertSame('57.14', $quote->fresh()->suggested_total);

        $this->actingAs($user)->post("/orcamentos/{$quote->id}/anexos", ['category' => 'technical', 'file' => UploadedFile::fake()->create('corte.svg', 10, 'image/svg+xml')])->assertRedirect();
        $this->assertDatabaseHas('quote_attachments', ['quote_id' => $quote->id, 'original_name' => 'corte.svg', 'category' => 'technical', 'version' => 1]);
    }

    public function test_customer_can_approve_a_quote_from_a_secure_public_link_and_download_pdf(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['type' => 'PF', 'name' => 'Cliente Proposta', 'email' => 'proposta@cliente.test', 'customer_group' => 'final']);
        $quote = Quote::create(['number' => 'ORC-2026-PUBLICA', 'customer_id' => $customer->id, 'created_by' => $user->id, 'version' => 1, 'status' => 'sent', 'valid_until' => today()->addDays(7), 'deposit_percent' => 50, 'production_lead_days' => 5, 'payment_terms' => '50% de entrada.', 'approval_token' => 'token-publico-seguro', 'subtotal' => 200, 'cost_total' => 80, 'total' => 200]);
        QuoteItem::create(['quote_id' => $quote->id, 'description' => 'Peça personalizada', 'type' => 'custom', 'quantity' => 1, 'unit_price' => 200, 'unit_cost' => 80, 'total' => 200, 'total_cost' => 80]);

        $this->get('/proposta/token-publico-seguro')->assertOk()->assertSee('images/alfarei-logo.png')->assertSee('Proposta comercial')->assertSee('Peça personalizada');
        $pdf = $this->get('/proposta/token-publico-seguro/pdf')->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString('/Subtype /Image', $pdf->getContent());
        $this->post('/proposta/token-publico-seguro/resposta', ['decision' => 'approved', 'response' => 'Podem seguir com a produção.'])->assertRedirect('/proposta/token-publico-seguro');

        $this->assertDatabaseHas('quotes', ['id' => $quote->id, 'status' => 'approved', 'customer_response' => 'Podem seguir com a produção.']);
        $this->assertNotNull($quote->fresh()->approved_at);
    }

    public function test_sent_quote_is_immutable_and_revision_gets_its_own_link(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $customer = Customer::create(['type' => 'PF', 'name' => 'Cliente das versões', 'customer_group' => 'final']);
        $quote = Quote::create([
            'number' => 'ORC-2026-VERSAO', 'customer_id' => $customer->id, 'created_by' => $user->id,
            'version' => 1, 'status' => 'draft', 'valid_until' => today()->addWeek(),
            'subtotal' => 200, 'cost_total' => 80, 'total' => 200,
        ]);
        QuoteItem::create([
            'quote_id' => $quote->id, 'description' => 'Peça original', 'type' => 'custom',
            'quantity' => 1, 'unit_price' => 200, 'unit_cost' => 80, 'total' => 200, 'total_cost' => 80,
        ]);
        Storage::disk('public')->put('quotes/original/desenho.svg', '<svg></svg>');
        $attachment = $quote->attachments()->create([
            'uploaded_by' => $user->id, 'original_name' => 'desenho.svg', 'path' => 'quotes/original/desenho.svg',
            'mime_type' => 'image/svg+xml', 'size' => 11, 'category' => 'technical', 'version' => 1, 'approved' => true,
        ]);

        $this->actingAs($user)->post("/orcamentos/{$quote->id}/enviar")->assertRedirect();
        $quote->refresh();
        $this->assertSame('sent', $quote->status);
        $this->assertNotNull($quote->approval_token);
        $this->get("/orcamentos/{$quote->id}/edit")->assertOk()->assertSee('Esta versão foi publicada e preservada.');
        $this->put("/orcamentos/{$quote->id}", [
            'customer_id' => $customer->id, 'status' => 'approved', 'discount' => 0,
            'items' => [['description' => 'Preço alterado', 'quantity' => 1, 'unit_price' => 20, 'unit_cost' => 10]],
        ])->assertStatus(409);
        $this->post("/orcamentos/{$quote->id}/anexos", [
            'category' => 'technical', 'file' => UploadedFile::fake()->create('alteracao.svg', 2, 'image/svg+xml'),
        ])->assertStatus(409);
        $this->assertDatabaseHas('quote_items', ['quote_id' => $quote->id, 'description' => 'Peça original', 'unit_price' => 200]);

        $this->post("/orcamentos/{$quote->id}/nova-versao")->assertRedirect();
        $revision = Quote::where('parent_quote_id', $quote->id)->firstOrFail();
        $this->assertSame('draft', $revision->status);
        $this->assertNull($revision->approval_token);
        $this->assertSame('ORC-2026-VERSAO-V2', $revision->number);
        $copiedAttachment = $revision->attachments()->firstOrFail();
        $this->assertSame($attachment->original_name, $copiedAttachment->original_name);
        $this->assertNotSame($attachment->path, $copiedAttachment->path);
        $this->assertFalse($copiedAttachment->approved);
        Storage::disk('public')->assertExists($copiedAttachment->path);
        $this->post("/orcamentos/{$revision->id}/enviar")->assertRedirect();
        $this->assertDatabaseHas('quotes', ['id' => $quote->id, 'status' => 'superseded']);
        $this->post("/proposta/{$quote->approval_token}/resposta", ['decision' => 'approved'])->assertSessionHasErrors('response');
        $this->assertNotSame($quote->approval_token, $revision->fresh()->approval_token);
    }

    public function test_technical_sheet_calculates_machine_time_cost_for_quotes(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['type' => 'PF', 'name' => 'Cliente Custo', 'customer_group' => 'final']);
        $material = Material::create(['code' => 'CUSTO-CHAPA', 'name' => 'Chapa custo', 'category' => 'MDF', 'unit' => 'un', 'cost_per_unit' => 20, 'stock_quantity' => 5, 'minimum_stock' => 1]);
        $machine = Machine::create(['name' => 'Router custo', 'code' => 'ROUTER-CUSTO', 'type' => 'router', 'hourly_cost' => 0, 'labor_cost_hour' => 60, 'status' => 'available', 'active' => true]);
        $product = Product::create(['name' => 'Peça calculada', 'type' => 'product', 'base_price' => 100, 'production_cost' => 0]);

        $this->actingAs($user)->put("/produtos/{$product->id}/ficha-tecnica", [
            'materials' => [['material_id' => $material->id, 'quantity' => 2, 'loss_percent' => 10]],
            'operations' => [['machine_id' => $machine->id, 'operation_name' => 'Corte', 'minutes_per_unit' => 30, 'setup_minutes' => 0]],
        ])->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'production_cost' => 74]);
        $this->actingAs($user)->post('/orcamentos', [
            'customer_id' => $customer->id, 'status' => 'draft', 'discount' => 0,
            'items' => [['product_id' => $product->id, 'description' => 'Peça calculada', 'quantity' => 1, 'unit_price' => 100, 'unit_cost' => 0]],
        ])->assertRedirect();
        $this->assertDatabaseHas('quote_items', ['product_id' => $product->id, 'unit_cost' => 74]);
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
        $this->assertDatabaseHas('customer_notifications', ['order_id' => $order->id, 'event' => 'production_started', 'status' => 'pending']);
    }

    public function test_production_user_can_schedule_and_complete_machine_maintenance(): void
    {
        $user = User::factory()->create(['role' => 'production', 'permissions' => ['production']]);
        $machine = Machine::create(['name' => 'Laser manutenção', 'code' => 'LASER-MANUT', 'type' => 'laser_co2', 'hourly_cost' => 50, 'status' => 'available', 'active' => true]);

        $this->actingAs($user)->post("/maquinas/{$machine->id}/manutencoes", [
            'type' => 'preventive', 'scheduled_for' => '2026-10-15', 'cost' => 180.50,
            'machine_hours' => 125, 'description' => 'Limpeza e alinhamento.',
        ])->assertRedirect();

        $maintenance = MachineMaintenance::firstOrFail();
        $this->assertDatabaseHas('machines', ['id' => $machine->id, 'next_maintenance_at' => '2026-10-15 00:00:00']);
        $this->actingAs($user)->post("/maquinas/{$machine->id}/manutencoes/{$maintenance->id}/concluir")->assertRedirect();
        $this->assertDatabaseHas('machine_maintenances', ['id' => $maintenance->id, 'status' => 'completed', 'cost' => 180.50]);
        $this->assertDatabaseHas('machines', ['id' => $machine->id, 'next_maintenance_at' => null]);
        $this->actingAs($user)->get("/maquinas/{$machine->id}/manutencoes?from=2026-10-01&to=2026-10-31")
            ->assertOk()
            ->assertSee('R$ 180,50')
            ->assertSee('Preventiva');
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

    public function test_finished_production_queues_ready_notification_for_pickup(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['type' => 'PF', 'name' => 'Cliente Retirada', 'phone' => '(65) 99999-1111', 'customer_group' => 'final']);
        Machine::create(['name' => 'Laser finalização', 'code' => 'LASER-FINAL', 'type' => 'laser_co2', 'hourly_cost' => 50, 'status' => 'available', 'active' => true]);
        $order = Order::create(['number' => 'PED-2026-PRONTO', 'customer_id' => $customer->id, 'created_by' => $user->id, 'status' => 'ready_for_production', 'source' => 'ecommerce', 'delivery_method' => 'pickup', 'total' => 200, 'cost_total' => 80, 'deposit_amount' => 100]);

        $workflow = app(ProductionWorkflowService::class);
        $production = $workflow->createFromOrder($order, $user->id);
        foreach (range(1, 5) as $step) {
            $production = $workflow->advance($production->fresh(), $user->id);
        }

        $this->assertDatabaseHas('production_orders', ['id' => $production->id, 'status' => 'ready']);
        $this->assertDatabaseHas('customer_notifications', ['order_id' => $order->id, 'event' => 'ready', 'status' => 'pending', 'recipient' => '65999991111']);
    }

    public function test_receiving_purchase_increases_material_stock_once(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::create(['name' => 'Fornecedor teste', 'active' => true]);
        $material = Material::create(['code' => 'COMPRA-TESTE', 'name' => 'Material compra', 'category' => 'Insumo', 'unit' => 'un', 'cost_per_unit' => 10, 'stock_quantity' => 1, 'minimum_stock' => 2]);

        $this->actingAs($user)->post('/compras', ['supplier_id' => $supplier->id, 'status' => 'ordered', 'expected_at' => '2026-10-01', 'payment_due_at' => '2026-10-20', 'items' => [['material_id' => $material->id, 'quantity' => 4, 'unit_cost' => 12.5]]])->assertRedirect();

        $purchase = PurchaseOrder::firstOrFail();
        $this->actingAs($user)->post("/compras/{$purchase->id}/receber")->assertRedirect();
        $this->actingAs($user)->post("/compras/{$purchase->id}/receber")->assertRedirect();

        $this->assertDatabaseHas('materials', ['id' => $material->id, 'stock_quantity' => 5, 'cost_per_unit' => 12.5]);
        $this->assertDatabaseHas('inventory_movements', ['material_id' => $material->id, 'type' => 'purchase', 'status' => 'posted', 'quantity' => 4]);
        $this->assertDatabaseCount('inventory_movements', 1);
        $this->assertDatabaseHas('finance_entries', ['type' => 'payable', 'source_type' => 'purchase', 'source_id' => $purchase->id, 'amount' => 50, 'status' => 'pending']);
        $this->assertSame('2026-10-20', FinanceEntry::where('source_type', 'purchase')->firstOrFail()->due_date->format('Y-m-d'));
    }

    public function test_draft_purchase_creates_payable_only_when_confirmed(): void
    {
        $user = User::factory()->create();
        $supplier = Supplier::create(['name' => 'Fornecedor do rascunho', 'active' => true]);
        $material = Material::create(['code' => 'COMPRA-RASC', 'name' => 'Material rascunho', 'category' => 'Insumo', 'unit' => 'un', 'cost_per_unit' => 10, 'stock_quantity' => 0, 'minimum_stock' => 1]);
        $this->actingAs($user)->post('/compras', [
            'supplier_id' => $supplier->id, 'status' => 'draft', 'payment_due_at' => '2026-11-15',
            'items' => [['material_id' => $material->id, 'quantity' => 2, 'unit_cost' => 10]],
        ])->assertRedirect();
        $purchase = PurchaseOrder::firstOrFail();
        $this->assertDatabaseCount('finance_entries', 0);
        $this->post("/compras/{$purchase->id}/receber")->assertStatus(409);
        $this->post("/compras/{$purchase->id}/confirmar")->assertRedirect();
        $this->assertDatabaseHas('finance_entries', ['source_type' => 'purchase', 'source_id' => $purchase->id, 'amount' => 20]);
        $this->post("/compras/{$purchase->id}/confirmar")->assertStatus(409);
        $this->assertDatabaseCount('finance_entries', 1);
    }

    public function test_finance_entry_can_be_settled(): void
    {
        $user = User::factory()->create();
        $entry = FinanceEntry::create(['type' => 'receivable', 'source_type' => 'manual_test', 'source_id' => 999, 'description' => 'Receita teste', 'amount' => 120, 'status' => 'pending']);

        $this->actingAs($user)->post("/financeiro/{$entry->id}/baixar", ['payment_method' => 'pix'])->assertRedirect();

        $this->assertDatabaseHas('finance_entries', ['id' => $entry->id, 'status' => 'paid', 'payment_method' => 'pix']);
    }

    public function test_manual_payable_installments_and_partial_payments_keep_correct_balance(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $supplier = Supplier::create(['name' => 'Locador da oficina', 'active' => true]);
        $this->actingAs($user)->get('/financeiro/criar')->assertOk()->assertSee('Nova conta');
        $this->actingAs($user)->post('/financeiro', [
            'type' => 'payable', 'description' => 'Aluguel da oficina', 'category' => 'Aluguel',
            'supplier_id' => $supplier->id, 'amount' => '100.01', 'due_date' => '2026-01-31',
            'installments' => 3, 'document_number' => 'CONT-42',
        ])->assertRedirect();

        $entries = FinanceEntry::where('source_type', 'manual')->orderBy('installment_number')->get();
        $this->assertCount(3, $entries);
        $this->assertSame(['33.33', '33.33', '33.35'], $entries->pluck('amount')->all());
        $this->assertSame(['2026-01-31', '2026-02-28', '2026-03-31'], $entries->map(fn ($entry) => $entry->due_date->format('Y-m-d'))->all());
        $first = $entries->first();
        $this->get("/financeiro/{$first->id}")->assertOk()->assertSee('Parcelas desta conta');

        $this->post("/financeiro/{$first->id}/baixar", [
            'amount' => '10.00', 'paid_at' => '2026-02-01', 'payment_method' => 'pix',
            'receipt' => UploadedFile::fake()->image('comprovante.png'),
        ])->assertRedirect();
        $this->assertDatabaseHas('finance_entries', ['id' => $first->id, 'status' => 'partial', 'paid_amount' => '10.00']);
        $this->assertEqualsWithDelta(23.33, $first->fresh()->remaining_amount, 0.001);
        $payment = $first->payments()->firstOrFail();
        Storage::disk('local')->assertExists($payment->receipt_path);
        $this->get("/financeiro/{$first->id}/pagamentos/{$payment->id}/comprovante")->assertOk();
        $restricted = User::factory()->create(['role' => 'commercial', 'permissions' => ['dashboard']]);
        $this->actingAs($restricted)->get("/financeiro/{$first->id}/pagamentos/{$payment->id}/comprovante")->assertForbidden();
        $this->actingAs($user);
        $this->post("/financeiro/{$first->id}/baixar", ['amount' => '23.34'])->assertSessionHasErrors('amount');
        $this->post("/financeiro/{$first->id}/baixar", ['amount' => '23.33', 'payment_method' => 'transfer'])->assertRedirect();
        $this->assertDatabaseHas('finance_entries', ['id' => $first->id, 'status' => 'paid', 'paid_amount' => '33.33']);
        $this->assertDatabaseCount('finance_payments', 2);
        $this->get('/financeiro')->assertOk()->assertViewHas('pendingPayable', fn ($value) => abs($value - 66.68) < 0.001)
            ->assertViewHas('paidOut', fn ($value) => abs($value - 33.33) < 0.001);
    }

    public function test_manual_account_can_be_edited_or_cancelled_only_before_payment(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post('/financeiro', [
            'type' => 'receivable', 'description' => 'Serviço avulso', 'category' => 'Serviços',
            'amount' => '80.00', 'due_date' => '2026-10-10', 'installments' => 1,
        ])->assertRedirect();
        $entry = FinanceEntry::where('source_type', 'manual')->firstOrFail();
        $this->put("/financeiro/{$entry->id}", [
            'type' => 'receivable', 'description' => 'Serviço corrigido', 'category' => 'Serviços',
            'amount' => '90.00', 'due_date' => '2026-10-11',
        ])->assertRedirect();
        $this->assertSame('Serviço corrigido', $entry->fresh()->description);
        $this->post("/financeiro/{$entry->id}/cancelar")->assertRedirect();
        $this->assertSame('cancelled', $entry->fresh()->status);
        $this->post("/financeiro/{$entry->id}/baixar", ['amount' => 10])->assertSessionHasErrors('amount');
    }

    public function test_store_checkout_creates_order_and_financial_entries(): void
    {
        $user = User::factory()->create(['active' => true]);
        $product = Product::create(['name' => 'Peça da loja', 'type' => 'product', 'base_price' => 150, 'production_cost' => 60, 'made_to_order' => true, 'active' => true, 'store_visible' => true, 'allow_personalization' => true]);
        $this->assertTrue($product->fresh()->store_visible);
        $this->get("/loja/produto/{$product->id}")->assertOk()->assertSee('Peça da loja');

        $cart = ["{$product->id}|Nome da cliente" => ['product_id' => $product->id, 'quantity' => 2, 'personalization' => 'Nome da cliente']];
        $this->withSession(['store_cart' => $cart])->get('/loja/carrinho')->assertSee('Peça da loja');
        $response = $this->withSession(['store_cart' => $cart])
            ->post('/loja/finalizar', ['name' => 'Cliente Loja', 'email' => 'loja@cliente.test', 'phone' => '(65) 99999-0000', 'city' => 'Cuiabá', 'state' => 'MT', 'delivery_method' => 'pickup']);
        $response->assertRedirect();

        $this->assertDatabaseHas('orders', ['source' => 'ecommerce', 'total' => 300, 'deposit_amount' => 150, 'status' => 'awaiting_deposit', 'delivery_method' => 'pickup']);
        $this->assertDatabaseHas('order_items', ['description' => 'Peça da loja · Personalização: Nome da cliente', 'quantity' => 2]);
        $this->assertDatabaseCount('finance_entries', 2);
        $this->assertDatabaseHas('customer_notifications', ['event' => 'order_created', 'status' => 'pending', 'recipient' => '65999990000']);
        $deposit = FinanceEntry::where('source_type', 'payment')->firstOrFail();
        $this->actingAs($user)->post("/financeiro/{$deposit->id}/baixar", ['amount' => 150, 'payment_method' => 'pix'])->assertRedirect();
        $this->assertDatabaseHas('orders', ['status' => 'awaiting_art']);
        $this->assertNotNull(Order::firstOrFail()->deposit_paid_at);
        $this->assertDatabaseHas('payments', ['id' => $deposit->source_id, 'status' => 'paid']);
    }

    public function test_confirming_deposit_from_order_records_financial_payment_once(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['type' => 'PF', 'name' => 'Cliente da entrada', 'customer_group' => 'final']);
        $order = Order::create([
            'number' => 'PED-ENTRADA-TESTE', 'customer_id' => $customer->id, 'created_by' => $user->id,
            'status' => 'awaiting_deposit', 'source' => 'ecommerce', 'total' => 100, 'cost_total' => 40, 'deposit_amount' => 50,
        ]);
        $payment = Payment::create(['order_id' => $order->id, 'type' => 'deposit', 'status' => 'pending', 'amount' => 50]);
        $entry = FinanceEntry::create([
            'type' => 'receivable', 'source_type' => 'payment', 'source_id' => $payment->id,
            'description' => 'Entrada do pedido', 'amount' => 50, 'status' => 'pending',
        ]);

        $this->actingAs($user)->post("/pedidos/{$order->id}/confirmar-entrada")->assertRedirect();
        $this->post("/pedidos/{$order->id}/confirmar-entrada")->assertRedirect();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'ready_for_production']);
        $this->assertDatabaseHas('finance_entries', ['id' => $entry->id, 'status' => 'paid', 'paid_amount' => 50]);
        $this->assertDatabaseHas('finance_payments', ['finance_entry_id' => $entry->id, 'amount' => 50]);
        $this->assertDatabaseCount('finance_payments', 1);
    }

    public function test_store_tracking_requires_matching_order_number_and_customer_email(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['type' => 'PF', 'name' => 'Cliente Rastreio', 'email' => 'rastreio@cliente.test', 'customer_group' => 'final']);
        Order::create(['number' => 'PED-2026-RASTREIO', 'customer_id' => $customer->id, 'created_by' => $user->id, 'status' => 'in_production', 'source' => 'ecommerce', 'total' => 100, 'cost_total' => 40, 'deposit_amount' => 50]);

        $this->post('/loja/rastrear', ['number' => 'PED-2026-RASTREIO', 'email' => 'rastreio@cliente.test'])->assertOk()->assertSee('Em produção');
        $this->post('/loja/rastrear', ['number' => 'PED-2026-RASTREIO', 'email' => 'outro@cliente.test'])->assertOk()->assertSee('Não encontramos um pedido');
    }

    public function test_quality_order_can_be_dispatched_with_tracking_code(): void
    {
        $user = User::factory()->create();
        $customer = Customer::create(['type' => 'PF', 'name' => 'Cliente Entrega', 'customer_group' => 'final']);
        $order = Order::create(['number' => 'PED-2026-ENTREGA', 'customer_id' => $customer->id, 'created_by' => $user->id, 'status' => 'quality', 'source' => 'ecommerce', 'delivery_method' => 'shipping', 'delivery_city' => 'Cuiabá', 'delivery_state' => 'MT', 'total' => 100, 'cost_total' => 40, 'deposit_amount' => 50]);

        $this->actingAs($user)->post("/pedidos/{$order->id}/despachar", ['tracking_code' => 'BR123456789'])->assertRedirect();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'delivered', 'tracking_code' => 'BR123456789']);
        $this->assertDatabaseHas('customer_notifications', ['order_id' => $order->id, 'event' => 'shipped', 'status' => 'pending']);
    }
}
