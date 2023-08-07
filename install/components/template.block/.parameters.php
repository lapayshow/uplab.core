<?
use Uplab\Core\Components\TemplateBlock;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 */


$arComponentParameters = [
	"GROUPS"     => [
	],
	"PARAMETERS" => [
	],
];


if ($GLOBALS["USER"]->IsAdmin()) {
	$arComponentParameters["PARAMETERS"]["DELAY"] = [
		"PARENT" => "BASE",
		"NAME"   => "Отложенное выполнение компонента",
		"TYPE"   => "CHECKBOX",
	];
	$arComponentParameters["PARAMETERS"]["CACHE_FOR_PAGE"] = [
		"PARENT" => "CACHE_SETTINGS",
		"NAME"   => "Добавить адрес страницы в кеш",
		"TYPE"   => "CHECKBOX",
	];
	$arComponentParameters["PARAMETERS"]["CACHE_TIME"] = ['DEFAULT' => 3600000];
}


CBitrixComponent::includeComponentClass("uplab.core:template.block");
TemplateBlock::prepareCustomParametersGroups($arComponentParameters, $templateProperties);
