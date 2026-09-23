<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ResellerApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResellerProgramTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_applies_and_only_admin_approval_enables_tier_price(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $this->get('/loja/revenda')->assertRedirect('/loja/entrar');
        $this->post('/loja/entrar', ['email' => $customer->email, 'password' => 'senha-forte-123'])->assertRedirect('/');
        $this->post('/loja/revenda', $this->applicationData())->assertRedirect('/loja/revenda');
        $application = ResellerApplication::firstOrFail();
        $this->assertSame('pending', $application->status);
        $this->assertSame('final', $customer->fresh()->customer_group);
        $this->get("/loja/produto/{$product->id}")->assertOk()->assertDontSee('R$ 70,00');
        $this->post('/loja/revenda', $this->applicationData())->assertSessionHasErrors('reseller');

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post("/revendedores/solicitacoes/{$application->id}/decidir", ['decision' => 'approve'])->assertRedirect();
        $this->assertSame('approved', $customer->fresh()->reseller_status);
        $this->assertTrue($customer->fresh()->hasResellerPricing());
        $this->post('/loja/sair');
        $this->post('/loja/entrar', ['email' => $customer->email, 'password' => 'senha-forte-123']);
        $this->get("/loja/produto/{$product->id}")->assertOk()->assertSee('R$ 70,00');
        $this->assertDatabaseHas('reseller_events', ['customer_id' => $customer->id, 'event' => 'approved', 'actor_user_id' => $admin->id]);
    }

    public function test_information_request_can_be_completed_and_rejection_allows_new_request(): void
    {
        $customer = $this->customer();
        $this->post('/loja/entrar', ['email' => $customer->email, 'password' => 'senha-forte-123']);
        $this->post('/loja/revenda', $this->applicationData());
        $application = ResellerApplication::firstOrFail();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post("/revendedores/solicitacoes/{$application->id}/decidir", ['decision' => 'needs_info'])->assertSessionHasErrors('note');
        $this->actingAs($admin)->post("/revendedores/solicitacoes/{$application->id}/decidir", ['decision' => 'needs_info', 'note' => 'Envie o link do catálogo.'])->assertRedirect();
        $this->get('/loja/revenda')->assertOk()->assertSee('Envie o link do catálogo.');
        $this->post('/loja/revenda', $this->applicationData())->assertRedirect();
        $this->assertDatabaseCount('reseller_applications', 1);
        $this->assertSame('pending', $application->fresh()->status);

        $this->actingAs($admin)->post("/revendedores/solicitacoes/{$application->id}/decidir", ['decision' => 'reject', 'note' => 'Atividade não verificada.'])->assertRedirect();
        $this->post('/loja/revenda', $this->applicationData())->assertRedirect();
        $this->assertDatabaseCount('reseller_applications', 2);
        $this->assertSame('pending', $customer->fresh()->reseller_status);
    }

    public function test_inactivity_only_suggests_review_and_human_suspension_changes_pricing(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $customer->forceFill([
            'customer_group' => 'reseller',
            'reseller_status' => 'approved',
            'reseller_approved_at' => now()->subMonths(13),
            'reseller_reviewed_at' => now()->subMonths(13),
            'created_at' => now()->subMonths(15),
        ])->save();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/revendedores')->assertOk()->assertSee($customer->name);
        $this->assertTrue($customer->fresh()->hasResellerPricing());
        $this->actingAs($admin)->post("/revendedores/clientes/{$customer->id}/revisar", ['action' => 'review', 'note' => 'Contato confirmado.'])->assertRedirect();
        $this->actingAs($admin)->get('/revendedores')->assertOk()->assertSee('Nenhuma revisão pendente.');
        $this->actingAs($admin)->post("/revendedores/clientes/{$customer->id}/revisar", ['action' => 'suspend'])->assertSessionHasErrors('note');
        $this->actingAs($admin)->post("/revendedores/clientes/{$customer->id}/revisar", ['action' => 'suspend', 'note' => 'Atividade encerrada.'])->assertRedirect();
        $this->assertFalse($customer->fresh()->hasResellerPricing());

        $this->post('/loja/entrar', ['email' => $customer->email, 'password' => 'senha-forte-123']);
        $this->get("/loja/produto/{$product->id}")->assertOk()->assertDontSee('R$ 70,00');
        $this->actingAs($admin)->post("/revendedores/clientes/{$customer->id}/revisar", ['action' => 'reactivate', 'note' => 'Atividade confirmada.'])->assertRedirect();
        $this->assertTrue($customer->fresh()->hasResellerPricing());
        $this->post('/loja/sair');
        $this->post('/loja/entrar', ['email' => $customer->email, 'password' => 'senha-forte-123']);
        $this->get("/loja/produto/{$product->id}")->assertOk()->assertSee('R$ 70,00');
    }

    public function test_customer_cannot_access_administrative_decisions(): void
    {
        $customer = $this->customer();
        $this->post('/loja/entrar', ['email' => $customer->email, 'password' => 'senha-forte-123']);
        $this->post('/loja/revenda', $this->applicationData());
        $application = ResellerApplication::firstOrFail();

        $this->get('/revendedores')->assertRedirect('/entrar');
        $this->post("/revendedores/solicitacoes/{$application->id}/decidir", ['decision' => 'approve'])->assertRedirect('/entrar');
        $this->assertSame('pending', $application->fresh()->status);
        $this->assertSame('final', $customer->fresh()->customer_group);
    }

    private function customer(): Customer
    {
        return Customer::create(['type' => 'PJ', 'name' => 'Parceiro Teste', 'email' => 'parceiro@teste.com', 'customer_group' => 'final', 'password' => 'senha-forte-123', 'active' => true]);
    }

    private function product(): Product
    {
        return Product::create(['name' => 'Peça comercial', 'type' => 'product', 'base_price' => 100, 'production_cost' => 40, 'active' => true, 'store_visible' => true, 'reseller_price' => 70, 'reseller_min_quantity' => 5]);
    }

    private function applicationData(): array
    {
        return ['business_name' => 'Parceiro Teste', 'sales_channel' => 'online', 'sales_url' => 'https://example.com', 'description' => 'Vendemos produtos personalizados em nossa loja virtual.'];
    }
}
