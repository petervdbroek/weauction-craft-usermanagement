<?php

namespace Craft;

/**
 * Class MyAuctionController
 * @package Craft
 */
class MyAuctionController extends BaseController
{

    /**
     * @var array
     */
    public $subNav = [];

    /**
     * Initialize plugin
     */
    public function init(): void
    {
        $this->subNav = [
            'index' => ['label' => 'Dashboard', 'url'=>'myauction'],
        ];

        if (craft()->userSession->isAdmin() || craft()->userSession->checkPermission('manageUsers')) {
            $this->subNav['users'] = ['label' => 'Users', 'url' => 'myauction/users'];
            $this->subNav['log'] = ['label' => 'Login log', 'url' => 'myauction/log'];
        }

        parent::init();
    }

    /**
     * Index action
     */
    public function actionIndex(): void
    {
        $this->renderTemplate('myauction/index', [
            'subnav' => $this->subNav,
            'selectedSubnavItem' => 'index',
        ]);
    }

    /**
     * Users page
     */
    public function actionUsersPage(): void
    {
        craft()->userSession->requirePermission('manageUsers');

        $this->renderTemplate('myauction/users', [
            'subnav' => $this->subNav,
            'selectedSubnavItem' => 'users',
            'crumbs' => [
                ['label' => 'User management', 'url' => 'index'],
                ['label' => 'Users', 'url' => 'users'],
            ],
        ]);
    }

    /**
     * User page
     */
    public function actionUserPage(): void
    {
        craft()->userSession->requirePermission('manageUsers');

        $this->renderTemplate('myauction/user', [
            'subnav' => $this->subNav,
            'selectedSubnavItem' => 'users',
            'crumbs' => [
                ['label' => 'User management', 'url' => 'index'],
                ['label' => 'Users', 'url' => 'users'],
            ],
        ]);
    }

    /**
     * User page
     */
    public function actionloginLogPage(): void
    {
        craft()->userSession->requirePermission('manageUsers');

        $this->renderTemplate('myauction/login-log', [
            'subnav' => $this->subNav,
            'selectedSubnavItem' => 'Login log',
            'crumbs' => [
                ['label' => 'User management', 'url' => 'index'],
                ['label' => 'Login log', 'url' => 'log'],
            ],
        ]);
    }
}
