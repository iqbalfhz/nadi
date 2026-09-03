<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use Illuminate\Http\Request;

/**
 * Who is signed in, and what their phone should offer them.
 *
 * The app calls this on launch rather than trusting what it stored at login:
 * a role changed this morning takes effect this morning.
 */
class MeController extends Controller
{
    public function __invoke(Request $request): UserResource
    {
        return new UserResource($request->user()->load('department'));
    }
}
