<?

namespace Uplab\Core\Iblock;


use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use CDBResult;
use CFile;
use CIBlock;
use CIBlockPropertyEnum;
use CIBlockSection;
use CIBlockElement;
use Bitrix\Iblock\InheritedProperty;
use Uplab\Core\IblockHelper;
use Uplab\Core\UplabCache;


Loader::includeModule("iblock");


/**
 * Class Helper
 *
 * Класс доступен через алиас: Uplab\Core\IblockHelper
 *
 * @package Uplab\Core\Iblock
 */
class Helper
{

	/**
	 * Универсальный метод для получения массива элементов ИБ.
	 * По аналогии с D7 принимает массив параметров
	 *
	 * Доступные ключи:
	 * - (int)   iblock
	 * - (array) order
	 * - (array) select
	 * - (array) navParams
	 * - (array) group
	 *
	 * - (bool)  properties:    если true, то возвращает массив "PROPERTIES"
	 * - (array) filter
	 * - (bool)  iprops:        если true, то будут получены значения SEO-настроек
	 * - (bool)  defaultFilter: по умолчанию выбираются только активные элементы;
	 *                          для большинства случаев это хорошая практика,
	 *                          и это даже полезно, но не всегда;
	 *                          если передать в этот параметр false,
	 *                          то дефолтный фильтр применен не будет
	 * - (bool)  returnObject:  если true, то возвращает объект CDBResult
	 * - (int)   limit:         если 1, то возвращает один элемент, иначе возвращает массив
	 *
	 * @param                      $params
	 * @param bool                 $cache
	 * @param null|mixed|CDBResult $res
	 *
	 * @return array|bool|\CIBlockResult
	 */
	public static function getList($params, $cache = true, &$res = null)
	{
		$iblock = isset($params["filter"]["IBLOCK_ID"])
			? $params["filter"]["IBLOCK_ID"]
			: $params["iblock"];

		if ($params["returnObject"] === true) $cache = false;

		if ($cache === true) {
			return UplabCache::cacheMethod(__METHOD__, [
				"arguments" => [$params, false],
				"tags"      => [
					"iblock_id_" . $iblock,
				],
			]);
		}

		if (!Loader::includeModule("iblock")) return false;

		$arItems = array();

		// Готовим параметры для гетлиста

		// Можно передать в фильтр как параметр "sort", так и "order"
		if (isset($params["sort"]) && !isset($params["order"])) {
			$params["order"] = $params["sort"];
		}

		if (isset($params["useTilda"])) {
			$useTilda = (bool)$params["useTilda"];
		} else {
			$useTilda = true;
		}

		$editorButtons = isset($params["editorButtons"]) ? (bool)$params["editorButtons"] : false;
		$params["defaultSelect"] = $params["defaultSelect"] ?? true;
		$params["defaultFilter"] = $params["defaultFilter"] ?? true;

		// Добавляем сортировку по умолчанию (только если никакой сортировки нет)
		$arOrder = array_key_exists("order", $params) ?
			$params["order"] :
			array("sort" => "asc", "date_active_from" => "desc");

		// По умолчанию выбираем только активные элементы,
		// если это не нужно - передать параметр "defaultFilter"
		if ($params["defaultFilter"] !== false) {
			$arFilter = array(
				"ACTIVE"      => "Y",
				"ACTIVE_DATE" => "Y",
			);
		} else {
			$arFilter = [];
		}

		$arFilter = array_merge($arFilter, (array)$params["filter"]);

		if ($iblock != $arFilter["IBLOCK_ID"]) {
			$arFilter["IBLOCK_ID"] = $params["iblock"];
		}

		if ($params["defaultSelect"] !== false) {
			$arSelect = array_merge(array(
				"ID",
				"IBLOCK_ID",
				"NAME",
			), (array)$params["select"]);
		} else {
			$arSelect = (array)$params["select"];
		}

		if (isset($params["navParams"])) {
			$arNavParams = $params["navParams"];
		} elseif (($limit = intval($params["limit"])) && $limit > 0) {
			$arNavParams = ["nTopCount" => $limit];
		} else {
			$arNavParams = false;
		}

		if (is_array($arNavParams)) {
			$arNavParams["checkOutOfRange"] = true;
		}

		$arGroupBy = isset($params["group"]) ? $params["group"] : false;

		$res = CIBlockElement::GetList($arOrder, $arFilter, $arGroupBy, $arNavParams, $arSelect);
		if ($params["returnObject"] === true) return $res;

		while ($ob = $res->GetNextElement(true, $useTilda)) {
			$item = $ob->GetFields();

			// Добавляем настройки SEO, если передан параметр "iprops" => true
			if (isset($params["iprops"]) && $params["iprops"] === true) {
				$ipropValues = new InheritedProperty\ElementValues(
					$item["IBLOCK_ID"],
					$item["ID"]
				);
				$item["IPROPERTY_VALUES"] = (array)($ipropValues->getValues());

				$item["IPROPERTY_VALUES"]["ELEMENT_META_TITLE"] =
					$item["IPROPERTY_VALUES"]["ELEMENT_META_TITLE"] ??
					$item["NAME"];

				$item["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"] =
					$item["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"] ??
					$item["NAME"];

				$item["~IPROPERTY_VALUES"] = $item["IPROPERTY_VALUES"];
				array_walk(
					$item["~IPROPERTY_VALUES"],
					function (
						/** @noinspection PhpUnusedParameterInspection */
						&$a,
						$b
					) {
						$a = htmlspecialchars_decode($a);
					}
				);
			}

			if (isset($item["DETAIL_PICTURE"])) {
				$item["~DETAIL_PICTURE"] = CFile::GetFileArray($item["DETAIL_PICTURE"]);
			}
			if (isset($item["PREVIEW_PICTURE"])) {
				$item["~PREVIEW_PICTURE"] = CFile::GetFileArray($item["PREVIEW_PICTURE"]);
			}
			if (!empty($item["DATE_ACTIVE_FROM"])) {
				$item["TIMESTAMP_ACTIVE_FROM"] = strtotime($item["DATE_ACTIVE_FROM"]);
			}
			if (!empty($item["DATE_ACTIVE_TO"])) {
				$item["TIMESTAMP_ACTIVE_TO"] = strtotime($item["DATE_ACTIVE_TO"]);
			}
			if (!empty($params["properties"]) && $params["properties"] === true) {
				$item["PROPERTIES"] = $ob->GetProperties();
			}

			if ($editorButtons) {
				$buttons = CIBlock::GetPanelButtons(
					$item["IBLOCK_ID"],
					$item["ID"],
					0,
					["SECTION_BUTTONS" => false, "SESSID" => false]
				);
				$item["EDIT_LINK"] = $buttons["edit"]["edit_element"]["ACTION_URL"];
				$item["DELETE_LINK"] = $buttons["edit"]["delete_element"]["ACTION_URL"];
			}

			if (is_array($arNavParams) && $arNavParams["nTopCount"] == 1) return $item;

			if (!empty($item[$params["byKey"]])) {
				$arItems[$item[$params["byKey"]]] = $item;
			} else {
				$arItems[] = $item;
			}
		}

		//d($arItems);

		return $arItems;
	}

	/**
	 * Универсальный метод для получения массива разделов ИБ.
	 * По аналогии с D7 принимает массив параметров
	 *
	 * Доступные ключи:
	 * - (string) byKey:         можно передать код параметра, значение которого
	 *                           будет использовано в качестве ключа в возвращаемом массиве
	 *                           (например: ID, CODE)
	 * - (bool)   returnObject:  если true, то возвращает объект CDBResult
	 * - (bool)   iprops:        если true, то будут получены значения SEO-настроек
	 * - (int)    limit:         если 1, то возвращает один элемент, иначе возвращает массив
	 *
	 * @param           $params
	 * @param bool      $cache
	 * @param CDBResult $res
	 *
	 * @return array|mixed
	 * @throws LoaderException
	 */
	public static function getSectionsList($params, $cache = true, &$res = null)
	{
		if ($params["returnObject"] === true) $cache = false;

		$iblock = isset($params["filter"]["IBLOCK_ID"]) ?
			$params["filter"]["IBLOCK_ID"] :
			$params["iblock"];

		if ($cache === true) {
			return UplabCache::cacheMethod(__METHOD__, [
				"arguments" => [$params, false],
				"tags"      => [
					"iblock_id_" . $iblock,
					"iblock_new",
				],
			]);
		}

		if (!Loader::includeModule("iblock")) return [];

		$arSections = array();

		if (isset($params["sort"]) && !isset($params["order"])) {
			$params["order"] = $params["sort"];
		}

		$editorButtons = isset($params["editorButtons"]) ? (bool)$params["editorButtons"] : false;

		$arOrder = array_key_exists("order", $params) ?
			$params["order"] :
			array("sort" => "asc", "date_active_from" => "desc");

		$arFilter = array_merge(array(
			"ACTIVE"      => "Y",
			"ACTIVE_DATE" => "Y",
		), (array)$params["filter"]);

		if ($iblock != $arFilter["IBLOCK_ID"]) {
			$arFilter["IBLOCK_ID"] = $iblock;
		}

		$arSelect = array_merge(array(
			"ID",
			"IBLOCK_ID",
			"NAME",
			"SECTION_PAGE_URL",
		), (array)$params["select"]);

		$res = CIBlockSection::GetList($arOrder, $arFilter, true, $arSelect);
		if ($params["returnObject"] === true) return $res;

		while ($item = $res->GetNext()) {
			if ($params["iprops"] ?? false === true) {
				self::prepareSectionIprops($item);
			}

			if (isset($item["PICTURE"])) {
				$item["~PICTURE"] = CFile::GetFileArray($item["PICTURE"]);
			}

			if ($params["limit"] == 1) {
				return $item;
			}

			if ($editorButtons) {
				$buttons = CIBlock::GetPanelButtons(
					$item["IBLOCK_ID"],
					0,
					$item["ID"],
					["SECTION_BUTTONS" => true, "SESSID" => false]
				);
				$item["EDIT_LINK"] = $buttons["edit"]["edit_section"]["ACTION_URL"];
				$item["DELETE_LINK"] = $buttons["edit"]["delete_section"]["ACTION_URL"];
			}

			if ($params["byKey"] &&
				(array_key_exists($params["byKey"], $item)) &&
				($key = $item[$params["byKey"]])) {
				$arSections[$key] = $item;
			} else {
				$arSections[] = $item;
			}
		}

		return $arSections;
	}

	/**
	 * Возвращает выбранный раздел и его подразделы.
	 * В фильтр нужно передавать такие же параметры, как если бы мы хотели
	 * выбрать один элемент через метод self::getSectionsList
	 *
	 * Через ключ "rootSection" можно передать родительский раздел, если он уже известен,
	 * тогда без дополнительных запросов сразу будут получены его подразделы.
	 *
	 * Доступен ключ массива params: "subSectionsFilter", который применяется к списку подразделов
	 *
	 * @param      $params
	 * @param bool $cache
	 *
	 * @return array|null
	 * @throws LoaderException
	 */
	public static function getSubsectionsForSection($params, $cache = true)
	{
		$arSubSections = [];

		$params["select"] = array_unique(
			array_merge(
				(array)($params["select"] ?? []),
				[
					"LEFT_MARGIN",
					"RIGHT_MARGIN",
					"DEPTH_LEVEL",
				]
			)
		);

		$rootSect = $params["rootSection"] ?? [];

		if (
			!$rootSect ||
			!isset($rootSect["LEFT_MARGIN"]) ||
			!isset($rootSect["RIGHT_MARGIN"]) ||
			!isset($rootSect["DEPTH_LEVEL"])
		) {
			$rootParams = $params;
			$rootParams["limit"] = 1;

			$rootSect = self::getSectionsList($rootParams, $cache);
		}

		if ($rootSect) {
			$params["iblock"] = $rootSect["IBLOCK_ID"];
			$params["filter"] = array_merge(
				[
					">LEFT_MARGIN"  => $rootSect["LEFT_MARGIN"],
					"<RIGHT_MARGIN" => $rootSect["RIGHT_MARGIN"],
				],
				(array)($params["subSectionsFilter"] ?? [])
			);

			if ($params["includeSubsections"] ?? true) {
				$params["filter"][">DEPTH_LEVEL"] = $rootSect["DEPTH_LEVEL"];
			} else {
				$params["filter"]["DEPTH_LEVEL"] = $rootSect["DEPTH_LEVEL"] + 1;
			}

			unset($params["rootSection"]);
			unset($params["subSectionsFilter"]);
			unset($params["includeSubsections"]);

			if (isset($params["limit"]) && $params["limit"] < 2) {
				unset($params["limit"]);
			}

			$arSubSections = self::getSectionsList($params, $cache);
		}

		return [
			"root"        => $rootSect,
			"subsections" => $arSubSections,
		];
	}

	/**
	 * Возвращает путь из разделов от корневого до заданного
	 *
	 * @param       $iblockId
	 * @param       $sectionId
	 * @param array $params
	 * @param bool  $cache
	 *
	 * @return array|mixed
	 * @throws LoaderException
	 */
	public static function getNavChain(
		$iblockId,
		$sectionId,
		$params = [],
		$cache = true
	) {
		if ($cache === true) {
			return UplabCache::cacheMethod(__METHOD__, [
				"arguments" => [$iblockId, $sectionId, $params, false],
				"tags"      => [
					"iblock_id_" . $iblockId,
					"iblock_new",
				],
			]);
		}

		/**
		 * Используется два запроса, так как GetNavChain не возвращает UF_* поля.
		 * Сначала получаем ID разделов, а затем обычным GetList-ом получаем выборку по разделам
		 */

		$res = CIBlockSection::GetNavChain($iblockId, $sectionId, ["ID", "IBLOCK_ID"]);

		$arSectionsFilter = [
			"IBLOCK_ID" => $iblockId,
			"ID"        => [],
		];
		while ($arSection = $res->Fetch()) {
			$arSectionsFilter["ID"][] = $arSection["ID"];
		}

		return IblockHelper::getSectionsList(
			[
				"filter" => $arSectionsFilter,
				"iprops" => ($params["iprops"] ?? false) === true,
				"select" => $params["select"] ?? [],
				"order"  => [
					"DEPTH_LEVEL" => "ASC",
					"LEFT_MARGIN" => "ASC",
				],
			],
			false
		);
	}

	/**
	 * Get Params ID by value for drop down list
	 *
	 * @param        $iblock
	 * @param        $property
	 * @param string $value
	 * @param string $code
	 *
	 * @return mixed
	 * @throws LoaderException
	 */
	public static function getEnumId($iblock, $property, $value, $code = "")
	{
		if ((!$value && !$code) || !$iblock || !$property) return false;
		if (!Loader::includeModule("iblock")) return false;

		if ($value) {
			$enumRes = CIBlockPropertyEnum::GetList(array(), array("IBLOCK_ID" => $iblock, "VALUE" => $value));
		} else {
			$enumRes = CIBlockPropertyEnum::GetList(array(), array("IBLOCK_ID" => $iblock, "CODE" => $code));
		}

		if ($arEnum = $enumRes->GetNext()) {
			return $arEnum["ID"];
		}

		return false;
	}

	/**
	 * Get Params List by code for drop down list
	 *
	 * @param $param
	 *
	 * @return array|bool
	 * @throws LoaderException
	 */
	public static function getEnumList($param)
	{
		$iblock = false;
		$code = "";
		$by = false;
		extract($param);

		if (!$code || !$iblock) return false;
		if (!Loader::includeModule("iblock")) return false;

		$arResult = array();
		$res = CIBlockPropertyEnum::GetList(
			["def" => "desc", "sort" => "asc"],
			["IBLOCK_ID" => $iblock, "CODE" => $code]
		);
		while ($enum = $res->GetNext()) {
			if ($by && $enum[$by]) {
				$arResult[$enum[$by]] = $enum;
			} else {
				$arResult[$enum["ID"]] = $enum;
			}
		}

		return $arResult;
	}

	/**
	 * @param bool $iblock
	 * @param bool $filter
	 *
	 * @return \CIBlockResult|int
	 * @throws LoaderException
	 */
	public static function getCount($iblock = false, $filter = false)
	{
		if (!Loader::includeModule("iblock")) return null;
		if ($iblock) {
			$filter = array_merge(
				["IBLOCK_ID" => $iblock],
				(array)$filter
			);
		}

		return CIBlockElement::GetList(false, $filter, [], false, ["ID"]);
	}

	public static function prepareTags($strTags, $relative = false, $key = "tags")
	{
		$relative = empty($relative) ? "?{$key}=" : "{$relative}?{$key}=";

		$arTags = array(
			"SRC" => $strTags,
		);
		$arTags["NAMES"] = preg_split('~\s*,\s*~', $strTags);
		$arTags["NAMES"] = array_filter($arTags["NAMES"]);

		$arTags["ITEMS"] = [];
		foreach ($arTags["NAMES"] as $tag) {
			$arTags["%NAMES"][] = "%{$tag}%";
			$arTags["ITEMS"][] = array(
				"NAME" => $tag,
				"URL"  => $relative . urlencode($tag),
			);
		}
		$arTags["URL"] = urlencode(implode(",", $arTags["NAMES"]));
		$arTags["URL"] = empty($arTags["URL"]) ? "" : $relative . $arTags["URL"];

		return $arTags;
	}

	public static function buildAdminElementLink($iblock, $id)
	{
		if (strlen($id) <= 0) return "";
		$id = intval($id);

		$iblock = intval($iblock);
		if (empty($iblock)) {
			$res = CIBlockElement::GetList([], ["ID" => $id], false, ["IBLOCK_ID", "ID"]);
			if ($item = $res->Fetch()) {
				$iblock = $item["IBLOCK_ID"];
				$id = $item["ID"];
			} else {
				return "";
			}
		}

		$res = CIBlock::GetByID($iblock);
		if ($item = $res->Fetch()) {
			$type = $item["IBLOCK_TYPE_ID"];
		} else {
			return "";
		}

		return "/bitrix/admin/iblock_element_edit.php?IBLOCK_ID={$iblock}&type={$type}&ID={$id}";
	}

	public static function buildAdminElementNewLink($iblock)
	{
		return self::buildAdminElementLink($iblock, 0);
	}

	public static function addOrUpdateElement($arLoad, $arLoadProps, $iblock, $id = false, &$log = "")
	{
		if (!Loader::includeModule("iblock")) return null;
		$el = new CIBlockElement;

		if (empty($arLoad["IBLOCK_ID"])) {
			$arLoad["IBLOCK_ID"] = $iblock;
		}

		// TODO: гоняем эти бедные проперти туда-сюда, нужно что-то изменить
		if (empty($arLoadProps) && isset($arLoad["PROPERTY_VALUES"])) {
			$arLoadProps = $arLoad["PROPERTY_VALUES"];
			unset($arLoad["PROPERTY_VALUES"]);
		}

		$result = false;
		if ($arLoad["REMOVE"] === true) {
			if (!empty($id)) $el->Delete($id);
		} elseif (empty($id)) {
			$arLoad["PROPERTY_VALUES"] = $arLoadProps;
			$result = $el->Add($arLoad);
			$log .= PHP_EOL . "{$result} add" . PHP_EOL;
		} else {
			$log .= PHP_EOL . "{$id} update" . PHP_EOL;

			CIBlockElement::SetPropertyValuesEx($id, $iblock, $arLoadProps);
			$el->Update($id, $arLoad);
			$result = $id;
		}

		if (!$result) {
			$log .= PHP_EOL . $el->LAST_ERROR . PHP_EOL;
		}

		return $result;
	}

	protected static function prepareSectionIprops(&$item)
	{
		$ipropValues = new InheritedProperty\SectionValues(
			$item["IBLOCK_ID"],
			$item["ID"]
		);
		$item["IPROPERTY_VALUES"] = (array)($ipropValues->getValues());

		$item["IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"] =
			$item["IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"] ??
			$item["NAME"];

		$item["IPROPERTY_VALUES"]["SECTION_META_TITLE"] =
			$item["IPROPERTY_VALUES"]["SECTION_META_TITLE"] ??
			$item["NAME"];

		$item["~IPROPERTY_VALUES"] = $item["IPROPERTY_VALUES"];
		array_walk(
			$item["~IPROPERTY_VALUES"],
			function (
				/** @noinspection PhpUnusedParameterInspection */
				&$a,
				$b
			) {
				$a = htmlspecialchars_decode($a);
			}
		);
	}

	public static function prepareSectionsForElements(&$arResult)
	{
		$sectionsList = [];
		$sectionsGroups = [];
		$sectionsItems = [];

		foreach ($arResult["ITEMS"] as &$arItem) {
			if (empty($arItem["IBLOCK_SECTION_ID"])) continue;

			$sectionsItems[$arItem["IBLOCK_SECTION_ID"]][] = &$arItem;
			$arItem["SECTION"] = &$sectionsList[$arItem["IBLOCK_SECTION_ID"]];

			unset($arItem);
		}

		if (empty($sectionsItems)) return;

		$sectionIterator = CIBlockSection::GetList(
			[
				"left_margin" => "asc",
				"sort"        => "asc",
			],
			["ID" => array_keys($sectionsList)]
		);

		while ($section = $sectionIterator->GetNext()) {
			$sectionsList[$section["ID"]] = $section;

			$section["ITEMS"] = &$sectionsItems[$section["ID"]];
			$sectionsGroups[] = &$section;

			unset($section);
		}

		$arResult["SECTIONS_GROUPS"] = $sectionsGroups;
	}

}
