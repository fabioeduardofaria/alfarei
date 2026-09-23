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

## Display personalizado na loja

No administrativo, abra `/administracao/displays` (menu **Configurador de displays**). Cadastre antes um produto sob encomenda visível na loja, o MDF 3 mm, o adesivo e a máquina laser. Selecione esses cadastros, configure os custos próprios do display e defina somente a largura e a altura máximas. Na loja, o cliente informa medidas livres em passos de 0,1 cm, dentro desses limites, e recebe o preço antes de comprar. A mesma tela administrativa permite simular um orçamento com as medidas reais. O tempo de laser começa em 3 minutos por unidade e pode ser alterado no configurador.

Use a simulação administrativa para conferir custo e preço nas medidas e quantidade reais. O configurador só pode ser publicado quando produto, materiais, máquina e limites máximos estiverem válidos. Depois de publicado, o cliente monta o pedido em `/loja/display` e vê o preço antes de adicionar ao carrinho. O preço e a composição ficam preservados no pedido, mesmo que os custos sejam atualizados depois.

## Nomes e textos personalizados

Abra `/administracao/nomes-textos` para configurar o produto, a máquina laser, os limites de tamanho, o tempo estimado de corte por caractere, os custos de acabamento e as margens. Os materiais MDF e acrílico elegíveis vêm do cadastro de materiais ativo: cada um precisa ter espessura, dimensões da chapa e custo. O acabamento "sem pintura" usa a cor original da chapa; "branco" e "pintado" usam custos de acabamento configuráveis. Uma chapa branca pronta pode ser cadastrada como material branco e usada sem pintura.

No orçamento, selecione **Nome ou texto recortado** e informe texto, material, acabamento, altura e quantidade. A largura pode ser informada ou estimada pelo sistema; a estimativa e o tempo de corte são parâmetros administrativos, não uma leitura do desenho final. O servidor recalcula o preço ao salvar e guarda a composição na proposta e no pedido. Após habilitar o produto na loja, o cliente usa `/loja/nome-personalizado` para ver o preço e adicionar ao carrinho. Uma arte final fora das medidas cotadas exige nova cotação.

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
