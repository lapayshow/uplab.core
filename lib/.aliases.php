<?

namespace Uplab\Core;


use CMain;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 * @var array    $arParams
 * @var array    $arResult
 */


class_alias(Iblock\Helper::class, '\Uplab\Core\IblockHelper');
class_alias(Iblock\Helper::class, '\Uplab\Core\UplabIblock');
class_alias(Iblock\HighloadBlock::class, '\Uplab\Core\UplabHlBlock');
class_alias(Helper::class, '\Uplab\Core\UplabHelper');
class_alias(Data\Cache::class, '\Uplab\Core\UplabCache');
class_alias(Uri::class, '\Uplab\Core\UplabUri');
class_alias(Data\StringUtils::class, '\Uplab\Core\UplabStringUtils');
class_alias(Data\Search\SearchPhraseTable::class, '\Uplab\Core\Data\Search');
