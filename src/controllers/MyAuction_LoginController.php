<?php
namespace Craft;

/**
 * Class MyAuction_LoginController
 * @package Craft
 */
class MyAuction_LoginController extends BaseController
{
    /**
     * @var bool
     */
    protected $allowAnonymous = true;

    /**
     * Login
     */
    public function actionDoLogin(): void
    {
        $this->requirePostRequest();

        craft()->myAuction_login->login($_POST['email'], $_POST['password']);
    }

    /**
     * Forgot Password
     */
    public function actionForgotPassword(): void
    {
        $this->requirePostRequest();

        if (isset($_POST['email'])) {
            craft()->myAuction_login->forgotPassword($_POST['email']);
        } else {
            craft()->userSession->setError(Craft::t('No email specified'));
        }
    }

    /**
     * Reset Password
     */
    public function actionResetPassword(): void
    {
        $this->requirePostRequest();

        // Validate password
        $user = new MyAuction_UserModel();
        $user->email = 'test@email.com'; // Fake email for password validation
        $user->password = $_POST['password'];
        $user->confirmPassword = $_POST['confirmPassword'];

        // Validate passwords
        if (!$user->validate()) {
            craft()->userSession->setFlash('errors', $user->getAllErrors());
            craft()->userSession->setError(Craft::t('Change password failed'));
        } else {
            if (isset($_POST['code'])) {
                if (craft()->myAuction_login->resetPassword($_POST['code'], $_POST['password'])) {
                    craft()->request->redirect(craft()->myAuction_craft->getUrl('loginForm'));
                }
            } else {
                craft()->userSession->setError(Craft::t('No code specified'));
            }
        }
    }

    /**
     * Change password
     */
    public function actionChangePassword(): void
    {
        $this->requirePostRequest();

        // Validate password
        $user = new MyAuction_UserModel();
        $user->email = 'test@email.com'; // Fake email for password validation
        $user->password = $_POST['newPassword'];
        $user->confirmPassword = $_POST['confirmNewPassword'];

        // Validate passwords
        if (!$user->validate()) {
            craft()->userSession->setFlash('errors', $user->getAllErrors());
            craft()->userSession->setError(Craft::t('Change password failed'));
        } else {
            craft()->myAuction_login->changePassword($_POST['password'], $_POST['newPassword']);
        }
        craft()->request->redirect(craft()->myAuction_craft->getUrl('myAccount') . '?activeTab=change-password');
    }

    /**
     * Change profile
     */
    public function actionChangeProfile(): void
    {
        $this->requirePostRequest();

        $profile = MyAuction_ProfileModel::populateModel($_POST);

        if (!$profile->validate()) {
            craft()->userSession->setError(Craft::t('Profile has validation errors'));
            craft()->urlManager->setRouteVariables([
                'newProfile' => $profile
            ]);
        } else {
            if (craft()->myAuction_register->setProfile($profile)) {
                craft()->userSession->setNotice(Craft::t('Profile succesfully changed'));
                craft()->setLanguage($profile->getAttribute('language'));
                craft()->request->redirect(craft()->myAuction_craft->getUrl('myAccount') . '?activeTab=profile');
            } else {
                craft()->userSession->setError(Craft::t('Profile has validation errors'));
                craft()->urlManager->setRouteVariables([
                    'newProfile' => $profile
                ]);
            }
        }
    }

    public function actionBlockBidder(): void
    {
        $params = $this->getActionParams();

        if (isset($params['id'])) {
            if (craft()->myAuction_login->blockBidder($params['id'])) {
                craft()->userSession->setNotice(Craft::t('User successfully blocked'));
            }
        } else {
            craft()->userSession->setError(Craft::t('No id specified'));
        }
        craft()->request->redirect('/admin/myauction/users');
    }

    public function actionUnBlockBidder(): void
    {
        $params = $this->getActionParams();

        if (isset($params['id'])) {
            if (craft()->myAuction_login->unBlockBidder($params['id'])) {
                craft()->userSession->setNotice(Craft::t('User successfully unblocked'));
            }
        } else {
            craft()->userSession->setError(Craft::t('No id specified'));
        }
        craft()->request->redirect('/admin/myauction/users');
    }

    public function actionSendDepositReminder()
    {
        $params = $this->getActionParams();

        if (isset($params['email'])) {
            if (craft()->myAuction_login->sendDepositReminder($params['email'])) {
                craft()->userSession->setNotice(Craft::t('Reminder successfully sent'));
            } else {
                craft()->userSession->setError(Craft::t('Something went wrong, try again.'));
            }
        } else {
            craft()->userSession->setError(Craft::t('No email specified'));
        }

        craft()->request->redirect('/admin/myauction/users');
    }

    public function actionSendCompleteProfileReminder()
    {
        $params = $this->getActionParams();

        if (isset($params['email'])) {
            if (craft()->myAuction_login->sendCompleteProfileReminder($params['email'])) {
                craft()->userSession->setNotice(Craft::t('Reminder successfully sent'));
            } else {
                craft()->userSession->setError(Craft::t('Something went wrong, try again.'));
            }
        } else {
            craft()->userSession->setError(Craft::t('No email specified'));
        }

        craft()->request->redirect('/admin/myauction/users');
    }

    public function actionDeleteUser()
    {
        $params = $this->getActionParams();

        if (isset($params['id'])) {
            if (craft()->myAuction_login->deleteUser($params['id'])) {
                craft()->userSession->setNotice(Craft::t('User successfully deleted'));
            } else {
                craft()->userSession->setError(Craft::t('Something went wrong, try again.'));
            }
        } else {
            craft()->userSession->setError(Craft::t('No id specified'));
        }

        craft()->request->redirect('/admin/myauction/users');
    }
}