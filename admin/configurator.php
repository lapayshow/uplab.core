<?
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;


/** @noinspection PhpIncludeInspection */
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");


Loc::loadMessages(__FILE__);


/** @global $APPLICATION \CMain */
global $APPLICATION;


CJSCore::Init("jquery3");
Loader::includeModule("uplab.core");


/** @noinspection PhpIncludeInspection */
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");


$APPLICATION->SetTitle("Интеграция 2.0: Конфигуртор");
?>


<? $APPLICATION->IncludeComponent("uplab.core:admin.configurator.page", "", array(
	"CACHE_TYPE" => "N",
)); ?>


<?
/** @noinspection PhpIncludeInspection */
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
