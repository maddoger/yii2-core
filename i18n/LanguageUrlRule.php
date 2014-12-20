<?php
/**
 * @copyright Copyright (c) 2014 Vitaliy Syrchikov
 * @link http://syrchikov.name
 */

namespace maddoger\core\i18n;

use Yii;
use yii\web\UrlRule;

/**
 * LanguageUrlRule
 *
 * Usage:
 *
 * [
 *   'class' => 'common\components\LanguageUrlRule',
 *   'pattern' => '<languageSlug:(ru|en)>/index',
 *   'route' => 'site/index',
 * ],
 *
 * @author Vitaliy Syrchikov <maddoger@gmail.com>
 * @link http://syrchikov.name
 * @package maddoger/yii2-core
 */
class LanguageUrlRule extends UrlRule
{
    /**
     * @var string
     */
    public $languageParam = 'languageSlug';

    /**
     * @var string
     */
    public $defaultLanguage;

    /**
     * @param \yii\web\UrlManager $manager
     * @param \yii\web\Request $request
     * @return array|bool
     */
    public function parseRequest($manager, $request)
    {
        $res = parent::parseRequest($manager, $request);
        if (!$res) {
            return $res;
        }
        list($route, $params) = $res;
        if (isset($params[$this->languageParam])) {
            $languageSlug = $params[$this->languageParam];
            $language = I18N::getLanguageBySlug($languageSlug);
            if ($language) {
                Yii::$app->language = $language['locale'];
                Yii::trace("Change language by URL to: {$language['locale']}", __METHOD__);
            } elseif ($this->defaultLanguage !== null) {
                Yii::$app->language = $this->defaultLanguage;
            }
        }
        return $res;
    }

    /**
     * @param \yii\web\UrlManager $manager
     * @param string $route
     * @param array $params
     * @return bool|string
     */
    public function createUrl($manager, $route, $params)
    {
        if (!isset($params[$this->languageParam]) || !$params[$this->languageParam]) {
            $params[$this->languageParam] = I18N::getCurrentLanguageSlug();
        }
        return parent::createUrl($manager, $route, $params);
    }
}