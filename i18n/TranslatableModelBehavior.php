<?php
/**
 * @copyright Copyright (c) 2014 Vitaliy Syrchikov
 * @link http://syrchikov.name
 */

namespace maddoger\core\i18n;

use Yii;
use yii\base\Behavior;
use yii\base\InvalidParamException;
use yii\base\Model;

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
class TranslatableModelBehavior extends Behavior
{
    /**
     * @var \yii\base\Model
     */
    public $owner;

    /**
     * @var string the name of the translations relation
     */
    public $translationsAttribute = 'translations';

    /**
     * @var string class name for translation model
     */
    public $translationClass;

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
     * @var string the language selected.
     */
    private $_language;

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

        if (isset($this->owner->{$this->translationsAttribute}[$name])) {
            return $this->owner->{$this->translationsAttribute};
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
     * Sets current model's language
     *
     * @param $value
     */
    public function setLanguage($value)
    {
        //$value = strtolower($value);
        if (!isset($this->owner->{$this->translationsAttribute}[$value])) {
            $this->owner->{$this->translationsAttribute}[$value] = $this->loadTranslation($value);
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
     * Model has translation to the language
     *
     * @param string|null $language the language to return. If null, current sys language
     *
     * @return bool
     */
    public function hasTranslation($language = null)
    {
        $translation = $this->getTranslation($language);
        return $translation && $translation->validate();
    }

    /**
     * Returns a related translation model
     *
     * @param string|null $language the language to return. If null, current sys language
     *
     * @return Model
     */
    public function getTranslation($language = null)
    {
        if ($language === null) {
            $language = $this->getLanguage();
        }

        if (!isset($this->owner->{$this->translationsAttribute}[$language])) {
            $this->owner->{$this->translationsAttribute}[$language] = $this->loadTranslation($language);
        }

        return $this->owner->{$this->translationsAttribute}[$language];
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
        $class = $this->translationClass;
        if (!$class) {
            throw new InvalidParamException('Translation class not set.');
        }
        $translation = new $class;
        $translation->{$this->languageAttribute} = $language;

        return $translation;
    }
}