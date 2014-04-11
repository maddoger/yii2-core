<?php

namespace maddoger\core;


class BackendController extends FrontendController
{
	public function behaviors()
	{
		return [
			'access' => [
				'class' => 'yii\filters\AccessControl',
				'rules' => [
					[
						'allow' => true,
						'roles' => ['@'],
					],
					[
						'allow' => false,
					]
				],
			],
		];
	}

	/**
	 * @return array
	 */
	public function getDefaultRenderParams()
	{
		$params = parent::getDefaultRenderParams();

		$params['frontend'] = false;
		$params['backend'] = true;

		return $params;
	}
}