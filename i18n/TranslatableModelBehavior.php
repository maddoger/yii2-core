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
     * @var array translation is active if at least one of this attributes is set.
     */
    public $requiredAttributes;

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
        return $translation && $this->isTranslationActive($translation);
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
     * Returns array of available languages.
     * Populated models will be used if its set, otherwise query will be used.
     * @return array
     */
    public function getAvailableLanguages()
    {
        if (!empty($this->owner->{$this->translationsAttribute})) {
            return array_keys($this->owner->{$this->translationsAttribute});
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
        $valid = !empty($this->owner->{$this->translationsAttribute});
        $activeTranslations = 0;
        foreach ($this->owner->{$this->translationsAttribute} as $model) {
            if ($this->isTranslationActive($model)) {
                $activeTranslations++;
                $valid = $valid && $model->validate();
            }
        }
        return $activeTranslations>0 && $valid;
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
        $class = $this->translationClass;
        if (!$class) {
            throw new InvalidParamException('Translation class not set.');
        }
        $translation = new $class;
        $translation->{$this->languageAttribute} = $language;

        return $translation;
    }
}