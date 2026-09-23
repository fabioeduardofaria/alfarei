<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StoreTierPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_and_final_customer_only_see_normal_price(): void
    {
        $product = $this->product();

        $this->get('/')->assertOk()->assertSee('R$ 100,00')->assertDontSee('Preço normal R$')->assertDontSee('R$ 70,00');
        $this->post('/loja/cadastrar', [
            'name' => 'Cliente Final', 'email' => 'final@teste.com',
            'password' => 'senha-forte-123', 'password_confirmation' => 'senha-forte-123',
            'customer_group' => 'reseller',
        ])->assertRedirect('/');
        $customer = Customer::where('email', 'final@teste.com')->firstOrFail();
        $this->assertSame('final', $customer->customer_group);
        $this->assertTrue(Hash::check('senha-forte-123', $customer->password));
        $this->get("/loja/produto/{$product->id}")->assertOk()->assertSee('R$ 100,00')->assertDontSee('R$ 70,00');
    }

    public function test_reseller_tier_only_applies_after_minimum_and_is_used_in_order(): void
    {
        User::factory()->create(['active' => true]);
        $product = $this->product();
        $customer = Customer::create(['type' => 'PJ', 'name' => 'Revenda Teste', 'email' => 'revenda@teste.com', 'customer_group' => 'reseller', 'password' => 'senha-forte-123', 'active' => true]);

        $this->post('/loja/entrar', ['email' => $customer->email, 'password' => 'senha-forte-123'])->assertRedirect('/');
        $this->assertTrue(Auth::guard('customer')->check());
        $this->get("/loja/produto/{$product->id}")->assertOk()->assertSee('Preço normal R$ 100,00')->assertSee('R$ 70,00')->assertSee('a partir de 5 unidades');

        $cart = ["{$product->id}|" => ['product_id' => $product->id, 'quantity' => 4, 'personalization' => null]];
        $this->withSession(['store_cart' => $cart])->get('/loja/carrinho')->assertOk()->assertSee('4 × R$ 100,00')->assertSee('R$ 400,00');
        $cart["{$product->id}|"]['quantity'] = 5;
        $this->withSession(['store_cart' => $cart])->get('/loja/carrinho')->assertOk()->assertSee('5 × R$ 70,00')->assertSee('R$ 350,00')->assertSee('Preço revendedor ativado');
        $this->withSession(['store_cart' => $cart])->get('/loja/finalizar')->assertOk()->assertSee('5 × R$ 70,00')->assertSee('R$ 350,00');
        $this->withSession(['store_cart' => $cart])->post('/loja/finalizar', [
            'name' => 'Nome adulterado', 'email' => 'outro@teste.com', 'phone' => '65999990000',
            'city' => 'Cuiabá', 'state' => 'MT', 'delivery_method' => 'pickup',
        ])->assertRedirect();
        $this->assertDatabaseHas('orders', ['customer_id' => $customer->id, 'total' => 350]);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'unit_price' => 70, 'quantity' => 5, 'total' => 350]);
    }

    public function test_wholesale_uses_its_own_tier_and_guest_cannot_claim_special_account_by_email(): void
    {
        StoreSetting::create(['store_open' => true]);
        $product = $this->product();
        $customer = Customer::create(['type' => 'PJ', 'name' => 'Atacado Teste', 'email' => 'atacado@teste.com', 'customer_group' => 'wholesale', 'password' => 'senha-forte-123', 'active' => true]);
        $cart = ["{$product->id}|" => ['product_id' => $product->id, 'quantity' => 10, 'personalization' => null]];

        $this->withSession(['store_cart' => $cart])->post('/loja/finalizar', [
            'name' => 'Impostor', 'email' => $customer->email, 'phone' => '65999990000',
            'city' => 'Cuiabá', 'state' => 'MT', 'delivery_method' => 'pickup',
        ])->assertSessionHasErrors('email');
        $this->assertDatabaseCount('orders', 0);

        $this->post('/loja/entrar', ['email' => $customer->email, 'password' => 'senha-forte-123'])->assertRedirect('/');
        $this->get("/loja/produto/{$product->id}")->assertOk()->assertSee('R$ 55,00')->assertSee('a partir de 10 unidades')->assertDontSee('R$ 70,00');
        $this->withSession(['store_cart' => $cart])->get('/loja/carrinho')->assertOk()->assertSee('10 × R$ 55,00')->assertSee('R$ 550,00');
        $this->post('/loja/sair')->assertRedirect('/');
        $this->withSession(['store_cart' => $cart])->get('/loja/carrinho')->assertOk()->assertSee('10 × R$ 100,00');
    }

    public function test_admin_sets_tiers_and_customer_access_without_changing_existing_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/produtos', [
            'name' => 'Placa com faixas', 'type' => 'product', 'base_price' => 100,
            'production_cost' => 40, 'reseller_price' => 70, 'reseller_min_quantity' => 5,
            'wholesale_price' => 55, 'wholesale_min_quantity' => 10,
        ])->assertRedirect('/produtos');
        $product = Product::where('name', 'Placa com faixas')->firstOrFail();
        $this->assertSame('70.00', $product->reseller_price);
        $this->assertSame(10, $product->wholesale_min_quantity);

        $this->actingAs($admin)->post('/clientes', [
            'name' => 'Nova Revenda', 'type' => 'PJ', 'email' => 'nova@revenda.com',
            'customer_group' => 'reseller', 'password' => 'senha-forte-123', 'active' => 1,
        ])->assertRedirect('/clientes');
        $customer = Customer::where('email', 'nova@revenda.com')->firstOrFail();
        $this->assertTrue(Hash::check('senha-forte-123', $customer->password));
        $this->actingAs($admin)->put("/clientes/{$customer->id}", [
            'name' => $customer->name, 'type' => 'PJ', 'email' => $customer->email,
            'customer_group' => 'reseller', 'active' => 1,
        ])->assertRedirect('/clientes');
        $this->assertTrue(Hash::check('senha-forte-123', $customer->fresh()->password));
    }

    public function test_minimum_counts_same_product_across_personalizations_and_rejects_invalid_tiers(): void
    {
        $product = $this->product();
        $customer = Customer::create(['type' => 'PJ', 'name' => 'Revenda', 'email' => 'revenda@loja.test', 'customer_group' => 'reseller', 'password' => 'senha-forte-123', 'active' => true]);
        $this->post('/loja/entrar', ['email' => $customer->email, 'password' => 'senha-forte-123'])->assertRedirect('/');
        $cart = [
            "{$product->id}|A" => ['product_id' => $product->id, 'quantity' => 3, 'personalization' => 'A'],
            "{$product->id}|B" => ['product_id' => $product->id, 'quantity' => 2, 'personalization' => 'B'],
        ];
        $this->withSession(['store_cart' => $cart])->get('/loja/carrinho')->assertOk()->assertSee('3 × R$ 70,00')->assertSee('2 × R$ 70,00')->assertSee('R$ 350,00');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post('/produtos', [
            'name' => 'Faixa inválida', 'type' => 'product', 'base_price' => 100,
            'production_cost' => 40, 'reseller_price' => 120, 'reseller_min_quantity' => 5,
        ])->assertSessionHasErrors('reseller_price');
        $this->assertDatabaseMissing('products', ['name' => 'Faixa inválida']);
    }

    public function test_customer_can_change_own_password_but_not_with_wrong_current_password(): void
    {
        $customer = Customer::create(['type' => 'PJ', 'name' => 'Revenda', 'email' => 'conta@loja.test', 'customer_group' => 'reseller', 'password' => 'senha-inicial-123', 'active' => true]);
        $this->get('/loja/minha-conta')->assertRedirect('/loja/entrar');
        $this->post('/loja/entrar', ['email' => $customer->email, 'password' => 'senha-inicial-123'])->assertRedirect('/');
        $this->get('/loja/minha-conta')->assertOk()->assertSee('Revendedor');
        $this->put('/loja/minha-conta/senha', ['current_password' => 'errada', 'password' => 'nova-senha-123', 'password_confirmation' => 'nova-senha-123'])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('senha-inicial-123', $customer->fresh()->password));
        $this->put('/loja/minha-conta/senha', ['current_password' => 'senha-inicial-123', 'password' => 'nova-senha-123', 'password_confirmation' => 'nova-senha-123'])->assertRedirect();
        $this->assertTrue(Hash::check('nova-senha-123', $customer->fresh()->password));
    }

    public function test_store_login_is_separate_from_admin_and_inactive_customer_cannot_enter(): void
    {
        $inactive = Customer::create(['type' => 'PJ', 'name' => 'Inativo', 'email' => 'inativo@loja.test', 'customer_group' => 'reseller', 'password' => 'senha-forte-123', 'active' => false]);
        $this->post('/loja/entrar', ['email' => $inactive->email, 'password' => 'senha-forte-123'])->assertSessionHasErrors('email');
        $this->assertFalse(Auth::guard('customer')->check());

        $active = Customer::create(['type' => 'PJ', 'name' => 'Ativo', 'email' => 'ativo@loja.test', 'customer_group' => 'reseller', 'password' => 'senha-forte-123', 'active' => true]);
        $this->post('/loja/entrar', ['email' => $active->email, 'password' => 'senha-forte-123'])->assertRedirect('/');
        $this->get('/painel')->assertRedirect('/entrar');
    }

    private function product(): Product
    {
        return Product::create([
            'name' => 'Peça comercial', 'type' => 'product', 'base_price' => 100,
            'production_cost' => 40, 'active' => true, 'store_visible' => true,
            'reseller_price' => 70, 'reseller_min_quantity' => 5,
            'wholesale_price' => 55, 'wholesale_min_quantity' => 10,
        ]);
    }
}
