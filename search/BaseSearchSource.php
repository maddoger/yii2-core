<?php
/**
 * @copyright Copyright (c) 2014 Vitaliy Syrchikov
 * @link http://syrchikov.name
 */

namespace maddoger\core\search;

use Yii;
use yii\base\Component;
use yii\base\InvalidParamException;
use yii\helpers\Url;

/**
 * BaseSearchSource
 *
 * @author Vitaliy Syrchikov <maddoger@gmail.com>
 * @link http://syrchikov.name
 * @package maddoger/yii2-core
 */
abstract class BaseSearchSource extends Component
{
    /**
     * @var string|\Closure Label attribute or Closure returning label value
     */
    public $label = 'label';

    /**
     * @var string
     */
    public $labelPrefix = '';

    /**
     * @var array|string|\Closure Url route, or url attribute name (string) or Closure returning url value
     */
    public $url;

    /**
     * @var string[]
     */
    public $searchAttributes;

    /**
     * @var array
     */
    public $additionalAttributes;

    /**
     * @throws InvalidParamException
     */
    public function init()
    {
        parent::init();
        if (!$this->label) {
            throw new InvalidParamException('Label must be set.');
        }
        if (!$this->url) {
            throw new InvalidParamException('URL must be set.');
        }
        if (!$this->searchAttributes) {
            throw new InvalidParamException('Search attributes must be set.');
        }
    }

    public function getResult($query, $page = 0, $pageSize = 10)
    {
        $dataProvider = $this->getDataProvider($query);
        $dataProvider->getPagination()->pageSize = $pageSize;
        $dataProvider->getPagination()->page = $page;

        $res = [];

        $label = $this->label;
        $url = $this->url;

        foreach ($dataProvider->getModels() as $model) {

            $item = [];

            if (is_string($label)) {
                $item['label'] = $this->labelPrefix.$model[$label];
            } elseif ($label instanceof \Closure) {
                $item['label'] = $this->labelPrefix.$label($model);
            }

            if (is_string($url)) {
                $item['url'] = Url::to($model[$url]);
            } elseif (is_array($url)){
                $u = $url;
                foreach ($url as $key=>$value) {
                    if (isset($model[$key]) && empty($value)) {
                        $u[$key] = $model[$key];
                    }
                }
                $item['url'] = Url::to($u);
            } elseif ($url instanceof \Closure) {
                $item['url'] = $url($model);
            }

            if ($this->additionalAttributes ) {
                foreach ($this->additionalAttributes as $attribute) {
                    $item[$attribute] = $model[$attribute];
                }
            }
            $res[] = $item;
        }

        return $res;
    }

    /**
     * @param string $q Query string
     * @return \yii\data\DataProviderInterface
     */
    abstract public function getDataProvider($q);
}