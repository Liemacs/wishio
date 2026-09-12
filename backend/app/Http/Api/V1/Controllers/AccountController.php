<?php

namespace App\Http\Api\V1\Controllers;

use App\Domain\Account\Actions\DeleteAccount;
use App\Domain\Account\Actions\ExportUserData;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function export(Request $request, ExportUserData $export): JsonResponse
    {
        return response()->json($export($request->user()))
            ->header('Content-Disposition', 'attachment; filename="wishio-export.json"');
    }

    public function destroy(Request $request, DeleteAccount $delete): JsonResponse
    {
        $data = $request->validate([
            // Confirmarea prin email scris de mână: o ștergere ireversibilă
            // nu trebuie să poată fi declanșată dintr-o apăsare greșită.
            'confirm_email' => ['required', 'string'],
            'password'      => ['nullable', 'string'],
        ]);

        $user = $request->user();

        if (mb_strtolower(trim($data['confirm_email'])) !== mb_strtolower($user->email)) {
            throw ValidationException::withMessages(['confirm_email' => __('account.confirm_mismatch')]);
        }

        // Dacă are parolă, o cerem: altfel un telefon deblocat și lăsat pe
        // masă ar fi destul pentru a-i șterge cuiva toate datele.
        if ($user->password && ! Hash::check($data['password'] ?? '', $user->password)) {
            throw ValidationException::withMessages(['password' => __('auth.password')]);
        }

        $delete($user);

        return response()->json(status: 204);
    }
}
