<?php

namespace App\Services;

use App\Models\User\Notification;
use App\Models\Organization\OrgFollow;
use App\Models\Organization\OrganizationMember;

class NotificationService
{
    /**
     * @param string $key   i18n key (e.g. "notification.member_added")
     * @param array  $params interpolation params stored as JSON in description
     */
    public static function send(string $userId, string $key, array $params = [], string $route = ''): void
    {
        Notification::create([
            'user_id'     => $userId,
            'title'       => $key,
            'description' => empty($params) ? '' : json_encode($params),
            'route'       => $route,
        ]);
    }

    public static function sendToFollowers(string $orgId, string $key, array $params = [], string $route = ''): void
    {
        $followerIds = OrgFollow::where('organization_id', $orgId)->pluck('user_id');
        foreach ($followerIds as $userId) {
            static::send($userId, $key, $params, $route);
        }
    }

    public static function sendToOrgRoles(string $orgId, array $roles, string $key, array $params = [], string $route = ''): void
    {
        $memberIds = OrganizationMember::where('organization_id', $orgId)
            ->whereIn('role', $roles)
            ->pluck('user_id');
        foreach ($memberIds as $userId) {
            static::send($userId, $key, $params, $route);
        }
    }
}
