<?php

namespace App\Http\Controllers;

use App\Models\CustomerNotification;
use App\Services\CustomerNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CustomerNotificationController extends Controller
{
    public function index(): View
    {
        return view('notifications.index', [
            'notifications' => CustomerNotification::with('order.customer')->latest()->paginate(20),
            'eventLabels' => CustomerNotificationService::EVENTS,
        ]);
    }

    public function markSent(CustomerNotification $notificacao): RedirectResponse
    {
        $notificacao->update(['status' => 'sent', 'sent_at' => now()]);

        return back()->with('success', 'Notificação marcada como enviada.');
    }

    public function reopen(CustomerNotification $notificacao): RedirectResponse
    {
        $notificacao->update(['status' => 'pending', 'sent_at' => null]);

        return back()->with('success', 'Notificação retornou para os pendentes.');
    }
}
