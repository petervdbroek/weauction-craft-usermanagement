<?php

namespace Craft;

use CDbCriteria;

/**
 * Class MyAuction_LoginLogService
 *
 * @package Craft
 */
class MyAuction_LoginLogService extends BaseApplicationComponent
{
    /**
     * @param \DateTime|null $from
     * @param \DateTime|null $to
     * @param string|null $groupBy
     * @return array
     */
    public function getLoginLog(\DateTime $from = null, \DateTime $to = null, string $groupBy = null): array
    {
        $criteria = new CDbCriteria(array('order'=>'dateCreated DESC','limit'=>100));
        if (null !== $from && null != $to) {
            $from->setTimezone(new \DateTimeZone('GMT'));
            $to->setTimezone(new \DateTimeZone('GMT'));

            $criteria->addBetweenCondition('dateCreated', $from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s'));
        }

        $criteria->select = 'user_id, ip, os, browser';

        if ($groupBy == 'user') {
            $criteria->group = 'user_id';
            $criteria->select .= ', COUNT(id) AS id, MAX(dateCreated) AS dateCreated';
        } else {
            $criteria->select .= ', dateCreated';
        }

        return MyAuction_LoginLogRecord::model()->findAll($criteria);
    }
}
