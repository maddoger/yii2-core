<?php
/**
 * @copyright Copyright (c) 2014 Vitaliy Syrchikov
 * @link http://syrchikov.name
 */

namespace maddoger\core\i18n;

use Yii;
use yii\base\Behavior;
use yii\base\InvalidParamException;
use yii\db\ActiveRecord;

/**
 * TranslatableBehavior
 *
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
     * @var array translation is active if at least one of this attributes is set.
     */
    public $requiredAttributes;

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
            //Try find best language from available
            $availableLanguages = $this->getAvailableLanguages();
            if ($availableLanguages) {
                if (in_array(Yii::$app->language, $availableLanguages)) {
                    $this->_language = Yii::$app->language;
                } elseif (Yii::$app->has('request')) {
                    $this->_language = Yii::$app->request->getPreferredLanguage($availableLanguages);
                }
            }
            //Use application language, because we don`t have any translation yet
            if (!$this->_language) {
                $this->_language = Yii::$app->language;
            }
        }
        return $this->_language;
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


        //Is translation valid?
        if (!$this->isTranslationActive($model)) {
            $this->deleteTranslation($model);
            return false;
        }

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

            //Is translation valid?
            if (!$this->isTranslationActive($model)) {
                $this->deleteTranslation($model);
                continue;
            }

            if (!$model->save()) {
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
        return $translation && $this->isTranslationActive($translation);
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
        if (!$language) {
            $language = $this->getLanguage();
        }
        if (!isset($this->_models[$language])) {
            $this->_models[$language] = $this->loadTranslation($language);
        }
        return $this->_models[$language];
    }

    /**
     * Returns array of available languages.
     * Populated models will be used if its set, otherwise query will be used.
     * @return array
     */
    public function getAvailableLanguages()
    {
        if (!empty($this->_models)) {
            return array_keys($this->_models);
        } else {
            /** @var \yii\db\ActiveQuery $relation */
            $relation = $this->owner->getRelation($this->relation);
            /** @var ActiveRecord $class */
            $class = $relation->modelClass;
            if ($this->owner->getPrimarykey()) {
                return $class::find()->select([$this->languageAttribute])->where(
                    [key($relation->link) => $this->owner->getPrimarykey()]
                )->orderBy([$this->languageAttribute => SORT_ASC])->column($this->owner->getDb());
            }
        }
        return null;
    }

    /**
     * Load owner model and translation models from data.
     * @param $data
     * @param null $languages array of languages for loading
     * @param null $formName
     * @param null $translationFormName
     * @return bool
     */
    public function loadWithTranslations($data, $languages, $formName = null, $translationFormName = null)
    {
        if ($this->owner->load($data, $formName)) {
            if (!$languages) {
                throw new InvalidParamException('Languages must be set.');
            }
            foreach ($languages as $language) {
                $modelI18n = static::getTranslation($language);
                $modelI18n->load($data, $translationFormName);
            }
            return true;
        }
        return false;
    }

    /**
     * At least one translation must be valid.
     * Each translation must be active (with one of required attributes) and validates itself.
     * @return bool
     */
    public function validateTranslations()
    {
        $valid = !empty($this->_models);
        $activeTranslations = 0;
        foreach ($this->_models as $model) {
            if ($this->isTranslationActive($model)) {
                $activeTranslations++;
                $valid = $valid && $model->validate();
            }
        }
        return $activeTranslations>0 && $valid;
    }

    /**
     * @return \yii\db\ActiveRecord[]
     */
    public function getTranslationModels()
    {
        return $this->_models;
    }

    /**
     * Translation is active or just empty model?
     * @param $model
     * @return bool
     */
    private function isTranslationActive($model)
    {
        if (!$model) {
            return false;
        }
        if (is_array($this->requiredAttributes) && !empty($this->requiredAttributes)) {
            foreach ($this->requiredAttributes as $attribute) {
                if ($model->{$attribute} && !empty($model->{$attribute})) {
                    return true;
                }
            }
        } else {
            return true;
        }
        return false;
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

    /**
     * @param \yii\db\ActiveRecord $model
     * @return bool
     */
    private function deleteTranslation($model)
    {
        if (!$model->isNewRecord) {
            $model->delete();
            unset($this->_models[$model->getAttribute($this->languageAttribute)]);
        }
        return true;
    }
}