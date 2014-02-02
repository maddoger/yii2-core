<?php

namespace rusporting\core\behaviors;

use yii\base\Behavior;
use yii\base\Event;
use yii\base\Exception;
use yii\db\ActiveRecord;
use yii\db\Query;
use Yii;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/**
 *
 * 'tags' => Array(
 *   'class' => 'rusporting\core\behaviors\Taggable',
 *
 * 		// Tag model path alias.
 * 		'tagModel' => 'rusporting\news\models\Tag',
 *
 *		// The field name which contains tag title.
 *		'tagTableTitle' => 'title',
 *
 *		// The name of relation table.
 *		'tagRelationTable' => $this->getDb()->tablePrefix.'user_tag',
 *
 *		 // The name of attribute in relation table which recalls tag.
 *		 'tagRelationTableTagFk' => 'tag_id',
 *
 * 		// The name of attribute in relation table which recalls model.
 *		 'tagRelationTableModelFk' => 'user_id',
 *
 * 		// Separator for tags in strings.
 * 		'tagsSeparator' => ', '
 *
 * 		// Read-only, default is false
 * 		'readOnly' => true
 *
 *
 * @author Vitaliy Syrchikov <maddoger@gmail.com>
 *
 */
class Taggable extends Behavior
{
	/**
	 * @var \yii\db\ActiveRecord
	 */
	public $owner;

	/**
	 * The name of relation
	 *
	 * By default will be '{tagModelClassName}'.
	 */
	public $relationName = null;

	/**
	 * Tag model path alias.
	 *
	 * Will be passed as 'class' attribute value to Yii::createComponent().
	 *
	 * @var string
	 *
	 * @see YiiBase::createComponent()
	 */
	public $tagModel = null;

	/**
	 * @var string frequency field name for counters update. If null, updates turns off.
	 */
	public $frequency = null;

	/**
	 * The field name which contains tag title.
	 *
	 * Will be passed to CActiveRecord::getAttribute().
	 *
	 * @var string
	 *
	 * @see CActiveRecord::getAttribute()
	 */
	public $tagTableTitle = 'title';

	/**
	 * The name of relation table.
	 *
	 * By default will be '{modelTableName}_{tagTableName}'.
	 *
	 * @var string
	 */
	public $tagRelationTable = null;

	/**
	 * The name of attribute in relation table which recalls tag.
	 *
	 * By default will be '{tagTableName}_id'.
	 *
	 * @var string
	 */
	public $tagRelationTableTagFk = null;

	/**
	 * The name of attribute in relation table which recalls model.
	 *
	 * By default will be '{modelTableName}_id'.
	 *
	 * @var string
	 */
	public $tagRelationTableModelFk = null;

	/**
	 * Separator for tags in strings.
	 *
	 * @var string
	 */
	public $tagsSeparator = ',';

	/**
	 * @var bool can use only existing tags
	 */
	public $readOnly = false;

	/**
	 * @var array attributes for new record
	 */
	public $newRecordAttributes = [];

	/**
	 * The list of attached to model tags.
	 *
	 * @var Array
	 */
	protected $tagsList;

	/**
	 * Instance of blank (without attributes) tag model for internal usage.
	 *
	 * @var \yii\db\ActiveRecord
	 */
	protected $blankTagModel = null;

	/**
	 * Shows were tags already loaded from DB or not.
	 *
	 * @var bool
	 */
	protected $tagsAreLoaded = false;

	/**
	 * @var \yii\db\ActiveRelationInterface
	 */
	protected $relation = null;

	/**
	 * @inheritdoc
	 */
	public function events()
	{
		return [
			ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
			ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
			ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
		];
	}

	/**
	 * @param Event $event
	 */
	public function afterSave($event)
	{
		$this->loadTags();

		if (!$this->owner->isNewRecord) {
			$this->clearAttachedTags();
		}

		$rows = [];

		/* @var $tag ActiveRecord */
		foreach ($this->tagsList as $tag) {

			if ($tag->isNewRecord) {
				if ($this->readOnly) {
					continue;
				}
				$tag->setAttributes($this->newRecordAttributes);
			}
			if ($this->frequency !== null) {
				$tag->{$this->frequency}++;
			}
			if (!$tag->save()) {
				continue;
			}

			$rows[] = [$this->owner->getPrimaryKey(), $tag->getPrimaryKey()];
		}

		if (!empty($rows)) {
			$this->owner->getDb()
						->createCommand()
						->batchInsert($this->tagRelationTable, [$this->tagRelationTableModelFk, $this->tagRelationTableTagFk], $rows)
						->execute();
		}
	}

	/**
	 * @param Event $event
	 */
	public function afterDelete($event)
	{
		$this->clearAttachedTags();
	}

	public function attach($owner)
	{
		parent::attach($owner);

		$this->tagsList = [];

		if (!$this->tagModel) {
			throw new Exception('tagModel must be set!');
		}
		$class = $this->tagModel;
		$this->blankTagModel = new $class();
		if (!$this->blankTagModel) {
			throw new Exception('tagModel invalid value!');
		}

		if ($this->tagRelationTable === null) {
			$this->tagRelationTable = $this->owner->tableName() . '_' . Inflector::camel2id(StringHelper::basename($this->tagModel), '_');
		}
		if ($this->tagRelationTableTagFk === null) {
			$this->tagRelationTableTagFk = Inflector::camel2id(StringHelper::basename($this->tagModel), '_') . '_id';
		}
		if ($this->tagRelationTableModelFk === null) {
			$this->tagRelationTableModelFk = Inflector::camel2id(StringHelper::basename(get_class($this->owner)), '_') . '_id';
		}

		if ($this->relationName === null) {
			$this->relationName = lcfirst(basename($this->tagModel));
		}
	}

	public function getRelation()
	{
		if ($this->relation === null) {
			$this->relation = $this->owner->hasMany($this->tagModel, [$this->blankTagModel->primaryKey()[0] => $this->tagRelationTableTagFk])
										  ->viaTable($this->tagRelationTable, [$this->tagRelationTableModelFk => $this->owner->primaryKey()[0]]);

		}
		return $this->relation;
	}

	public function loadTags()
	{
		if (!$this->tagsAreLoaded) {
			$tagsList = $this->getRelation()->all();

			if ($tagsList) {
				foreach ($tagsList as $tag) {
					$this->tagsList[$tag->{$this->tagTableTitle}] = $tag;
				}
			}
			$this->tagsAreLoaded = true;
		}
		return $this->tagsList;
	}

	public function getAllExistingTags($array = false)
	{
		/**
		 * @var \yii\db\ActiveQuery $q
		 */
		$q = $this->blankTagModel->find()->orderBy($this->tagTableTitle);
		return $q->asArray($array)->all();
	}

	public function getAllExistingTagsFormAutocomplete()
	{
		$tags = $this->blankTagModel->find()->orderBy($this->tagTableTitle)->all();

		$res = [];
		foreach ($tags as $tag) {
			$res[] = array('id' => $tag->{$this->tagTableTitle}, 'text' => $tag->{$this->tagTableTitle});
		}
		return $res;
	}

	/**
	 * @return \ArrayObject
	 */
	public function get()
	{
		$this->loadTags();
		return $this->tagsList;
	}

	/**
	 * Checks whether or not specified tags are attached to the model.
	 *
	 * Can be called with any number of arguments of any type. Only constraint
	 * is that Object arguments should have __toString defined (Not applicable
	 * to instances of tag model).
	 *
	 * @return boolean True if ALL specified tags are attached to the model.
	 */
	public function has()
	{

		$this->loadTags();
		$tagsList = $this->getTagsList(func_get_args());

		$result = true;

		foreach (array_keys($tagsList) as $tagTitle) {

			if (array_key_exists($tagTitle, $this->tagsList)) {
				$result = false;
				break;
			}
		}

		return $result;
	}

	/**
	 * Attaches to the model specified set of tags that will replace all
	 * previous ones.
	 *
	 * Can be called with any number of arguments of any type. Only constraint
	 * is that Object arguments should have __toString defined (Not applicable
	 * to instances of tag model).
	 *
	 * Model will be selected if it has AT LEAST ONE of the specified tags attached.
	 *
	 * @return ActiveRecord Model that behaviour is attached to.
	 */
	public function set()
	{
		$this->tagsAreLoaded = true;

		$this->tagsList = $this->getTagsList(func_get_args());

		return $this->owner;
	}

	/**
	 * Attaches tags to model.
	 *
	 * Can be called with any number of arguments of any type. Only constraint
	 * is that Object arguments should have __toString defined (Not applicable
	 * to instances of tag model).
	 *
	 * @return ActiveRecord Model that behaviour is attached to.
	 */
	public function add()
	{
		$this->loadTags();
		$new = $this->getTagsList(func_get_args());
		if ($new) {
			$this->tagsList = array_merge($this->tagsList, $new);
		}

		return $this->owner;
	}

	/**
	 * Detaches specified tags from the model.
	 *
	 * Can be called with any number of arguments of any type. Only constraint
	 * is that Object arguments should have __toString defined (Not applicable
	 * to instances of tag model).
	 *
	 * @return ActiveRecord Model that behaviour is attached to.
	 */
	public function remove()
	{
		$this->loadTags();

		$tagsList = $this->getTagsList(func_get_args());

		foreach (array_keys($tagsList) as $tagTitle) {
			unset($this->tagsList[$tagTitle]);
		}

		return $this->owner;
	}


	/**
	 * Detaches all tags from the model.
	 *
	 * @return ActiveRecord Model that behaviour is attached to.
	 */
	public function reset()
	{
		$this->tagsAreLoaded = true;

		$this->tagsList = [];

		return $this->owner;
	}

	public function getString()
	{
		$this->loadTags();

		return implode(
			$this->tagsSeparator,
			array_keys($this->tagsList)
		);
	}


	public function getForAutocomplete()
	{
		$this->loadTags();

		$res = [];
		foreach ($this->tagsList as $tag) {
			$res[] = array('id' => $tag->{$this->tagTableTitle}, 'text' => $tag->{$this->tagTableTitle});
		}
		return $res;
	}

	public function getArray()
	{
		$this->loadTags();

		$res = [];
		foreach ($this->tagsList as $tag) {
			$res[] = $tag->attributes;
		}
		return $res;
	}

	public function __toString()
	{
		return $this->getString();
	}

	/**
	 * Parses array of arguments were passed to one of interface methods and
	 * creates a has map (CMap) of corresponding tag objects.
	 *
	 * @param Array $methodArguments The list of input parameters were passed to interface method.
	 * @return \ArrayObject List of tag object corresponding to input parameters.
	 */
	protected function getTagsList($methodArguments)
	{
		$result = [];

		foreach ($methodArguments as $tagList) {

			$this->normalizeTagList($tagList);

			foreach ($tagList as $tag) {

				$tagTitle = $this->prepareTagTitle($tag);

				$result[$tagTitle] = $this->prepareTagObject($tag, $tagTitle);
			}
		}

		return $result;
	}

	/**
	 * Makes sure that a list of tags is an array.
	 *
	 * @param Array $tagList
	 */
	private function normalizeTagList(&$tagList)
	{

		if (!is_array($tagList)) {

			if (is_string($tagList)) {
				$tagList = explode($this->tagsSeparator, $tagList);

			} else {
				$tagList = Array($tagList);
			}
		}
	}

	/**
	 * Makes sure that tag object will be instance of tag model.
	 *
	 * @param mixed $tag Initial value of tag
	 * @param string $tagTitle Tag title
	 * @return ActiveRecord Tag object
	 *
	 * @see CActiveRecord
	 */
	protected function prepareTagObject(
		$tag,
		$tagTitle
	)
	{

		/* @var $tagModel ActiveRecord */
		$tagModel = $this->blankTagModel;
		$tagModelClass = get_class($tagModel);

		if (isset($this->tagsList[$tagTitle])) {
			$result = $this->tagsList[$tagTitle];

		} else {

			if (is_object($tag) && $tag instanceof $tagModelClass) {
				$result = $tag;

			} else {
				$result = $tagModel::find([$this->tagTableTitle => $tagTitle]);

				if ($result === null) {
					$result = new $tagModelClass();
					$result->{$this->tagTableTitle} = $tagTitle;
				}
			}
		}

		return $result;
	}


	/**
	 * Prepares string tag title.
	 *
	 * @param mixed $tag initial tag value
	 * @return string Tag title
	 *
	 * @throws Exception
	 */
	protected function prepareTagTitle($tag)
	{

		/* @var $tagModel ActiveRecord */
		$tagModel = $this->blankTagModel;
		$tagModelClass = get_class($tagModel);

		if ($tag instanceof $tagModelClass) {
			$tagTitle = $tag->getAttribute($this->tagTableTitle);

		} elseif (is_object($tag) && !method_exists($tag, '__toString')) {
			throw new Exception(
				sprintf(
					'It is unable to typecast to String object of class %s',
					get_class($tag)
				)
			);

		} else {
			$tagTitle = (string)$tag;
		}

		$result = trim(strip_tags($tagTitle));

		return $result;
	}

	protected function clearAttachedTags()
	{
		$relation = $this->getRelation();
		$pivot = $relation->via->from[0];

		if ($this->frequency !== null) {

			/** @var ActiveRecord $class */
			$class = $relation->modelClass;
			$query = new Query();
			$pks = $query
				->select(current($relation->link))
				->from($pivot)
				->where([key($relation->via->link) => $this->owner->getPrimaryKey()])
				->column($this->owner->getDb());

			if (!empty($pks)) {
				$class::updateAllCounters([$this->frequency => -1], ['in', $class::primaryKey(), $pks]);
			}
		}

		$this->owner->getDb()
					->createCommand()
					->delete($pivot, [key($relation->via->link) => $this->owner->getPrimaryKey()])
					->execute();
	}

}