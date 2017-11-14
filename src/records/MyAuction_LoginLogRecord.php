<?php
namespace Craft;

/**
 * Class MyAuction_LoginLogRecord
 * @package Craft
 *
 */
class MyAuction_LoginLogRecord extends BaseRecord
{

    /**
     * Define table name
     *
     * @return string
     */
    public function getTableName(): string
    {
        return 'myauction_loginlog';
    }

    /**
     * Define table attributes
     *
     * @return array
     */
    protected function defineAttributes(): array
    {
        return [
            'user_id' => [
                AttributeType::String,
                'required'      => true
            ],
            'ip' => [
                AttributeType::String,
                'required'      => false
            ],
            'os' => [
                AttributeType::String,
                'required'      => false
            ],
            'browser' => [
                AttributeType::String,
                'required'      => false
            ],
        ];
    }
}