<?
use Uplab\Core\Components\TemplateBlock;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 * @var    array $arCurrentValues
 */


CBitrixComponent::includeComponentClass("uplab.core:template.block");


TemplateBlock::addDynamicParameters(
	$set, $arCurrentValues,
	[
		"CODE" => "FILE",
		"NAME" =>  "Список файлов"
	],
	[
		"SRC" => array(
			"NAME"              => 'Путь к файлу',
			"TYPE"              => "FILE",
			"COLS"              => 30,
			"FD_TARGET"         => "F",
			"FD_EXT"            => 'pdf,doc,docx,xls,xlsx',
			"FD_UPLOAD"         => true,
			"FD_USE_MEDIALIB"   => true,
			"FD_MEDIALIB_TYPES" => array('image'),
			'REFRESH'           => 'Y',
		),
		"TEXT" => ["NAME" => "Описание"],
	]
);


$arTemplateParameters = TemplateBlock::initParameters($set);
