<?php

namespace rusporting\core\components;


class BackendController extends FrontendController
{
	public function behaviors()
	{
		return [
			'access' => [
				'class' => 'yii\web\AccessControl',
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
}