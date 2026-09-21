<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'role', 'active', 'permissions', 'last_login_at', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_LABELS = [
        'admin' => 'Administrador',
        'commercial' => 'Comercial',
        'production' => 'Produção',
        'stock_purchases' => 'Estoque e compras',
        'finance' => 'Financeiro',
        'logistics' => 'Logística',
        'service' => 'Atendimento',
    ];

    public const MODULE_LABELS = [
        'dashboard' => 'Painel', 'customers' => 'Clientes', 'materials' => 'Materiais',
        'suppliers' => 'Fornecedores', 'products' => 'Produtos e ficha técnica', 'quotes' => 'Orçamentos',
        'orders' => 'Pedidos', 'production' => 'Produção', 'deliveries' => 'Entregas',
        'purchases' => 'Compras', 'finance' => 'Financeiro', 'notifications' => 'Notificações',
        'store_settings' => 'Configurar loja', 'users' => 'Usuários e acessos',
    ];

    public static function defaultPermissions(string $role): array
    {
        return match ($role) {
            'admin' => array_keys(self::MODULE_LABELS),
            'commercial' => ['dashboard', 'customers', 'products', 'quotes', 'orders', 'notifications'],
            'production' => ['dashboard', 'materials', 'products', 'orders', 'production', 'notifications'],
            'stock_purchases' => ['dashboard', 'materials', 'products', 'suppliers', 'purchases'],
            'finance' => ['dashboard', 'orders', 'finance'],
            'logistics' => ['dashboard', 'orders', 'deliveries', 'notifications'],
            'service' => ['dashboard', 'customers', 'orders', 'notifications'],
            default => [],
        };
    }

    public function effectivePermissions(): array
    {
        return $this->role === 'admin' ? array_keys(self::MODULE_LABELS) : ($this->permissions ?? self::defaultPermissions($this->role));
    }

    public function canAccess(string $module): bool
    {
        return $this->active && in_array($module, $this->effectivePermissions(), true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'permissions' => 'array',
            'active' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
