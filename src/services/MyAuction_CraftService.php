<?php
namespace Craft;

/**
 * Class MyAuction_CraftService
 * @package Craft
 */
class MyAuction_CraftService extends BaseApplicationComponent
{
    /**
     * Get URL based on section handle, first entry's URL
     *
     * @param string $section
     * @return string
     */
    public function getUrl(string $section): string
    {
        $locales = ['nl', 'en', 'de', 'fr'];
        $locale = (string)craft()->request->getCookie('myauction_language');
        if (!in_array($locale, $locales, false)) {
            $locale = 'en';
        }

        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->locale = $locale;
        $criteria->section = $section;

        return $criteria->first()->getUrl();
    }

    /**
     * Get mail from CMS
     *
     * @param string $locale
     * @param string $mail
     *
     * @return EntryModel
     * @throws Exception
     */
    public function getMail(string $locale, string $mail): EntryModel
    {
        $criteria = craft()->elements->getCriteria(ElementType::Entry);
        $criteria->section = 'mails';
        $criteria->mail = $mail;
        $criteria->locale = $locale;
        $result = $criteria->find();

        if (!$result || !$result[0]) {
            throw new Exception('Mail not found');
        }

        return $result[0];
    }
}