<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query()->latest();
        if ($search = $request->string('busca')->trim()->toString()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
        }

        return view('products.index', ['products' => $query->paginate(12)->withQueryString()]);
    }

    public function create(): View
    {
        return view('products.form', ['product' => new Product]);
    }

    public function store(Request $request): RedirectResponse
    {
        $product = Product::create($this->validated($request));
        $this->storeGallery($request, $product);

        return redirect()->route('produtos.index')->with('success', 'Produto cadastrado com sucesso.');
    }

    public function edit(Product $produto): View
    {
        return view('products.form', ['product' => $produto]);
    }

    public function update(Request $request, Product $produto): RedirectResponse
    {
        $produto->update($this->validated($request, $produto));
        $this->storeGallery($request, $produto);

        return redirect()->route('produtos.index')->with('success', 'Produto atualizado com sucesso.');
    }

    public function destroyImage(Product $produto, ProductImage $foto): RedirectResponse
    {
        abort_unless($foto->product_id === $produto->id, 404);
        if (Str::startsWith($foto->path, '/storage/')) {
            Storage::disk('public')->delete(Str::after($foto->path, '/storage/'));
        }
        $foto->delete();

        return back()->with('success', 'Foto removida da galeria.');
    }

    public function destroyMainImage(Product $produto): RedirectResponse
    {
        if (Str::startsWith((string) $produto->image_url, '/storage/')) {
            Storage::disk('public')->delete(Str::after($produto->image_url, '/storage/'));
        }
        $produto->update(['image_url' => null]);

        return back()->with('success', 'Foto principal removida.');
    }

    public function storeImages(Request $request, Product $produto): RedirectResponse
    {
        $request->validate([
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('image')) {
            if (Str::startsWith((string) $produto->image_url, '/storage/')) {
                Storage::disk('public')->delete(Str::after($produto->image_url, '/storage/'));
            }
            $produto->update(['image_url' => Storage::url($request->file('image')->store('products', 'public'))]);
        }
        $this->storeGallery($request, $produto);

        return back()->with('success', 'Fotos atualizadas com sucesso.');
    }

    public function downloadDigital(Product $produto): StreamedResponse
    {
        abort_unless(is_string($produto->digital_file_path) && str_starts_with($produto->digital_file_path, 'digital-products/') && ! str_contains($produto->digital_file_path, '..') && Storage::disk('local')->exists($produto->digital_file_path), 404);

        return Storage::disk('local')->download($produto->digital_file_path, $produto->digital_file_name, ['Content-Type' => 'application/octet-stream', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $skuRule = 'unique:products,sku'.($product ? ','.$product->id : '');
        $data = $request->validate([
            'sku' => ['nullable', 'string', 'max:60', $skuRule], 'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:product,service,virtual'], 'base_price' => ['required', 'numeric', 'min:0'],
            'production_cost' => ['required', 'numeric', 'min:0'], 'made_to_order' => ['boolean'],
            'active' => ['boolean'], 'description' => ['nullable', 'string', 'max:3000'], 'store_visible' => ['boolean'],
            'store_slug' => ['nullable', 'string', 'max:180', 'unique:products,store_slug'.($product ? ','.$product->id : '')],
            'image_url' => ['nullable', 'string', 'max:1000', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! Str::startsWith($value, '/storage/') && filter_var($value, FILTER_VALIDATE_URL) === false) {
                    $fail('Informe uma URL válida para a imagem.');
                }
            }],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], 'allow_personalization' => ['boolean'],
            'store_featured' => ['boolean'], 'store_occasions' => ['nullable', 'array', 'max:3'],
            'store_occasions.*' => ['in:presente,decoracao,empresa'],
            'reseller_price' => ['nullable', 'required_with:reseller_min_quantity', 'numeric', 'min:0', 'lte:base_price'],
            'reseller_min_quantity' => ['nullable', 'required_with:reseller_price', 'integer', 'min:1', 'max:100'],
            'wholesale_price' => ['nullable', 'required_with:wholesale_min_quantity', 'numeric', 'min:0', 'lte:base_price'],
            'wholesale_min_quantity' => ['nullable', 'required_with:wholesale_price', 'integer', 'min:1', 'max:100'],
            'images' => ['nullable', 'array', 'max:8'], 'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'digital_file' => ['nullable', 'file', 'max:51200', function (string $attribute, mixed $value, \Closure $fail): void {
                if (! in_array(Str::lower($value->getClientOriginalExtension()), ['dxf', 'svg', 'zip'], true)) {
                    $fail('Envie um arquivo DXF, SVG ou ZIP.');
                }
            }],
            'digital_version' => ['nullable', 'string', 'max:40'],
            'digital_license_terms' => ['nullable', 'string', 'max:3000'],
        ]);
        if ($data['type'] === 'virtual') {
            $data['made_to_order'] = false;
            $data['allow_personalization'] = false;
            if ($data['store_visible'] && ! ($request->hasFile('digital_file') || ($product?->digital_file_path && Storage::disk('local')->exists($product->digital_file_path)))) {
                throw ValidationException::withMessages(['digital_file' => 'Envie o arquivo antes de mostrar o produto virtual na loja.']);
            }
            if ($data['store_visible'] && (blank($data['digital_version'] ?? null) || blank($data['digital_license_terms'] ?? null))) {
                throw ValidationException::withMessages(['digital_license_terms' => 'Informe a versão e os termos de uso antes de publicar o arquivo.']);
            }
        } elseif ($request->hasFile('digital_file')) {
            throw ValidationException::withMessages(['digital_file' => 'Selecione Produto virtual para enviar um arquivo digital.']);
        }
        $data['store_occasions'] = array_values(array_unique($data['store_occasions'] ?? []));
        if ($request->hasFile('image')) {
            if ($product && Str::startsWith((string) $product->image_url, '/storage/')) {
                Storage::disk('public')->delete(Str::after($product->image_url, '/storage/'));
            }
            $data['image_url'] = Storage::url($request->file('image')->store('products', 'public'));
        }
        if ($request->hasFile('digital_file')) {
            $file = $request->file('digital_file');
            $data['digital_file_path'] = $file->store('digital-products', 'local');
            $data['digital_file_name'] = basename(str_replace('\\', '/', $file->getClientOriginalName()));
            $data['digital_file_size'] = $file->getSize();
            $data['digital_file_sha256'] = hash_file('sha256', $file->getRealPath());
        }
        unset($data['image'], $data['images'], $data['digital_file']);

        return $data;
    }

    private function storeGallery(Request $request, Product $product): void
    {
        $nextOrder = ((int) $product->images()->max('sort_order')) + 1;
        foreach ($request->file('images', []) as $image) {
            $product->images()->create([
                'path' => Storage::url($image->store('products', 'public')),
                'sort_order' => $nextOrder++,
            ]);
        }
    }
}
