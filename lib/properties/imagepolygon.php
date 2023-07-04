<?

namespace Uplab\Core\Properties;


use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use CIBlockElement;
use CJSCore;
use Uplab\Core\Traits\SingletonTrait;
use CIBlockSection;
use CFile;


/**
 * Отображает в админке картинку, на которой можно рисовать путь
 */
class ImagePolygon
{
	use SingletonTrait;

	const PROPERTY_USER_TYPE = "UplabImagePolygon";
	const PROPERTY_ID        = "image.polygon";

    public $moduleId = "uplab.core";
    public $moduleSrc;
    public $moduleDir;
    public $cssDir;
    public $jsDir;
    public $tplPath;

	function __construct()
	{
		/** @noinspection PhpUnhandledExceptionInspection */
		if (!Loader::includeModule("iblock")) return;

		/** @noinspection PhpUnhandledExceptionInspection */
		if (!Loader::includeModule("uplab.core")) return;

		$this->moduleDir = getLocalPath("modules/{$this->moduleId}");
		$this->moduleSrc = $_SERVER["DOCUMENT_ROOT"] . $this->moduleDir;

		$this->cssDir = "/bitrix/css/{$this->moduleId}";
		$this->jsDir = "/bitrix/js/{$this->moduleId}";
		$this->tplPath = "{$this->moduleSrc}/include/tpl/" . self::PROPERTY_ID;

		IncludeModuleLangFile(
			$this->moduleSrc . "/properties/" . self::PROPERTY_ID . ".php"
		);
	}

	public static function getUserTypeDescription()
	{
		self::getInstance();

		return array(
			"PROPERTY_TYPE"             => "S",
			"USER_TYPE"                 => self::PROPERTY_USER_TYPE,
			"DESCRIPTION"               => "Свойство «Разметка полигона на картинке»",
			"GetPropertyFieldHtml"      => array(self::class, "GetPropertyFieldHtml"),
			"GetPropertyFieldHtmlMulty" => array(self::class, "GetPropertyFieldHtmlMulty"),
			"GetPublicEditHTML"         => array(self::class, "GetPropertyFieldHtml"),
			"GetPublicEditHTMLMulty"    => array(self::class, "GetPropertyFieldHtmlMulty"),
			"GetAdminFilterHTML"        => array(self::class, "GetAdminFilterHTML"),
			"PrepareSettings"           => array(self::class, "PrepareSettings"),
			"GetSettingsHTML"           => array(self::class, "GetSettingsHTML"),
			"ConvertToDB"               => array(self::class, "ConvertToDB"),
			"ConvertFromDB"             => array(self::class, "ConvertFromDB"),
		);
	}

	public static function ConvertToDB(
		/** @noinspection PhpUnusedParameterInspection */
		$arProperty,
		$value
	) {
		return $value;
	}

	public static function ConvertFromDB(
		/** @noinspection PhpUnusedParameterInspection */
		$arProperty,
		$value
	) {
		return $value;
	}

	public static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
	{
		$settings = self::PrepareSettings($arProperty);

		$arPropertyFields = array(
			"HIDE" => array("ROW_COUNT", "COL_COUNT", "MULTIPLE_CNT"),
		);

		return '
			<tr valign="top">
				<td>URL картинки:</td>
				<td>
					<input type="text" 
					       size="50" 
					       name="' . $strHTMLControlName["NAME"] . '[imgUrl]" value="' . $settings["imgUrl"] . '">
				</td>
			</tr>
			<tr valign="top">
				<td>Код свойства раздела,<br>в котором хранится изображение</td>
				<td>
					<input type="text" 
					       size="50" 
					       name="' . $strHTMLControlName["NAME"] . '[sectionImgCode]" value="' . $settings["sectionImgCode"] . '">
				</td>
			</tr>
			';
	}

	public static function PrepareSettings($arProperty)
	{
		if (empty($imgUrl) && is_array($arProperty["USER_TYPE_SETTINGS"])) {
			$imgUrl = trim(strip_tags($arProperty["USER_TYPE_SETTINGS"]["imgUrl"]));
		}
		$imgUrl = $imgUrl ?: "";

		if (empty($sectionImgCode) && is_array($arProperty["USER_TYPE_SETTINGS"])) {
			$sectionImgCode = trim(strip_tags($arProperty["USER_TYPE_SETTINGS"]["sectionImgCode"]));
		} else {
			$sectionImgCode = "PICTURE";
		}

		if (is_array($arProperty["USER_TYPE_SETTINGS"]) && $arProperty["USER_TYPE_SETTINGS"]["multiple"] === "Y") {
			$multiple = "Y";
		} else {
			$multiple = "N";
		}

		return compact("multiple", "imgUrl", "sectionImgCode");
	}

	public static function PrepareImageUrl($arProperty)
	{
		$settings = self::PrepareSettings($arProperty);
		$elementID = intval($_REQUEST["ID"]);

		/**
		 * Получаем из родительского раздела изображение,
		 * на которое будут ставиться точки.
		 * По умолчанию используется стандартное изображение раздела (`PICTURE`).
		 */
		if (empty($imgUrl) && !empty($settings["sectionImgCode"]) && !empty($elementID)) {
			/** @noinspection PhpUnhandledExceptionInspection */
			Loader::includeModule("iblock");

			$sectionID = "";

			$res = CIBlockElement::GetList(
				[],
				[
					"IBLOCK_ID" => $arProperty["IBLOCK_ID"],
					"ID"        => $elementID,
				],
				false,
				["nTopCount" => 1],
				["ID", "NAME", "IBLOCK_SECTION_ID"]
			);
			if ($element = $res->Fetch()) {
				$sectionID = $element["IBLOCK_SECTION_ID"];
			}

			if (!empty($sectionID)) {
				$res = CIBlockSection::GetList(
					false,
					[
						"IBLOCK_ID" => $arProperty["IBLOCK_ID"],
						"ID"        => $sectionID,
					],
					[],
					[$settings["sectionImgCode"]]
				);
				if ($section = $res->Fetch()) {
					if (!empty($section[$settings["sectionImgCode"]])) {
						$arImg = CFile::GetFileArray($section[$settings["sectionImgCode"]]);
						if (!empty($arImg["SRC"])) $settings["imgUrl"] = $arImg["SRC"];
					}
				}
			}
		}

		return $settings;
	}

	public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		$settings = self::PrepareImageUrl($arProperty);

		self::includeAssets();

		$wrapperClass = "uplab-image-polygon-" . randString(6, "abcdefghijklnmopqrstuvwxyz");

		$html = "";
		$html .= "<div class=\"{$wrapperClass}\">";

		if (empty($settings["imgUrl"])) {

			$html .= "<div style='color: #aaa;'>";
			$html .= "Необходимо указать изображение!<br>";
			$html .= "Изображение из раздела будет загружено только после сохранения элемента.";
			$html .= "</div>";

		} else {
			ob_start(); ?>

			<!--suppress HtmlFormInputWithoutLabel -->
			<textarea name="<?= $strHTMLControlName["VALUE"] ?>"
			          class="canvas-area"
			          data-image-url="<?= $settings["imgUrl"] ?>"
			          cols="70"
			          rows="4"
			          style="text-align: center;"
			><?= $value["VALUE"] ?></textarea>

			<div class="canvas-placeholder"></div>

			<script>
				window.initCanvasResize &&
				window.initCanvasResize('.<?=$wrapperClass?>');
			</script>

			<? $html .= ob_get_clean();
		}


		$html .= "</div>";

		return $html;
	}

	public static function includeAssets()
	{
		global $APPLICATION;

		# ====== предотвращение повторного вызова функции ====== >>>
		static $flag = false;
		if ($flag) return;
		$flag = true;
		# <<< ======================================================

		CJSCore::Init("jquery");

		/** @noinspection PhpDeprecationInspection */
		$APPLICATION->AddHeadScript(self::getInstance()->jsDir . "/image-polygon.js");
	}

	public static function bindEvents()
	{
		$event = EventManager::getInstance();

		$event->addEventHandler(
			"iblock", "OnIBlockPropertyBuildList",
			[self::class, "getUserTypeDescription"]
		);

//		$event->addEventHandler(
//			"main", "OnProlog",
//			[self::class, "setInputValues"]
//		);
	}


    public static function setInputValues($values)
    {
        if (!array_key_exists(self::PROPERTY_ID . "_values", $_REQUEST)) return;
        $values = json_decode($_REQUEST[self::PROPERTY_ID . "_values"]);
        if (empty($values)) return;
        $string = "";

        foreach ($values as $key => $value) {
            $string .= "window.__inp = document.querySelector('[name=\"$key\"]');";
            $string .= "window.hasOwnProperty('__inp') && (__inp.value = '{$value}');";
        }

        /** @noinspection JSUnresolvedVariable */
        /** @noinspection BadExpressionStatementJS */
        Asset::getInstance()->addString(str_replace(
            "__STRING__", $string,
            "<script>BX.ready(function() { __STRING__ });</script>"
        ));
        die("<pre>" . print_r($values, true));
    }

	function GetPropertyFieldHtmlMulty(
		/** @noinspection PhpUnusedParameterInspection */
		$arProperty,
		$value,
		$strHTMLControlName
	) {
		$html = "";

		return $html;
	}

	function GetAdminFilterHTML(
		/** @noinspection PhpUnusedParameterInspection */
		$arProperty,
		$strHTMLControlName
	) {
		$html = "";

		return $html;
	}

	function GetOptionsHtml(
		/** @noinspection PhpUnusedParameterInspection */
		$arProperty,
		$values,
		&$bWasSelect
	) {
		$options = "";

		return $options;
	}
}
