<?php


namespace Uplab\Core\Component;


use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use CFileMan;
use CIBlockSection;
use Uplab\Core\Constant;
use Uplab\Core\Hermitage;
use Uplab\Core\IblockHelper;


trait ComponentParametersTrait
{

	/**
	 * Позволяет показать кстомную кнопку "Редактировать параметры компонента",
	 * даже если у компонента активирована настройка ["HIDE_ICONS" => "Y]
	 *
	 * Метод обязательно необходимо вызывать после инициализации шаблона
	 *
	 * Для корректной работы метода, в параметры компонента необходимо добавить путь к текущему файлу,
	 * а также номер компонента на странице, если на странице несколько одинаковых компонентов:
	 * [
	 *     "COMPONENT_CALL_PATH" => __FILE__,
	 *     "COMPONENT_CALL_NUMBER" => 1,
	 *     // ...
	 * ]
	 */
	public function initComponentEditAction()
	{
		if (Hermitage::isDesignMode() && ($v = ($this->arParams["COMPONENT_CALL_PATH"] ?? ""))) {
			$componentName = $this->getName();
			$template = $this->getTemplate();

			if ($template instanceof \CBitrixComponentTemplate) {

				$componentLineNumber = Hermitage::getLineNumberOfComponent(
					[
						"src_path"           => $v,
						"component_name"     => $componentName,
						"component_template" => $template->getName(),
						"component_number"   => $this->arParams["COMPONENT_CALL_NUMBER"] ?? 1,
					]
				);

				if (!empty($componentLineNumber)) {
					$this->arResult["COMPONENT_EDIT_URL"] = Hermitage::getUrl(
						"component",
						[
							"filePath"   => $v,
							"component"  => $componentName,
							"template"   => $template->getName(),
							"lineNumber" => $componentLineNumber,
							"noBackUrl"  => true,
						]
					);

					$this->arResult["COMPONENT_EDIT_AREA_ID"] = "{$componentName}:{$componentLineNumber}";
					$this->arResult["COMPONENT_EDIT_ATTRIBUTE"] =
						" id=\"" .
						$this->GetEditAreaId($this->arResult["COMPONENT_EDIT_AREA_ID"]) .
						"\" ";
				}

			}
		}
	}

	/*
	Примеры:

	$paramsArray = [
		"IMAGE" => [
			"NAME"              => 'Путь к файлу',
			"TYPE"              => "FILE",
			"COLS"              => 30,
			"FD_TARGET"         => "F",
			"FD_EXT"            => 'jpg,jpeg,gif,png,svg',
			"FD_UPLOAD"         => true,
			"FD_USE_MEDIALIB"   => true,
			"FD_MEDIALIB_TYPES" => array('image'),
			'REFRESH'           => 'Y',
		],
	];
	*/
	/**
	 * Вызывается в файле .paramterers.php для инициализации параметров компонента
	 * Принимает массив параметров в упрощенной форме и возвращает их в подробном формате
	 *
	 * @param $paramsArray
	 *
	 * @return array
	 */
	public static function initParameters($paramsArray)
	{
		$arTemplateParameters = array();

		foreach ($paramsArray as $k => $val) {

			$param = [
				"COLS"   => 60,
				"PARENT" => 'DATA_SOURCE',
			];

			if (is_array($val)) {

				switch ($val["TYPE"]) {

					case "VISUAL_EDITOR":

						if (is_callable(static::class . "::getComponentClassPath")) {

							$settingsPath = static::getComponentClassPath() . "/settings";

							$val["TYPE"] = "CUSTOM";

							$param["JS_FILE"] = "{$settingsPath}/visualEditor.js";
							$param["JS_EVENT"] = 'UCTB_OnCustomVisualEditorInit';
							$param["JS_DATA"] = json_encode([
								"phpScript" => "{$settingsPath}/visualEditor.php",
							]);

						} else {
							$val["TYPE"] = "TEXT";
						}

						$param["DEFAULT"] = "";
						$param["REFRESH"] = "Y";
						$param = array_merge($param, $val);

						break;

					default:
						$param = array_merge($param, $val);
						break;

				}

			} else {
				$param['NAME'] = $val;
			}
			$arTemplateParameters[$k] = $param;
		}

		return $arTemplateParameters;
	}

	/**
	 * @param string $baseKey
	 * @param mixed  $paramKeysForCheck
	 * @param        $arCurrentValues
	 *
	 * @return int
	 */
	public static function getNumberOfDynamicParams($baseKey, $paramKeysForCheck, &$arCurrentValues)
	{
		if (empty($baseKey) || empty($paramKeysForCheck)) return 0;

		$paramKeysForCheck = (array)$paramKeysForCheck;
		$maxIndex = 0;

		$pattern = sprintf("/%s(\d+)_(.+)/", $baseKey);

		foreach ($arCurrentValues as $key => &$value) {

			if (!empty($value) && preg_match($pattern, $key, $match)) {

				list(, $index, $paramKey) = $match;
				$index++;

				if ($index <= $maxIndex) continue;

				if (in_array($paramKey, $paramKeysForCheck)) {
					$maxIndex = $index;
				}
			}

			unset($value);
		}

		return $maxIndex + 1;
	}

	public static function prepareCustomParametersGroups(&$arComponentParameters, &$templateProperties)
	{
		$customGroups = [];
		foreach ($templateProperties as &$templateProperty) {
			if (isset($templateProperty["CUSTOM_PARENT"])) {
				$group = (array)array_filter([
					"CODE" => $templateProperty["CUSTOM_PARENT"][0],
					"NAME" => $templateProperty["CUSTOM_PARENT"][1],
				]);

				if (count($group) < 2) continue;

				$templateProperty["PARENT"] = $group["CODE"];

				if (empty($customGroups[$group["CODE"]])) {
					$customGroups[$group["CODE"]] = [
						"NAME" => $group["NAME"],
					];
				}
			}
		}

		$arComponentParameters["GROUPS"] = array_merge(
			(array)$arComponentParameters["GROUPS"],
			(array)$customGroups
		);
	}

	/**
	 * Добавить группу множественный параметров в настройки компонента
	 *
	 * @param array $paramsArray
	 * @param array $arCurrentValues
	 * @param array $groupParams
	 * @param array $appendParams
	 * @param int   $numberOfItems
	 */
	public static function addDynamicParameters(
		&$paramsArray,
		&$arCurrentValues,
		$groupParams,
		$appendParams,
		$numberOfItems = null
	) {
		if (empty($groupParams["CODE"])) return;

		$keyPattern = "%s%s_%s";
		$namePattern = "[%s #%02d]: %s";

		$sectionKeyPattern = "%s_%s";
		$sectionNamePattern = "[%s #%02d]";

		$checkKey = key($appendParams);

		if (empty($numberOfItems)) {
			$numberOfItems = self::getNumberOfDynamicParams($groupParams["CODE"], $checkKey, $arCurrentValues);
		}

		// die("<pre>" . print_r(compact("n"), true));

		for ($i = 0; $i < $numberOfItems; $i++) {
			foreach ($appendParams as $key => $appendParam) {
				$paramKey = sprintf($keyPattern, $groupParams["CODE"], $i, $key);
				$appendParam["NAME"] = sprintf($namePattern, $groupParams["NAME"], $i + 1, $appendParam["NAME"]);

				$appendParam["CUSTOM_PARENT"] = [
					sprintf($sectionKeyPattern, $groupParams["CODE"], $i),
					sprintf($sectionNamePattern, $groupParams["NAME"], $i + 1),
				];

				if ($checkKey == $key) {
					$appendParam["REFRESH"] = "Y";
				}

				// вызываем коллбэк-модификатр
				if (is_callable($appendParam["MODIFIER"])) {
					$appendParam = call_user_func($appendParam["MODIFIER"], $i, $paramKey, $keyPattern, $appendParam);
					if (empty($appendParam)) continue;
					unset($appendParam["MODIFIER"]);
				}

				$paramsArray[$paramKey] = $appendParam;
			}
		}
	}

	protected function initEditorParameters(&$params)
	{
		global $APPLICATION;

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
	}

	/**
	 * "Парсит" набор динамических компонентов в массив.
	 * Сохраняет результат в массиве ``$arResult["PARSED_PARAMS"]``
	 *
	 * Пример использования в шаблоне компонента:
	 *
	 * CBitrixComponent::includeComponentClass("uplab.core:template.block");
	 * TemplateBlock::parseDynamicGroupFromParams("ITEM", $arParams, $arResult);
	 *
	 * @param $groupCode
	 * @param $arParams
	 * @param $arResult
	 */
	public static function parseDynamicGroupFromParams($groupCode, &$arParams, &$arResult = false)
	{
		$pattern = "/~{$groupCode}(\d+)_(.+)/";

		$arParsedParams = [];

		foreach ($arParams as $key => &$value) {

			if (preg_match($pattern, $key, $match)) {
				$arParsedParams[$match[1]][$match[2]] = $value;
			}

			unset($value);

		}

		$arParsedParams = array_filter($arParsedParams, function ($element) {
			$hasNonEmptyValue = false;
			foreach ($element as $value) {
				if (!empty($value)) {
					$hasNonEmptyValue = true;
					break;
				}
			}

			return $hasNonEmptyValue;
		});

		$result = array_values($arParsedParams);

		if ($arResult === false) {
			return $result;
		} else {
			$arResult["PARSED_PARAMS"][$groupCode] = $result;
		}

		return null;
	}

	/**
	 * Метод пытается получить SITE_ID из одного из доступных источников
	 *
	 * @return mixed|string
	 */
	public static function guessSiteId()
	{
		if (!empty($_REQUEST["site"])) {
			$siteId = htmlspecialchars($_REQUEST["site"]);
		} elseif (!empty($_REQUEST["src_site"])) {
			$siteId = htmlspecialchars($_REQUEST["src_site"]);
		} else {
			$siteId = SITE_ID;
		}

		return $siteId;
	}

	/**
	 * Принимает массив ["VALUE" => (int)$iblockId, "TITLE" => (string)$iblockName]
	 * Массив может быть получен методом Constant::getArray()
	 *
	 * @param $iblock
	 *
	 * @return array|bool|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getParentSectionSelection($iblock)
	{
		if (empty($iblock["VALUE"])) return false;
		if (empty($iblock["TITLE"])) $iblock["TITLE"] = "#{$iblock["VALUE"]}";

		$sections = [];

		$res = IblockHelper::getSectionsList(
			[
				"order"        => [
					"LEFT_MARGIN" => "ASC",
					"NAME"        => "ASC",
				],
				"filter"       => [
					"IBLOCK_ID"     => $iblock["VALUE"],
					"ACTIVE"        => "Y",
					"GLOBAL_ACTIVE" => "Y",
					"!CODE"         => false,
				],
				"select"       => [
					"ID",
					"IBLOCK_ID",
					"NAME",
					"CODE",
					"DEPTH_LEVEL",
				],
				"returnObject" => true,
			]
		);

		while ($item = $res->Fetch()) {
			$sections[$item["CODE"]] =
				str_repeat(
					"• ",
					max($item["DEPTH_LEVEL"] - 1, 0)
				) .
				$item["NAME"] .
				" [{$item["CODE"]}]";
		}

		if (!empty($sections)) {
			$sections =
				[
					"" => Loc::getMessage("COMPONENT_PARAMETERS_PARENT_SECTION_DEFAULT"),
				]
				+
				$sections;

			return [
				"NAME"    => Loc::getMessage("COMPONENT_PARAMETERS_PARENT_SECTION_CODE", [
					"#IBLOCK_NAME#" => $iblock["TITLE"],
				]),
				"TYPE"    => "LIST",
				"VALUES"  => $sections,
				"PARENT"  => "DATA_SOURCE",
				"DEFAULT" => "",
			];
		}

		return null;
	}

	public static function prepareParentSectionSelection(
		array &$arComponentParameters,
		array $templateProperties,
		array $arCurrentValues
	) {
		if (isset($templateProperties["PARENT_SECTION_CODE"])) return;
		if (empty($arCurrentValues["IBLOCK_ID"])) return;

		$siteId = self::guessSiteId();
		$iblockId = intval($arCurrentValues["IBLOCK_ID"]);
		$iblock = false;

		if (empty($iblockId)) {
			if (preg_match("/=\{(.+_IBLOCK)\}/", $arCurrentValues["IBLOCK_ID"], $matches)) {
				$iblock = Constant::getArray($siteId)[$matches[1]] ?? false;
			}
		} else {
			$iblock = [
				"VALUE" => $iblockId,
				"TITLE" => "#{$iblockId}",
			];
		}

		if (empty($iblock)) return;

		if ($v = self::getParentSectionSelection($iblock)) {
			$arComponentParameters["PARAMETERS"]["PARENT_SECTION_CODE"] = $v;
		}
	}

}
