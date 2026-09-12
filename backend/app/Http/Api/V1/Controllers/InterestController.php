<?php

namespace App\Http\Api\V1\Controllers;

use App\Domain\People\Models\InterestGroup;
use App\Http\Api\V1\Resources\InterestGroupResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InterestController extends Controller
{
    /** Taxonomia pentru selectorul de interese (ecranul P4), in limba cererii. */
    public function index(): AnonymousResourceCollection
    {
        $groups = InterestGroup::with('interests')->orderBy('sort_order')->get();

        return InterestGroupResource::collection($groups);
    }
}
