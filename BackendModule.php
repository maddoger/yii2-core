<?php

/**
 * @copyright Copyright (c) 2014 Vitaliy Syrchikov
 * @link http://syrchikov.name
 */

namespace maddoger\core;

use Yii;
use yii\base\DynamicModel;
use yii\base\Module as BaseModule;

/**
 * BackendModule
 *
 * @author Vitaliy Syrchikov <maddoger@gmail.com>
 * @link http://syrchikov.name
 * @package maddoger\core
 *
 */
class BackendModule extends BaseModule
{
	/**
	 * Number for sorting in backend navigation
	 *
	 * @var integer
	 */
	public $sortNumber = null;

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
	public function getIconClass()
	{
		return null;
	}

	/**
	 * Returns configuration model
	 *
	 * @return \yii\base\Model
	 */
	public function getConfigurationModel()
	{
		$model = new DynamicModel();

        $model->defineAttribute('sortNumber', $this->sortNumber);
        $model->addRule('sortNumber', 'integer');
        $model->addRule('sortNumber', 'filter', ['filter' => 'intval']);

		return $model;
	}

	/**
	 * Returns configuration view file name
	 *
	 * @return string|null
	 */
	public function getConfigurationView()
	{
		$path = $this->getViewPath() . DIRECTORY_SEPARATOR . 'config.php';
		if (file_exists($path)) {
			return $path;
		} else {
			return null;
		}
	}

	/**
	 * Rules needed for administrator
	 *
	 * @return array|null
	 */
	public function getRbacRoles()
	{
		return null;
	}

	/**
	 * Returns navigation items for backend
	 *
	 * @return array
	 */
	public function getNavigation()
	{
		return null;
	}
}