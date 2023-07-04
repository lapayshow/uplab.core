<?

use Bitrix\Main\Localization\Loc;
use Uplab\Core\Events;
use Uplab\Core\Helper;
use Uplab\Core\Renderer;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();


Loc::loadMessages(__FILE__);


include_once __DIR__ . "/lib/.aliases.php";


// глобальные функции
include __DIR__ . "/include/functions_COMMON.php";
if (Helper::isDevMode()) {
	include __DIR__ . "/include/functions.php";
} else {
	include __DIR__ . "/include/functions_PRODUCTION.php";
}


Events::bindEvents();