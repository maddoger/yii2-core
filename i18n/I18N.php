<?php
/**
 * @copyright Copyright (c) 2014 Vitaliy Syrchikov
 * @link http://syrchikov.name
 */

namespace maddoger\core\i18n;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

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
     * @var string Template for URL with language.
     *
     * Available placeholders:
     * {languageSlug} - slug of language `en_US`
     * {languageLocale} - locale of language `ru_RU`
     * {slug} - page url
     */
    public $languageUrlTemplate = '@frontendUrl/{languageSlug}/{slug}';

    /**
     * @return null|static
     */
    public static function getInstance()
    {
        if (Yii::$app->i18n instanceof I18N) {
            return Yii::$app->i18n;
        } else {
            return null;
        }
    }

    /**
     * @return array
     */
    public static function getAvailableLanguages()
    {
        $availableLanguages = null;
        if ($instance = static::getInstance()) {
            $availableLanguages = $instance->availableLanguages;
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

    /**
     * @param $slug
     * @param null $locale
     * @param bool $forceLanguage
     * @return string
     */
    public static function getFrontendUrl($slug, $locale=null, $forceLanguage = false)
    {
        if ($instance = static::getInstance()) {

            if (!$locale) {
                $locale = Yii::$app->language;
            }
            $language = static::getLanguageByLocale($locale);

            $languages = static::getAvailableLanguages();
            if (count($languages)>1 || $forceLanguage) {
                $url = strtr($instance->languageUrlTemplate, [
                    'slug' => $slug,
                    'languageLocale' => $language['locale'],
                    'languageSlug' => $language['slug'],
                ]);
                return Url::to($url);
            }
        }
        return Url::to('@frontendUrl/'.$slug);
    }
}