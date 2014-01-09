<?php
/**
 * @author Vitaliy Syrchikov <maddoger@gmail.com>
 * @link http://syrchikov.name/
 * @copyright Copyright (c) 2013-2014 Rusporting Inc.
 */

namespace rusporting\core;

use yii\db\ActiveRecord as BaseActiveRecord;
use yii\base\Event;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

class ActiveRecord extends BaseActiveRecord
{
	/**
	 * Attribute option for active form
	 * @return array
	 */
	public function attributeOptions()
	{
		return [];
	}


	/*public function init(){
		Event::on(ActiveRecord::className(), self::EVENT_AFTER_FIND, function ($event) {
			$class = get_called_class();
			\Yii::trace($class . ' is finded.');
			if(method_exists($class, 'onAfterFind')){
				$this->onAfterFind();
			}
		});
		Event::on(ActiveRecord::className(), self::EVENT_BEFORE_VALIDATE, function ($event) {
			$class = get_called_class();
			\Yii::trace($class . ' is before check.');
			if(method_exists($class, 'onBeforeValidate')){
				$this->onBeforeValidate();
			}
		});
		Event::on(ActiveRecord::className(), self::EVENT_AFTER_VALIDATE, function ($event) {
			$class = get_called_class();
			\Yii::trace($class . ' is checked.');
			if(method_exists($class, 'onAfterValidate')){
				$this->onAfterValidate();
			}
		});
		Event::on(ActiveRecord::className(), ActiveRecord::EVENT_BEFORE_INSERT, function ($event) {
			$class = get_called_class();
			\Yii::trace($class . ' is before insert.');
			if(method_exists($class, 'onBeforeInsert')){
				$this->onBeforeInsert();
			}
		});
		Event::on(ActiveRecord::className(), ActiveRecord::EVENT_AFTER_INSERT, function ($event) {
			$class = get_called_class();
			\Yii::trace($class . ' is inserted.');
			if(method_exists($class, 'onAfterInsert')){
				$this->onAfterInsert();
			}
		});
	}*/

}