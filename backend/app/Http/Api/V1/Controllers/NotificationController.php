<?php

namespace App\Http\Api\V1\Controllers;

use App\Domain\Reminders\Models\QueuedNotification;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Reminderul a fost apăsat. Contează doar prima deschidere: ora ei arată
     * cât de repede a reacționat omul, iar a doua nu mai spune nimic nou.
     */
    public function opened(Request $request, QueuedNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 404);

        if ($notification->opened_at === null) {
            $notification->update(['opened_at' => now()]);
        }

        return response()->json(status: 204);
    }
}
