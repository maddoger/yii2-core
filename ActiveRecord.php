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
	 * Declares the name of the database table associated with this AR class.
	 * By default this method returns the class name as the table name by calling [[Inflector::camel2id()]]
	 * with prefix from database connection. For example, 'Customer' becomes 'tbl_customer', and 'OrderItem' becomes
	 * 'tbl_order_item'. You may override this method if the table is not named after this convention.
	 * @return string the table name
	 */
	public static function tableName()
	{
		return static::getDb()->tablePrefix . Inflector::camel2id(StringHelper::basename(get_called_class()), '_');
	}

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