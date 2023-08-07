<?

namespace Uplab\Core\Components;


use Bitrix\Main\AccessDeniedException;
use Bitrix\Main\Application;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Uplab\Core\Helper;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Page\Asset;
use CBitrixComponent;
use CFileMan;
use CIBlock;
use Exception;
use Uplab\Core\Component\ComponentParametersTrait;
use Uplab\Core\IblockHelper;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();


class TemplateBlock extends CBitrixComponent
{
	protected $cacheKeys = [
		"ID",
		"NAME",
		"TEMPLATE_DATA",
		"RENDER_TEMPLATE_PATH",
		"__RETURN_VALUE",
	];

	protected $dependModules = ["fileman", "uplab.core"];

	/**
	 * дополнительные параметры, от которых должен зависеть кеш
	 *
	 * @var array
	 */
	protected $additionalCacheID = [];

	/**
	 * Коллекция ошибок работы компонента
	 *
	 * @var ErrorCollection $errors
	 */
	protected $errors = [];

	/**
	 * Методы для использования в файле .parameters.php
	 */
	use ComponentParametersTrait;

	public static function getComponentClassPath()
	{
		static $path = false;

		if ($path === false) {
			$path = str_replace(Application::getDocumentRoot(), "", __DIR__);
		}

		return $path;
	}

	public function onPrepareComponentParams($params)
	{
		global $APPLICATION;

		$this->errors = new ErrorCollection();

		foreach ($this->dependModules as $module) {
			if (!Loader::includeModule($module)) {
				ShowError("$module not found");
			}
		}

		$params["SITE_ID"] = SITE_ID;
		if ($params["CACHE_FOR_PAGE"] == "Y") {
			$params["CUR_PAGE"] = $APPLICATION->GetCurPage(false);
		}

		if ($APPLICATION->GetShowIncludeAreas()) {
			echo "<div style='display: none;'>";
			CFileMan::AddHTMLEditorFrame("", "", "", "");
			echo "</div>";

			Asset::getInstance()->addString("<style>.bxcompprop-cont-table-r textarea {min-height: 250px;}</style>");
		}

		$params["TEMPLATE_DATA"] = empty($params["TEMPLATE_DATA"]) ? [] : $params["TEMPLATE_DATA"];

		$params["EDIT_MODE"] = $APPLICATION->GetShowIncludeAreas() ? "Y" : "N";

		$params["DELAY"] = $params["DELAY"] == "Y" ? "Y" : "N";

		if ($params["NO_DELAY_ON_EDIT"] == "Y" && $params["EDIT_MODE"] == "Y") {
			$params["DELAY"] = "N";
		}

		if (empty($params["CACHE_TYPE"])) {
			$params["CACHE_TYPE"] = "N";
		}

		if ($params["EDIT_MODE"] == "Y") {
			$params["CACHE_TYPE"] = "N";
		}

		if (
			!isset($params["CACHE_TIME"])
			||
			(
				empty($params["CACHE_TIME"]) &&
				isset($params["CACHE_TYPE"]) &&
				(($params["CACHE_TYPE"] == "A") || ($params["CACHE_TYPE"] == "Y"))
			)
		) {
			if (defined("CACHE_TIME")) {
				$params["CACHE_TIME"] = CACHE_TIME;
			} else {
				$params["CACHE_TIME"] = 360000;
			}
		} else {
			$params["CACHE_TIME"] = (int)$params["CACHE_TIME"];
		}

		if (empty($params["CACHE_TIME"])) {
			$params["CACHE_TYPE"] = "N";
		}

		return $params;
	}

	public function executeComponent()
	{
		try {

			$this->checkRequiredParams();

			if ($this->arParams["DELAY"] == "Y") {
				\Bitrix\Main\Data\StaticHtmlCache::getInstance()->markNonCacheable();

				$cb = [$this, "executeTemplate"];

				$GLOBALS["APPLICATION"]->AddBufferContent(function () use ($cb) {
					ob_start();

					call_user_func($cb);

					return ob_get_clean();
				});
			} else {
				if ($this->arParams["AJAX_BUFFER"] == "Y") {
					$this->arParams["IS_AJAX"] = Helper::ajaxBuffer();
				}

				$this->executeProlog();
				$this->__includeComponent();
				$this->executeTemplate();

				if ($this->arParams["AJAX_BUFFER"] == "Y") {
					Helper::ajaxBuffer(false);
				}
			}

			return $this->arResult["__RETURN_VALUE"];

		} catch (Exception $exception) {
			$this->errors->setError(new Error($exception->getMessage()));
		}

		$this->showErrorsIfAny();

		return false;
	}

	/**
	 * Проверяет выполнение всех необходимых условий для работы компонента
	 *
	 * @throws AccessDeniedException
	 * @throws Exception
	 */
	protected function checkRequiredParams()
	{
		// if (!$this->arParams["USER_ID"]) {
		// 	throw new AccessDeniedException("Access denied.");
		// }
		// $user = UserTable::getById($this->arParams["USER_ID"])->fetchObject();
		// if (!$user) {
		// 	throw new Exception("User does not exists.");
		// }
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

		return !($this->StartResultCache(false, $this->additionalCacheID));
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
	 * выполяет действия перед кешированием
	 */
	protected function executeProlog()
	{
	}

	/**
	 * выполняет действия после выполения компонента, например установка заголовков из кеша
	 */
	protected function executeEpilog()
	{
	}

	protected function prepareResult()
	{
		global $APPLICATION;

		$this->arResult["IBLOCK"] = [];
		$this->arResult["SECTION"] = [];

		if ($this->arParams["IBLOCK_ID"]) {
			$this->arResult["IBLOCK"] = IblockTable::getById($this->arParams["IBLOCK_ID"])->fetch();

			if (($iblock = $this->arResult["IBLOCK"]["ID"])) {
				if ($section = $this->arParams["IBLOCK_SECTION"]) {
					$filter = [
						"IBLOCK_ID" => $iblock,
						"CODE"      => $section,
					];
				} elseif ($section = $this->arParams["IBLOCK_SECTION_ID"]) {
					$filter = [
						"IBLOCK_ID" => $iblock,
						"ID"        => $section,
					];
				}

				if (!empty($filter)) {
					$this->arResult["SECTION"] = IblockHelper::getSectionsList([
						"filter" => $filter,
						"select" => [
							"NAME",
							"IBLOCK_SECTION_ID",
						],
						"limit"  => 1,
					], false);
				}

				if ($APPLICATION->GetShowIncludeAreas()) {
					$this->abortResultCache();

					$arButtons = CIBlock::GetPanelButtons(
						$iblock,
						0,
						(int)$this->arResult["SECTION"]["ID"] ?: 0,
						array(
							"SECTION_BUTTONS" => !$this->arResult["SECTION"]["ID"],
						)
					);

					$icons = CIBlock::GetComponentMenu($APPLICATION->GetPublicShowMode(), $arButtons);

					// d($icons, __FILE__);

					$this->addIncludeAreaIcons($icons);

					// foreach ($icons as $icon) {
					// 	$this->addIncludeAreaIcon($icon);
					// }
				}
			}
		}

		$this->arResult["TEMPLATE_DATA"] = $this->arParams["TEMPLATE_DATA"];
	}

	protected function executeTemplate()
	{
		if ($this->arParams["DELAY"] == "Y") {
			$this->setFrameMode(false);
		}

		if (!$this->readDataFromCache()) {
			$this->prepareResult();

			$this->initComponentTemplate();
			$this->initComponentEditAction();

			$this->putDataToCache();
			$this->includeComponentTemplate();
			$this->endResultCache();
		}

		$this->executeEpilog();
	}

	/**
	 * Отображает ошибки, возникшие при работе компонента, если они есть
	 */
	protected function showErrorsIfAny()
	{
		if ($this->errors->count()) {
			foreach ($this->errors as $error) {
				ShowError($error);
			}
		}
	}
}
