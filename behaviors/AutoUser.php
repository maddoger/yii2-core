<?php

namespace rusporting\core\behaviors;

use yii\base\Behavior;
use yii\base\Event;
use yii\db\Expression;
use yii\db\ActiveRecord;
use Yii;

/**
 * AutoUpdateInfo will automatically fill the attributes about creation and updating user.
 *
 * AutoUpdateInfo fills the attributes when the associated AR model is being inserted or updated.
 * You may specify an AR to use this behavior like the following:
 *
 * ~~~
 * public function behaviors()
 * {
 *     return [
 *         'user' => ['class' => 'rusporting\core\behaviors\AutoUser'],
 *     ];
 * }
 * ~~~
  *
 * @author Vitaliy Syrchikov <maddoger@gmail.com>
 */
class AutoUser extends Behavior
{
	/**
	 * @var array list of attributes that are to be automatically filled with timestamps.
	 * The array keys are the ActiveRecord events upon which the attributes are to be filled with timestamps,
	 * and the array values are the corresponding attribute(s) to be updated. You can use a string to represent
	 * a single attribute, or an array to represent a list of attributes.
	 * The default setting is to update the `create_time` attribute upon AR insertion,
	 * and update the `update_time` attribute upon AR updating.
	 */
	public $attributes = [
		ActiveRecord::EVENT_BEFORE_INSERT => 'create_user_id',
		ActiveRecord::EVENT_BEFORE_UPDATE => 'update_user_id',
	];

	/**
	 * @var \Closure|Expression The expression that will be used for generating the user id.
	 * This can be either an anonymous function that returns the user id value,
	 * or an [[Expression]] object representing a DB expression (e.g. `new Expression('NOW()')`).
	 * If not set, it will use the value of `Yii::$app->user->getId()` to fill the attributes.
	 */
	public $userId;

	/**
	 * Declares event handlers for the [[owner]]'s events.
	 * @return array events (array keys) and the corresponding event handler methods (array values).
	 */
	public function events()
	{
		$events = $this->attributes;
		foreach ($events as $i => $event) {
			$events[$i] = 'updateUser';
		}
		return $events;
	}

	/**
	 * Updates the attributes with the current timestamp.
	 * @param Event $event
	 */
	public function updateUser($event)
	{
		$attributes = isset($this->attributes[$event->name]) ? (array)$this->attributes[$event->name] : [];
		if (!empty($attributes)) {
			$user = $this->evaluateUserId();
			foreach ($attributes as $attribute) {
				$this->owner->$attribute = $user;
			}
		}
	}

	/**
	 * Gets the current user id.
	 * @return mixed the user id value
	 */
	protected function evaluateUserId()
	{
		if ($this->userId instanceof Expression) {
			return $this->userId;
		} elseif ($this->userId !== null) {
			return call_user_func($this->userId);
		} else {
			return Yii::$app->user->getId();
		}
	}
}
