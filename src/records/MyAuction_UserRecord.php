<?php
namespace Craft;

/**
 * Class MyAuction_UserRecord
 * @package Craft
 *
 * @property string $user_id
 * @property string $status
 */
class MyAuction_UserRecord extends BaseRecord
{

    /**
     * Define table name
     *
     * @return string
     */
    public function getTableName(): string
    {
        return 'myauction_users';
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
            'status' => [
                AttributeType::Enum,
                'required'      => true,
                'values'        => 'unblocked,blocked',
                'default'       => 'unblocked'
            ],
        ];
    }

    /**
     * Define indexes
     *
     * @return array
     */
    public function defineIndexes(): array
    {
        return [
            [
                'columns' => ['user_id'],
                'unique' => true
            ],
        ];
    }

    /**
     * @return string
     */
    public function primaryKey(): string
    {
        return 'user_id';
    }
}