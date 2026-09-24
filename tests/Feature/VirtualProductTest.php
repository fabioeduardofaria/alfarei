<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FinanceEntry;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VirtualProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_virtual_product_needs_private_file_and_license_before_publication(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post('/produtos', $this->productData())->assertSessionHasErrors('digital_file');
        $this->assertDatabaseMissing('products', ['name' => 'Molde DXF']);

        $this->post('/produtos', $this->productData() + ['digital_file' => UploadedFile::fake()->create('molde.dxf', 2)])
            ->assertRedirect(route('produtos.index'));
        $product = Product::firstOrFail();
        $this->assertSame('virtual', $product->type);
        $this->assertFalse($product->made_to_order);
        $this->assertFalse($product->allow_personalization);
        $this->assertNotNull($product->digital_file_sha256);
        Storage::disk('local')->assertExists($product->digital_file_path);
        $this->get(route('produtos.digital.download', $product))->assertDownload('molde.dxf');
    }

    public function test_digital_catalog_hides_unpublished_files_and_rejects_other_upload_types(): void
    {
        Storage::fake('local');
        StoreSetting::create(['store_open' => true]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));

        $this->post('/produtos', array_replace($this->productData(), ['store_visible' => 0]))
            ->assertRedirect(route('produtos.index'));
        $draft = Product::firstOrFail();
        $this->get(route('loja.index', ['uso' => 'arquivos-digitais']))->assertOk()->assertDontSee('Molde DXF');
        $this->get(route('loja.product', $draft))->assertNotFound();

        $this->post('/produtos', array_replace($this->productData(), [
            'name' => 'Arquivo inválido', 'digital_file' => UploadedFile::fake()->create('programa.exe', 2),
        ]))->assertSessionHasErrors('digital_file');

        $this->put(route('produtos.update', $draft), array_replace($this->productData(), [
            'digital_file' => UploadedFile::fake()->create('molde.svg', 2),
        ]))->assertRedirect(route('produtos.index'));
        $this->get(route('loja.index', ['uso' => 'arquivos-digitais']))->assertOk()->assertSee('Molde DXF');
        $this->get(route('loja.index', ['uso' => 'pronta-entrega']))->assertOk()->assertDontSee('Molde DXF');
    }

    public function test_buyer_downloads_only_after_full_payment_and_keeps_the_purchased_version(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        StoreSetting::create(['store_open' => true, 'pix_enabled' => true, 'pix_key' => 'teste@pix.local', 'deposit_percent' => 50]);
        $this->actingAs($admin)->post('/produtos', $this->productData() + [
            'digital_file' => UploadedFile::fake()->create('molde-v1.svg', 2),
        ])->assertRedirect(route('produtos.index'));
        $product = Product::firstOrFail();
        $originalPath = $product->digital_file_path;

        $this->get(route('loja.product', $product))->assertOk()->assertSee('Download após confirmação');
        $this->post(route('loja.add', $product), ['quantity' => 1])->assertRedirect(route('loja.cart'));
        $this->get(route('loja.checkout'))->assertRedirect(route('loja.account.login'));

        $this->put(route('produtos.update', $product), array_replace($this->productData(), [
            'digital_version' => '2.0', 'digital_file' => UploadedFile::fake()->create('molde-v2.svg', 2),
        ]))->assertRedirect(route('produtos.index'));
        $this->assertNotSame($originalPath, $product->fresh()->digital_file_path);
        $this->assertSame('2.0', $product->fresh()->digital_version);
        Storage::disk('local')->assertExists($originalPath);
        $this->get(route('loja.cart'))->assertOk()->assertSee('versão 1.0');

        $buyer = Customer::create(['type' => 'PF', 'name' => 'Comprador Digital', 'email' => 'buyer@teste.com', 'password' => 'senha-segura', 'active' => true]);
        Auth::guard('customer')->login($buyer);
        $this->get(route('loja.checkout'))->assertOk()->assertSee('Entrega digital')->assertDontSee('Endereço de entrega');
        $this->post(route('loja.place-order'), [
            'name' => $buyer->name, 'email' => $buyer->email, 'phone' => '65999990000', 'delivery_method' => 'digital',
        ])->assertSessionHasErrors('accept_digital_license');
        $this->assertDatabaseCount('orders', 0);
        $this->post(route('loja.place-order'), [
            'name' => $buyer->name, 'email' => $buyer->email, 'phone' => '65999990000', 'delivery_method' => 'digital', 'accept_digital_license' => 1,
        ])->assertRedirect();
        $order = Order::with('items')->firstOrFail();
        $item = $order->items->firstOrFail();
        $this->assertSame('digital', $order->delivery_method);
        $this->assertEquals($order->total, $order->deposit_amount);
        $this->assertSame($originalPath, $item->configuration_snapshot['digital_file_path']);
        $this->assertSame(1, FinanceEntry::where('source_type', 'payment')->count());
        $this->assertSame(0, FinanceEntry::where('source_type', 'order_balance')->count());
        $this->get(route('loja.digital.download', $item))->assertNotFound();

        $this->post(route('pedidos.confirm-deposit', $order))->assertRedirect();
        $this->assertSame('delivered', $order->fresh()->status);
        $this->get(route('loja.account.profile'))->assertOk()->assertSee('Baixar arquivo');
        $this->get(route('loja.digital.download', $item))->assertDownload('molde-v1.svg');

        $other = Customer::create(['type' => 'PF', 'name' => 'Outra pessoa', 'email' => 'other@teste.com', 'password' => 'senha-segura', 'active' => true]);
        Auth::guard('customer')->login($other);
        $this->get(route('loja.digital.download', $item))->assertNotFound();
    }

    public function test_mixed_order_keeps_physical_delivery_and_releases_file_after_finance_receipt(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('digital-products/arte.svg', '<svg></svg>');
        $admin = User::factory()->create(['role' => 'admin']);
        StoreSetting::create(['store_open' => true, 'pickup_enabled' => true, 'deposit_percent' => 50]);
        $digital = Product::create([
            'name' => 'Arte SVG', 'type' => 'virtual', 'base_price' => 30, 'production_cost' => 1,
            'active' => true, 'store_visible' => true, 'made_to_order' => false,
            'digital_file_path' => 'digital-products/arte.svg', 'digital_file_name' => 'arte.svg',
            'digital_version' => '1.0', 'digital_license_terms' => 'Uso individual.',
        ]);
        $physical = Product::create(['name' => 'Placa MDF', 'type' => 'product', 'base_price' => 50, 'production_cost' => 20, 'active' => true, 'store_visible' => true, 'made_to_order' => true]);
        $buyer = Customer::create(['type' => 'PF', 'name' => 'Comprador Misto', 'email' => 'mixed@teste.com', 'password' => 'senha-segura', 'active' => true]);
        Auth::guard('customer')->login($buyer);

        $this->post(route('loja.add', $digital), ['quantity' => 1])->assertRedirect();
        $this->post(route('loja.add', $physical), ['quantity' => 1])->assertRedirect();
        $this->get(route('loja.checkout'))->assertOk()->assertSee('Como você quer receber?');
        $this->post(route('loja.place-order'), [
            'name' => $buyer->name, 'email' => $buyer->email, 'phone' => '65999990000',
            'city' => 'Cuiabá', 'state' => 'MT', 'delivery_method' => 'pickup', 'accept_digital_license' => 1,
        ])->assertRedirect();
        $order = Order::with('items')->firstOrFail();
        $this->assertSame('pickup', $order->delivery_method);
        $this->assertEquals(80, $order->deposit_amount);
        $this->assertSame(0, FinanceEntry::where('source_type', 'order_balance')->count());
        $digitalItem = $order->items->firstWhere('type', 'virtual');
        $this->get(route('loja.digital.download', $digitalItem))->assertNotFound();

        $this->actingAs($admin)->post(route('financeiro.settle', FinanceEntry::where('source_type', 'payment')->firstOrFail()))->assertRedirect();
        $this->assertTrue($order->fresh()->digitalDownloadReady());
        $this->assertSame('awaiting_art', $order->fresh()->status);
        $this->get(route('loja.digital.download', $digitalItem))->assertDownload('arte.svg');
        $this->assertDatabaseHas('customer_notifications', ['order_id' => $order->id, 'event' => 'digital_ready']);
    }

    private function productData(): array
    {
        return [
            'name' => 'Molde DXF', 'type' => 'virtual', 'base_price' => 39.90, 'production_cost' => 2,
            'made_to_order' => 1, 'allow_personalization' => 1, 'active' => 1, 'store_visible' => 1,
            'digital_version' => '1.0', 'digital_license_terms' => 'Uso para corte de peças físicas. Não redistribuir o arquivo.',
        ];
    }
}
