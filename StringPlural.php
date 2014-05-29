<?php

namespace maddoger\core;
/*
echo(StringPlural::Plural(1, array('окно', 'окна', 'окон')).'<br>');
echo(StringPlural::Plural(2, 'окно', 'окна', 'окон').'<br>');
echo(StringPlural::Plural(5, array('окно', 'окна', 'окон')).'<br>');

echo(StringPlural::PluralEn(1, array('window', 'windows')).'<br>');
echo(StringPlural::PluralEn(2, 'window', 'windows').'<br>');
*/

class StringPlural
{
    /** Определяет дефолтовый язык */
    const PLURAL_DEFAULT_LANG = 'ru';

    /** Получить правильную форму слова для дефолтового(!) языка в соответствии с числом определяющим количество.
     * Дефолтовый язык определяется константой PLURAL_DEFAULT_LANG (по дефолту "ru") в данном классе.
     *
     * @param integer $amount число определяющее количетсво "предметов" слова
     * @param mixed $_
     *
     * @example self::Plural(1, array('стул', 'стула', 'стульев')); //стул
     * @example self::Plural(2, 'стул', 'стула', 'стульев'); //стула
     * @example self::Plural(5, 'стул', 'стула', 'стульев'); //стульев
     *
     * @return string
     */
    static function Plural($amount, $_)
    {
        $argv = func_get_args();
        $arr = array();

        if (is_array($_)) {
            $arr = $_;
        } else {
            for ($i = 1, $x = count($argv); $i < $x; $i++) $arr[] = $argv[$i];
        }

        return self::PluralLang(self::PLURAL_DEFAULT_LANG, $amount, $arr);
    }

    /** Получить правильную форму слова для английского языка в соответствии с числом определяющим количество
     *
     * @param integer $amount число определяющее количетсво "предметов" слова
     * @param mixed $_
     *
     * @example self::PluralEn(1, array('window', 'windows')); //window
     * @example self::PluralEn(2, 'window', 'windows'); //windows
     *
     * @return string
     */
    static function PluralEn($amount, $_)
    {
        $argv = func_get_args();
        $arr = array();

        if (is_array($_)) {
            $arr = $_;
        } else {
            for ($i = 1, $x = count($argv); $i < $x; $i++) $arr[] = $argv[$i];
        }

        return self::PluralLang('en', $amount, $arr);
    }

    /** Получить правильную форму слова для нужного языка в соответствии с числом определяющим количество
     *
     * @param string $lang индетификатор языка для которого нужно возвращать форму слова
     * @param integer $amount число определяющее количетсво "предметов" слова
     * @param mixed $_
     *
     * @example self::PluralLang('en', 1, array('window', 'windows')); //window
     * @example self::PluralLang('en', 2, 'window', 'windows'); //windows
     *
     * @return string
     */
    static function PluralLang($lang, $amount, $_)
    {
        $argv = func_get_args();

        if (count($argv) < 3) {
            trigger_error(__METHOD__ . ': missing required arguments', E_USER_WARNING);
            return null;
        }

        $amount = (int)$amount;

        $form = self::PluralLangGetForm($lang, $amount);
        if (is_array($_)) {
            if (array_key_exists($form, $_)) {
                return $_[$form];
            } elseif (count($_ > 0)) {
                return $_[0];
            } else {
                trigger_error(__METHOD__ . ': missing required arguments', E_USER_WARNING);
                return null;
            }
        } else {
            if (array_key_exists(($form + 2), $argv)) {
                return $argv[$form + 2];
            } else {
                return $argv[2];
            }
        }
    }

    /** Получить количество словоформ множественного числа для данного языка
     *
     * @param string $lang индетификатор языка
     * @return integer количество словоформ
     */
    static function PluralLangGetCount($lang)
    {
        switch ($lang) {
            case 'ach':
            case 'af':
            case 'ak':
            case 'am':
            case 'an':
            case 'arn':
            case 'ast':
            case 'az':
            case 'bg':
            case 'bn':
            case 'br':
            case 'ca':
            case 'da':
            case 'de':
            case 'el':
            case 'en':
            case 'eo':
            case 'es':
            case 'et':
            case 'eu':
            case 'fi':
            case 'fil':
            case 'fo':
            case 'fr':
            case 'fur':
            case 'fy':
            case 'gl':
            case 'gu':
            case 'ha':
            case 'he':
            case 'hi':
            case 'hu':
            case 'ia':
            case 'is':
            case 'it':
            case 'jv':
            case 'kn':
            case 'ku':
            case 'lb':
            case 'ln':
            case 'mai':
            case 'mfe':
            case 'mg':
            case 'mi':
            case 'mk':
            case 'ml':
            case 'mn':
            case 'mr':
            case 'nah':
            case 'nap':
            case 'nb':
            case 'ne':
            case 'nl':
            case 'se':
            case 'nn':
            case 'no':
            case 'nso':
            case 'oc':
            case 'or':
            case 'ps':
            case 'pa':
            case 'pap':
            case 'pms':
            case 'pt':
            case 'rm':
            case 'sco':
            case 'si':
            case 'so':
            case 'son':
            case 'sq':
            case 'sw':
            case 'sv':
            case 'ta':
            case 'te':
            case 'ti':
            case 'tk':
            case 'tr':
            case 'ur':
            case 'wa':
            case 'yo':
                return 2;
            case 'ar':
                return 6;
            case 'ay':
            case 'bo':
            case 'cgg':
            case 'dz':
            case 'fa':
            case 'hy':
            case 'id':
            case 'ja':
            case 'jbo':
            case 'ka':
            case 'kk':
            case 'km':
            case 'ko':
            case 'ky':
            case 'lo':
            case 'ms':
            case 'sah':
            case 'su':
            case 'tg':
            case 'th':
            case 'tt':
            case 'ug':
            case 'uz':
            case 'vi':
            case 'wo':
            case 'zh':
                return 1;
            case 'be':
            case 'bs':
            case 'cs':
            case 'hr':
            case 'lt':
            case 'lv':
            case 'mnk':
            case 'pl':
            case 'ro':
            case 'ru':
            case 'sk':
            case 'sr':
            case 'uk':
                return 3;
            case 'cy':
            case 'gd':
            case 'kw':
            case 'mt':
            case 'sl':
                return 4;
            case 'ga':
                return 5;
            default:
                return 1;
        }
    }

    /** Получить индетификатор формы множественного числа
     *
     * @param string $lang индетификатор языка
     * @param integer $n число по которму определяется форма слова
     * @return integer индетификатор формы слова
     */
    private static function PluralLangGetForm($lang, $n)
    {
        switch ($lang) {
            case 'ach':
            case 'ak':
            case 'am':
            case 'arn':
            case 'br':
            case 'fil':
            case 'fr':
            case 'ln':
            case 'mfe':
            case 'mg':
            case 'mi':
            case 'oc':
            case 'ti':
            case 'tr':
            case 'wa':
                return (int)($n > 1);
            case 'af':
            case 'an':
            case 'ast':
            case 'az':
            case 'bg':
            case 'bn':
            case 'ca':
            case 'da':
            case 'de':
            case 'el':
            case 'en':
            case 'eo':
            case 'es':
            case 'et':
            case 'eu':
            case 'fi':
            case 'fo':
            case 'fur':
            case 'fy':
            case 'gl':
            case 'gu':
            case 'ha':
            case 'he':
            case 'hi':
            case 'hu':
            case 'ia':
            case 'it':
            case 'kn':
            case 'ku':
            case 'lb':
            case 'mai':
            case 'ml':
            case 'mn':
            case 'mr':
            case 'nah':
            case 'nap':
            case 'nb':
            case 'ne':
            case 'nl':
            case 'se':
            case 'nn':
            case 'no':
            case 'nso':
            case 'or':
            case 'ps':
            case 'pa':
            case 'pap':
            case 'pms':
            case 'pt':
            case 'rm':
            case 'sco':
            case 'si':
            case 'so':
            case 'son':
            case 'sq':
            case 'sw':
            case 'sv':
            case 'ta':
            case 'te':
            case 'tk':
            case 'ur':
            case 'yo':
                return (int)($n != 1);
            case 'jv':
                return (int)($n != 0);
            case 'ay':
            case 'bo':
            case 'cgg':
            case 'dz':
            case 'fa':
            case 'hy':
            case 'id':
            case 'ja':
            case 'jbo':
            case 'ka':
            case 'kk':
            case 'km':
            case 'ko':
            case 'ky':
            case 'lo':
            case 'ms':
            case 'sah':
            case 'su':
            case 'tg':
            case 'th':
            case 'tt':
            case 'ug':
            case 'uz':
            case 'vi':
            case 'wo':
            case 'zh':
                return (int)(0);

            case 'be':
            case 'bs':
            case 'hr':
            case 'ru':
            case 'sr':
            case 'uk':
                return (int)($n % 10 == 1 && $n % 100 != 11 ? 0 : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? 1 : 2));
            case 'lt':
                return (int)($n % 10 == 1 && $n % 100 != 11 ? 0 : ($n % 10 >= 2 && ($n % 100 < 10 or $n % 100 >= 20) ? 1 : 2));
            case 'lv':
                return (int)($n % 10 == 1 && $n % 100 != 11 ? 0 : ($n != 0 ? 1 : 2));
            case 'mt':
                return (int)($n == 1 ? 0 : ($n == 0 || ($n % 100 > 1 && $n % 100 < 11) ? 1 : (($n % 100 > 10 && $n % 100 < 20) ? 2 : 3)));
            case 'pl':
                return (int)($n == 1 ? 0 : ($n % 10 >= 2 && $n % 10 <= 4 && ($n % 100 < 10 || $n % 100 >= 20) ? 1 : 2));
            case 'ar':
                return (int)($n == 0 ? 0 : ($n == 1 ? 1 : ($n == 2 ? 2 : ($n % 100 >= 3 && $n % 100 <= 10 ? 3 : ($n % 100 >= 11 ? 4 : 5)))));
            case 'gd':
                return (int)(($n == 1 || $n == 11) ? 0 : (($n == 2 || $n == 12) ? 1 : (($n > 2 && $n < 20) ? 2 : 3)));
            case 'is':
                return (int)($n % 10 != 1 || $n % 100 == 11);

            case 'cs':
            case 'sk':
                return (int)(($n == 1) ? 0 : (($n >= 2 && $n <= 4) ? 1 : 2));
            case 'cy':
                return (int)(($n == 1) ? 0 : (($n == 2) ? 1 : (($n != 8 && $n != 11) ? 2 : 3)));
            case 'ga':
                return (int)(($n == 1) ? 0 : (($n == 2) ? 1 : ($n < 7 ? 2 : ($n < 11 ? 3 : 4))));
            case 'kw':
                return (int)(($n == 1) ? 0 : (($n == 2) ? 1 : (($n == 3) ? 2 : 3)));

            case 'mk':
                return (int)(($n == 1 || $n % 10 == 1) ? 0 : 1);
            case 'mnk':
                return (int)($n == 0 ? 0 : ($n == 1 ? 1 : 2));

            case 'ro':
                return (int)($n == 1 ? 0 : (($n == 0 || ($n % 100 > 0 && $n % 100 < 20)) ? 1 : 2));
            case 'sl':
                return (int)($n % 100 == 1 ? 1 : ($n % 100 == 2 ? 2 : ($n % 100 == 3 || $n % 100 == 4 ? 3 : 0)));
            default:
                return 0;
        }
    }
}

?>
