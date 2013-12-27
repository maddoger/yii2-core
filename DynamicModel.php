<?php

namespace rusporting\core;

use yii\base\Model;
use yii\helpers\ArrayHelper;

class DynamicModel extends Model
{
	protected $_attributes = [];

	protected $_attributeParams = [];

	protected $_rules = [];

	public function addAttribute($name, $params=[])
	{
		$type = ArrayHelper::getValue($params, 'type', 'text');
		$value = ArrayHelper::getValue($params, 'value', null);
		$params['type'] = $type;
		$this->_attributes[$name] = $value;
		$this->_attributeParams[$name] = $params;
		if (isset($params['rules'])) {
			$this->addAttributeRules($name, $type, $params['rules']);
		}
	}

	public function addAttributes($attributes)
	{
		foreach ($attributes as $name=>$params) {
			$this->addAttribute($name, $params);
		}
		return $this;
	}

	public function addAttributeRules($name, $type, $rules)
	{
		if ($rules) {
			$this->_rules = ArrayHelper::merge($this->_rules, $rules);
		}
	}

	public function rules()
	{
		return $this->_rules;
	}

	public function scenarios()
	{
		return ['default'=> [], 'all' => $this->attributes() ];
	}

	public function attributes()
	{
		$public = parent::attributes();
		return ArrayHelper::merge($public, array_keys($this->_attributes));
	}

	public function attributeLabels()
	{
		if ($this->_attributeParams) {
			$res = [];
			foreach ($this->_attributeParams as $key=>$params)
			{
				if (isset($params['label'])) {
					$res[$key] = $params['label'];
				}
			}
			return $res;
		}
		return parent::attributeLabels();
	}

	public function attributeOptions()
	{
		return $this->_attributeParams;
	}

	/**
	 * Returns the value of a component property.
	 * This method will check in the following order and act accordingly:
	 *
	 *  - a property defined by a getter: return the getter result
	 *  - a property of a behavior: return the behavior property value
	 *
	 * Do not call this method directly as it is a PHP magic method that
	 * will be implicitly called when executing `$value = $component->property;`.
	 * @param string $name the property name
	 * @return mixed the property value or the value of a behavior's property
	 * @see __set()
	 */
	public function __get($name)
	{
		if (array_key_exists($name, $this->_attributes)) {
			return $this->_attributes[$name];
		}
		return parent::__get($name);
	}

	/**
	 * Sets the value of a component property.
	 * This method will check in the following order and act accordingly:
	 *
	 *  - a property defined by a setter: set the property value
	 *  - an event in the format of "on xyz": attach the handler to the event "xyz"
	 *  - a behavior in the format of "as xyz": attach the behavior named as "xyz"
	 *  - a property of a behavior: set the behavior property value
	 *
	 * Do not call this method directly as it is a PHP magic method that
	 * will be implicitly called when executing `$component->property = $value;`.
	 * @param string $name the property name or the event name
	 * @param mixed $value the property value
	 * @see __get()
	 */
	public function __set($name, $value)
	{
		if (array_key_exists($name, $this->_attributes)) {
			$this->_attributes[$name] = $value;
			return;
		}
		return parent::__set($name, $value);
	}

	/**
	 * Checks if a property value is null.
	 * This method will check in the following order and act accordingly:
	 *
	 *  - a property defined by a setter: return whether the property value is null
	 *  - a property of a behavior: return whether the property value is null
	 *
	 * Do not call this method directly as it is a PHP magic method that
	 * will be implicitly called when executing `isset($component->property)`.
	 * @param string $name the property name or the event name
	 * @return boolean whether the named property is null
	 */
	public function __isset($name)
	{
		if (array_key_exists($name, $this->_attributes)) {
			return true;
		}
		return parent::__isset($name);
	}

	/**
	 * Sets a component property to be null.
	 * This method will check in the following order and act accordingly:
	 *
	 *  - a property defined by a setter: set the property value to be null
	 *  - a property of a behavior: set the property value to be null
	 *
	 * Do not call this method directly as it is a PHP magic method that
	 * will be implicitly called when executing `unset($component->property)`.
	 * @param string $name the property name
	 */
	public function __unset($name)
	{
		if (array_key_exists($name, $this->_attributes)) {
			unset($this->_attributes[$name]);
			unset($this->_attributeParams[$name]);
			return;
		}
		return parent::__unset($name);
	}
}