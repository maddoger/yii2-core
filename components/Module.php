<?php

//Core module class

namespace rusporting\core\components;

use Yii;
use yii\base\Module as BaseModule;

class Module extends BaseModule
{
	/**
	 * Translation category for Yii::t function
	 * @var string
	 */
	public static $translationCategory = null;

	/**
	 * @return string|null Module name
	 */
	public function getName()
	{
		return null;
	}

	/**
	 * @return string|null Module description
	 */
	public function getDescription()
	{
		return null;
	}

	/**
	 * @return string|null Module version
	 */
	public function getVersion()
	{
		return null;
	}

	/**
	 * @return string|null Icon
	 */
	public function getIcon()
	{
		return null;
	}

	/**
	 * Has module several languages?
	 *
	 * @return bool
	 */
	public function isMultiLanguage()
	{
		return false;
	}

	/**
	 * @return bool Module has frontend?
	 */
	public function hasFrontend()
	{
		return true;
	}

	/**
	 * @return string|null Module has backend
	 */
	public function hasBackend()
	{
		return false;
	}

	/**
	 * Returns configuration model
	 * Example:
	 * [
	 *    [ ['newsOnPage' => 'number'], 'Show news', 'URL for news showing by id' ],
	 * ]
	 *
	 * @return \yii\base\Model|null
	 */
	public function getConfigurationForm()
	{
		return null;
	}

	/**
	 * Returns default routes for module
	 * Example:
	 * [
	 *    [ ['news/<id>'=>'news/item/show'], 'Show news', 'URL for news showing by id' ],
	 *  [ 'route', 'title', 'description' ],
	 * ]
	 *
	 * @return array|null
	 */
	public function getDefaultRoutes()
	{
		return null;
	}

	/**
	 * Rules needed for administrator
	 * @return array|null
	 */
	public function getRights()
	{
		return null;
	}

	/**
	 * Translate message from module dictionary
	 * @param $message
	 * @param null $params
	 * @param null $language
	 * @return string
	 */
	public static function t($message, $params=null, $language=null)
	{
		return Yii::t(static::$translationCategory, $message, $params, $language);
	}
}