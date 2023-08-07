<?
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Uplab\Core\Constant;
use __MODULE_NAMESPACE__\Events;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();


Loc::loadMessages(__FILE__);


if (Loader::includeModule("uplab.core")) {
	Constant::define();
}


if (Loader::includeModule("__MODULE_ID__")) {
	Events::bindEvents();
}
