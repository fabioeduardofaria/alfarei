<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Material;
use App\Models\Machine;
use App\Models\Product;
use App\Models\User;
use App\Models\Supplier;
use App\Models\StoreSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@alfarei.local'], ['name' => 'Administrador Alfarei', 'password' => Hash::make('password'), 'role' => 'admin', 'active' => true]);
        Customer::updateOrCreate(['document' => '12.345.678/0001-00'], ['type' => 'PJ', 'name' => 'Studio Casa Arquitetura', 'email' => 'contato@studiocasa.test', 'phone' => '(65) 99999-1000', 'city' => 'Cuiabá', 'state' => 'MT', 'customer_group' => 'final']);
        $material = Material::updateOrCreate(['code' => 'MDF-18-CRU'], ['name' => 'MDF cru 18 mm', 'category' => 'MDF', 'unit' => 'chapa', 'thickness_mm' => 18, 'width_mm' => 2750, 'height_mm' => 1850, 'cost_per_unit' => 189.90, 'stock_quantity' => 2, 'minimum_stock' => 6, 'location' => 'A-02']);
        $product = Product::updateOrCreate(['sku' => 'PLA-DEC-01'], ['name' => 'Placa decorativa personalizada', 'type' => 'product', 'base_price' => 89, 'production_cost' => 42.50, 'made_to_order' => true, 'description' => 'Produto personalizável para a loja virtual.', 'store_visible' => true, 'store_slug' => 'placa-decorativa-personalizada', 'allow_personalization' => true]);
        $product->materials()->sync([$material->id => ['quantity' => 0.100, 'loss_percent' => 10]]);
        Supplier::updateOrCreate(['document' => '98.765.432/0001-10'], ['name' => 'Madeiras Centro-Oeste', 'email' => 'vendas@madeirasco.test', 'phone' => '(65) 3333-1200', 'contact_name' => 'Comercial MDF', 'active' => true]);
        StoreSetting::firstOrCreate([], ['store_name' => 'Alfarei', 'hero_title' => 'Peças que fazem sua ideia existir.', 'hero_subtitle' => 'Produtos personalizados com acabamento profissional, produzidos pela Alfarei CNC.', 'delivery_message' => 'Produção sob encomenda com acompanhamento do seu pedido.', 'store_open' => true]);
        Machine::updateOrCreate(['code' => 'LASER-CO2-01'], ['name' => 'Laser CO₂ Principal', 'type' => 'laser_co2', 'hourly_cost' => 68.50, 'status' => 'available', 'active' => true]);
    }
}
