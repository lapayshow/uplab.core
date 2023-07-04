<?

namespace __MODULE_NAMESPACE__;


use CMain;
use CUser;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();


/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 */
class Helper
{
	const MODULE_ID = "__MODULE_ID__";

	public static function test()
	{
		die("It works! Module __MODULE_ID__ installed correctly.");
	}
}
