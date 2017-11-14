<?php
namespace Craft;

/**
 * Class MyAuction_ProfileModel
 *
 * @package Craft
 * @property string displayname
 * @property string initials
 * @property string firstname
 * @property string lastname
 * @property string nationality
 * @property string gender
 * @property string address1
 * @property string address2
 * @property string zipcode
 * @property string city
 * @property string country
 * @property string phone
 * @property string dateofbirth
 * @property mixed  language
 */
class MyAuction_ProfileModel extends BaseModel
{
    /**
     * @return array
     */
    protected function defineAttributes(): array
    {
        return [
            'firstname' => [
                AttributeType::String,
                'label'         => Craft::t('Firstname'),
                'required'      => true,
                'minLength'     => 2,
                'maxLength'     => 30
            ],
            'initials' => [
                AttributeType::String,
                'label'         => Craft::t('Initials'),
                'required'      => true,
                'minLength'     => 1,
                'maxLength'     => 10
            ],
            'lastname' => [
                AttributeType::String,
                'label'         => Craft::t('Lastname'),
                'required'      => true,
                'minLength'     => 2,
                'maxLength'     => 60
            ],
            'phone' => [
                AttributeType::String,
                'label'         => Craft::t('Phone'),
                'required'      => true,
                'minLength'     => 10,
                'maxLength'     => 15
            ],
            'displayname' => [
                AttributeType::String,
                'label'         => Craft::t('Displayname'),
                'required'      => true,
                'minLength'     => 4,
                'maxLength'     => 16,
                'matchPattern'  => '/^[a-zA-Z0-9]{4,16}$/'
            ],
            'nationality' => [
                AttributeType::String,
                'label'         => Craft::t('Nationality'),
                'required'      => true,
                'minLength'     => 2,
                'maxLength'     => 200
            ],
            'country' => [
                AttributeType::String,
                'label'         => Craft::t('Country'),
                'required'      => true,
                'minLength'     => 2,
                'maxLength'     => 200
            ],
            'city' => [
                AttributeType::String,
                'label'         => Craft::t('City'),
                'required'      => true,
                'minLength'     => 3,
                'maxLength'     => 60
            ],
            'address1' => [
                AttributeType::String,
                'label'         => Craft::t('Address'),
                'required'      => true,
                'minLength'     => 2,
                'maxLength'     => 60
            ],
            'address2' => [
                AttributeType::String,
                'label'         => Craft::t('Address line 2'),
                'minLength'     => 1,
                'maxLength'     => 60
            ],
            'language' => [
                AttributeType::String,
                'label'         => Craft::t('Language'),
                'minLength'     => 2,
                'maxLength'     => 2,
                'required'      => true,
                'default'       => 'en'
            ],
            'zipcode' => [
                AttributeType::String,
                'label'         => Craft::t('Postal code'),
                'required'      => true,
                'minLength'     => 4,
                'maxLength'     => 10
            ],
            'gender' => [
                AttributeType::Enum,
                'label'         => Craft::t('Gender'),
                'required'      => true,
                'values'        => "M,F"
            ],
            'dateofbirth' => [
                AttributeType::String,
                'label'         => Craft::t('Date of birth'),
                'required'      => true,
                'matchPattern' => '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/'
            ],
        ];
    }
}