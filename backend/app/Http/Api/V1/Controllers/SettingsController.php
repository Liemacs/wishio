<?php

namespace App\Http\Api\V1\Controllers;

use App\Domain\Reminders\Jobs\RescheduleRemindersForUser;
use App\Domain\Reminders\Models\DeviceToken;
use App\Domain\Reminders\Models\UserSettings;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    /** Preferințele de care depinde momentul unei notificări push. */
    private const SCHEDULING = ['reminder_days', 'preferred_hour', 'quiet_from', 'quiet_to', 'push_enabled'];

    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->payload($request)]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            // Doar treptele din scara docs/09 § 3; ziua ocaziei se adaugă mereu.
            // Împreună cu ea nu depășesc limita pe ocazie: altfel cea mai
            // îndepărtată treaptă ar fi tăiată fără ca utilizatorul să afle.
            'reminder_days'   => ['sometimes', 'array', 'max:'.(config('wishio.reminders.max_per_occasion') - 1)],
            'reminder_days.*' => ['integer', 'distinct', Rule::in([1, 3, 7, 14])],
            'preferred_hour'  => ['sometimes', 'integer', 'between:0,23'],
            'quiet_from'      => ['sometimes', 'integer', 'between:0,23'],
            'quiet_to'        => ['sometimes', 'integer', 'between:0,23'],
            'push_enabled'    => ['sometimes', 'boolean'],
            'email_digest'    => ['sometimes', 'boolean'],
        ]);

        $settings = UserSettings::updateOrCreate(['user_id' => $request->user()->id], $data);

        // Schimbarea se aplică și notificărilor deja planificate.
        $touched = array_intersect(array_keys($data), self::SCHEDULING) !== [];

        if ($touched && ($settings->wasRecentlyCreated || $settings->wasChanged(self::SCHEDULING))) {
            RescheduleRemindersForUser::dispatch($request->user()->id);
        }

        return response()->json(['data' => $this->payload($request)]);
    }

    /** Înregistrarea dispozitivului pentru notificări. */
    public function storeDevice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token'    => ['required', 'string', 'max:255'],
            'platform' => ['required', Rule::in(['ios', 'android', 'web'])],
        ]);

        // Același token poate migra între conturi pe un telefon partajat:
        // updateOrCreate pe token, nu pe user.
        DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id'        => $request->user()->id,
                'platform'       => $data['platform'],
                'locale'         => $request->user()->locale,
                'last_active_at' => now(),
            ]
        );

        return response()->json(status: 204);
    }

    public function destroyDevice(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:255']]);

        DeviceToken::where('token', $data['token'])
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json(status: 204);
    }

    private function payload(Request $request): array
    {
        $settings = UserSettings::firstOrCreate(['user_id' => $request->user()->id]);

        return [
            'reminder_days'  => $settings->reminderDays(),
            'preferred_hour' => $settings->preferred_hour,
            'quiet_from'     => $settings->quiet_from,
            'quiet_to'       => $settings->quiet_to,
            'push_enabled'   => $settings->push_enabled,
            'email_digest'   => $settings->email_digest,
            'has_device'     => DeviceToken::where('user_id', $request->user()->id)->exists(),
        ];
    }
}
