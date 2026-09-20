# Alfarei CNC

Sistema operacional para comercial, produção, estoque e loja virtual da Alfarei.

## Abrir no VS Code

Abra esta pasta no VS Code:

```powershell
code "C:\Users\fabio\Documents\Codex\2026-09-19\pre\outputs\alfarei-system"
```

## Executar localmente

Requisitos: PHP 8.3+, Composer e MySQL 8+.

```powershell
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve --port=8001
```

Depois acesse `http://127.0.0.1:8001`.

## Banco de dados

O ambiente local usa MySQL com o banco `alfarei`. A conexão está configurada no arquivo `.env`.

- Banco: `alfarei`
- Host: `127.0.0.1`
- Porta: `3306`

SQLite é usado somente pelos testes automatizados, em memória, para que a suíte não altere dados locais.

## Acesso de demonstração

- E-mail: `admin@alfarei.local`
- Senha: `password`

Troque esta senha antes de qualquer implantação compartilhada.

## Estado atual

- Autenticação de usuário e perfis base.
- Painel administrativo.
- Cadastros persistidos de clientes, materiais e produtos/serviços.
- Orçamentos em desenvolvimento: estrutura de banco, itens, custo, preço, desconto, margem e validade.

## Organização

- `app/Models`: entidades do negócio.
- `app/Http/Controllers`: fluxos HTTP e validações.
- `app/Services`: regras de negócio centralizadas.
- `database/migrations`: estrutura versionada do banco.
- `resources/views`: interface Blade.
- `tests/Feature`: testes automatizados.

## Próximas entregas

1. Finalizar interface de orçamentos e aprovação.
2. Pedido, entrada e central de arte.
3. Ordem de Produção, estoque e apontamentos.
4. Financeiro e loja virtual integrada.
