<?php
/**
 * @copyright Copyright (c) 2014 Vitaliy Syrchikov
 * @link http://syrchikov.name
 */

namespace maddoger\core\search;

use Yii;
use yii\data\ArrayDataProvider;

/**
 * SearchSource
 *
 * @author Vitaliy Syrchikov <maddoger@gmail.com>
 * @link http://syrchikov.name
 * @package maddoger/yii2-core
 */
class ArraySearchSource extends BaseSearchSource
{
    /**
     * @var array
     */
    public $data;

    /**
     * @var string
     */
    public $label = 'label';

    /**
     * @var string
     */
    public $url = 'url';

    /**
     * @var array
     */
    public $searchAttributes = ['label'];

    /**
     * @param $q
     * @return \yii\data\DataProviderInterface
     */
    public function getDataProvider($q)
    {
        $res = [];
        $words = array_filter(explode(' ', $q));
        if ($this->data) {
            foreach ($this->data as $item) {
                if (!$this->searchAttributes) {
                    $this->searchAttributes = [];
                    foreach ($item as $key=>$value) {
                        if (is_string($value)) {
                            $this->searchAttributes[] = $key;
                        }
                    }
                }
                //All words must be found
                $found = false;

                foreach ($this->searchAttributes as $attribute) {
                    $attributeFound = true;
                    foreach ($words as $word) {
                        if (mb_stripos($item[$attribute], $word, null, 'utf8') === false) {
                            $attributeFound = false;
                            //break;
                        }
                    }
                    $found = $found || $attributeFound;
                }

                if ($found) {
                    $res[] = $item;
                }
            }
        }

        $dataProvider = new ArrayDataProvider([
            'allModels' => $res,
        ]);

        return $dataProvider;
    }
}