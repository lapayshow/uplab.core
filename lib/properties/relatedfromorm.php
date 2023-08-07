<?

namespace Uplab\Core\Properties;


use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Web\Uri;
use DigitalWand\AdminHelper\Helper\AdminBaseHelper;
use DigitalWand\AdminHelper\Helper\AdminInterface;
use Uplab\Core\Traits\SingletonTrait;


/**
 * Отображает в админке элементы ORM сущности,
 * связанные с данным элементом ИБ
 */
class RelatedFromORM
{
	use SingletonTrait;

	const PROPERTY_USER_TYPE = "UplabRelatedFromOrm";
	const PROPERTY_ID        = "relatedFromOrm";

	private $MODULE_ID = "uplab.core";
	private $moduleSrc;
	private $moduleDir;

	function __construct()
	{
		$this->moduleDir = getLocalPath("modules/{$this->MODULE_ID}");
		$this->moduleSrc = $_SERVER["DOCUMENT_ROOT"] . $this->moduleDir;

		IncludeModuleLangFile($this->moduleSrc . '/properties/' . self::PROPERTY_ID . '.php');
	}

	public static function bindEvents()
	{
		$event = EventManager::getInstance();

		$event->addEventHandler(
			"iblock", "OnIBlockPropertyBuildList",
			[self::class, "getUserTypeDescription"]
		);

		$event->addEventHandler(
			"main", "OnProlog",
			[self::class, "setInputValues"]
		);
	}

	public static function setInputValues()
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
		// die("<pre>" . print_r($values, true));
	}

	public static function getUserTypeDescription()
	{
		self::getInstance();

		return array(
			"PROPERTY_TYPE"        => "S",
			"USER_TYPE"            => self::PROPERTY_USER_TYPE,
			"DESCRIPTION"          => Loc::getMessage(self::PROPERTY_ID . "_PROPERTY_NAME"),
			"GetPropertyFieldHtml" => [self::class, "GetPropertyFieldHtml"],
			"PrepareSettings"      => [self::class, "PrepareSettings"],
			"GetSettingsHTML"      => [self::class, "GetSettingsHTML"],
		);
	}


	public static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
	{
		$settings = self::PrepareSettings($arProperty);

		$arPropertyFields = array(
			"HIDE" => array("ROW_COUNT", "COL_COUNT", "MULTIPLE_CNT"),
		);

		return Loc::getMessage(self::PROPERTY_ID . "_SETTINGS_HTML", [
			"#CONTROL_NAME#" => $strHTMLControlName["NAME"],
			"#className#"    => $settings["className"],
			"#relatedField#" => $settings["relatedField"],
			"#addButton#"    => $settings["addButton"],
			"#listButton#"   => $settings["listButton"],
		]);
	}

	public static function PrepareSettings($arProperty)
	{
		$className = "";
		if (is_array($arProperty["USER_TYPE_SETTINGS"])) {
			$className = trim(strip_tags($arProperty["USER_TYPE_SETTINGS"]["className"]));
			if (!class_exists($className)) $className = "";
		}

		return array(
			"multiple"     => "N",
			"className"    => $className,
			"relatedField" => $arProperty["USER_TYPE_SETTINGS"]["relatedField"],
			"addButton"    => $arProperty["USER_TYPE_SETTINGS"]["addButton"],
			"listButton"   => $arProperty["USER_TYPE_SETTINGS"]["listButton"],
		);
	}

	public static function getElementID(
		/** @noinspection PhpUnusedParameterInspection */
		$arProperty, $value, $strHTMLControlName
	) {
		if (!($iblock = $arProperty["PROPERTY_VALUE_ID"])) {
			preg_match(
				"~PROP\[{$arProperty["ID"]}\]\[(\d+):{$arProperty["ID"]}\]\[VALUE\]~",
				$strHTMLControlName["VALUE"],
				$matches
			);

			$iblock = $matches[1];
		}

		return $iblock ?: intval($_REQUEST["ID"]);
	}

	public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		/** @var AdminInterface $interface */
		/** @var AdminBaseHelper $helper */


		ob_start();
		$settings = self::PrepareSettings($arProperty);
		if (empty($settings["className"])) {
			return "Ошибка. Неверно указан класс сущности.";
		}
		$elementID = self::getElementID($arProperty, $value, $strHTMLControlName);
		$field = $settings["relatedField"];


		$className = $settings["className"];
		$interface = new $className;
		$helpers = $interface->helpers();


		$helper = $helpers[0];
		$listUrl = $helper::getUrl();
		$listUrl = (new Uri($listUrl))->addParams([
			"find_{$field}"      => $elementID,
			"set_filter"         => "Y",
			"adm_filter_applied" => 0,
		])->getUri();


		$helper = $helpers[1];
		$editUrl = $helper::getUrl();
		$editUrl = (new Uri($editUrl))->addParams([
			self::PROPERTY_ID . "_values" => json_encode([
				"FIELDS[{$field}]" => $elementID,
			]),
		])->getUri();


		?>
		<div class="adm-filter-content"
		     style="padding: 10px;border-radius: 5px;margin-bottom: 10px;display: inline-block;">
			<a href="<?= $listUrl ?>"
			   target="_blank"
			   style="white-space: nowrap; max-width: 150px;overflow: hidden;text-overflow: ellipsis;"
			   class="adm-btn"><?= $settings["listButton"] ?></a>

			<a href="<?= $editUrl ?>"
			   style="white-space: nowrap; margin-left: 5px;max-width: 150px;overflow: hidden;text-overflow: ellipsis;"
			   target="_blank"
			   class="adm-btn adm-btn-save adm-btn-add"><?= $settings["addButton"] ?></a>

			<input type="hidden" name="<?= $strHTMLControlName["VALUE"] ?>" value="">
		</div>
		<?


		return ob_get_clean();
	}
}