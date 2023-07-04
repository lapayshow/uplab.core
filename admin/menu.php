<?
use Bitrix\Main\Loader;


global $APPLICATION, $USER;


if (!Loader::includeModule("uplab.core")) return;


CJSCore::Init(["jquery", "ajax", "popup"]);


if ($USER->IsAdmin()) {
	return [
		[
			"parent_menu" => "global_menu_services",
			"section"     => "Uplab",
			"sort"        => 50,
			"text"        => "Быстрая очистка Bitrix-кеша",
			"icon"        => "util_menu_icon",
			"page_icon"   => "util_page_icon",
			"items_id"    => "uplab_core",
			"url"         => "javascript:sendBxClearCacheQuery();",
		],
		[
			"parent_menu" => "global_menu_settings",
			"section"     => "Uplab",
			"sort"        => 5,
			"text"        => "[Uplab.CORE] Иинтеграция 2.0",
			"icon"        => "util_menu_icon",
			"page_icon"   => "util_page_icon",
			"items_id"    => "uplab_core",
			"url"         => "",
			"items"       => [
				[
					"text"     => "Настройки",
					"icon"     => "rating_menu_icon",
					"url"      => "settings.php?mid=uplab.core",
				],
				// [
				// 	"text"     => "Конфигуртор проекта",
				// 	"icon"     => "rating_menu_icon",
				// 	"url"      => "uplab.core_configurator.php",
				// ],
				// [
				// 	"text"     => "Генераторы",
				// 	"icon"     => "rating_menu_icon",
				// 	"url"      => "uplab.core_generators.php",
				// ],
			],
		],
	];
}


return false;
