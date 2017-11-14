<?php
namespace Craft;

use Guzzle\Http\Client;
use Guzzle\Http\Message\Response;

/**
 * Class MyAuction_ApiService
 * @package Craft
 */
class MyAuction_ApiService extends BaseApplicationComponent
{
    /**
     * @param string $uri
     * @param string $type
     * @param string $data
     * @param string $access_token
     * @param array $headers
     *
     * @return Response
     */
    public function doRequest(string $uri, string $type, string $data = '', string $access_token = '', array $headers = []): Response
    {
        $protectedUris = [
            'action/SignupUser',
            'action/UpdateUser',
            'action/DeleteUser',
            'action/ArchiveUser',
            'action/UnarchiveUser',
            'action/FetchAllUserInfo',
            'action/FetchSingleUserInfo',
            'action/FetchSingleUserInfoByEmail',
        ];

        if (in_array($uri, $protectedUris)) {
            $access_token = craft()->config->get('api_key', 'myauction');
        }

        $client = new Client(craft()->config->get('um_api_base_url', 'myauction'));

        $options = [
            'timeout'         => 20,
            'connect_timeout' => 100,
            'allow_redirects' => true
        ];

        switch ($type) {
            case 'post':
                $request = $client->post($uri, $headers, null, $options);
                $request->setBody($data, 'application/json');
                break;
            default:
                $request = $client->get($uri, $headers, $options);
                break;
        }

        if (isset($access_token) && $access_token != '') {
            $request->addHeader('Authorization', 'Bearer ' . $access_token);
        }

        return $request->send();
    }
}