<?php

namespace rusporting\core;

use yii\web\Controller;
use Yii;

class FrontendController extends Controller
{
	/**
	 * @var string Page title
	 */
	public $title = null;
	/**
	 * @var string Page subtitle
	 */
	public $subtitle = null;
	/**
	 * @var string Window title
	 */
	public $windowTitle = null;
	/**
	 * @var string Meta keywords
	 */
	public $metaKeywords = null;
	/**
	 * @var string Meta description
	 */
	public $metaDescription = null;
	/**
	 * @var string Author
	 */
	public $metaAuthor = null;
	/**
	 * OpenGraph data array
	 * Example:
	 * [
	 *        'title' => 'News title',
	 *        'description' => 'News description',
	 *        'url' => 'http://example.com/news/23',
	 *        'type' => 'article',
	 *        'image' => 'http://example.com/uploads/news/23_1.jpg',
	 * ]
	 *
	 * @var array
	 */
	public $og = null;
	/**
	 * @var array|null breadcrums
	 *
	 * ```
	 * [
	 * ['label'=>Yii::t('rusporting/admin', 'Modules'), 'fa'=>'gears', 'url'=> ['/admin/modules']],
	 * ['label'=>$module->getName(), 'url'=> ['/admin/modules/config', 'module'=>$module->id], 'fa'=>$module->getFaIcon()],
	 * ['label'=>Yii::t('rusporting/admin', 'Configuration')],
	 * ]
	 * ```
	 */
	public $breadcrumbs = [];

	/**
	 * @inheritdoc
	 */
	public function render($view, $params = [])
	{
		$params = array_merge($this->getDefaultRenderParams(), $params);
		$this->setViewParams();
		$output = $this->getView()->render($view, $params, $this);
		$layoutFile = $this->findLayoutFile($this->getView());
		if ($layoutFile !== false) {
			$params['content'] = $output;
			return $this->getView()->renderFile($layoutFile, $params, $this);
		} else {
			return $output;
		}
	}

	/**
	 * @return array
	 */
	public function getDefaultRenderParams()
	{
		$params = [];
		$params['isAuth'] = Yii::$app->user->isGuest;
		$params['isGuest'] = !$params['isAuth'];
		$params['user'] = null;
		if ($params['isAuth']) {
			$params['user'] = Yii::$app->user->identity;
		}
		$params['controller'] = $this;
		$params['frontend'] = true;
		$params['backend'] = false;
		$params['breadcrumbs'] = $this->breadcrumbs;
		if ($this->windowTitle === null) {
			$this->windowTitle = $this->title;
		}
		return $params;
	}

	/**
	 * Set controller params (keywords, title) to View
	 */
	public function setViewParams()
	{
		/**
		 * @var \Yii\web\View $view
		 */
		$view = $this->getView();
		$view->title = $this->windowTitle;
		if ($this->metaKeywords !== null) {
			$view->registerMetaTag(['name' => 'keywords', 'content' => $this->metaKeywords], 'keywords');
		}
		if ($this->metaDescription !== null) {
			$view->registerMetaTag(['name' => 'description', 'content' => $this->metaDescription], 'description');
		}
		if ($this->metaAuthor !== null) {
			$view->registerMetaTag(['name' => 'author', 'content' => $this->metaAuthor], 'author');
		}
		if ($this->og !== null) {
			foreach ($this->og as $key => $value) {
				$key = 'og:' . $key;
				$view->registerMetaTag(['name' => $key, 'content' => $value]);
			}
		}
		$view->params['breadcrumbs'] = $this->breadcrumbs;
	}

	/**
	 * @inheritdoc
	 */
	public function renderPartial($view, $params = [])
	{
		$params = array_merge($this->getDefaultRenderParams(), $params);
		$this->setViewParams();
		return parent::render($view, $params);
	}

	/**
	 * @inheritdoc
	 */
	public function renderFile($file, $params = [])
	{
		$params = array_merge($this->getDefaultRenderParams(), $params);
		$this->setViewParams();
		return parent::renderFile($file, $params);
	}
}