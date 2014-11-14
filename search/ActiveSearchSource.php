<?php
/**
 * @copyright Copyright (c) 2014 Vitaliy Syrchikov
 * @link http://syrchikov.name
 */

namespace maddoger\core\search;

use Yii;
use yii\base\InvalidParamException;
use yii\data\ActiveDataProvider;

/**
 * SearchSource
 *
 * @author Vitaliy Syrchikov <maddoger@gmail.com>
 * @link http://syrchikov.name
 * @package maddoger/yii2-core
 */
class ActiveSearchSource extends BaseSearchSource
{
    /**
     * @var string ActiveRecord model class
     */
    public $modelClass;

    /**
     * @var \yii\db\ActiveQuery model class
     */
    public $query;

    /**
     * @var string[]
     */
    public $searchAttributes;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (!$this->modelClass && !$this->query) {
            throw new InvalidParamException('Model class or query must be set.');
        }
    }

    /**
     * @param $q
     * @return \yii\data\DataProviderInterface
     */
    public function getDataProvider($q)
    {
        $words = array_filter(explode(' ', $q));

        if ($this->query) {
            $query = $this->query;
        } else {
            $modelClass = $this->modelClass;
            $query = $modelClass::find();
        }

        /* @var \yii\db\ActiveQuery $query */
        $query->asArray();

        foreach ($this->searchAttributes as $attribute) {

            $where = [];
            foreach ($words as $word) {
                $where = ['and', $where, ['like', $attribute, $word]];
            }
            if (!empty($where)) {
                $query->orWhere($where);
            }
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);
        return $dataProvider;
    }
}