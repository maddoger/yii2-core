<?php

namespace maddoger\core\behaviors;

use yii\base\Behavior;
use yii\base\Event;
use yii\db\ActiveRecord;
use yii\db\Query;

use IntlDateFormatter;
use NumberFormatter;
use DateTime;

/**
 * @author Vitaliy Syrchikov <maddoger@gmail.com>
 */
class TimeZoneConverter extends Behavior
{
	/**
	 * @var ActiveRecord the owner of this behavior.
	 */
	public $owner;
	/**
	 * @var array
	 */
	public $attributes = [];
	/**
	 * @var string|\IntlTimeZone|\DateTimeZone the timezone to use for formatting time and date values for output.
	 * This can be any value that may be passed to [date_default_timezone_set()](http://www.php.net/manual/en/function.date-default-timezone-set.php)
	 * e.g. `UTC`, `Europe/Berlin` or `America/Chicago`.
	 * Refer to the [php manual](http://www.php.net/manual/en/timezones.php) for available timezones.
	 * This can also be an IntlTimeZone or a DateTimeZone object.
	 * If not set, [[\yii\base\Application::timezone]] will be used.
	 */
	public $localTimeZone = null;
	/**
	 * @var string|\IntlTimeZone|\DateTimeZone the timezone to use for formatting time and date values for saving.
	 * This can be any value that may be passed to [date_default_timezone_set()](http://www.php.net/manual/en/function.date-default-timezone-set.php)
	 * e.g. `UTC`, `Europe/Berlin` or `America/Chicago`.
	 * Refer to the [php manual](http://www.php.net/manual/en/timezones.php) for available timezones.
	 * This can also be an IntlTimeZone or a DateTimeZone object.
	 * UTC is default.
	 */
	public $savingTimeZone = 'UTC';

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		if ($this->localTimeZone === null) {
			$this->localTimeZone = \Yii::$app->timeZone;
		}
		if ($this->savingTimeZone === null) {
			$this->savingTimeZone = 'UTC';
		}
		parent::init();
	}

	/**
	 * @inheritdoc
	 */
	public function events()
	{
		return [
			ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
			ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
			ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
			ActiveRecord::EVENT_AFTER_INSERT => 'afterFind',
			ActiveRecord::EVENT_AFTER_UPDATE => 'afterFind',
		];
	}

	/**
	 * @param Event $event
	 */
	public function afterFind($event)
	{
		if (count($this->attributes) > 0) {

			foreach ($this->attributes as $attr) {

				if (isset($this->owner->{$attr})) {

					$date = new DateTime($this->owner->{$attr}, new \DateTimeZone($this->savingTimeZone));
					$date->setTimezone(new \DateTimeZone($this->localTimeZone));

					$this->owner->setAttribute($attr, is_numeric($this->owner->{$attr}) ? $date->getTimestamp() : $date->format('Y-m-d H:i:s'));

				} else {
					//throw exception
				}
			}
		}
	}

	/**
	 * @param Event $event
	 */
	public function beforeSave($event)
	{
		if (count($this->attributes) > 0) {

			$attributes = $this->owner->getDirtyAttributes($this->attributes);

			if ($attributes) {
				foreach ($attributes as $attr=>$value) {

					if (is_numeric($value)) {
						$date = new DateTime('@'.$value, new \DateTimeZone($this->localTimeZone));
					} else {
						$date = new DateTime($value, new \DateTimeZone($this->localTimeZone));
					}

					$date->setTimezone(new \DateTimeZone($this->savingTimeZone));
					$this->owner->setAttribute($attr, is_numeric($value) ? $date->getTimestamp() : $date->format('Y-m-d H:i:s'));
				}
			}
		}
	}
}