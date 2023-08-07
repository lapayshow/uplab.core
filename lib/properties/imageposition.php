<?

namespace Uplab\Core\Properties;


use Bitrix\Main\Loader;
use CIBlockElement;
use CJSCore;
use Uplab\Core\Traits\SingletonTrait;
use CIBlockSection;
use CFile;


/**
 * Отображает в админке картинку, на которой расположена точка.
 * Точку можно двигать. В поле записываются координаты точки в %
 */
class ImagePosition
{
	use SingletonTrait;

	const PROPERTY_USER_TYPE = "UplabImagePosition";
	const PROPERTY_ID        = "image.position";

	private $moduleId = "uplab.core";
	private $moduleSrc;
	private $moduleDir;
	private $cssDir;
	private $jsDir;
	private $tplPath;

	function __construct()
	{
		if (!Loader::includeModule("iblock")) return;
		if (!Loader::includeModule("uplab.core")) return;

		$this->moduleDir = getLocalPath("modules/{$this->moduleId}");
		$this->moduleSrc = $_SERVER["DOCUMENT_ROOT"] . $this->moduleDir;

		$this->cssDir = "/bitrix/css/{$this->moduleId}";
		$this->jsDir = "/bitrix/js/{$this->moduleId}";
		$this->tplPath = "{$this->moduleSrc}/include/tpl/" . self::PROPERTY_ID;

		IncludeModuleLangFile(
			$this->moduleSrc . '/properties/' . self::PROPERTY_ID . '.php'
		);
	}

	public static function getUserTypeDescription()
	{
		self::getInstance();

		return array(
			"PROPERTY_TYPE"             => "S",
			"USER_TYPE"                 => self::PROPERTY_USER_TYPE,
			"DESCRIPTION"               => "Свойство «Координаты точки на картинке»",
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
		$arProperty, $value
	) {
		return $value;
	}

	public static function ConvertFromDB(
		/** @noinspection PhpUnusedParameterInspection */
		$arProperty, $value
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
			<tr valign="top">
				<td>
					Дополнительный CSS для метки выбора<br>
					<em style="white-space: nowrap">
						(По умолч.: <strong>transform:&nbsp;translate(-50%,-50%);</strong>)
					</em>
				</td>
				<td><input type="text" size="50" name="' . $strHTMLControlName["NAME"] . '[pinCss]" value="' . $settings["pinCss"] . '"></td>
			</tr>
			';
	}

	public static function PrepareSettings($arProperty)
	{
		$imgUrl = "";

		if (empty($imgUrl) && is_array($arProperty["USER_TYPE_SETTINGS"])) {
			$imgUrl = trim(strip_tags($arProperty["USER_TYPE_SETTINGS"]["imgUrl"]));
		}
		$imgUrl = $imgUrl ?: "";

		if (empty($sectionImgCode) && is_array($arProperty["USER_TYPE_SETTINGS"])) {
			$sectionImgCode = trim(strip_tags($arProperty["USER_TYPE_SETTINGS"]["sectionImgCode"]));
		}

		if (empty($pinCss) && is_array($arProperty["USER_TYPE_SETTINGS"])) {
			$pinCss = trim(strip_tags($arProperty["USER_TYPE_SETTINGS"]["pinCss"]));
		}

		if (is_array($arProperty["USER_TYPE_SETTINGS"]) && $arProperty["USER_TYPE_SETTINGS"]["multiple"] === "Y") {
			$multiple = "Y";
		} else {
			$multiple = "N";
		}

		return compact("multiple", "imgUrl", "sectionImgCode", "pinCss");
	}

	public static function PrepareImageUrl($arProperty)
	{
		$settings = self::PrepareSettings($arProperty);
		$elementID = intval($_REQUEST["ID"]);

		/**
		 * Получаем из родительского раздела изображение,
		 * на которое будут ставиться точки.
		 * По умолчанию используется стандартное изображение раздела.
		 */
		if (empty($imgUrl) && !empty($settings["sectionImgCode"]) && !empty($elementID)) {
			Loader::includeModule("iblock");
			$sectionID = "";

			$res = CIBlockElement::GetList(
				[],
				[
					'IBLOCK_ID' => $arProperty["IBLOCK_ID"],
					'ID'        => $elementID,
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
						'IBLOCK_ID' => $arProperty["IBLOCK_ID"],
						'ID'        => $sectionID,
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

		// d(compact("arProperty", "value", "strHTMLControlName"), __METHOD__);
		$wrapperClass = "uplab-image-position-" . randString(6, "abcdefghijklnmopqrstuvwxyz");

		$html = "";
		$html .= "<div class=\"{$wrapperClass}\">";

		if ($v = $settings["pinCss"]) {
			$html .= "
			<style>.{$wrapperClass} .uplab-image-position .dot { {$v}; }</style>
			";
		}

		if (empty($settings["imgUrl"])) {
			$html .= "<div style='color: #aaa;'>";
			$html .= "Необходимо указать изображение!<br>";
			$html .= "Изображение из раздела будет загружено только после сохранения элемента.";
			$html .= "</div>";
		} else {
			$html .= "<div class=\"uplab-image-position\">";
			$html .= "  <img style=\"max-width: unset; max-height: 90vh;\" src=\"" . $settings["imgUrl"] . "\" draggable=\"false\">";
			$html .= "</div><br>";
			$html .= "<input type=\"text\" size=\"35\" name=\"" . $strHTMLControlName["VALUE"] . "\" ";
			$html .= "       style=\"text-align: center;\" ";
			$html .= "       value=\"" . $value["VALUE"] . "\">";
		}

		self::includeAssets();

		$html .= "</div>";

		return $html;
	}

	public static function includeAssets()
	{
		global $APPLICATION;

		# ====== предотвращение повторного вызова функции ====== >>>
		static $flag = false;
		if ($flag == true) return;
		$flag = true;
		# <<< ======================================================

		CJSCore::Init('jquery');

		$APPLICATION->SetAdditionalCSS(
			'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css'
		);
		/** @noinspection PhpDeprecationInspection */
		$APPLICATION->AddHeadScript('https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js');

		$APPLICATION->SetAdditionalCSS(self::getInstance()->cssDir . '/image-position.css');
		/** @noinspection PhpDeprecationInspection */
		$APPLICATION->AddHeadScript(self::getInstance()->jsDir . '/image-position.js');
	}

	function GetPropertyFieldHtmlMulty(
		/** @noinspection PhpUnusedParameterInspection */
		$arProperty, $value, $strHTMLControlName
	) {
		$html = '';

		return $html;
	}

	function GetAdminFilterHTML(
		/** @noinspection PhpUnusedParameterInspection */
		$arProperty, $strHTMLControlName
	) {
		$html = '';

		return $html;
	}

	function GetOptionsHtml(
		/** @noinspection PhpUnusedParameterInspection */
		$arProperty, $values, &$bWasSelect
	) {
		$options = '';

		return $options;
	}
}
