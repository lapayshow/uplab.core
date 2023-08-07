<?

namespace Uplab\Core\Components;

defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();

use Bitrix\Main;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Iblock;
use CBitrixComponent;
use CDBResult;
use CIBlock;
use CPageOption;
use Exception;
use Uplab\Core\Data\Pagination;
use Uplab\Core\Data\Registry\Registry;
use Uplab\Core\IblockHelper;
use Uplab\Core\Legacy\Users;


class IblockItemsComponent extends CBitrixComponent
{
	/**
	 * кешируемые ключи arResult
	 *
	 * @var array
	 */
	protected $cacheKeys = array(
		"ID",
		"SECTION",
		"SECTIONS",
		"SECTION_ID",
		"IBLOCK_ID",
		"TITLE",
		"__RETURN_VALUE",
	);

	/**
	 * дополнительные параметры, от которых должен зависеть кеш
	 *
	 * @var array
	 */
	protected $cacheAddon = [];

	/**
	 * модули, которые необходимо подключить
	 *
	 * @var array
	 */
	protected $dependModules = ["iblock", "uplab.core"];

	/**
	 * параметры, которые необходимо проверить
	 *
	 * @var array
	 */
	protected $requiredParams = [
		"int"   => [
			"IBLOCK_ID",
		],
		"isset" => [],
	];

	/**
	 * парамтеры постраничной навигации
	 *
	 * @var array
	 */
	protected $navParams = [];

	/**
	 * строка постраничной навигации
	 *
	 * @var array
	 */
	protected $navString = [];

	/**
	 * внешний фильтр
	 *
	 * @var array
	 */
	protected $arFilter = [];

	/**
	 * @var array
	 */
	protected $arSort = [];

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

		$params["COUNT_LIMIT"] = (int)$params["COUNT_LIMIT"];
		if ($params["COUNT_LIMIT"] <= 0 || $params["COUNT_LIMIT"] > 1000) {
			$params["COUNT_LIMIT"] = 10;
		}

		$params["PAGER_SIGNS_COUNT"] = (int)$params["PAGER_SIGNS_COUNT"];
		if ($params["PAGER_SIGNS_COUNT"] <= 0 || $params["PAGER_SIGNS_COUNT"] > 1000) {
			$params["PAGER_SIGNS_COUNT"] = 7;
		}

		if (isset($params["FILTER"]) && !empty($params["FILTER"])) {
			if ($params["FILTER"] instanceof Registry) {
				$this->arFilter = $params["FILTER"]->getAll();
				if (!is_array($this->arFilter)) $this->arFilter = [];
			} else {
				throw new ObjectNotFoundException("FILTER object not found");
			}
		}

		$this->arFilter = $this->arFilter ?? [];
		if (!empty($params["PARENT_SECTION_CODE"])) {
			$this->arFilter["SECTION_CODE"] = $params["PARENT_SECTION_CODE"];
			$this->arFilter["INCLUDE_SUBSECTIONS"] = $params["INCLUDE_SUBSECTIONS"] == "N"
				? "N"
				: "Y";
		}
		if (!empty($params["PARENT_SECTION"])) {
			$this->arFilter["SECTION_ID"] = $params["PARENT_SECTION"];
			$this->arFilter["INCLUDE_SUBSECTIONS"] = $params["INCLUDE_SUBSECTIONS"] == "N"
				? "N"
				: "Y";
		}
		if ($params["SECTION_GLOBAL_ACTIVE"] == "Y") {
			$this->arFilter["SECTION_GLOBAL_ACTIVE"] = $params["SECTION_GLOBAL_ACTIVE"];
		}

		if (isset($params["SORT"]) && !empty($params["SORT"])) {
			if ($params["SORT"] instanceof Registry) {
				$this->arSort = $params["SORT"]->getAll();
				if (!is_array($this->arSort)) $this->arSort = null;
			} else {
				throw new ObjectNotFoundException("SORT object not found");
			}
		} else {
			$this->arSort = array(
				$params["SORT_BY1"] => $params["SORT_ORDER1"],
				$params["SORT_BY2"] => $params["SORT_ORDER2"],
			);
		}

		$result = array(
			"DESIGN_MODE"       => $bDesignMode,
			"IBLOCK_ID"         => intval($params["IBLOCK_ID"]),
			"USE_FILTER"        => (!empty($this->arFilter) ? "Y" : "N"),
			"PAGER_TITLE"       => trim($params["PAGER_TITLE"]),
			"DETAIL_URL"        => trim($params["DETAIL_URL"]),
			"SECTION_URL"       => trim($params["SECTION_URL"]),
			"IBLOCK_URL"        => trim($params["IBLOCK_URL"]),
			"PAGER_TEMPLATE"    => trim($params["PAGER_TEMPLATE"]),
			"PAGER_SHOW_ALWAYS" => ($params["PAGER_SHOW_ALWAYS"] == "Y" ? "Y" : "N"),
			"SHOW_NAV"          => ($params["SHOW_NAV"] == "Y" ? "Y" : "N"),
			"SET_META"          => ($params["SET_META"] == "Y" ? "Y" : "N"),
			"SET_CHAIN"         => ($params["SET_CHAIN"] == "Y" ? "Y" : "N"),
			"COUNT_LIMIT"       => $params["COUNT_LIMIT"],
			"CACHE_GROUPS"      => ($params["CACHE_GROUPS"] == "Y" ? "Y" : "N"),
			"RETURN_ITEMS"      => ($params["RETURN_ITEMS"] == "Y" ? "Y" : "N"),
			"SET_STATUS_404"    => ($params["SET_STATUS_404"] == "Y" ? "Y" : "N"),
			"SECTIONS_SELECT"   => array_unique(
				array_merge(
					$params["SECTIONS_SELECT"] ?? [],
					[
						"DESCRIPTION",
						"SECTION_PAGE_URL",
					]
				)
			),
		);

		return array_merge($params, $result);
	}

	/**
	 * @param                  $iblock
	 * @param                  $id
	 * @param CBitrixComponent $component
	 * @param bool             $isSect
	 *
	 * @noinspection PhpUnused
	 */
	public function getEditButtons($iblock, $id, &$component, $isSect = false)
	{
		if ((int)$iblock <= 0) {
			return;
		}
		if (!$GLOBALS["USER"]->IsAuthorized()) {
			return;
		}

		try {
			$this->checkModules();
		} catch (Exception $e) {
			return;
		}

		if ($isSect) {
			$arButtons = CIBlock::GetPanelButtons(
				$iblock, 0, $id, array("SESSID" => false, "CATALOG" => true)
			);

			$edit = $arButtons["edit"]["edit_section"]["ACTION_URL"];
			$delete = $arButtons["edit"]["delete_section"]["ACTION_URL"];

			$component->AddEditAction($id, $edit, CIBlock::GetArrayByID($iblock, "SECTION_EDIT"));
			$component->AddDeleteAction($id, $delete, CIBlock::GetArrayByID($iblock, "SECTION_DELETE"),
				array("CONFIRM" => GetMessage("CT_BCSL_ELEMENT_DELETE_CONFIRM")));
		} else {
			$arButtons = CIBlock::GetPanelButtons(
				$iblock, $id, 0, array("SECTION_BUTTONS" => false, "SESSID" => false)
			);

			$edit = $arButtons["edit"]["edit_element"]["ACTION_URL"];
			$delete = $arButtons["edit"]["delete_element"]["ACTION_URL"];

			$component->AddEditAction($id, $edit, CIBlock::GetArrayByID($iblock, "ELEMENT_EDIT"));
			$component->AddDeleteAction($id, $delete, CIBlock::GetArrayByID($iblock, "ELEMENT_DELETE"),
				array("CONFIRM" => GetMessage("CT_BNL_ELEMENT_DELETE_CONFIRM")));
		}
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
				if (defined("BX_COMP_MANAGED_CACHE")) {
					global $CACHE_MANAGER;
					$CACHE_MANAGER->RegisterTag("iblock_id_" . $this->arParams["IBLOCK_ID"]);
				}

				if (empty($this->arResult["ITEMS"]) && $this->arParams["SET_STATUS_404"] === "Y") {
					$this->abortResultCache();

					Iblock\Component\Tools::process404(
						"",
						($this->arParams["SET_STATUS_404"] === "Y"),
						($this->arParams["SET_STATUS_404"] === "Y"),
						($this->arParams["SHOW_404"] === "Y"),
						$this->arParams["FILE_404"]
					);
				} else {
					$this->initEditButtons();
					$this->putDataToCache();
					$this->endDataCache();
				}
			}
			$this->includeComponentTemplate();
			$this->executeEpilog();

			if (isset($this->arResult["__RETURN_VALUE"])) {
				return $this->arResult["__RETURN_VALUE"];
			}

			return $this->arResult;

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
				throw new Main\LoaderException(
					Loc::getMessage("ITEMS_LIST_MODULE_NOT_FOUND") . " " . $module
				);
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
		if ($this->arParams["COUNT_LIMIT"] > 0) {
			CPageOption::SetOptionString("main", "nav_page_in_session", "N");
			if ($this->arParams["SHOW_NAV"] == "Y") {
				$this->navParams = array(
					"nPageSize" => $this->arParams["COUNT_LIMIT"],
				);
			} else {
				$this->navParams = array(
					"nPageSize" => $this->arParams["COUNT_LIMIT"],
				);
			}
		}

		if ($this->arParams["RETURN_ITEMS"] == "Y") {
			$this->cacheKeys[] = "ITEMS";
		}

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

		// сортировка влияет на кэш
		$this->cacheAddon[] = $this->arSort;

		if ($this->navParams) {
			$this->cacheAddon[] = CDBResult::GetNavParams($this->navParams);
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
		$this->arResult = array(
			"TITLE"          => "",
			"IBLOCK_ID"      => $this->arParams["IBLOCK_ID"],
			"ITEMS"          => [],
			"SECTIONS"       => [],
			"FILTER"         => $this->arFilter,
			"SORT"           => $this->arSort,
			"__RETURN_VALUE" => null,
		);

		$this->arResult["ITEMS"] = IblockHelper::getList(
			[
				"sort"          => $this->arSort,
				"filter"        => $this->arFilter,
				"iblock"        => $this->arParams["IBLOCK_ID"],
				"navParams"     => $this->navParams,
				"select"        => [
					"DETAIL_PAGE_URL",
					"PREVIEW_TEXT",
					"DETAIL_TEXT",
					"PREVIEW_PICTURE",
					"DETAIL_PICTURE",
					"DATE_ACTIVE_FROM",
					"DATE_ACTIVE_TO",
					// "*",
				],
				"properties"    => true,
				"editorButtons" => $this->arParams["DESIGN_MODE"],
			],
			false,
			$dbElements
		);

		if ($this->arParams["PREPARE_SECTIONS_FOR_ELEMENTS"] == "Y") {
			IblockHelper::prepareSectionsForElements($this->arResult);
		}

		$this->arResult["NAV_DATA"] = array(
			"NavPageCount"  => $dbElements->NavPageCount,
			"NavPageSize"   => $dbElements->NavPageSize,
			"NavNum"        => $dbElements->NavNum,
			"NavPageNomer"  => $dbElements->NavPageNomer,
			"NavPagerArray" => Pagination::init($dbElements, null, $this->arParams["PAGER_SIGNS_COUNT"])
				->getArray(),
		);

		$this->arResult["NAV_RESULT"] = $dbElements;
		$this->arResult["NAV_PAGER_ARRAY"] = $this->arResult["NAV_DATA"]["NavPagerArray"];

		unset($arItem);
		unset($dbElements);

		$this->prepareSection();

		if ($this->arParams["SET_TITLE"] == "Y") {
			$obIBlock = new Iblock\IblockTable();
			try {
				$dbIBlock = $obIBlock->getList(array(
					"filter" => array("ID" => $this->arParams["IBLOCK_ID"]),
					"select" => array("ID", "NAME"),
					"limit"  => 1,
				));
				if ($arIBlock = $dbIBlock->fetch()) {
					$this->arResult["TITLE"] = $arIBlock["NAME"];
				}
			} catch (Exception $e) {
			}
		}
	}

	/**
	 * подготовка данных по кнопкам эрмитажа для режима правки
	 */
	protected function initEditButtons()
	{
		if ($this->arParams["IBLOCK_ID"] <= 0) {
			return;
		}
		if (!$this->arParams["DESIGN_MODE"]) {
			return;
		}

		$arButtons = CIBlock::GetPanelButtons(
			$this->arParams["IBLOCK_ID"],
			0,
			0,
			array("SECTION_BUTTONS" => false, "SESSID" => false)
		);
		$this->arResult["ADD_LINK"] = $arButtons["edit"]["add_element"]["ACTION_URL"];

		if (!empty($this->arResult["ITEMS"])) {
			foreach ($this->arResult["ITEMS"] as &$arItem) {
				$arButtons = CIBlock::GetPanelButtons(
					$this->arParams["IBLOCK_ID"],
					$arItem["ID"],
					0,
					array("SECTION_BUTTONS" => false, "SESSID" => false)
				);
				$arItem["EDIT_LINK"] = $arButtons["edit"]["edit_element"]["ACTION_URL"];
				$arItem["DELETE_LINK"] = $arButtons["edit"]["delete_element"]["ACTION_URL"];
			}
		}
		unset($arItem);
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
	 * формирование набора кнопок для эрмитажа в режиме правки
	 */
	protected function showEditButtons()
	{
		global $APPLICATION;

		if ($this->arParams["IBLOCK_ID"] <= 0) {
			return;
		}
		if (!$this->arParams["DESIGN_MODE"]) {
			return;
		}

		$arButtons = CIBlock::GetPanelButtons(
			$this->arParams["IBLOCK_ID"],
			0,
			$this->arResult["SECTION_ID"],
			array("SECTION_BUTTONS" => true)
		);
		$this->AddIncludeAreaIcons(
			CIBlock::GetComponentMenu(
				$APPLICATION->GetPublicShowMode(), $arButtons
			)
		);
	}

	/**
	 * @throws ArgumentException
	 * @noinspection PhpUnused
	 */
	protected function prepareSection()
	{
		if (isset($this->arResult["SECTION_ID"])) return;

		if (!empty($this->arParams["SECTION_ID"])) {

			$sectFilter = [
				"ID" => (int)$this->arParams["SECTION_ID"],
			];

		} elseif (!empty($this->arFilter)) {

			if ($sectionCode = ($this->arFilter["SECTION_CODE"] ?? false)) {

				$sectFilter = [
					"CODE" => $sectionCode,
				];

			} elseif ($sectionId = ($this->arFilter["SECTION_ID"] ?? false)) {

				$sectFilter = [
					"ID" => $sectionId,
				];

			}

		}

		if (!empty($sectFilter)) {

			if (!empty($sectFilter["ID"])) {

				$this->arResult["SECTIONS"] = IblockHelper::getNavChain(
					$this->arParams["IBLOCK_ID"],
					$sectFilter["ID"],
					[
						"select" => $this->arParams["SECTIONS_SELECT"],
						"iprops" => true,
					]
				);

			} else {

				$sectFilter["ACTIVE"] = "Y";
				$sectFilter["IBLOCK_ID"] = $this->arParams["IBLOCK_ID"];

				$parentSection = IblockHelper::getSectionsList([
					"filter" => $sectFilter,
					"limit"  => 1,
				]);

				if (!empty($parentSection["ID"])) {
					$this->arResult["SECTIONS"] = IblockHelper::getNavChain(
						$this->arParams["IBLOCK_ID"],
						$parentSection["ID"],
						[
							"select" => $this->arParams["SECTIONS_SELECT"],
							"iprops" => true,
						]
					);
				}

			}

			if (!empty($this->arResult["SECTIONS"])) {
				$this->arResult["SECTION"] = end($this->arResult["SECTIONS"]);
				reset($this->arResult["SECTIONS"]);
			}

		}

		$this->arResult["SECTION_ID"] = $this->arResult["SECTION"]["ID"] ?? false;

	}

	/**
	 * выполняет действия после выполения компонента, например установка заголовков из кеша
	 */
	protected function executeEpilog()
	{
		global $APPLICATION;

		if ($this->arParams["SET_META"] == "Y") {
			if ($v = $this->arResult["SECTION"]["~IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"] ?? "") {
				$APPLICATION->SetTitle($v);

			}

			if ($v = $this->arResult["SECTION"]["~IPROPERTY_VALUES"]["SECTION_META_TITLE"] ?? "") {
				$APPLICATION->SetPageProperty("title", strip_tags($v));
			}
		}

		if ($this->arParams["SET_CHAIN"] == "Y" && !empty($this->arResult["SECTIONS"])) {
			array_walk($this->arResult["SECTIONS"], function ($section) use ($APPLICATION) {
				$APPLICATION->AddChainItem(
					$section["~IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"] ?? $section["~NAME"],
					$section["SECTION_PAGE_URL"] ?? ""
				);
			});
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
}
