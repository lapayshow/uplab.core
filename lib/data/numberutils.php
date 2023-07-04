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
 * Class NumberUtils
 *
 * @package Uplab\Core
 */
class NumberUtils
{

	public static function normalizeDecimal($val)
	{
		$input = str_replace(' ', '', $val);
		$number = str_replace(',', '.', $input);
		if (strpos($number, '.')) {
			$groups = explode('.', str_replace(',', '.', $number));
			$lastGroup = array_pop($groups);
			$number = implode('', $groups) . '.' . $lastGroup;
		}

		return $number;
	}

}