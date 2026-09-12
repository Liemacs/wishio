<?php

namespace App\Http\Api\V1\Controllers;

use App\Domain\Profiles\Models\PublicProfile;
use App\Domain\Profiles\Models\WishlistItem;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MyProfileController extends Controller
{
    private const VISIBILITY_FIELDS = ['birth_date', 'interests', 'wishlist'];

    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->payload($request)]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'display_name' => ['sometimes', 'string', 'max:120'],
            'is_active'    => ['sometimes', 'boolean'],
            'is_indexable' => ['sometimes', 'boolean'],
            'visibility'   => ['sometimes', 'array'],
            'visibility.*' => [Rule::in(['private', 'signal_only', 'contacts', 'public'])],
        ]);

        if (isset($data['visibility'])) {
            // Doar câmpurile pe care le cunoaștem: o cheie inventată n-ar face
            // nimic, dar ar sugera utilizatorului că a setat ceva.
            $data['visibility'] = array_intersect_key(
                $data['visibility'],
                array_flip(self::VISIBILITY_FIELDS)
            );
        }

        $this->profileFor($request)->update($data);

        return response()->json(['data' => $this->payload($request)]);
    }

    public function storeWishlistItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kind'       => ['required', Rule::in(['product', 'place', 'experience'])],
            'title'      => ['required', 'string', 'max:190'],
            'url'        => ['nullable', 'url', 'max:500'],
            'note'       => ['nullable', 'string', 'max:500'],
            'priority'   => ['nullable', Rule::in(['want', 'maybe'])],
            'visibility' => ['nullable', Rule::in(['private', 'signal_only', 'contacts', 'public'])],
        ]);

        $item = $request->user()->wishlistItems()->create($data);

        return response()->json(['data' => $item], 201);
    }

    public function destroyWishlistItem(Request $request, WishlistItem $item): JsonResponse
    {
        abort_unless($item->user_id === $request->user()->id, 403);

        $item->delete();

        return response()->json(status: 204);
    }

    private function profileFor(Request $request): PublicProfile
    {
        $user = $request->user();

        // Profilul se creează la prima cerere, nu la înregistrare: nu are rost
        // să generăm slug-uri pentru conturi care nu-l vor folosi niciodată.
        return $user->publicProfile ?? $user->publicProfile()->create([
            'slug'         => PublicProfile::generateSlug($user->name),
            'display_name' => $user->name,
            'locale'       => $user->locale,
            'visibility'   => PublicProfile::DEFAULT_VISIBILITY,
        ]);
    }

    private function payload(Request $request): array
    {
        $profile = $this->profileFor($request);

        return [
            'slug'         => $profile->slug,
            'url'          => url('/@'.$profile->slug),
            'display_name' => $profile->display_name,
            'is_active'    => $profile->is_active,
            'is_indexable' => $profile->is_indexable,
            'visibility'   => array_merge(PublicProfile::DEFAULT_VISIBILITY, $profile->visibility ?? []),
            'view_count'   => $profile->view_count,
            'submissions'  => $profile->submissions()->count(),
            'wishlist'     => $request->user()->wishlistItems()->get(),
        ];
    }
}
