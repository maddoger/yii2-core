<?php
/**
 * @copyright Copyright (c) 2014 Vitaliy Syrchikov
 * @link http://syrchikov.name
 */

namespace maddoger\core\config;

use Yii;
use yii\base\Model;

/**
 * Config Model
 *
 * Model for saving configuration.
 *
 * @author Vitaliy Syrchikov <maddoger@gmail.com>
 * @link http://syrchikov.name
 * @package maddoger/yii2-website
 */
class ConfigModel extends Model
{
    public $containerClass = 'maddoger\core\models\Config';

    public $objectClass;

    /**
     * Save config
     * @return bool
     */
    public function save()
    {
        $objectClass = $this->objectClass;
        $modelClass = $this->containerClass;
        return $modelClass::setConfig($objectClass, $this);
    }

    /**
     * Returns owner config
     * @param string $objectClass
     * @param array $defaults
     * @param string $containerClass
     * @return static
     */
    public static function getConfig($objectClass, $defaults = [], $containerClass = 'maddoger\core\models\Config')
    {
        $obj = $containerClass::getConfig($objectClass);
        $thisClass = static::className();
        if (!$obj || !($obj instanceof $thisClass)) {
            $obj = Yii::createObject($thisClass);
            $obj->setAttributes($defaults);
            $obj->objectClass = $objectClass;
            $obj->containerClass = $containerClass;
        }
        return $obj;
    }
}