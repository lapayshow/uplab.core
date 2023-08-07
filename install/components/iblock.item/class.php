<?

namespace Uplab\Core\Components;

defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Iblock;
use CBitrixComponent;
use CIBlock;
use CIBlockElement;
use Exception;
use Uplab\Core\Data\Registry\Registry;
use Uplab\Core\Helper;
use Uplab\Core\IblockHelper;
use Uplab\Core\Legacy\Users;


class IblockItemComponent extends CBitrixComponent implements Controllerable
{
	/**
	 * кешируемые ключи arResult
	 *
	 * @var array()
	 */
	protected $cacheKeys = array(
		"ID",
		"IBLOCK_ID",
		"SECTION_ID",
		"META_TITLE",
		"META_KEYWORDS",
		"META_DESCRIPTION",
		"PAGE_TITLE",
		"EDIT_LINK",
		"DELETE_LINK",
		"NAV_CHAIN",
		"ELEMENT",
		"__RETURN_VALUE",
		"__TIMESTAMP",
	);

	/**
	 * дополнительные параметры, от которых должен зависеть кеш
	 *
	 * @var array
	 */
	protected $cacheAddon = array();

	/**
	 * модули, которые необходимо подключить
	 *
	 * @var array
	 */
	protected $dependModules = array("iblock", "uplab.core");

	/**
	 * параметры, которые необходимо проверить
	 *
	 * @var array
	 */
	protected $requiredParams = array(
		"int"   => array(
			"IBLOCK_ID",
		),
		"isset" => array(
			"FILTER",
		),
	);

	/**
	 * внешний фильтр
	 *
	 * @var array
	 */
	protected $arFilter = array();

	/**
	 * внешняя сортировка
	 *
	 * @var array
	 */
	protected $arSort = array();

	/**
	 * флаг использования кэшированных данных
	 *
	 * @var array
	 */
	protected $fromCache = true;

	/**
	 * подключает языковые файлы
	 */
	public function onIncludeComponentLang()
	{
		$this->includeComponentLang(basename(__FILE__));
		Loc::loadMessages(__FILE__);
	}

	/**
	 * подготавливает входные параметры
	 *
	 * @param array $params
	 *
	 * @return array
	 * @throws ObjectNotFoundException
	 */
	public function onPrepareComponentParams($params)
	{
		$bDesignMode =
			$GLOBALS["APPLICATION"]->GetShowIncludeAreas() &&
			is_object($GLOBALS["USER"]) &&
			($GLOBALS["USER"]->IsAdmin() || Users::isInGroup("CONTENT"));

		if (!isset($params["CACHE_TIME"]) && !$bDesignMode) {
			if (defined("CACHE_TIME")) {
				$params["CACHE_TIME"] = CACHE_TIME;
			} else {
				$params["CACHE_TIME"] = 3600;
			}
		}

		foreach ($this->requiredParams as $key => $requiredRow) {
			$this->requiredParams[$key] = array_merge(
				(array)$this->requiredParams[$key],
				(array)$params["REQUIRED_" . strtoupper($key) . "_PARAMS"]
			);
		}

		if (isset($params["FILTER"]) && !empty($params["FILTER"])) {
			if ($params["FILTER"] instanceof Registry) {
				$this->arFilter = $params["FILTER"]->getAll();
			} else {
				throw new ObjectNotFoundException("FILTER object not found");
			}
		}

		if (!is_array($this->arFilter)) {
			$this->arFilter = [];
		}

		if (($params["CHECK_PERMISSIONS"] ?? "") != "N") {
			$this->arFilter["CHECK_PERMISSIONS"] = "Y";
		}

		if (!empty($params["ELEMENT_CODE"])) {
			$this->arFilter["CODE"] = htmlspecialchars($params["ELEMENT_CODE"]);
		}
		if (!empty($params["ELEMENT_ID"]) && ($id = intval($params["ELEMENT_ID"]))) {
			$this->arFilter["ID"] = $id;
		}
		if (!empty($params["SECTION_CODE"])) {
			$this->arFilter["SECTION_CODE"] = htmlspecialchars($params["SECTION_CODE"]);
			$this->arFilter["INCLUDE_SUBSECTIONS"] = "Y";
		} elseif (!empty($params["SECTION_ID"]) && ($id = intval($params["ELEMENT_ID"]))) {
			$this->arFilter["SECTION_ID"] = $id;
			$this->arFilter["INCLUDE_SUBSECTIONS"] = "Y";
		}

		if (isset($params["SORT"]) && !empty($params["SORT"])) {
			if ($params["SORT"] instanceof Registry) {
				$this->arSort = $params["SORT"]->getAll();
				if (!is_array($this->arSort)) $this->arSort = null;
			} else {
				throw new ObjectNotFoundException("SORT object not found");
			}
		}

		if (($params["SET_META"] ?? "") == "Y") {
			$params["SET_TITLE"] = "Y";
		}

		$result = array(
			"SITE_ID"            => substr(preg_replace("/[^a-z0-9_]/i", "", trim($params["SITE_ID"])), 0, 2),
			"DESIGN_MODE"        => $bDesignMode,
			"IBLOCK_ID"          => intval($params["IBLOCK_ID"]),
			"DETAIL_URL"         => trim($params["DETAIL_URL"]),
			"SECTION_URL"        => trim($params["SECTION_URL"]),
			"IBLOCK_URL"         => trim($params["IBLOCK_URL"]),
			"USE_FILTER"         => (!empty($this->arFilter) ? "Y" : "N"),
			"CACHE_GROUPS"       => ($params["CACHE_GROUPS"] == "Y" ? "Y" : "N"),
			"GET_NAV_CHAIN"      => ($params["GET_NAV_CHAIN"] == "Y" ? "Y" : "N"),
			"SET_SECTIONS_CHAIN" => ($params["SET_SECTIONS_CHAIN"] == "Y" ? "Y" : "N"),
			"SET_ELEMENT_CHAIN"  => ($params["SET_ELEMENT_CHAIN"] == "Y" ? "Y" : "N"),
			"SET_TITLE"          => ($params["SET_TITLE"] == "Y" ? "Y" : "N"),
			"SET_STATUS_404"     => ($params["SET_STATUS_404"] == "Y" ? "Y" : "N"),
			"SHOW_404"           => ($params["SHOW_404"] == "N" ? "N" : "Y"),
			"FILE_404"           => trim($params["FILE_404"]),
		);

		if ($result["SET_SECTIONS_CHAIN"] == "Y") {
			$result["GET_NAV_CHAIN"] = "Y";
		}

		return array_filter(array_merge($params, $result));
	}

	/**
	 * выполняет логику работы компонента
	 */
	public function executeComponent()
	{
		try {
			$this->checkModules();
			$this->checkParams();
			$this->executeProlog();
			$this->fromCache = true;
			if (!$this->readDataFromCache()) {
				$this->fromCache = false;
				$this->getResult();

				if (defined("BX_COMP_MANAGED_CACHE") && ($taggedCache = Application::getInstance()->getTaggedCache())) {
					$taggedCache->startTagCache($this->getCachePath());
					$taggedCache->registerTag("iblock_id_" . $this->arResult["IBLOCK_ID"]);
				}

				$this->putDataToCache();
				$this->includeComponentTemplate();
				$this->endDataCache();
			}

			if (empty($this->arResult["ID"]) && $this->arParams["SET_STATUS_404"] === "Y") {
				Iblock\Component\Tools::process404(
					"",
					($this->arParams["SET_STATUS_404"] === "Y"),
					($this->arParams["SET_STATUS_404"] === "Y"),
					($this->arParams["SHOW_404"] === "Y"),
					$this->arParams["FILE_404"]
				);
			}

			$this->executeEpilog();

			return $this->arResult["__RETURN_VALUE"];
		} catch (Exception $e) {
			$this->abortDataCache();
			ShowError($e->getMessage());

			return false;
		}
	}

	/**
	 * проверяет подключение необходимых модулей
	 *
	 * @throws Main\LoaderException
	 */
	protected function checkModules()
	{
		foreach ($this->dependModules as $module) {
			if (!Main\Loader::includeModule($module)) {
				throw new Main\LoaderException("Module not found `{$module}`");
			}
		}
	}

	/**
	 * проверяет заполнение обязательных параметров
	 *
	 * @throws Main\ArgumentNullException
	 */
	protected function checkParams()
	{
		foreach ($this->requiredParams["int"] as $param) {
			if (intval($this->arParams[$param]) <= 0) {
				throw new Main\ArgumentNullException($param);
			}
		}
		foreach ($this->requiredParams["isset"] as $param) {
			if (!isset($this->arParams[$param]) && !empty($this->arParams[$param])) {
				throw new Main\ArgumentNullException($param);
			}
		}
	}

	/**
	 * выполяет действия перед кешированием
	 */
	protected function executeProlog()
	{
		$this->cacheAddon[] = $this->arFilter;
	}

	/**
	 * определяет читать данные из кеша или нет
	 *
	 * @return bool
	 */
	protected function readDataFromCache()
	{
		if ($this->arParams["CACHE_TYPE"] == "N") {
			return false;
		}
		if ($this->arParams["CACHE_FILTER"] == "Y") {
			$this->cacheAddon[] = $this->arFilter;
		}
		if ($this->arParams["CACHE_GROUPS"] == "Y" && is_object($GLOBALS["USER"])) {
			$this->cacheAddon[] = $GLOBALS["USER"]->GetUserGroupArray();
		}

		return !($this->StartResultCache(false, $this->cacheAddon));
	}

	/**
	 * завершает сохранение кэшируемых данных
	 *
	 * @return bool
	 */
	protected function endDataCache()
	{
		if ($this->arParams["CACHE_TYPE"] == "N" || $this->fromCache) {
			return false;
		}
		$this->EndResultCache();

		return true;
	}

	/**
	 * получение результатов
	 */
	protected function getResult()
	{
		$this->prepareSectionId();

		$this->arResult = array(
			"ID"        => false,
			"IBLOCK_ID" => $this->arParams["IBLOCK_ID"],
			"ITEM"      => array(),
			"ELEMENT"   => array(),
		);

		if (!empty($this->arFilter) || !empty($this->arSort) || !empty($this->arParams["IBLOCK_ID"])) {
			$arItem = IblockHelper::getList(
				[
					"iblock"        => $this->arParams["IBLOCK_ID"],
					"filter"        => $this->arFilter ?? [],
					"order"         => $this->arSort ?? [],
					"select"        => ["*"],
					"limit"         => 1,
					"iprops"        => true,
					"properties"    => true,
					"editorButtons" => $this->arParams["DESIGN_MODE"],
				],
				false
			);

			if ($this->arParams["GET_NAV_CHAIN"] == "Y" && !empty($arItem["IBLOCK_SECTION_ID"])) {
				$this->arResult["NAV_CHAIN"] = IblockHelper::getNavChain(
					$this->arParams["IBLOCK_ID"],
					$arItem["IBLOCK_SECTION_ID"],
					[],
					false
				);
			}

			if (!empty($arItem)) {
				$this->arResult["ID"] = $arItem["ID"];
				$this->arResult["IBLOCK_ID"] = $arItem["IBLOCK_ID"];
				$this->arResult["ITEM"] = $arItem;
				$this->arResult["EDIT_LINK"] = $arItem["EDIT_LINK"];
				$this->arResult["DELETE_LINK"] = $arItem["DELETE_LINK"];
				$this->arResult["PAGE_TITLE"] = $arItem["IPROPERTY_VALUES"]["ELEMENT_PAGE_TITLE"] ?? "";
				$this->arResult["META_TITLE"] = $arItem["IPROPERTY_VALUES"]["ELEMENT_META_TITLE"] ?? "";
				$this->arResult["META_KEYWORDS"] = $arItem["IPROPERTY_VALUES"]["ELEMENT_META_KEYWORDS"] ?? "";
				$this->arResult["META_DESCRIPTION"] = $arItem["IPROPERTY_VALUES"]["ELEMENT_META_DESCRIPTION"] ?? "";
			}

			unset($arItem);
		}
	}

	/**
	 * кеширует ключи массива arResult
	 */
	protected function putDataToCache()
	{
		if (is_array($this->cacheKeys) && sizeof($this->cacheKeys) > 0) {
			$this->SetResultCacheKeys($this->cacheKeys);
		}
	}

	/**
	 * @throws ArgumentException
	 * @noinspection PhpUnused
	 */
	protected function prepareSectionId()
	{
		if (isset($this->arResult["SECTION_ID"])) return;

		$currentSectionID = false;

		if (!empty($this->arParams["SECTION_ID"])) {

			$currentSectionID = (int)$this->arParams["SECTION_ID"];

		} elseif (!empty($this->arFilter)) {

			if ($sectionCode = ($this->arFilter["SECTION_CODE"] ?? false)) {

				$arSection = Iblock\SectionTable::getList(
					[
						"filter" => [
							"ACTIVE"    => "Y",
							"IBLOCK_ID" => $this->arParams["IBLOCK_ID"],
							"CODE"      => $sectionCode,
						],
						"select" => ["ID"],
					]
				)->fetch();
				$currentSectionID = $arSection["ID"] ?? false;

			} elseif (!empty($arFilter["SECTION_ID"])) {

				$currentSectionID = $arFilter["SECTION_ID"];

			}

		}

		$this->arResult["SECTION_ID"] = $currentSectionID;
	}

	/**
	 * формирование набора кнопок для эрмитажа в режиме правки
	 */
	protected function showEditButtons()
	{
		global $APPLICATION;

		if ($this->arResult["IBLOCK_ID"] <= 0) return;
		if ($this->arResult["ID"] <= 0) return;
		if (!$this->arParams["DESIGN_MODE"]) return;
		if (!$APPLICATION->GetShowIncludeAreas()) return;

		$arButtons = CIBlock::GetPanelButtons(
			$this->arResult["IBLOCK_ID"],
			$this->arResult["ID"],
			$this->arResult["SECTION_ID"] ?? 0,
			[
				"SECTION_BUTTONS" => (bool)($this->arResult["ID"] ?? false),
			]
		);

		$this->AddIncludeAreaIcons(
			CIBlock::GetComponentMenu(
				$APPLICATION->GetPublicShowMode(), $arButtons
			)
		);
	}

	/**
	 * выполняет действия после выполения компонента, например установка заголовков из кеша
	 */
	protected function executeEpilog()
	{
		global $APPLICATION;

		if ($this->arParams["SET_TITLE"] == "Y") {
			if (!empty($this->arResult["META_TITLE"])) {
				$APPLICATION->SetPageProperty("title", $this->arResult["META_TITLE"]);
			}
			if (!empty($this->arResult["PAGE_TITLE"])) {
				$APPLICATION->SetTitle($this->arResult["PAGE_TITLE"]);
			}
			if (!empty($this->arResult["META_DESCRIPTION"])) {
				$APPLICATION->SetPageProperty("description", $this->arResult["META_DESCRIPTION"]);
			}
		}

		if ($this->arParams["SET_SECTIONS_CHAIN"] == "Y" && !empty($this->arResult["NAV_CHAIN"])) {
			array_walk($this->arResult["NAV_CHAIN"], function ($section) use ($APPLICATION) {
				$APPLICATION->AddChainItem(
					$section["~IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"] ?? $section["~NAME"],
					$section["SECTION_PAGE_URL"] ?? ""
				);
			});
		}

		if ($this->arParams["SET_ELEMENT_CHAIN"] == "Y" && !empty($this->arResult["PAGE_TITLE"])) {
			$APPLICATION->AddChainItem($this->arResult["PAGE_TITLE"]);
		}

		$arReturn = &$this->arResult["__RETURN_VALUE"];
		$arReturn = $arReturn ?: [];

		$arReturn["ID"] = $this->arResult["ID"];
		$arReturn["IBLOCK_ID"] = $this->arResult["IBLOCK_ID"];
		$arReturn["ITEM"] = [];

		if (!empty($this->arResult["ITEM"])) {
			$arReturn["ITEM"] = $this->arResult["ITEM"];
		}

		unset($arReturn);

		if (!empty($this->arResult["ID"])) {
			CIBlockElement::CounterInc($this->arResult["ID"]);
		}

		$this->showEditButtons();
	}

	/**
	 * прерывает кеширование
	 */
	protected function abortDataCache()
	{
		$this->AbortResultCache();
	}

	/**
	 * @return array
	 */
	public function listKeysSignedParameters()
	{
		return [
			"IBLOCK_ID",
			"ELEMENT_CODE",
		];
	}

	/**
	 * @return array
	 */
	public function configureActions()
	{
		return [
			"getCounter" => [
				"prefilters"  => [],
				"postfilters" => [],
			],
		];
	}

	/**
	 * Возвращает количество просмотров текущего элемента
	 *
	 * @return array
	 * @throws ArgumentException
	 * @throws ObjectNotFoundException
	 * @noinspection PhpUnused
	 */
	public function getCounterAction()
	{
		$arFilter = [
			"IBLOCK_ID"     => $this->arParams["IBLOCK_ID"],
			"ACTIVE"        => "Y",
			"GLOBAL_ACTIVE" => "Y",
		];

		if ($this->arParams["ELEMENT_ID"]) {
			$arFilter["ID"] = $this->arParams["ELEMENT_ID"];
		} else {
			if ($this->arParams["ELEMENT_CODE"]) {
				$arFilter["CODE"] = $this->arParams["ELEMENT_CODE"];
			} else {
				throw new ArgumentException("Не указан символьный код или идентификатор элемента.");
			}
		}

		$dbElements = CIBlockElement::GetList(
			[],
			$arFilter,
			false,
			["nTopCount" => 1],
			[
				"ID",
				"IBLOCK_ID",
				"SHOW_COUNTER",
			]
		);

		if ($arElement = $dbElements->Fetch()) {
			return ["counter" => (int)$arElement["SHOW_COUNTER"]];
		}

		throw new ObjectNotFoundException("Элемент не найден.");
	}
}
