<?php
/**
 * @copyright Copyright (c) 2014 Vitaliy Syrchikov
 * @link http://syrchikov.name
 */

namespace maddoger\core\faker;
use Yii;
use yii\base\Object;

/**
 * FakerGenerator
 *
 * @author Vitaliy Syrchikov <maddoger@gmail.com>
 * @link http://syrchikov.name
 * @package maddoger/yii2-core
 */
class FakerGenerator extends Object
{
    public $language;

    public $modelClass;

    private $_generator;

    /**
     * Returns Faker generator instance. Getter for private property.
     * @return \Faker\Generator
     */
    public function getGenerator()
    {
        if ($this->_generator === null) {
            $language = $this->language === null ? Yii::$app->language : $this->language;
            $this->_generator = \Faker\Factory::create(str_replace('-', '_', $language));
        }
        return $this->_generator;
    }

    /**
     * @param \Closure $function
     * @param int $index
     * @return mixed
     */
    public function generateData($function, $index)
    {
        $faker = $this->getGenerator();
        return call_user_func_array($function, ['faker' => $faker, 'index' => $index]);
    }
}