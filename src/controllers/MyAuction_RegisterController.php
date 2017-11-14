<?php
namespace Craft;
use Guzzle\Http\Message\Response;

/**
 * Class MyAuction_RegisterController
 * @package Craft
 */
class MyAuction_RegisterController extends BaseController
{
    /**
     * @var bool
     */
    protected $allowAnonymous = true;

    /**
     * Register step 1
     */
    public function actionRegisterStep1(): void
    {
        $this->requirePostRequest();

        $user = MyAuction_UserModel::populateModel($_POST);

        if (!$user->validate()) {
            craft()->userSession->setError(Craft::t('Registration failed'));
            craft()->urlManager->setRouteVariables([
                'user' => $user
            ]);
        } else {
            // Validate terms and conditions
            if (!isset($_POST['terms_conditions'])) {
                craft()->userSession->setError(Craft::t('Registration failed, you should tick the terms and conditions box'));
                craft()->urlManager->setRouteVariables([
                    'user' => $user
                ]);
            } else {
                if (craft()->myAuction_register->registerStep1($user)) {
                    $this->redirectToPostedUrl();
                }
            }
        }
    }

    /**
     * Register step 2
     */
    public function actionRegisterStep2(): void
    {
        $this->requirePostRequest();

        // Make good dateofbirth
        if (strtotime($_POST['dateofbirth']) === false) {
            $_POST['dateofbirth'] = '';
        } else {
            $_POST['dateofbirth'] = date('Y-m-d', strtotime($_POST['dateofbirth']));
        }

        $profile = MyAuction_ProfileModel::populateModel($_POST);

        if (!$profile->validate()) {
            craft()->userSession->setError(Craft::t('Profile has validation errors'));
            craft()->urlManager->setRouteVariables([
                'profile' => $profile
            ]);
        } else {
            if (craft()->myAuction_register->setProfile($profile)) {
                // Send deposit reminder mail
                $user = craft()->myAuction_login->getUser();
                craft()->myAuction_login->sendDepositReminder($user->email[0]->value);
                craft()->request->redirect(craft()->myAuction_craft->getUrl('loginForm'));
            } else {
                craft()->userSession->setError(Craft::t('Profile has validation errors'));
                craft()->urlManager->setRouteVariables([
                    'profile' => $profile
                ]);
            }
        }
    }

    /**
     *  Validate e-mail in User Management API with token from e-mail
     */
    public function actionValidateToken(): void
    {
        $params = $this->getActionParams();

        if (isset($params['t'])) {
            $data = json_encode([
                "token" => $params['t']
            ]);

            /** @var Response $response */
            $response = craft()->myAuction_api->doRequest('action/VerifyEmail', 'post', $data);

            if ($response->getStatusCode() == '200') {
                craft()->userSession->setNotice(Craft::t('Email successfully verified'));
            } else {
                craft()->userSession->setError(Craft::t('Code not valid'));
            }
        } else {
            craft()->userSession->setError(Craft::t('No code specified'));
        }

        craft()->request->redirect(craft()->myAuction_craft->getUrl('loginForm'));
    }

    public function validateClerkToken(): void
    {
        $params = $this->getActionParams();

        if (isset($params['t'])) {
            $data = json_encode([
                "token" => $params['t']
            ]);

            /** @var Response $response */
            $response = craft()->myAuction_api->doRequest('action/VerifyEmail', 'post', $data);

            if ($response->getStatusCode() == '200') {
                craft()->userSession->setNotice(Craft::t('Email successfully verified'));
            } else {
                craft()->userSession->setError(Craft::t('Code not valid'));
            }
        } else {
            craft()->userSession->setError(Craft::t('No code specified'));
        }

        craft()->request->redirect('/admin');
    }

    /**
     * Resend validation e-mail to address
     */
    public function actionResendVerification(): void
    {
        $params = $this->getActionParams();
        if (isset($params['email'])) {

            if (craft()->myAuction_register->resendVerification($params['email'])) {
                craft()->userSession->setNotice(Craft::t('Verification e-mail succesfully sent'));
            }
        } else {
            craft()->userSession->setError(Craft::t('No e-mailaddress specified'));
        }

        if (isset($params['redirect']) && $params['redirect'] == 'controlpanel') {
            craft()->request->redirect('/admin/myauction/users');
        } else {
            craft()->request->redirect(craft()->myAuction_craft->getUrl('loginForm'));
        }

    }
}