<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductionController;
use App\Http\Controllers\TechnicalSheetController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\FinanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => auth()->check() ? redirect()->route('dashboard') : redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/entrar', [AuthController::class, 'create'])->name('login');
    Route::post('/entrar', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/painel', DashboardController::class)->name('dashboard');
    Route::resource('clientes', CustomerController::class)->except('show', 'destroy');
    Route::resource('materiais', MaterialController::class)->except('show', 'destroy');
    Route::resource('fornecedores', SupplierController::class)->except('show', 'destroy');
    Route::get('compras', [PurchaseOrderController::class, 'index'])->name('compras.index');
    Route::get('compras/nova', [PurchaseOrderController::class, 'create'])->name('compras.create');
    Route::post('compras', [PurchaseOrderController::class, 'store'])->name('compras.store');
    Route::get('compras/{compra}', [PurchaseOrderController::class, 'show'])->name('compras.show');
    Route::post('compras/{compra}/receber', [PurchaseOrderController::class, 'receive'])->name('compras.receive');
    Route::get('financeiro', [FinanceController::class, 'index'])->name('financeiro.index');
    Route::post('financeiro/{lancamento}/baixar', [FinanceController::class, 'settle'])->name('financeiro.settle');
    Route::resource('produtos', ProductController::class)->except('show', 'destroy');
    Route::get('produtos/{produto}/ficha-tecnica', [TechnicalSheetController::class, 'edit'])->name('produtos.technical-sheet.edit');
    Route::put('produtos/{produto}/ficha-tecnica', [TechnicalSheetController::class, 'update'])->name('produtos.technical-sheet.update');
    Route::resource('orcamentos', QuoteController::class)->except('show', 'destroy');
    Route::post('orcamentos/{orcamento}/converter-em-pedido', [QuoteController::class, 'convertToOrder'])->name('orcamentos.convert');
    Route::get('pedidos', [OrderController::class, 'index'])->name('pedidos.index');
    Route::get('pedidos/{pedido}', [OrderController::class, 'show'])->name('pedidos.show');
    Route::post('pedidos/{pedido}/confirmar-entrada', [OrderController::class, 'confirmDeposit'])->name('pedidos.confirm-deposit');
    Route::post('pedidos/{pedido}/aprovar-arte', [OrderController::class, 'approveArt'])->name('pedidos.approve-art');
    Route::post('pedidos/{pedido}/criar-op', [OrderController::class, 'createProductionOrder'])->name('pedidos.create-op');
    Route::get('producao', [ProductionController::class, 'index'])->name('producao.index');
    Route::get('producao/{ordemProducao}', [ProductionController::class, 'show'])->name('producao.show');
    Route::post('producao/{ordemProducao}/avancar', [ProductionController::class, 'advance'])->name('producao.advance');
    Route::post('producao/{ordemProducao}/ocorrencia', [ProductionController::class, 'logOccurrence'])->name('producao.occurrence');
    Route::post('/sair', [AuthController::class, 'destroy'])->name('logout');
});
