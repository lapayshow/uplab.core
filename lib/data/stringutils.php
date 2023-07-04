<?php
/**
 * Created by PhpStorm.
 * User: geffest
 * Date: 2019-03-02
 * Time: 04:52
 */

namespace Uplab\Core\Data;


use Bitrix\Main\Localization\Loc;
use CTextParser;
use CUtil;


/**
 * Class StringUtils
 *
 * @package Uplab\Core
 */
class StringUtils
{

	const SANITIZE_SOFT  = 1;
	const SANITIZE_CLEAR = 2;

	public static function isStringStartsWith($haystack, $needle)
	{
		$length = strlen($needle);

		return (substr($haystack, 0, $length) === $needle);
	}

	public static function isStringEndsWith($haystack, $needle)
	{
		$length = strlen($needle);

		return $length === 0 ||
			(substr($haystack, -$length) === $needle);
	}

	/**
	 * Преобразует CamelCase строку в under_score формат
	 * Отличительная особенность в том, что преобразует SomeTEXT в some_text
	 * Более простые методы делают так: some_t_e_x_t
	 *
	 * @param      $input
	 *
	 * @param bool $trim
	 *
	 * @return string
	 */
	public static function convertCamelCaseToUnderScore($input, $trim = true)
	{
		preg_match_all("~([A-Z][A-Z0-9]*(?=\$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)~", $input, $matches);
		$ret = $matches[0];
		foreach ($ret as &$match) {
			$match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
		}

		$ret = implode('_', $ret);

		if ($trim) $ret = trim($ret, '_');

		return $ret;
	}

	public static function convertUnderScoreToCamelCase($string, $toLower = true, $capitalizeFirstCharacter = false)
	{
		if ($toLower) {
			$string = mb_strtolower($string);
		}

		$str = str_replace('_', '', ucwords($string, '_'));

		if (!$capitalizeFirstCharacter) {
			$str = lcfirst($str);
		}

		return $str;
	}

	/**
	 * @param      $name
	 * @param bool $dash
	 * @param bool $lng
	 * @param bool $length
	 *
	 * @return null|string|string[]
	 */
	public static function translit($name, $dash = false, $lng = false, $length = false)
	{
		if ($dash === false) $dash = "_";
		if (empty($lng)) $lng = "ru";

		if (ini_get("mbstring.func_overload") == 2) {
			$arParams = array(
				"replace_space" => $dash,
				"replace_other" => $dash,
			);

			if (intval($length)) {
				$name = trim(substr($name, 0, $length));
			}

			$name = Cutil::translit($name, $lng, $arParams);
		} else {
			$name = strtr($name, array(
				"а" => "a",
				"б" => "b",
				"в" => "v",
				"г" => "g",
				"д" => "d",
				"е" => "e",
				"ё" => "e",
				"ж" => "zh",
				"з" => "z",
				"и" => "i",
				"й" => "y",
				"к" => "k",
				"л" => "l",
				"м" => "m",
				"н" => "n",
				"о" => "o",
				"п" => "p",
				"р" => "r",
				"с" => "s",
				"т" => "t",
				"у" => "u",
				"ф" => "f",
				"х" => "h",
				"ц" => "c",
				"ч" => "ch",
				"ш" => "sh",
				"щ" => "sch",
				"ь" => "",
				"ы" => "y",
				"ъ" => "",
				"э" => "e",
				"ю" => "yu",
				"я" => "ya",

				"А" => "A",
				"Б" => "B",
				"В" => "V",
				"Г" => "G",
				"Д" => "D",
				"Е" => "E",
				"Ё" => "E",
				"Ж" => "Zh",
				"З" => "Z",
				"И" => "I",
				"Й" => "Y",
				"К" => "K",
				"Л" => "L",
				"М" => "M",
				"Н" => "N",
				"О" => "O",
				"П" => "P",
				"Р" => "R",
				"С" => "S",
				"Т" => "T",
				"У" => "U",
				"Ф" => "F",
				"Х" => "H",
				"Ц" => "C",
				"Ч" => "Ch",
				"Ш" => "Sh",
				"Щ" => "Sch",
				"Ь" => "",
				"Ы" => "Y",
				"Ъ" => "",
				"Э" => "E",
				"Ю" => "Yu",
				"Я" => "Ya",
			));
			$name = strtolower($name);
			$name = preg_replace("~[^a-z0-9{$dash}]+~u", $dash, $name);
		}
		$name = trim($name, $dash);

		return $name;
	}

	public static function clearPhone($phone)
	{
		return preg_replace('~[^\d\+]+~', '', $phone);
	}

	/**
	 * @param $number
	 * @param $msg
	 *
	 * @return string
	 *
	 * @deprecated
	 * @see StringUtils::getPluralForm()
	 */
	public static function getMultipleWord($number, $msg)
	{
		return self::getPluralForm($number, $msg);
	}

	/**
	 * Получение склонения слова после числа
	 *
	 * Идея в том, что мы заводим в языковых сообщениях три варианта фразы
	 * с суффиксами XXX_1, XXX_2, XXX_3
	 *
	 * Пример языковых сообщений:
	 * $MESS["EXAMPLE_1"] = "итого: #NUM# пример";   // кратно 1
	 * $MESS["EXAMPLE_2"] = "итого: #NUM# примера";  // кратно 2
	 * $MESS["EXAMPLE_3"] = "итого: #NUM# примеров"; // кратно 5
	 *
	 * Далее мы вызываем метод
	 * echo Helper::getMultipleWord("EXAMPLE", 1);
	 *
	 * @param int $number      число
	 * @param int $langMsgCode код языковой константы
	 *
	 * @return  string
	 */
	public static function getPluralForm($number, $langMsgCode)
	{
		$cmpNumber = $number % 100;
		$id = 1;
		$num = ($cmpNumber > 20) ? $cmpNumber % 10 : $cmpNumber;

		if ($num >= 5 || $num == 0) {
			$id = 3;
		} elseif ($num >= 2) {
			$id = 2;
		} elseif ($num == 1) {
			$id = 1;
		}

		$result = Loc::getMessage("{$langMsgCode}_{$id}", ["#NUM#" => $number]);

		if (empty($result)) {
			$result = Loc::getMessage($langMsgCode, ['#NUM#' => $number]);
		}

		return $result;
	}

	public static function ucFirst($string, $enc = "UTF-8")
	{
		return
			mb_strtoupper(mb_substr($string, 0, 1, $enc), $enc) .
			mb_substr($string, 1, mb_strlen($string, $enc), $enc);
	}

	/**
	 * Метод скопирован из исходников Bitrix:
	 * http://bxapi.ru/src/?module_id=main&name=CTextParser%3A%3Ahtml_cut
	 * функция substr заменена на mb_substr
	 *
	 * @param      $html
	 * @param bool $size
	 *
	 * @return string
	 */
	public static function cutString($html, $size = false)
	{
		$parser = new CTextParser;
		$symbols = strip_tags($html);
		$symbols_len = strlen($symbols);

		if ($symbols_len < strlen($html)) {
			$strip_text = $parser->strip_words($html, $size);

			if ($symbols_len > $size) {
				$strip_text = $strip_text . "...";
			}

			$final_text = $parser->closetags($strip_text);
		} elseif ($symbols_len > $size) {
			$final_text = mb_substr($html, 0, $size) . "...";
		} else {
			$final_text = $html;
		}

		return $final_text;
	}

	public static function findPicturesInText($text)
	{
		$pattern = '~(src=|url\()[\'\"]?([^\s\'\"]+\.(jpe?g|png|gif))~';
		preg_match_all($pattern, $text, $matches);

		return $matches;
	}

	public static function getRandString($length = 6, $alphabetOnly = true, $passChars = null)
	{
		if ($alphabetOnly && !isset($passChars)) {
			$passChars = "abcdefghijklnmopqrstuvwxyz";
		}

		return randString($length, $passChars);
	}

	/**
	 * Вычисляет хеш для строки и при необходимости сокращает его
	 *
	 * @param      $string
	 * @param bool $shortenLength
	 *
	 * @return bool|string
	 */
	public static function md5($string, $shortenLength = false)
	{
		$result = md5($string);

		if ($shortenLength) {
			$result = mb_substr($string, 0, $shortenLength);
		}

		return $result;
	}

	public static function sanitizeString($string, $mode = self::SANITIZE_CLEAR)
	{
		$string = html_entity_decode(
			htmlspecialchars_decode($string)
		);

		switch ($mode) {
			/** @noinspection PhpMissingBreakStatementInspection */
			case self::SANITIZE_CLEAR:
				$string = trim(strip_tags($string));
			// break;

			case self::SANITIZE_SOFT:
				$string = str_replace(["<", ">", "'", "\""], "", $string);
				break;
		}

		return $string;
	}

}