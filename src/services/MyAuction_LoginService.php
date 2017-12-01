<?php

namespace Craft;

use DeviceDetector\DeviceDetector;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Message\Response;

/**
 * Class MyAuction_LoginService
 *
 * @package Craft
 */
class MyAuction_LoginService extends BaseApplicationComponent
{
    private $secureSegments = ['register-step-2', 'my-account', 'no-live-auction-available'];

    /**
     * Check login
     */
    public function checkLogin(): void
    {

        // See if languages is switched and we need to save it to the profile
        if (isset($_GET['switchLanguage'])) {
            // Only if logged in
            if ($this->isLoggedIn()) {
                $newLanguage = craft()->getLocale()->getId();
                craft()->myAuction_register->saveLanguage($newLanguage);
            }
        }
        // See if Segment is secure segment, than check login, if not logged in, redirect to login page
        foreach (craft()->request->getSegments() AS $segment) {
            if (in_array($segment, $this->secureSegments, false)) {
                if (!$this->isLoggedIn()) {
                    craft()->request->redirect('/' . craft()->getLocale()->getId() . '/loginForm/');
                }
            }

            switch ($segment) {
                case 'logoutAction':
                    $this->logout();
                break;
                case 'loginForm':
                case 'register':
                    if ($this->isLoggedIn()) {
                        $cookie = craft()->request->getCookie('myauction_login');
                        $this->redirectAfterLogin($cookie->value);
                    }
                break;
                case 'my-account':
                    if ($this->isLoggedIn()) {
                        $cookie = craft()->request->getCookie('myauction_login');
                        $this->redirectAfterLogin($cookie->value, false); // Only to register step 2
                    }
                break;
                case 'bidpage':
                    if ($this->isLoggedIn()) {
                        $cookie = craft()->request->getCookie('myauction_login');
                        $this->redirectAfterLogin($cookie->value, false); // Only to register step 2

                        //Check if user has deposit, if not redirect to account page
                        $user = $this->getUser();
                        if (!craft()->depositManagement_payment->userDeposited($user->email[0]->value)) {
                            craft()->request->redirect('/' . craft()->getLocale()->getId() . '/my-account/');
                        }
                    } else {
                        craft()->request->redirect('/' . craft()->getLocale()->getId() . '/viewerbidderchoice/');
                    }
                break;
            }
        }

        // Language select based on User agent
        //$locales = craft()->i18n->getSiteLocaleIds();
        $locales = ['nl', 'en', 'de', 'fr'];
        $cookie = craft()->request->getCookie('myauction_language');
        if (null === $cookie || !$cookie->value || !in_array($cookie->value, $locales, false)) {
            if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $userLocale = \Locale::lookup($locales, $_SERVER['HTTP_ACCEPT_LANGUAGE'], true, 'en');
                $cookieData = $userLocale;
                $cookie = new HttpCookie(
                    'myauction_language',
                    $cookieData,
                    ['expire' => strtotime('+30 day')]
                );

                craft()->request->getCookies()->add($cookie->name, $cookie);
                craft()->setLanguage($cookie->value);
                craft()->request->redirect(craft()->request->getUrl());
            }
        }
    }

    /**
     * @param string $email
     *
     * @return bool
     * @throws Exception
     */
    public function forgotPassword(string $email): bool
    {
        $user = $this->getUserByEmail($email);
        $locale = 'en';
        if (null !== $user && isset($user->profile) && isset($user->profile->language)) {
            $locale = ($user && $user->profile && $user->profile->language) ? $user->profile->language : 'en';
        }
        /** @var EntryModel $mail */
        $mail = craft()->myAuction_craft->getMail($locale, 'forgotPassword');

        $body = $mail->getContent()->getAttribute('mailBody');
        $body = str_replace('%baseUrl%', craft()->config->get('environmentVariables')['baseUrl'], $body);

        $data = json_encode(
            [
                "email" => $email,
                "emailSubjectTemplate" => $mail->getContent()->getAttribute('subject'),
                "emailBodyTemplate" => $body,
                "clientId" => craft()->config->get('um_api_client_id', 'myauction'),
            ]
        );

        // Request code for e-mail. Don't do error handling (always correct response)
        try {
            craft()->myAuction_api->doRequest('action/ForgotPassword', 'post', $data);
        } catch (\Exception $e) {

        }
        craft()->userSession->setNotice(Craft::t('Password change successfully requested, e-mail sent'));

        return true;
    }

    /**
     * @param string $password
     * @param string $newPassword
     *
     * @return bool
     * @throws Exception
     */
    public function changePassword(string $password, string $newPassword): bool
    {
        $data = json_encode(
            [
                "password" => $password,
                "newPassword" => $newPassword,
                "clientId" => craft()->config->get('um_api_client_id', 'myauction'),
            ]
        );

        // Request code for e-mail. Don't do error handling (always correct response)
        /** @var Response $response */
        try {
            $response = craft()->myAuction_api->doRequest(
                'action/ChangePassword',
                'post',
                $data,
                $this->getAccessToken()
            );
            if ($response->getStatusCode() == '200') {
                craft()->userSession->setNotice(Craft::t('Password changed successfully'));

                return true;
            }
        } catch (\Exception $e) {
            if ($e instanceof ClientErrorResponseException) {
                switch ($e->getResponse()->getStatusCode()) {
                    case '401': // Conflict
                        craft()->userSession->setError(Craft::t('Old password not valid'));
                    break;
                }

                return false;
            } else {
                throw new Exception($e);
            }
        }

        craft()->userSession->setError(Craft::t('Something went wrong, try again'));

        return false;
    }

    /**
     * @param string $code
     * @param string $password
     *
     * @return bool
     * @throws Exception
     */
    public function resetPassword(string $code, string $password): bool
    {
        $data = json_encode(
            [
                "code" => $code,
                "password" => $password,
                "clientId" => craft()->config->get('um_api_client_id', 'myauction'),
            ]
        );

        try {
            /** @var Response $response */
            $response = craft()->myAuction_api->doRequest('action/ResetPassword', 'post', $data);

            if ($response->getStatusCode() == '200') {
                craft()->userSession->setNotice(Craft::t('Password successfully changed'));

                return true;
            } else {
                craft()->userSession->setError(Craft::t('Code not valid'));

                return false;
            }
        } catch (\Exception $e) {
            if ($e instanceof ClientErrorResponseException) {
                switch ($e->getResponse()->getStatusCode()) {
                    case '409': // Conflict
                        craft()->userSession->setError(Craft::t('Code not valid'));
                    break;
                }

                return false;
            } else {
                throw new Exception($e);
            }
        }
    }

    /**
     * @param $email
     * @param $password
     *
     * @return bool
     * @throws Exception
     */
    public function login(string $email, string $password): bool
    {
        $data = json_encode(
            [
                "email" => $email,
                "password" => $password,
                "clientId" => craft()->config->get('um_api_client_id', 'myauction'),
            ]
        );

        try {

            /** @var Response $response */
            $response = craft()->myAuction_api->doRequest('action/SigninUser', 'post', $data);

            $responseData = json_decode($response->getBody());

            $cookieData = $responseData->result->accessToken;

            $cookie = new HttpCookie(
                'myauction_login',
                $cookieData,
                ['expire' => round($responseData->result->expires / 1000, 0)]
            );

            // Save in log
            $this->logLogin($responseData->result->userId);

            craft()->request->getCookies()->add($cookie->name, $cookie);

            $this->redirectAfterLogin($responseData->result->accessToken);

            return true;
        } catch (\Exception $e) {
            if ($e instanceof ClientErrorResponseException) {
                switch ($e->getResponse()->getStatusCode()) {
                    case '401': // 401: Not authorized
                        craft()->userSession->setError(Craft::t('Username / password invalid'));
                    break;
                    case '404': // 404: Authorized, good username / passwordt but not verified
                        craft()->userSession->setError('mailExists');
                        craft()->urlManager->setRouteVariables(
                            [
                                'email' => $email,
                            ]
                        );

                    break;
                    case '409':
                        craft()->userSession->setError(Craft::t('Account blocked'));
                    break;
                }

                return false;
            }
            throw new Exception($e);
        }
    }

    /**
     * @return bool
     */
    private function logout(): bool
    {
        craft()->request->deleteCookie('myauction_login');
        craft()->request->redirect('/' . craft()->getLocale()->getId() . '/loginForm/');

        return true;
    }


    /**
     * Check if user has access token and that access token is not expired
     *
     * @return bool: logged in / not
     */
    public function isLoggedIn(): bool
    {
        $cookie = craft()->request->getCookie('myauction_login');
        if (isset($cookie->value)) {
            return true;
        }

        return false;
    }

    /**
     * @return string: accessToken
     */
    public function getAccessToken(): string
    {
        $cookie = craft()->request->getCookie('myauction_login');

        return $cookie->value;
    }

    /**
     * @param string $accessToken
     * @param bool $redirect
     *
     * @return bool
     */
    private function redirectAfterLogin(string $accessToken, bool $redirect = true): bool
    {
        // Redirect to register-step-2 or My Account
        /** @var Response $response */
        $response = craft()->myAuction_api->doRequest('action/FetchUserInfo', 'post', '', $accessToken);

        $body = json_decode($response->getBody());

        if ($body->result->profile !== null) {
            if ($redirect) {
                $language = ($body->result->profile->language) ? $body->result->profile->language
                    : craft()->getLocale()->getId();
                craft()->request->redirect('/' . $language . '/my-account/');
            }

            return true;
        } else {
            craft()->request->redirect('/' . craft()->getLocale()->getId() . '/register-step-2/');
        }

        return false;
    }

    /**
     * @return \stdClass : user
     */
    public function getUser(): \stdClass
    {
        try {
            /** @var Response $response */
            $response = craft()->myAuction_api->doRequest(
                'action/FetchUserInfo',
                'post',
                '',
                $this->getAccessToken()
            );

            return json_decode($response->getBody())->result;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getUsers(): ?array
    {
        try {
            /** @var Response $response */
            $response = craft()->myAuction_api->doRequest('action/FetchAllUserInfo', 'post');

            return json_decode($response->getBody())->result;
        } catch (\Exception $e) {
            throw new Exception($e);
        }
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function isBlocked(string $id): bool
    {
        /** @var MyAuction_UserRecord $user */
        $user = MyAuction_UserRecord::model()->findById($id);

        return ($user && $user->status == 'blocked');
    }

    /**
     * @param string $id
     *
     * @return \stdClass|null
     * @throws Exception
     */
    public function getUserById(string $id): ?\stdClass
    {
        $data = json_encode(
            [
                "uuid" => $id,
            ]
        );

        try {
            /** @var Response $response */
            $response = craft()->myAuction_api->doRequest('action/FetchSingleUserInfo', 'post', $data);

            return json_decode($response->getBody())->result;
        } catch (\Exception $e) {
            if ($e instanceof ClientErrorResponseException) {
                switch ($e->getResponse()->getStatusCode()) {
                    case '404': // Conflict
                        // Not found, no problem, return null
                        return null;
                    break;
                }
            }
            throw new Exception($e);
        }
    }

    /**
     * @param string $status
     *
     * @return int
     */
    public function numUsers(string $status = ''): int
    {
        $users = $this->getUsers();


        $i = 0;

        foreach ($users AS $user) {
            switch ($status) {
                case 'verified':
                    if ($user->email && $user->email[0]->verified) {
                        $i++;
                    }
                break;
                case 'profile':
                    if ($user->profile) {
                        $i++;
                    }
                break;
                default:
                    $i++;
                break;
            }
        }

        return $i;
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function blockBidder(string $id): bool
    {
        if (craft()->auctionManagement_object->blockBidder($id)) {
            $user = $this->getOrCreateUserRecord($id);
            $user->setAttribute('status', 'blocked');

            return $user->save();
        }

        return false;
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function unBlockBidder(string $id): bool
    {
        if (craft()->auctionManagement_object->unBlockBidder($id)) {
            $user = $this->getOrCreateUserRecord($id);
            $user->setAttribute('status', 'unblocked');

            return $user->save();
        }

        return false;
    }

    /**
     * @param string $id
     *
     * @return MyAuction_UserRecord
     */
    private function getOrCreateUserRecord(string $id): MyAuction_UserRecord
    {
        /** @var MyAuction_UserRecord $user */
        $user = MyAuction_UserRecord::model()->findById($id);

        if (!$user) {
            $user = new MyAuction_UserRecord();
            $user->setAttribute('user_id', $id);
            $user->save();
        }

        return $user;
    }

    /**
     * @param string $toEmail
     *
     * @return bool
     */
    public function sendDepositReminder(string $toEmail): bool
    {
        $user = $this->getUserByEmail($toEmail);
        $locale = ($user && $user->profile->language) ? $user->profile->language : 'en';
        /** @var EntryModel $mail */
        $mail = craft()->myAuction_craft->getMail($locale, 'reminderDeposit');

        $body = $mail->getContent()->getAttribute('mailBody');
        $body = str_replace('%baseUrl%', craft()->config->get('environmentVariables')['baseUrl'], $body);

        $email = new EmailModel();
        $email->toEmail = $toEmail;
        $email->subject = $mail->getContent()->getAttribute('subject');
        $email->body = nl2br($body);

        if (craft()->email->sendEmail($email)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $toEmail
     *
     * @return bool
     */
    public function sendCompleteProfileReminder(string $toEmail): bool
    {

        // Without a profile, a user doesn't have a language yet
        /** @var EntryModel $mail */
        $mail = craft()->myAuction_craft->getMail('en', 'reminderCompleteProfile');

        $email = new EmailModel();
        $email->toEmail = $toEmail;
        $email->subject = $mail->getContent()->getAttribute('subject');
        $email->body = nl2br($mail->getContent()->getAttribute('mailBody'));

        if (craft()->email->sendEmail($email)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $email
     *
     * @return null|\StdClass
     * @throws Exception
     */
    public function getUserByEmail(string $email): ?\StdClass
    {
        $data = json_encode(
            [
                "email" => $email,
            ]
        );
        try {
            /** @var Response $response */
            $response = craft()->myAuction_api->doRequest('action/FetchSingleUserInfoByEmail', 'post', $data);
            if ($response->getStatusCode() == '200') {

                return json_decode($response->getBody())->result;
            }
        } catch (\Exception $e) {
            return null;
        }

        return null;
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function deleteUser(string $id): bool
    {
        $data = json_encode(
            [
                "uuid" => $id,
            ]
        );

        try {
            craft()->myAuction_api->doRequest('action/DeleteUser', 'post', $data);
        } catch (\Exception $e) {

            return false;
        }

        return true;
    }

    /**
     * @param string $user_id
     * @return bool
     */
    private function logLogin(string $user_id): bool
    {
        // Get browser / OS info
        $dd = new DeviceDetector($_SERVER['HTTP_USER_AGENT']);
        $dd->discardBotInformation();
        $dd->skipBotDetection();
        $dd->parse();

        $os = $dd->getOs();
        $os = $os['name'] . ' ' . $os['version'] . ' (' . $os['platform'] . ')';

        $browser = $dd->getClient();
        $browser = $browser['name'] . ' ' . $browser['version'];

        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $paymentRecord = new MyAuction_LoginLogRecord();
        $paymentRecord->setAttribute('user_id', $user_id);
        $paymentRecord->setAttribute('ip', $ip);
        $paymentRecord->setAttribute('os', $os);
        $paymentRecord->setAttribute('browser', $browser);

        return $paymentRecord->save();
    }
}
