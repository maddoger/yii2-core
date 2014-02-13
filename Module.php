<?php

//Core module class

namespace maddoger\core;

use Yii;
use yii\base\Module as BaseModule;

class Module extends BaseModule
{

	/**
	 * Translation category for Yii::t function
	 *
	 * @var string
	 */
	public $translationCategory = null;
	/**
	 * Number for sorting in backend navigation
	 *
	 * @var integer
	 */
	public $backendSortNumber = null;
	/**
	 * Backend index url
	 *
	 * @var string
	 */
	public $backendIndex = null;
	/**
	 * Is backend enabled?
	 *
	 * @var bool
	 */
	public $backendEnabled = true;
	/**
	 * Custom backend class
	 *
	 * @var mixed
	 */
	public $backendClass = null;
	/**
	 * @var bool Has it frontend? Default is true.
	 */
	protected $hasFrontend = true;
	/**
	 * @var bool Has it backend? Default is false.
	 */
	protected $hasBackend = false;

	public function init()
	{
		parent::init();
		//Register backend submodule
		if ($this->hasBackend && $this->backendEnabled) {

			if (!$this->backendClass) {
				$class = get_class($this);
				if (($pos = strrpos($class, '\\')) !== false) {
					$this->backendClass = substr($class, 0, $pos) . '\\modules\\backend\\BackendModule';
				}
			}

			$this->setModule('backend', ['class' => $this->backendClass]);
		}
	}

	public function getNameSpace()
	{
		$class = get_class($this);
		if (($pos = strrpos($class, '\\')) !== false) {
			return substr($class, 0, $pos);
		}
		return '';
	}

	/**
	 * Returns backend module
	 *
	 * @param bool $load
	 * @return null|BaseModule
	 */
	public function getBackendModule($load = true)
	{
		return $this->getModule($this->backendClass, $load);
	}

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
	 *
	 * @return string|null Icon
	 */
	public function getFaIcon()
	{
		return null;
	}

	/**
	 * FontAwesome icon class
	 *
	 * @return string|null Icon
	 */
	public function getBackendIndex()
	{
		if ($this->backendIndex !== null) {
			return $this->id . '/' . $this->backendIndex;
		} else {
			return $this->id . '/' . $this->defaultRoute . '/index';
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
	 * Returns configuration model
	 * Example:
	 * [
	 * 'pageTitle' => ['label' => Yii::t('maddoger/admin', 'Admin page title')],
	 * 'brandName' => ['label' => Yii::t('maddoger/admin', 'Brand name')],
	 * 'brandLogo' => ['type'=>'file', 'label' => Yii::t('maddoger/admin', 'Brand logo file')],
	 * ]
	 *
	 * @return DynamicModel
	 */
	public function getConfigurationModel()
	{
		$model = new DynamicModel();

		if ($this->hasBackend()) {
			$model->addAttributes([
				'backendSortNumber' => ['label' => Yii::t('maddoger/admin', 'Sort in admin menu'),
					'help' => Yii::t('maddoger/admin', 'Number for sorting the module menu in admin panel.'),
					'rules' => [
						['backendSortNumber', 'integer'],
						['backendSortNumber', 'filter', 'filter'=>'intval'],
					]
				],
			]);
		}

		return $model;
	}

	/**
	 * Has module backend?
	 * Module must have submodule backend
	 *
	 * @return string|null Module has backend
	 */
	public function hasBackend()
	{
		return $this->hasBackend && $this->backendEnabled && ($this->hasModule('backend'));
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
	 *
	 * @return array|null
	 */
	public function getRbacRoles()
	{
		if ($this->hasBackend())
			return null;
	}

	/**
	 * Returns navigation items for backend
	 *
	 * @return array
	 */
	public function getBackendNavigation()
	{
		return null;
	}
}