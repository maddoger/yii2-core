<?php

namespace maddoger\core\datetime;

use yii\base\Behavior;
use yii\base\Event;
use yii\base\InvalidParamException;
use yii\db\BaseActiveRecord;
use yii\helpers\ArrayHelper;
use yii\i18n\Formatter;
use yii\validators\DateValidator;


/**
 * Class DateTimeBehavior
 */
class DateTimeBehavior extends Behavior
{
    /**
     * @var string
     */
    public $namingTemplate = '{attribute}_local';
    /**
     * @var Formatter
     */
    public $formatter;
    /**
     * @var string|array
     * Defaults to ['datetime', 'yyyy-MM-dd HH:mm:ss']
     * Your can use:
     * 'datetime' for ['datetime', 'yyyy-MM-dd HH:mm:ss']
     * 'date' for ['date', 'yyyy-MM-dd']
     * 'time' for ['time', 'HH:mm:ss']
     */
    public $originalFormat = 'datetime';

    /**
     * @var string
     */
    public $originalTimeZone = 'UTC';

    /**
     * @var string|array
     */
    public $localFormat = 'datetime';

    /**
     * @var string
     * If this property is not set, [[\yii\base\Application::timeZone]] will be used.
     */
    public $localTimeZone;

    /**
     * @var array
     */
    public $attributes = [];
    /**
     * @var array
     */
    public $attributeConfig = ['class' => 'maddoger\core\datetime\DateTimeAttribute'];

    /**
     * @var bool
     */
    public $performValidation = true;

    /**
     * @var DateTimeAttribute[]
     */
    public $attributeValues = [];


    public function init()
    {
        if (is_null($this->formatter))
            $this->formatter = \Yii::$app->formatter;
        elseif (is_array($this->formatter))
            $this->formatter = \Yii::createObject($this->formatter);


        if (!$this->localTimeZone) {
            $this->localTimeZone = \Yii::$app->timeZone;
        }

        $this->prepareAttributes();
    }

    public function events()
    {
        $events = [];
        if ($this->performValidation) {
            $events[BaseActiveRecord::EVENT_BEFORE_VALIDATE] = 'onBeforeValidate';
        }
        return $events;
    }

    /**
     * Performs validation for all the attributes
     * @param Event $event
     */
    public function onBeforeValidate($event)
    {
        foreach ($this->attributeValues as $name => $value) {

            $validator = \Yii::createObject([
                'class' => DateValidator::className(),
                'format' => $value->localFormat[1],
            ]);
            $validator->validateAttribute($this->owner, $value->localAttribute);
        }
    }

    protected function prepareAttributes()
    {
        foreach ($this->attributes as $key => $value) {
            $config = $this->attributeConfig;
            $config['originalFormat'] = $this->originalFormat;
            $config['localFormat'] = $this->localFormat;

            $config['originalTimeZone'] = $this->originalTimeZone;
            $config['localTimeZone'] = $this->localTimeZone;

            if (is_integer($key)) {
                $originalAttribute = $value;
                $localAttribute = $this->processTemplate($originalAttribute);
            } else {
                $originalAttribute = $key;
                if (is_string($value)) {
                    $localAttribute = $value;
                } else {
                    $localAttribute = ArrayHelper::remove($value, 'localAttribute', $this->processTemplate($originalAttribute));
                    $config = array_merge($config, $value);
                }
            }
            $config['behavior'] = $this;
            $config['originalAttribute'] = $originalAttribute;
            $config['localAttribute'] = $localAttribute;

            $this->attributeValues[$localAttribute] = \Yii::createObject($config);
        }
    }

    protected function processTemplate($originalAttribute)
    {
        return strtr($this->namingTemplate, [
            '{attribute}' => $originalAttribute,
        ]);
    }

    public function canGetProperty($name, $checkVars = true)
    {
        if ($this->hasAttributeValue($name))
            return true;
        else
            return parent::canGetProperty($name, $checkVars);
    }

    protected function hasAttributeValue($name)
    {
        return isset($this->attributeValues[$name]);
    }

    public function canSetProperty($name, $checkVars = true)
    {
        if ($this->hasAttributeValue($name))
            return true;
        else
            return parent::canSetProperty($name, $checkVars);
    }

    public function __get($name)
    {
        if ($this->hasAttributeValue($name)) {
            return $this->attributeValues[$name];
        } else {
            return parent::__get($name);
        }
    }

    public function __set($name, $value)
    {
        if ($this->hasAttributeValue($name)) {
            $this->attributeValues[$name]->setValue($value);
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * @param string|array $format
     * @param Formatter $formatter
     * @throws InvalidParamException
     * @return array|string
     */
    public static function normalizeOriginalFormat($format, $formatter)
    {
        if (is_string($format)) {
            switch ($format) {
                case 'date':
                    return ['date', 'yyyy-MM-dd'];
                case 'time':
                    return ['time', 'yyyy-MM-dd'];
                case 'datetime':
                    return ['datetime', 'yyyy-MM-dd HH:mm:ss'];
                default:
                    throw new InvalidParamException('$originalFormat has incorrect value');
            }
        }
        return $format;
    }

    /**
     * @param string|array $format
     * @param Formatter $formatter
     * @throws InvalidParamException
     * @return array|string
     */
    public static function normalizeLocalFormat($format, $formatter)
    {
        if (is_string($format)) {
            switch ($format) {
                case 'date':
                    return ['date', $formatter->dateFormat];
                case 'time':
                    return ['time', $formatter->timeFormat];
                case 'datetime':
                    return ['datetime', $formatter->datetimeFormat];
                default:
                    throw new InvalidParamException('$localFormat has incorrect value');
            }
        }
        return $format;
    }
} 