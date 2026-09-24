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
            ->assertDontSee('Ir para o painel');
    }

    public function test_administrator_can_return_to_dashboard_from_not_found_page(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get('/uma-pagina-antiga')
            ->assertNotFound()
            ->assertSee('Ir para o painel')
            ->assertSee('Explorar a loja');
    }
}
