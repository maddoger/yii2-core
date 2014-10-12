<?php

/**
 * @copyright Copyright (c) 2014 Vitaliy Syrchikov
 * @link http://syrchikov.name
 */

namespace maddoger\core\datetime;

use yii\base\InvalidParamException;
use yii\helpers\FormatConverter;
use yii\i18n\Formatter;


/**
 * Class DatePickerConfig
 */
class JuiDatePickerConfig
{
    /**
     * @param DateTimeAttribute $attribute
     * @param string $datePickerClass
     * @return array
     */
    public static function get($attribute, $datePickerClass = 'yii\jui\DatePicker')
    {
        $format = $attribute->localFormat;
        switch ($datePickerClass) {
            case 'yii\jui\DatePicker':
                return [
                    'language' => \Yii::$app->language,
                    'clientOptions' => [
                        'dateFormat' => FormatConverter::convertDateIcuToJui($format[1], $format[0]),
                    ]
                ];
            default:
                return [];
        }
    }
}