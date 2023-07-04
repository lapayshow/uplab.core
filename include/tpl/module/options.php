<?
use Bitrix\Main\Loader;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 */


$module_id = "__MODULE_ID__";
Loader::includeModule("uplab.core");
Loader::includeModule($module_id);


$options = new __MODULE_NAMESPACE__\Module\Options(__FILE__, [
	[
		"DIV"     => "common",
		"TAB"     => "Настройки",
		"OPTIONS" => [
			// "Название подраздела",
			// [
			// 	"option_code",
			// 	"Название опции",
			// 	"",
			// 	["text", 50],
			// ],
			"Сохраните настройки модуля для обновления стилей, скриптов, компонентов модуля",
		],
	],
]);


$options->drawOptionsForm();