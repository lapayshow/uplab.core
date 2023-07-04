<?

namespace Uplab\Core\Properties;


/*
	Отображает в админке свойство привязки к строке ORM таблицы
*/

use Bitrix\Main\Web\Uri;
use DigitalWand\AdminHelper\Helper\AdminBaseHelper;
use Uplab\Core\Traits\SingletonTrait;


class RelatedORM
{
	use SingletonTrait;

	const PROPERTY_USER_TYPE = "UplabRelatedOrm";
	const PROPERTY_ID        = "related.orm";

	private $MODULE_ID = "uplab.core";
	private $moduleSrc;
	private $moduleDir;

	function __construct()
	{
		$this->moduleDir = getLocalPath("modules/{$this->MODULE_ID}");
		$this->moduleSrc = $_SERVER["DOCUMENT_ROOT"] . $this->moduleDir;

		IncludeModuleLangFile(
			$this->moduleSrc . '/properties/' . self::PROPERTY_ID . '.php'
		);
	}

	public static function getUserTypeDescription()
	{
		self::getInstance();

		return array(
			"PROPERTY_TYPE"        => "S",
			"USER_TYPE"            => self::PROPERTY_USER_TYPE,
			"DESCRIPTION"          => "Свойство «Привязка к ORM»",
			"GetPropertyFieldHtml" => array(self::class, "GetPropertyFieldHtml"),
			"PrepareSettings"      => array(self::class, "PrepareSettings"),
			"GetSettingsHTML"      => array(self::class, "GetSettingsHTML"),
		);
	}


	public static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
	{
		$settings = self::PrepareSettings($arProperty);

		$arPropertyFields = array(
			"HIDE" => array("ROW_COUNT", "COL_COUNT", "MULTIPLE_CNT"),
		);

		return '
			<tr valign="top">
				<td>Класс ListHelper для отображения элементов сущности</td>
				<td>
					<input type="text" 
					       size="50" 
					       name="' . $strHTMLControlName["NAME"] . '[className]" value="' . $settings["className"] . '">
				</td>
			</tr>
			<tr valign="top">
				<td>Поле с названием</td>
				<td>
					<input type="text" 
					       size="50" 
					       name="' . $strHTMLControlName["NAME"] . '[elTitle]" value="' . $settings["elTitle"] . '">
				</td>
			</tr>
			';

	}

	public static function PrepareSettings($arProperty)
	{
		$className = '';
		if (is_array($arProperty["USER_TYPE_SETTINGS"])) {
			$className = trim(strip_tags($arProperty["USER_TYPE_SETTINGS"]["className"]));
			if (!class_exists($className)) $className = "";
		}

		if (is_array($arProperty["USER_TYPE_SETTINGS"]) && $arProperty["USER_TYPE_SETTINGS"]["multiple"] === "Y") {
			$multiple = "Y";
		} else {
			$multiple = "N";
		}

		return array(
			"multiple"  => $multiple,
			"className" => $className,
			"elTitle"   => $arProperty["USER_TYPE_SETTINGS"]["elTitle"],
		);
	}

	public static function getName($id, $settings)
	{
		/** @var AdminBaseHelper $interface */
		$interface = $settings["className"];
		$model = $interface::getModel();
		$inputValue = $model::getByPrimary($id)->fetch();

		return $inputValue[$settings["elTitle"]];
	}

	public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		$settings = self::PrepareSettings($arProperty);

		if (empty($settings["className"])) return "Ошибка. Неверно указан класс сущности.";
		$inpValue = self::getName($value["VALUE"], $settings);

		$inpID = "prop_" . $arProperty["CODE"];
		$inpKey = $arProperty["ID"];
		$link = (new Uri($settings["className"]::getUrl()))
			->addParams([
				"popup"   => "Y",
				"n"       => $inpID,
				"k"       => $inpKey,
				"eltitle" => $settings["elTitle"],
			])->getUri();

		ob_start();
		?>

		<!--suppress HtmlFormInputWithoutLabel -->
		<input type="text" id="<?= "{$inpID}[{$inpKey}]" ?>"
		       name="<?= $strHTMLControlName["VALUE"] ?>"
		       value="<?= $value["VALUE"] ?>"
		       size='10'
		>
		&nbsp;
		<!--suppress JSUnresolvedVariable, JSUnresolvedFunction -->
		<input type="button"
		       value="..."
		       onclick="jsUtils.OpenWindow('<?= $link ?>', 800, 600);">

		&nbsp;
		<span id="sp_<?= md5($inpID) . "_" . $inpKey ?>"><?= $inpValue ?></span>

		<?
		return ob_get_clean();
	}
}