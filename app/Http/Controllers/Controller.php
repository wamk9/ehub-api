<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function verifyLeagueUserAuthorization($userId, $leagueRoute, $authorizationType, $authorizationName)
    {
        $org = \App\Models\Organization\Organization::where('route', $leagueRoute)->first();

        if ($org) {
            $member = \App\Models\Organization\OrganizationMember::where('organization_id', $org->id)
                ->where('user_id', $userId)
                ->first();

            return $member && in_array($member->role, ['owner', 'admin', 'event_manager']);
        }

        return false;
    }
}
