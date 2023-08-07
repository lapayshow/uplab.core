<?php

namespace __namespace__\__Entity__;


use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use CMain;
use CUser;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 */


if (!class_exists("\__Entity__Table")) {
	if (Loader::includeModule("highloadblock")) {
		HighloadBlockTable::compileEntity("__Entity__");
	} else {
		throw new LoaderException("Module `highloadblock` not found");
	}
}


/**
 * Class __Entity__Table
 *
 * Класс-обертка над сущностью HL-блока
 * Позволяет работать с HL-блоком напрямую, минуя инициализации и компиляцию сущностей
 * Для удобства рекомендуется скачать файл аннотаций в свой проект
 *
 * @see     `annotations.phtml`
 * @package __namespace__\__Entity__
 */
class __Entity__Table extends \__Entity__Table
{

	public static function getEntity()
	{
		return static::$entity["\\__Entity__Table"];
	}

}