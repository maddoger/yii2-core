<?php

namespace maddoger\core\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%core_system_message}}".
 *
 * @property integer $id
 * @property string $type
 * @property string $title
 * @property string $message
 * @property mixed $data
 * @property string $created_at
 * @property integer $created_by
 */
class SystemMessage extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%core_system_message}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'updatedAtAttribute' => false,
            ],
            [
                'class' => BlameableBehavior::className(),
                'updatedByAttribute' => false,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['created_at'], 'safe'],
            [['created_by'], 'integer'],
            [['title', 'message'], 'string', 'max' => 255],
            [['type'], 'string', 'max' => 10],
            ['data', 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function afterFind()
    {
        if ($this->data) {
            try {
                $this->data = unserialize($this->data);
            } catch (\Exception $e) {
                $this->data = null;
            }
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
            try {
                $this->data = serialize($this->data);
            } catch (\Exception $e) {
                $this->data = null;
            }
        } else {
            $this->data = null;
        }
        return parent::beforeSave($insert);
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if ($this->data) {
            try {
                $this->data = unserialize($this->data);
            } catch (\Exception $e) {
                $this->data = null;
            }
        } else {
            $this->data = null;
        }
    }

    /**
     * Send system message
     * @param string $title
     * @param string $message
     * @param string $type
     * @param mixed $data
     * @return bool
     */
    public static function send($title, $message, $type = null, $data = null)
    {
        //Add only 1 hour
        if (SystemMessage::find()->
            where([
                    'and',
                    ['title' => $title],
                    ['>', 'created_at', strtotime('-1 hour')]
                ])->count() > 0
        ) {
            return true;
        }
        $message = new SystemMessage([
            'title' => trim($title),
            'message' => trim($message),
            'data' => $data,
            'type' => $type,
        ]);
        return $message->save();
    }

    /**
     * @param int $startTime default value is -1 week
     * @return \yii\db\ActiveQuery
     */
    public static function findLastMessages($startTime = null)
    {
        if ($startTime === null) {
            $startTime = strtotime('-1 week');
        }
        return static::find()->where(['>', 'created_at', $startTime])->orderBy(['created_at' => SORT_DESC]);
    }
}
