<?php

namespace maddoger\core\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%core_config}}".
 *
 * @property string $class
 * @property mixed $data
 * @property integer $created_at
 * @property integer $created_by
 * @property integer $updated_at
 * @property integer $updated_by
 */
class Config extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%core_config}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['class', 'data'], 'required'],
            [['created_at', 'created_by', 'updated_at', 'updated_by'], 'integer'],
            [['class'], 'string', 'max' => 255],
            ['data', 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
            BlameableBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        if ($this->data) {
            $this->data = @unserialize($this->data);
        } else {
            $this->data = null;
        }
        parent::afterFind();
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if ($this->data) {
            $this->data = @serialize($this->data);
        } else {
            $this->data = null;
        }
        return parent::beforeSave($insert);
    }

    /**
     * Get config by object class
     * @param string $class
     * @param int $cacheDuration seconds
     * @return mixed
     */
    public static function getConfig($class, $cacheDuration=60)
    {
        $model = Yii::$app->cache->get($class);
        if (!$model || $cacheDuration===false) {
            $model = static::findOne($class);
        }
        if ($model) {
            if ($cacheDuration !== false) {
                Yii::$app->cache->set($class, $model, $cacheDuration);
            }
            return $model->data;
        } else {
            return null;
        }
    }

    /**
     * Set config for class
     * @param string $class
     * @param mixed $data
     * @return bool
     */
    public static function setConfig($class, $data)
    {
        $model = static::findOne($class);
        if (!$model) {
            $model = new Config();
            $model->class = $class;
        }
        $model->data = $data;
        $res = $model->save();
        Yii::$app->cache->delete($class);
        return $res;
    }

    /**
     * Get config by object class
     * @param string $class
     */
    public static function deleteConfig($class)
    {
        $model = static::findOne($class);
        if ($model) {
            $model->delete();
        }
    }
}
