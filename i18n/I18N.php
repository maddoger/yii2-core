<?php
/**
 * @copyright Copyright (c) 2014 Vitaliy Syrchikov
 * @link http://syrchikov.name
 */

namespace maddoger\core\i18n;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * I18N Component for languages info
 *
 * @author Vitaliy Syrchikov <maddoger@gmail.com>
 * @link http://syrchikov.name
 * @package maddoger/yii2-core
 */
class I18N extends \yii\i18n\I18N
{
    /**
     * @var array available languages
     * [
     *  'slug' => 'ru',
     *  'locale' => 'ru-RU',
     *  'name' => 'Русский',
     * ],
     * [
     *  'slug' => 'en',
     *  'locale' => 'en-US',
     *  'name' => 'English',
     * ],
     */
    public $availableLanguages;

    /**
     * @return array
     */
    public static function getAvailableLanguages()
    {
        $availableLanguages = null;
        if (Yii::$app->i18n instanceof I18N) {
            $availableLanguages = Yii::$app->i18n->availableLanguages;
        }
        if (!$availableLanguages) {
            $availableLanguages = [[
                'slug' => substr(Yii::$app->language, 0, 2),
                'locale' => Yii::$app->language,
                'name' => 'Default',
            ]];
        }
        return $availableLanguages;
    }

    /**
     * @return array
     */
    public static function getCurrentLanguage()
    {
        $map = ArrayHelper::index(static::getAvailableLanguages(), 'locale');
        return isset($map[Yii::$app->language]) ? $map[Yii::$app->language] : null;
    }

    /**
     * @param $slug
     * @return null
     */
    public static function getLanguageBySlug($slug)
    {
        $map = ArrayHelper::index(static::getAvailableLanguages(), 'slug');
        return isset($map[$slug]) ? $map[$slug] : null;
    }

    /**
     * @param $locale
     * @return null
     */
    public static function getLanguageByLocale($locale)
    {
        $map = ArrayHelper::index(static::getAvailableLanguages(), 'locale');
        return isset($map[$locale]) ? $map[$locale] : null;
    }

    /**
     * @return array Locale by name
     */
    public static function getAvailableLanguagesList()
    {
        return ArrayHelper::map(static::getAvailableLanguages(), 'locale', 'name');
    }
}