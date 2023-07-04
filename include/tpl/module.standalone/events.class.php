<?

namespace __MODULE_NAMESPACE__;


use CMain;
use CUser;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();


/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 */
class Events
{
	public static function bindEvents()
	{
		// $event = EventManager::getInstance();
		// $event->addEventHandler("main", "OnProlog", [self::class, "someCallbackName"]);
		// $event->addEventHandler("main", "OnEpilog", [self::class, "someCallbackName"]);
	}
}
