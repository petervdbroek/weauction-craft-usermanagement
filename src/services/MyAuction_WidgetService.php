<?php

namespace Craft;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token;

/**
 * Class MyAuction_WidgetService
 *
 * @package Craft
 */
class MyAuction_WidgetService extends BaseApplicationComponent
{
    /**
     * @param $user
     *
     * @return Token|null
     */
    public function generateToken($user): ?Token
    {
        $signer = new Sha256();

        $role = "f840e2f9-43ad-4b5a-b303-6f6c28f783d3"; // Standard bidder role
        if ($user->role && $user->role->uuid) {
            $role = $user->role->uuid;
        }

        return (new Builder())
            ->set('sub', $user->uuid)
            ->set('role', $role)
            ->set('name', $user->profile->displayname)
            ->sign($signer, craft()->config->get('JWT_SECRET', 'myauction'))
            ->getToken();
    }
}
