<?php
namespace Craft;

/**
 * My Auction for Craft CMS
 *
 * @author    Peter van den Broek <p.vdbroek@outlook.com>
 * @copyright Copyright (c) 2017, VSR Partners
 */
class MyAuctionPlugin extends BasePlugin
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return Craft::t('User management');
    }

    /**
     * @return string
     */
    public function getVersion()
    {
        return '1.0.0';
    }

    public function getSchemaVersion()
    {
        return '1.0.0';
    }

    /**
     * @return string
     */
    public function getDeveloper(): string
    {
        return 'WeAuction';
    }

    /**
     * @return string
     */
    public function getDeveloperUrl(): string
    {
        return 'http://weauction.nl';
    }

    /**
     * @return string
     */
    public function getPluginUrl()
    {
        return 'https://github.com/petervdbroek/weauction-craft-usermanagement';
    }

    /**
     * @return string
     */
    public function getReleaseFeedUrl()
    {
        return 'https://raw.githubusercontent.com/petervdbroek/weauction-craft-usermanagement/master/releases.json';
    }

    /**
     * Initialize plugin
     */
    public function init()
    {
        require_once __DIR__ . '/vendor/autoload.php';
        $this->_bindEvents();
        craft()->myAuction_login->checkLogin();
    }

    /**
     * @return bool
     */
    public function hasCpSection(): bool
    {
        if (!craft()->isConsole()) {
            return (craft()->userSession->isAdmin() || craft()->userSession->checkPermission('accessPlugin-myAuction'));
        }
        return false;
    }

    /**
     * @return array
     */
    public function registerCpRoutes (): array
    {
        return [
            'myauction' => ['action' => 'myAuction/index'],
            'myauction/users' => ['action' => 'myAuction/usersPage'],
            'myauction/user' => ['action' => 'myAuction/userPage'],
            'myauction/log' => ['action' => 'myAuction/loginLogPage'],
        ];
    }

    /**
     * @return array
     */
    public function registerUserPermissions(): array
    {
        return [
            'manageUsers' => ['label' => Craft::t('Manage users')],
        ];
    }

    /**
     * Bind events
     */
    protected function _bindEvents(): void
    {
        craft()->on('users.onSaveUser', function(Event $event) {
            /** @var UserModel $craftUser */
            $craftUser = $event->params['user'];
            // Check if user is already in user management. If so, add clerk role, otherwise, add user
            craft()->myAuction_register->addOrUpdateClerk($craftUser);
        });

        craft()->on('users.onDeleteUser', function(Event $event) {
            /** @var UserModel $craftUser */
            $craftUser = $event->params['user'];
            $extUser = craft()->myAuction_login->getUserByEmail($craftUser->getAttribute('email'));
            craft()->myAuction_login->deleteUser($extUser->uuid);
        });
    }
}
