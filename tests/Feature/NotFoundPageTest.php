<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotFoundPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_branded_not_found_page_with_store_links(): void
    {
        $this->get('/um-caminho-que-nao-existe')
            ->assertNotFound()
            ->assertSee('Este caminho saiu')
            ->assertSee('Explorar a loja')
            ->assertSee('Rastrear pedido')
            ->assertDontSee('Voltar ao sistema');
    }

    public function test_administrator_can_return_to_dashboard_from_not_found_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->post('/entrar', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->get('/uma-pagina-antiga')
            ->assertNotFound()
            ->assertSee('Voltar ao sistema')
            ->assertSee('href="'.route('dashboard').'"', false)
            ->assertSee('Explorar a loja');
    }
}
