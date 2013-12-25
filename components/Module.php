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
	public $translationCategory = null;

	/**
	 * Number for sorting in backend navigation
	 * @var integer
	 */
	public $backendSortNumber = null;

	/**
	 * Backend index url
	 * @var string
	 */
	public $backendIndex = null;

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
	 * FontAwesome icon class without fa-
	 * @return string|null Icon
	 */
	public function getFaIcon()
	{
		return null;
	}

	/**
	 * FontAwesome icon class
	 * @return string|null Icon
	 */
	public function getBackendIndex()
	{
		if ($this->backendIndex !== null) {
			return $this->id .'/'.$this->backendIndex;
		} else {
			return $this->id . '/'.$this->defaultRoute . '/index';
		}
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
		if ($this->hasBackend())
		return null;
	}

	/**
	 * Returns navigation items for backend
	 * @return array
	 */
	public function getBackendNavigation()
	{
		return null;
	}
}