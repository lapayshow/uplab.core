<?
/*
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();


if (!Loader::includeModule("__MODULE_ID__")) return;
Loc::loadMessages(__FILE__);


return array(
	array(
		"sort"        => 100,
		"section"     => "__MODULE_ID__",
		"parent_menu" => "global_menu_content",
		"icon"        => "learning_icon_certification",
		"page_icon"   => "fileman_sticker_icon",
		"text"        => "Menu item",
		"url"         => "",
		"items_id"    => "__MODULE_ID__",
		"more_url"    => array(),
		"items"       => array(
			array(
				"text"     => "Child item",
				"icon"     => "rating_menu_icon",
				"url"      => "",
				"more_url" => array(),
			),
		),
	),
);