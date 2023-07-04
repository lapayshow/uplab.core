<?
use Bitrix\Main\Application;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 * @var array    $arParams
 * @var array    $arResult
 */


/** @noinspection PhpIncludeInspection */
include
	Application::getDocumentRoot() .
	getLocalPath("components/uplab.core/template.block/.parameters.php");