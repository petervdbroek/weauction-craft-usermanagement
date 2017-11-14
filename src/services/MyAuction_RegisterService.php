<?php
namespace Craft;

use Guzzle\Http\Message\Response;

/**
 * Class MyAuction_RegisterService
 * @package Craft
 */
class MyAuction_RegisterService extends BaseApplicationComponent
{
    /**
     * @param MyAuction_UserModel $user
     * @return bool
     */
    public function registerStep1(MyAuction_UserModel $user): bool
    {
        // Verify that e-mail doesn't already exist
        $data = json_encode([
            "email" => $user->email
        ]);

        /** @var Response $response */
        $response = craft()->myAuction_api->doRequest('action/CheckEmail', 'post', $data);
        $body = json_decode($response->getBody());

        if ($body->result->exists) {
            craft()->urlManager->setRouteVariables([
                'user' => $user
            ]);

            $extUser = craft()->myAuction_login->getUserByEmail($user->getAttribute('email'));

            if ($extUser->email && $extUser->email[0]->verified == '1') {
                craft()->userSession->setError('mailExistsVerified');
            } else {
                craft()->userSession->setError('mailExists');
            }

            return false;
        }

        $locale = (craft()->getLocale()->getId()) ? craft()->getLocale()->getId() : 'en';
        /** @var EntryModel $mail */
        $mail = craft()->myAuction_craft->getMail($locale, 'activateAccount');

        $body = $mail->getContent()->getAttribute('mailBody');
        $body = str_replace('%baseUrl%', craft()->config->get('environmentVariables')['baseUrl'], $body);

        $data = json_encode([
            "email" => $user->email,
            "password" => $user->password,
            "role" => "f840e2f9-43ad-4b5a-b303-6f6c28f783d3",
            "emailSubjectTemplate" => $mail->getContent()->getAttribute('subject'),
            "emailBodyTemplate" => $body,
        ]);

        /** @var Response $response */
        $response = craft()->myAuction_api->doRequest('action/SignupUser', 'post', $data);

        if ($response->getStatusCode() == '200') {
            // Subscribe to mailchimp
            /*
             * Temp disable, this doesn't work as expected (multiple mails)
             $mailchimpSettings = craft()->plugins->getPlugin('mailchimpsubscribe')->getSettings();
            craft()->mailchimpSubscribe->subscribe($user->email, $mailchimpSettings['mcsubListId'], 'html', null, null);
            */

            return true;
        }

        craft()->userSession->setError(Craft::t('Something went wrong, try again'));

        return false;
    }

    /**
     * @param MyAuction_ProfileModel $profile
     * @return bool
     */
    public function setProfile(MyAuction_ProfileModel $profile): bool
    {
        $ownUser = craft()->myAuction_login->getUser();

        // Check displayname
        $users = craft()->myAuction_login->getUsers();
        foreach ($users AS $user) {
            if ($ownUser->uuid != $user->uuid && $user->profile && $user->profile->displayname == $profile->displayname) {
                $profile->addError('displayname', Craft::t('Displayname already exists, choose another one'));
                return false;
            }
        }

        $data = json_encode([
            "displayname" => $profile->displayname,
            "language" => $profile->language,
            "initials" => $profile->initials,
            "firstname" => $profile->firstname,
            "lastname" => $profile->lastname,
            "nationality" => $profile->nationality,
            "gender" => $profile->gender,
            "address1" => $profile->address1,
            "address2" => $profile->address2,
            "zipcode" => $profile->zipcode,
            "city" => $profile->city,
            "country" => $profile->country,
            "phone" => $profile->phone,
            "dateofbirth" => $profile->dateofbirth
        ]);

        /** @var Response $response */
        $response = craft()->myAuction_api->doRequest('action/SetProfile', 'post', $data, craft()->myAuction_login->getAccessToken());

        if ($response->getStatusCode() == '200') {
            return true;
        }

        craft()->userSession->setError(Craft::t('Something went wrong, try again'));

        return false;
    }

    /**
     * Resend verification e-mail
     *
     * @param string $email
     * @return bool
     */
    public function resendVerification(string $email): bool
    {
        // Verify that e-mail doesn't already exist
        $data = json_encode([
            "email" => $email
        ]);

        /** @var Response $response */
        $response = craft()->myAuction_api->doRequest('action/CheckEmail', 'post', $data);
        $body = json_decode($response->getBody());

        if (!$body->result->exists) {
            craft()->urlManager->setRouteVariables([
                'email' => $email
            ]);
            craft()->userSession->setError('Mailaddress doesn\'t already exist, register a new account');
            return false;
        }

        $locale = (craft()->getLocale()->getId()) ? craft()->getLocale()->getId() : 'en';
        /** @var EntryModel $mail */
        $mail = craft()->myAuction_craft->getMail($locale, 'activateAccount');

        $body = $mail->getContent()->getAttribute('mailBody');
        $body = str_replace('%baseUrl%', craft()->config->get('environmentVariables')['baseUrl'], $body);

        $data = json_encode([
            "email" => $email,
            "emailSubjectTemplate" => $mail->getContent()->getAttribute('subject'),
            "emailBodyTemplate" => $body,
        ]);

        /** @var Response $response */
        $response = craft()->myAuction_api->doRequest('action/SendVerificationEmail', 'post', $data);

        if ($response->getStatusCode() == '200') {
            return true;
        }

        craft()->userSession->setError(Craft::t('Something went wrong, try again'));

        return false;
    }

    /**
     * @param UserModel $user
     *
     * @return bool
     */
    public function addOrUpdateClerk(UserModel $user): bool
    {
        $extUser = craft()->myAuction_login->getUserByEmail($user->getAttribute('email'));
        if (!$extUser) {
            $locale = (craft()->getLocale()->getId()) ? craft()->getLocale()->getId() : 'en';
            /** @var EntryModel $mail */
            $mail = craft()->myAuction_craft->getMail($locale, 'activateAdminAccount');

            $body = $mail->getContent()->getAttribute('mailBody');
            $body = str_replace('%baseUrl%', craft()->config->get('environmentVariables')['baseUrl'], $body);

            $password = base64_encode(random_bytes(10));
            $data = json_encode([
                "email" => $user->getAttribute('email'),
                "password" => $password,
                "role" => "80f1f2cd-1e81-40fd-9872-5f6c0fda0461",
                "emailSubjectTemplate" => $mail->getContent()->getAttribute('subject'),
                "emailBodyTemplate" => $body,
            ]);

            /** @var Response $response */
            $response = craft()->myAuction_api->doRequest('action/SignupUser', 'post', $data);
            if ($response->getStatusCode() == '200') {
                return true;
            }
            craft()->userSession->setError(Craft::t('Something went wrong, try again'));

            return false;
        }

        $data = json_encode(
            [
                "uuid" => $extUser->uuid,
                "isSuper" => false,
                "role" => "80f1f2cd-1e81-40fd-9872-5f6c0fda0461"
            ]
        );

        /** @var Response $response */
        $response = craft()->myAuction_api->doRequest('action/UpdateUser', 'post', $data);
        if ($response->getStatusCode() == '200') {
            return true;
        }
        craft()->userSession->setError(Craft::t('Something went wrong, try again'));

        return false;
    }

    /**
     * @param string $language
     *
     * @return bool
     */
    public function saveLanguage(string $language): bool
    {
        $user = craft()->myAuction_login->getUser();
        $profile = $user->profile;
        if (null === $user->profile || !$user->profile) {
            return false;
        }
        $data = json_encode([
            "displayname" => $profile->displayname,
            "language" => $language,
            "initials" => $profile->initials,
            "firstname" => $profile->firstname,
            "lastname" => $profile->lastname,
            "nationality" => $profile->nationality,
            "gender" => $profile->gender,
            "address1" => $profile->address1,
            "address2" => $profile->address2,
            "zipcode" => $profile->zipcode,
            "city" => $profile->city,
            "country" => $profile->country,
            "phone" => $profile->phone,
            "dateofbirth" => $profile->dateofbirth
        ]);
        /** @var Response $response */
        $response = craft()->myAuction_api->doRequest('action/SetProfile', 'post', $data, craft()->myAuction_login->getAccessToken());
        if ($response->getStatusCode() == '200') {
            return true;
        }
        craft()->userSession->setError(Craft::t('Something went wrong, try again'));

        return false;
    }
}
