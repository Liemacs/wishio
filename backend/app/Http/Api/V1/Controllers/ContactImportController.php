<?php

namespace App\Http\Api\V1\Controllers;

use App\Domain\People\Actions\ImportContacts;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactImportController extends Controller
{
    public function __construct(private readonly ImportContacts $import) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'contacts'                     => ['required', 'array', 'min:1', 'max:500'],
            'contacts.*.device_contact_id' => ['required', 'string', 'max:190'],
            'contacts.*.display_name'      => ['required', 'string', 'max:120'],
            'contacts.*.birth_date'        => ['nullable', 'date', 'before:tomorrow'],
            'contacts.*.birth_year_known'  => ['nullable', 'boolean'],

            // Telemetrie pentru riscul R1: cât de des e completată ziua de
            // naștere în agendele reale. Doar agregat, fără date personale.
            'stats.contacts_total'         => ['nullable', 'integer', 'min:0'],
            'stats.contacts_with_birthday' => ['nullable', 'integer', 'min:0'],
        ]);

        $summary = ($this->import)($request->user(), $data['contacts']);

        return response()->json(['data' => $summary->toArray()], 201);
    }
}
