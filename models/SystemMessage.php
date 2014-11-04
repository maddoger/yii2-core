<?php

namespace maddoger\core\models;

use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;

/**
 * This is the model class for table "{{%core_system_message}}".
 *
 * @property integer $id
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
            ['data', 'safe'],
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
     * Send system message
     * @param $title
     * @param $message
     * @param $data
     * @return bool
     */
    public static function send($title, $message, $data)
    {
        $message = new SystemMessage([
            'title' => trim($title),
            'message' => trim($message),
            'data' => $data,
        ]);
        return $message->save();
    }
}
