<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var array $arCurrentValues */

use Bitrix\Main\Localization\Loc;
use Uplab\Core\Components\TemplateBlock;


Loc::loadMessages(__FILE__);

$arComponentParameters = array(
	'GROUPS'     => array(
		'FIELDS_SETTINGS' => array(
			'NAME' => Loc::getMessage('UP_CORE_INCLUDE_AREA_PARAMETERS_FIELDS_SETTINGS'),
			'SORT' => 310,
		),
	),
	'PARAMETERS' => array(
		'CODE'         => array(
			'PARENT'  => 'DATA_SOURCE',
			'NAME'    => Loc::getMessage('UP_CORE_INCLUDE_AREA_PARAMETERS_CODE'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
		),
		'FILTER'       => array(
			'PARENT'  => 'DATA_SOURCE',
			'NAME'    => Loc::getMessage('UP_CORE_INCLUDE_AREA_PARAMETERS_FILTER'),
			'TYPE'    => 'STRING',
			'DEFAULT' => '',
		),
		'HAVE_HASHTAG' => array(
			'PARENT'  => 'DATA_SOURCE',
			'NAME'    => Loc::getMessage('UP_CORE_INCLUDE_AREA_PARAMETERS_HASHTAG'),
			'TYPE'    => 'CHECKBOX',
			'DEFAULT' => 'N',
		),
		'USE_FIELDS'   => array(
			'PARENT'  => 'FIELDS_SETTINGS',
			'NAME'    => Loc::getMessage('UP_CORE_INCLUDE_AREA_PARAMETERS_USE_FIELDS'),
			'TYPE'    => 'CHECKBOX',
			'DEFAULT' => 'N',
			'REFRESH' => 'Y',
		),
		'CACHE_TIME'   => array(
			'NAME'    => Loc::getMessage('UP_CORE_INCLUDE_AREA_PARAMETERS_CACHE_TIME'),
			'DEFAULT' => 36000000,
		),
		'CACHE_FILTER' => array(
			'PARENT'  => 'CACHE_SETTINGS',
			'NAME'    => Loc::getMessage('UP_CORE_INCLUDE_AREA_PARAMETERS_CACHE_FILTER'),
			'TYPE'    => 'CHECKBOX',
			'DEFAULT' => 'N',
		),
		'CACHE_GROUPS' => array(
			'PARENT'  => 'CACHE_SETTINGS',
			'NAME'    => Loc::getMessage('UP_CORE_INCLUDE_AREA_PARAMETERS_CACHE_GROUPS'),
			'TYPE'    => 'CHECKBOX',
			'DEFAULT' => 'Y',
		),
	),
);

if ($arCurrentValues['USE_FIELDS'] == 'Y') {
	CBitrixComponent::includeComponentClass('uplab.core:include.area');
	$component = new IncludeAreaComponent();
	$arAllowFieldTypes = $component->getAllowedFieldsType();

	$arComponentParameters['PARAMETERS']['FIELDS_TYPES'] = [
		'PARENT'   => 'FIELDS_SETTINGS',
		'NAME'     => Loc::getMessage('UP_CORE_INCLUDE_AREA_PARAMETERS_ALLOWED_FIELDS'),
		'TYPE'     => 'LIST',
		'DEFAULT'  => '',
		'MULTIPLE' => 'Y',
		'REFRESH'  => 'Y',
		'VALUES'   => $arAllowFieldTypes,
	];

	$arCurrentValues['FIELDS_TYPES'] = array_filter($arCurrentValues['FIELDS_TYPES']);
	if (!empty($arCurrentValues['FIELDS_TYPES'])) {
		foreach ($arCurrentValues['FIELDS_TYPES'] as $fieldType) {
			if (!array_key_exists($fieldType, $arAllowFieldTypes)) continue;
			$typeLabel = $arAllowFieldTypes[$fieldType];
			$arComponentParameters['PARAMETERS']['FIELDS_' . $fieldType . '_NAME'] = [
				'PARENT'   => 'FIELDS_SETTINGS',
				'NAME'     => Loc::getMessage('UP_CORE_INCLUDE_AREA_PARAMETERS_FIELDS_NAME', ['#TYPE#' => $typeLabel]),
				'TYPE'     => 'TEXT',
				'DEFAULT'  => '',
				'MULTIPLE' => 'Y',
				'REFRESH'  => 'Y',
			];
			$arCurrentValues['FIELDS_' . $fieldType . '_NAME'] = array_filter($arCurrentValues['FIELDS_' . $fieldType . '_NAME']);
			if (!empty($arCurrentValues['FIELDS_' . $fieldType . '_NAME'])) {
				foreach ($arCurrentValues['FIELDS_' . $fieldType . '_NAME'] as $key => $fieldName) {
					$arComponentParameters['PARAMETERS']['FIELDS_' . $fieldType . '_' . $key . '_CODE'] = [
						'PARENT'  => 'FIELDS_SETTINGS',
						'NAME'    => Loc::getMessage('UP_CORE_INCLUDE_AREA_PARAMETERS_FIELDS_CODE',
							['#NAME#' => $fieldName]),
						'TYPE'    => 'TEXT',
						'DEFAULT' => '',
					];
				}
			}
		}
	}
}

CBitrixComponent::includeComponentClass("uplab.core:template.block");
TemplateBlock::prepareCustomParametersGroups($arComponentParameters, $templateProperties);
