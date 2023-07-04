<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

/**
 * @var array $arCurrentValues
 */

Loc::loadMessages(__FILE__);

$arComponentParameters = array(
	"GROUPS"     => array(),
	"PARAMETERS" => array(
        // 'IBLOCK_ID' => array(
        //     'PARENT' => 'DATA_SOURCE',
        //     'NAME' => Loc::getMessage('ITEM_DETAIL_PARAMETERS_IBLOCK_ID'),
        //     'TYPE' => 'STRING',
        //     'DEFAULT' => '',
        // ),
        // 'FILTER' => array(
        //     'PARENT' => 'DATA_SOURCE',
        //     'NAME' => Loc::getMessage('ITEM_DETAIL_PARAMETERS_FILTER'),
        //     'TYPE' => 'STRING',
        //     'DEFAULT' => '',
        // ),
        // 'CACHE_TIME' => array(
        //     'NAME' => Loc::getMessage('ITEM_DETAIL_PARAMETERS_CACHE_TIME'),
        //     'DEFAULT' => 36000000
        // ),
        // 'CACHE_FILTER' => array(
        //     'PARENT' => 'CACHE_SETTINGS',
        //     'NAME' => Loc::getMessage('ITEM_DETAIL_PARAMETERS_CACHE_FILTER'),
        //     'TYPE' => 'CHECKBOX',
        //     'DEFAULT' => 'N',
        // ),
        // 'CACHE_GROUPS' => array(
        //     'PARENT' => 'CACHE_SETTINGS',
        //     'NAME' => Loc::getMessage('ITEM_DETAIL_PARAMETERS_CACHE_GROUPS'),
        //     'TYPE' => 'CHECKBOX',
        //     'DEFAULT' => 'Y',
        // ),
    ),
);
