<?php
/**
 * @copyright Copyright (c) 2014 Vitaliy Syrchikov
 * @link http://syrchikov.name
 */

namespace maddoger\core;
use Yii;

/**
 * DynamicModel for attribute labels support
 *
 * @author Vitaliy Syrchikov <maddoger@gmail.com>
 * @link http://syrchikov.name
 * @package maddoger/yii2-core
 */
class DynamicModel extends \yii\base\DynamicModel
{
    private $_attributeLabels;

    /**
     * Defines an attribute.
     * @param string $name the attribute name
     * @param mixed $value the attribute value
     * @param string $label the attribute label
     */
    public function defineAttribute($name, $value = null, $label = null)
    {
        if ($label) {
            $this->_attributeLabels[$name] = $label;
        }
        parent::defineAttribute($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return $this->_attributeLabels;
    }
}