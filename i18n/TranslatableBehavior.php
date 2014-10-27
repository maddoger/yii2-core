<?php
/**
 * @copyright Copyright (c) 2014 Vitaliy Syrchikov
 * @link http://syrchikov.name
 */

namespace maddoger\core\i18n;

use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;

/**
 * TranslatableBehavior
 *
 * @author Antonio Ramirez <amigo.cobos@gmail.com>
 * @author Vitaliy Syrchikov <maddoger@gmail.com>
 * @link http://www.ramirezcobos.com/
 * @link http://www.2amigos.us/
 * @link http://syrchikov.name
 * @package maddoger/yii2-core
 */
class TranslatableBehavior extends Behavior
{
    /**
     * @var \yii\db\ActiveRecord
     */
    public $owner;

    /**
     * @var string the name of the translations relation
     */
    public $relation = 'translations';

    /**
     * @var string the language field used in the related table. Determines the language to query | save.
     */
    public $languageAttribute = 'language';

    /**
     * @var string the default language field from main model
     * Example: 'default_language'
     */
    public $defaultLanguageAttribute;

    /**
     * @var array the list of attributes to translate. You can add validation rules on the owner.
     */
    public $translationAttributes = [];

    /**
     * @var ActiveRecord[] the models holding the translations.
     */
    private $_models = [];

    /**
     * @var string the language selected.
     */
    private $_language;


    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterInsert',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate',
        ];
    }

    /**
     * Make [[$translationAttributes]] writable
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        if (in_array($name, $this->translationAttributes)) {
            $this->getTranslation()->$name = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * Make [[$translationAttributes]] readable
     * @inheritdoc
     */
    public function __get($name)
    {
        if (!in_array($name, $this->translationAttributes) && !isset($this->_models[$name])) {
            return parent::__get($name);
        }

        if (isset($this->_models[$name])) {
            return $this->_models[$name];
        }

        $model = $this->getTranslation();
        return $model->$name;
    }

    /**
     * Expose [[$translationAttributes]] writable
     * @inheritdoc
     */
    public function canSetProperty($name, $checkVars = true)
    {
        return in_array($name, $this->translationAttributes) ? true : parent::canSetProperty($name, $checkVars);
    }

    /**
     * Expose [[$translationAttributes]] readable
     * @inheritdoc
     */
    public function canGetProperty($name, $checkVars = true)
    {
        return in_array($name, $this->translationAttributes) ? true : parent::canGetProperty($name, $checkVars);
    }

    /**
     * @param \yii\base\Event $event
     */
    public function afterFind($event)
    {
        $this->populateTranslations();
        $this->getTranslation($this->getLanguage());
    }

    /**
     * @param \yii\base\Event $event
     */
    public function afterInsert($event)
    {
        $this->saveTranslations();
    }

    /**
     * @param \yii\base\Event $event
     */
    public function afterUpdate($event)
    {
        $this->saveTranslations();
    }

    /**
     * Sets current model's language
     *
     * @param $value
     */
    public function setLanguage($value)
    {
        //$value = strtolower($value);
        if (!isset($this->_models[$value])) {
            $this->_models[$value] = $this->loadTranslation($value);
        }
        $this->_language = $value;
    }

    /**
     * Returns current models' language. If null, will return app's configured language.
     * @return string
     */
    public function getLanguage()
    {
        if ($this->_language === null) {
            if ($this->defaultLanguageAttribute) {
                $this->_language = $this->owner->{$this->defaultLanguageAttribute};
            }
            //var_dump($this->owner->{$this->defaultLanguageAttribute}, $this->_language);
            if (!$this->_language) {
                $this->_language = Yii::$app->language;
            }
        }
        return $this->_language;
    }

    /**
     * @param string $attribute
     * @return mixed
     */
    public function getTranslationAttribute($attribute)
    {
        return $this->{$attribute};
    }

    /**
     * @param string $attribute
     * @param mixed $value
     */
    public function setTranslationAttribute($attribute, $value)
    {
        $this->{$attribute} = $value;
    }

    /**
     * Saves current translation model
     * @return bool
     */
    public function saveTranslation()
    {
        $model = $this->getTranslation();
        $dirty = $model->getDirtyAttributes();
        if (empty($dirty)) {
            return true; // we do not need to save anything
        }
        /** @var \yii\db\ActiveQuery $relation */
        $relation = $this->owner->getRelation($this->relation);
        $model->{key($relation->link)} = $this->owner->getPrimaryKey();
        return $model->save();
    }

    /**
     * Saves all translations models
     * @return bool
     */
    public function saveTranslations()
    {
        $res = true;
        foreach ($this->_models as $model) {
            $dirty = $model->getDirtyAttributes();
            if (empty($dirty)) {
                continue;
            }
            /** @var \yii\db\ActiveQuery $relation */
            $relation = $this->owner->getRelation($this->relation);
            $model->{key($relation->link)} = $this->owner->getPrimaryKey();
            if (!$model->save())
            {
                $res = false;
            }
        }
        return $res;
    }

    /**
     * Model has translation to the language
     *
     * @param string|null $language the language to return. If null, current sys language
     *
     * @return bool
     */
    public function hasTranslation($language = null)
    {
        $translation = $this->getTranslation($language);
        return $translation && !$translation->isNewRecord;
    }

    /**
     * Returns a related translation model
     *
     * @param string|null $language the language to return. If null, current sys language
     *
     * @return ActiveRecord
     */
    public function getTranslation($language = null)
    {
        if ($language === null) {
            $language = $this->getLanguage();
        }

        if (!isset($this->_models[$language])) {
            $this->_models[$language] = $this->loadTranslation($language);
        }

        return $this->_models[$language];
    }

    /**
     * Loads all specified languages. For example:
     *
     * ```
     * $model->loadTranslations("en-US");
     *
     * $model->loadTranslations(["en-US", "es-ES"]);
     *
     * ```
     *
     * @param string|array $languages
     */
    public function loadTranslations($languages)
    {
        $languages = (array)$languages;

        foreach ($languages as $language) {
            $this->loadTranslation($language);
        }
    }

    /**
     * Loads a specific translation model
     *
     * @param string $language the language to return
     *
     * @return null|\yii\db\ActiveQuery|static
     */
    private function loadTranslation($language)
    {
        $translation = null;
        /** @var \yii\db\ActiveQuery $relation */
        $relation = $this->owner->getRelation($this->relation);
        /** @var ActiveRecord $class */
        $class = $relation->modelClass;
        if ($this->owner->getPrimarykey()) {
            $translation = $class::findOne(
                [$this->languageAttribute => $language, key($relation->link) => $this->owner->getPrimarykey()]
            );
        }
        if ($translation === null) {
            $translation = new $class;
            $translation->{key($relation->link)} = $this->owner->getPrimaryKey();
            $translation->{$this->languageAttribute} = $language;
        }

        return $translation;
    }

    /**
     * Populates already loaded translations
     */
    private function populateTranslations()
    {
        //translations
        $aRelated = $this->owner->getRelatedRecords();
        if (isset($aRelated[$this->relation]) && $aRelated[$this->relation] != null) {
            if (is_array($aRelated[$this->relation])) {
                foreach ($aRelated[$this->relation] as $model) {
                    $this->_models[$model->getAttribute($this->languageAttribute)] = $model;
                }
            } else {
                $model = $aRelated[$this->relation];
                $this->_models[$model->getAttribute($this->languageAttribute)] = $model;
            }
        }
    }
}