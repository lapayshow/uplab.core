<?
use Bitrix\Main\Localization\Loc;
use Uplab\Core\Components\TemplateBlock;


if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arCurrentValues */

Loc::loadMessages(__FILE__);

$arComponentParameters = [
	'GROUPS'     => [],
	'PARAMETERS' => [
		'IBLOCK_ID'    => [
			'PARENT'  => 'DATA_SOURCE',
			'NAME'    => Loc::getMessage('ITEM_DETAIL_PARAMETERS_IBLOCK_ID'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
		],
		'FILTER'       => [
			'PARENT'  => 'DATA_SOURCE',
			'NAME'    => Loc::getMessage('ITEM_DETAIL_PARAMETERS_FILTER'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
		],
		'ELEMENT_CODE' => [
			'PARENT'  => 'DATA_SOURCE',
			'NAME'    => Loc::getMessage('ITEM_DETAIL_PARAMETERS_ELEMENT_CODE'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
		],
		'ELEMENT_ID'   => [
			'PARENT'  => 'DATA_SOURCE',
			'NAME'    => Loc::getMessage('ITEM_DETAIL_PARAMETERS_ELEMENT_ID'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
		],
		'CACHE_TIME'   => ['DEFAULT' => 36000000],
	],
];

CBitrixComponent::includeComponentClass("uplab.core:template.block");
TemplateBlock::prepareCustomParametersGroups($arComponentParameters, $templateProperties);
