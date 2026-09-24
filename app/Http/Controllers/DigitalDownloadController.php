<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DigitalDownloadController extends Controller
{
    public function __invoke(OrderItem $item): StreamedResponse
    {
        $customer = Auth::guard('customer')->user();
        $item->loadMissing('order');
        abort_unless($customer?->active && $item->type === 'virtual' &&
            $item->order->customer_id === $customer->id && $item->order->digitalDownloadReady(), 404);

        $snapshot = $item->configuration_snapshot ?? [];
        $path = $snapshot['digital_file_path'] ?? null;
        abort_unless(is_string($path) && str_starts_with($path, 'digital-products/') && ! str_contains($path, '..') && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $snapshot['digital_file_name'] ?? 'arquivo-alfarei', [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
