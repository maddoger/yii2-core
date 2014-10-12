<?php

namespace maddoger\core\datetime;

use yii\base\Object;
use yii\helpers\FormatConverter;


/**
 * Class DateTimeAttribute
 * @property string $value
 */
class DateTimeAttribute extends Object
{
    /**
     * @var DateTimeBehavior
     */
    public $behavior;
    /**
     * @var string
     */
    public $originalAttribute;
    /**
     * @var string|array
     */
    public $originalFormat;

    /**
     * @var string|\IntlTimeZone|\DateTimeZone
     */
    public $originalTimeZone;

    /**
     * @var string
     */
    public $localAttribute;

    /**
     * @var string|array
     */
    public $localFormat;

    /**
     * @var string|\IntlTimeZone|\DateTimeZone
     */
    public $localTimeZone;

    protected $_originalFormatPhp;
    protected $_localFormatPhp;
    protected $_value;

    public function init()
    {
        parent::init();

        $this->originalFormat = DateTimeBehavior::normalizeOriginalFormat($this->originalFormat, $this->behavior->formatter);
        $this->localFormat = DateTimeBehavior::normalizeLocalFormat($this->localFormat, $this->behavior->formatter);

        if (!$this->_originalFormatPhp) {
            $this->_originalFormatPhp = FormatConverter::convertDateIcuToPhp($this->originalFormat[1], $this->originalFormat[0]);
        }
        if (!$this->_localFormatPhp) {
            $this->_localFormatPhp = FormatConverter::convertDateIcuToPhp($this->localFormat[1], $this->localFormat[0]);
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getValue();
    }

    /**
     * @return string
     */
    public function getValue()
    {
        if ($this->_value) {
            return $this->_value;
        }

        $datetime = \DateTime::createFromFormat(
            $this->_originalFormatPhp,
            $this->behavior->owner->{$this->originalAttribute},
            new \DateTimeZone($this->originalTimeZone)
        );
        if (!$datetime) {
            return null;
        } else {
            $datetime->setTimezone(new \DateTimeZone($this->localTimeZone));
            return $this->behavior->formatter->format($datetime, $this->localFormat);
        }
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->_value = $value;

        $datetime = \DateTime::createFromFormat(
            $this->_localFormatPhp,
            $value,
            new \DateTimeZone($this->localTimeZone)
        );
        if (!$datetime) {
            $this->behavior->owner->{$this->originalAttribute} = null;
        } else {
            $datetime->setTimezone(new \DateTimeZone($this->originalTimeZone));
            $this->behavior->owner->{$this->originalAttribute} = $datetime->format($this->_originalFormatPhp);
        }
    }
}