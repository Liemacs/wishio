<?php

namespace App\Http\Api\V1\Controllers;

use App\Domain\People\Models\Interest;
use App\Domain\People\Models\Person;
use App\Domain\Profiles\Actions\FindIdentityCandidates;
use App\Domain\Profiles\Actions\ResolveSubmissionIdentity;
use App\Domain\Profiles\Models\ProfileSubmission;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * „Cine este?” — completările din linkul public care așteaptă alegerea
 * proprietarului, fiindcă numele seamănă cu contacte existente (S9.8).
 */
class SubmissionController extends Controller
{
    public function pending(Request $request, FindIdentityCandidates $candidates): JsonResponse
    {
        $profile = $request->user()->publicProfile;

        if ($profile === null) {
            return response()->json(['data' => []]);
        }

        return response()->json([
            'data' => $profile->submissions()->pending()->oldest('id')->get()
                ->map(fn (ProfileSubmission $submission) => [
                    'id'               => $submission->id,
                    'display_name'     => $submission->display_name,
                    'birth_date'       => $submission->birth_date?->format('Y-m-d'),
                    'birth_year_known' => $submission->birth_year_known,
                    'interests'        => Interest::whereIn('code', $submission->interest_codes ?? [])->get()
                        ->map(fn (Interest $interest) => ['code' => $interest->code, 'label' => $interest->label()])
                        ->values(),
                    'message'      => $submission->message,
                    'submitted_at' => $submission->created_at?->toIso8601String(),
                    // Doar contactele proprietarului, cu ce știe el deja despre ele.
                    'candidates' => $candidates($submission)->map(fn (Person $person) => [
                        'id'           => $person->id,
                        'display_name' => $person->display_name,
                        // Poza din agendă, pe telefon, deosebește omonimii mai repede decât numele.
                        'device_contact_id' => $person->device_contact_id,
                        'relationship'      => $person->relationship,
                        'birth_date'        => $person->birth_date?->format('Y-m-d'),
                        'birth_year_known'  => $person->birth_year_known,
                    ])->values(),
                ])
                ->values(),
        ]);
    }

    public function resolve(Request $request, ProfileSubmission $submission, ResolveSubmissionIdentity $resolve): JsonResponse
    {
        $this->authorize('resolve', $submission);

        $data = $request->validate([
            // null înseamnă „altcineva”: o persoană nouă.
            'person_id' => ['present', 'nullable', 'integer'],
        ]);

        // A doua apăsare nu mai schimbă nimic: alegerea s-a făcut deja.
        if ($submission->accepted_at !== null) {
            return response()->json(['data' => ['person_id' => $submission->person_id]]);
        }

        $person = null;

        if ($data['person_id'] !== null) {
            $person = Person::findOrFail($data['person_id']);
            $this->authorize('update', $person);
        }

        return response()->json(['data' => ['person_id' => $resolve($submission, $person)->id]]);
    }
}
