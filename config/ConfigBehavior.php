<?php
/**
 * @copyright Copyright (c) 2014 Vitaliy Syrchikov
 * @link http://syrchikov.name
 */

namespace maddoger\core\config;

use maddoger\core\models\Config;
use Yii;
use yii\base\Behavior;
use yii\log\Logger;

/**
 * ConfigBehavior
 *
 * @author Vitaliy Syrchikov <maddoger@gmail.com>
 * @link http://syrchikov.name
 * @package maddoger/yii2-core
 */
class ConfigBehavior extends Behavior
{
    /**
     * @var string
     */
    public $modelClass = 'maddoger\core\models\Config';

    /**
     * @var bool
     */
    public $autoLoad = true;

    /**
     * @inheritdoc
     */
    public function attach($owner)
    {
        parent::attach($owner);
        if ($this->autoLoad) {
            $this->configure();
        }
    }

    /**
     * Returns owner config
     * @return mixed
     */
    public function getConfig()
    {
        $class = $this->owner->className();
        $modelClass = $this->modelClass;
        return $modelClass::getConfig($class);
    }

    /**
     * Returns owner config
     * @param mixed $config
     * @return bool
     */
    public function setConfig($config)
    {
        $class = $this->owner->className();
        $modelClass = $this->modelClass;
        return $modelClass::setConfig($class, $config);
    }

    /**
     * Configure owner
     */
    public function configure()
    {
        $config = $this->getConfig();
        if ($config) {
            Yii::getLogger()->log('CONFIG_BEHAVIOR_CONFIGURE', Logger::LEVEL_INFO);
            Yii::configure($this->owner, $config);
        }
    }
}