<?

namespace Uplab\Core\Iblock;


use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Uplab\Core\Constant;
use Bitrix\Main\IO\File;
use CIBlockProperty;
use CUserOptions;


/**
 * Класс для работы с формами
 */
class FormSettings
{
	private $iblock         = null;
	private $iblockCode     = null;
	private $settingsArray  = null;
	private $settingsString = null;
	private $dir            = null;
	private $properties     = [];

	function __construct($iblock, $lang = false)
	{
		if (!Loader::includeModule('iblock')) return;;

		if (intval($iblock)) {
			$this->iblockCode = "IBLOCK_" . $iblock;
			$this->iblock = $iblock;
		} else {
			$this->iblockCode = $iblock;
			$this->iblock = Constant::Get($iblock, $lang);
		}

		$this->preparePropertiesList();

		$this->dir = getLocalPath("php_interface") . "/data";
	}

	public static function convertStringToArray($settings)
	{
		$arTabs = explode("--;--", $settings);

		$settingsArray = array();

		foreach ($arTabs as &$tab) {
			$tab = explode("--,--", $tab);

			if (empty($tab[0])) continue;

			$tabName = "";
			foreach ($tab as $key => $field) {
				$field = explode("--#--", $field);

				if (empty($tabName)) {
					$tabName = $field[1];
					continue;
				}

				$settingsArray[$tabName][$field[0]] = $field[1];
			}
		}

		return $settingsArray;
	}

	public static function printSettings($settings)
	{
		$settingsArray = self::convertStringToArray($settings);

		echo '<pre>';
		var_export($settingsArray);
		echo '</pre>';
	}

	public function convertArrayToString(array $settingsArray)
	{
		$this->preparePropertiesList();

		$settings = '';
		$i = 1;
		foreach ($settingsArray as $tabName => $arTab) {
			$settings .= "edit" . $i++ . "--#--" . $tabName;
			foreach ($arTab as $code => $name) {
				$settings .= "--,--" . $code . "--#--" . $name;
			}
			$settings .= "--;--";
		}

		foreach ($this->properties as $key => $code) {
			$settings = str_replace("--PROPERTY_%$code%--", "--PROPERTY_$key--", $settings);
		}

		return $settings;
	}

	public function exportFormSettings($dir = false)
	{
		if (!empty($dir)) {
			if (strpos($dir, Application::getDocumentRoot()) !== 0) {
				$dir = Application::getDocumentRoot() . $dir;
			}
		} else {
			$dir = $this->dir;
		}
		if (!file_exists($dir)) mkdir($dir, 0777);
		if (!file_exists($dir)) return false;


		$settings =
			"<?" . PHP_EOL . " return " .
			var_export($this->getSettingsArray(), true) .
			";" . PHP_EOL;


		$suffix = substr(md5($settings), 0, 6);
		$path = $dir . "/export_form_" . strtolower($this->iblockCode) . "_{$suffix}.php";


		File::putFileContents($path, $settings);
		echo "Данные формы записаны по пути [{$path}]";


		return $path;
	}

	public function prepareCurrentSettings()
	{
		$settings = CUserOptions::GetOption("form", "form_element_" . $this->iblock, true)["tabs"];

		foreach ($this->properties as $key => $code) {
			$settings = str_replace("--PROPERTY_$key--", "--PROPERTY_%$code%--", $settings);
		}

		$this->settingsString = self::convertStringToArray($settings);
		$this->settingsArray = self::convertStringToArray($settings);

		// $this->printSettings($settings);

		return $settings;
	}

	public function getSettingsArray()
	{
		$this->prepareCurrentSettings();

		return $this->settingsArray;
	}

	public function getSettingsString()
	{
		$this->prepareCurrentSettings();

		return $this->settingsString;
	}

	public function setSettingsFromFile($path)
	{
		if (strpos($path, Application::getDocumentRoot()) === false) {
			$path = $this->dir . "/" . $path;
		}

		/** @noinspection PhpIncludeInspection */
		$settings = include $path;

		$this->setSettingsFromArray($settings);
	}

	public function setSettingsFromArray($settings = false)
	{
		if (empty($settings) || !is_array($settings)) return false;

		$settings = $this->convertArrayToString($settings);
		$this->printSettings($settings);

		CUserOptions::DeleteOptionsByName("form", "form_element_{$this->iblock}");
		CUserOptions::SetOption("form", "form_element_{$this->iblock}", ["tabs" => $settings], true, false);

		return true;
	}

	private function preparePropertiesList()
	{
		$arProps = array();
		$res = CIBlockProperty::GetList(
			["SORT" => "ASC"],
			["IBLOCK_ID" => $this->iblock, "CHECK_PERMISSIONS" => "N"]
		);

		while ($prop = $res->Fetch()) {
			$arProps[$prop["ID"]] = $prop["CODE"];
		}

		$this->properties = $arProps;

		return $arProps;
	}

	public static function copySettings($iblock1, $iblock2)
	{
		$ob1 = new static($iblock1);
		$ob2 = new static($iblock2);

		$ob2->setSettingsFromArray($ob1->getSettingsArray());
	}
}