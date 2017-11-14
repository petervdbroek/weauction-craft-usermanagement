<?php
namespace Craft;

/**
 * Class MyAuction_UserModel
 * @package Craft
 *
 * @property string email
 * @property string password
 * @property string confirmPassword
 */
class MyAuction_UserModel extends BaseModel
{
    protected function defineAttributes()
    {
        return array (
            'email' => array (
                AttributeType::Email,
                'required'      => true,
                'minLength'     => 5,
                'maxLength'     => 60
            ),
            'password' => array (
                'type'          => AttributeType::String,
                'label'         => Craft::t('Password'),
                'required'      => true,
                'minLength'     => 8,
                'matchPattern'  => '/(?:[a-zA-Z].*[^a-zA-Z]|[^a-zA-Z].*[a-zA-Z])/'
            ),
            'confirmPassword' => array (
                'type'          => AttributeType::String,
                'label'         => Craft::t('Confirm Password'),
                'compare'       => '==password',
                'minLength'     => 8,
                'required'      => true
            )
        );
    }
}