<?
/** @noinspection PhpUnusedLocalVariableInspection */

namespace Uplab\Core\Properties;


use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Uplab\Core\Data\StringUtils;
use Uplab\Core\Traits\SingletonTrait;
use CJSCore;
use CUtil;


class SmartGrid
{
	use SingletonTrait;

	const PROPERTY_USER_TYPE = 'UpSmartTable';
	const PROPERTY_ID        = 'smart.table';
	const LANGUAGE_FILE_ID   = 'smartTable';

	private $LANGUAGE_FILE_ID = 'smartTable';
	private $MODULE_ID        = "uplab.core";
	private $CSS_DIR;
	private $JS_DIR;
	private $TPL_PATH;
	private $MODULE_PATH;
	private $MODULE_SRC;

	function __construct()
	{
		if (!Loader::includeModule("uplab.core")) return;

		$this->CSS_DIR = "/bitrix/css/{$this->MODULE_ID}";
		$this->JS_DIR = "/bitrix/js/{$this->MODULE_ID}";
		$this->MODULE_PATH = getLocalPath("modules/{$this->MODULE_ID}");
		$this->MODULE_SRC = $_SERVER["DOCUMENT_ROOT"] . $this->MODULE_PATH;
		$this->TPL_PATH = "{$this->MODULE_SRC}/include/tpl/" . self::PROPERTY_ID;

		IncludeModuleLangFile("{$this->MODULE_SRC}/properties/{$this->LANGUAGE_FILE_ID}.php");
	}

	public static function getUserTypeDescription()
	{
		self::getInstance();

		$arProps = array(
			'PROPERTY_TYPE' => 'S',
			'USER_TYPE'     => self::PROPERTY_USER_TYPE,
			'DESCRIPTION'   => Loc::getMessage(self::LANGUAGE_FILE_ID . '_PROPERTY_NAME'),
		);

		$methods = array(
			'ConvertToDB',
			'ConvertFromDB',
			'GetPropertyFieldHtml',
			'GetAdminListViewHTML',
			'GetPublicViewHTML',
			'GetPublicEditHTML',
			'GetPublicFilterHTML',
			'GetAdminFilterHTML',
			'GetSettingsHTML',
			'PrepareSettings',
		);

		foreach ($methods as $method) {
			$arProps[$method] = array(__CLASS__, $method);
		}

		return $arProps;
	}

	public static function ConvertToDB($arProperty, $arValue)
	{
		$arColumns = $arProperty['USER_TYPE_SETTINGS']['COLUMNS'];
		$result = array();
		// echo PHP_EOL, print_r(compact('key'), true), PHP_EOL;

		if (!is_numeric(key($arValue['VALUE']))) {
			foreach ($arColumns as $column) {
				$code = $column['CODE'];
				foreach ($arValue['VALUE'][$code] as $index => $colValue) {
					$result[$index][$code] = $colValue;
				}
			}
		} else {
			foreach ($arValue['VALUE'] as $row) {
				$resultRow = [];
				foreach ($arColumns as $column) {
					$resultRow[$column['CODE']] = $row[$column['CODE']];
				}
				$result[] = $resultRow;
			}
		}

		// echo PHP_EOL, print_r(compact('arValue'), true), PHP_EOL;
		foreach ($result as $index => $row) {
			$empty = 0;
			foreach ($row as $value) {
				if (empty($value)) $empty++;
			}
			if ($empty == count($row)) unset($result[$index]);
		}
		

		// echo PHP_EOL, print_r(compact('result'), true), PHP_EOL;
		if($result) {
			$result = serialize($result);
		}
		
		return $result ? ['VALUE' => $result] : '';
	}
	
	public static function ConvertFromDB(
		/** @noinspection PhpUnusedParameterInspection */
		$arProperty, $value
	) {
		// AddMessage2Log(compact('arProperty', 'value'));

		return array('VALUE' => unserialize($value['VALUE']));
	}

	public static function GetSettingsHTML(
		/** @noinspection PhpUnusedParameterInspection */
		$arFields, $strHTMLControlName, &$arPropertyFields
	) {
		CJSCore::Init('jquery');


		echo '<link rel="stylesheet" href="' .
			CUtil::GetAdditionalFileUrl(
				self::getInstance()->CSS_DIR . '/smart-table.css') . '">';
		echo '<script src="' .
			CUtil::GetAdditionalFileUrl(
				self::getInstance()->JS_DIR . '/smart-table.js') . '"></script>';


		$arPropertyFields = array(
			'HIDE'                     => array(
				'ROW_COUNT',
				'COL_COUNT',
				// 'MULTIPLE_CNT',
				'WITH_DESCRIPTION',
				'DEFAULT_VALUE',
			),
			'USER_TYPE_SETTINGS_TITLE' => 'Колонки таблицы',
		);
		$arSettings = self::PrepareSettings($arFields);


		ob_start();

		/** @noinspection PhpIncludeInspection */
		include self::getInstance()->TPL_PATH . '/settings.php';

		return ob_get_clean();
	}

	public static function GetPropertyFieldHtml($arProperty, $arValue, $strHTMLControlName)
	{
		global $APPLICATION;
		CJSCore::Init('jquery');


		$APPLICATION->SetAdditionalCSS(
			'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css'
		);
		/** @noinspection PhpDeprecationInspection */
		$APPLICATION->AddHeadScript(
			'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js'
		);
		$APPLICATION->SetAdditionalCSS(
			self::getInstance()->CSS_DIR . '/smart-table.css');
		/** @noinspection PhpDeprecationInspection */
		$APPLICATION->AddHeadScript(
			self::getInstance()->JS_DIR . '/smart-table.js');


		$inpName = $strHTMLControlName['VALUE'];
		$arColumns = $arProperty['USER_TYPE_SETTINGS']['COLUMNS'];
		$arValue = empty($arValue['VALUE']) ? self::GetDefaultValue($arColumns) : $arValue;


		ob_start();


		/** @noinspection PhpIncludeInspection */
		include self::getInstance()->TPL_PATH . "/property-field.php";

		return ob_get_clean();
	}

	public static function GetAdminListViewHTML(
		/** @noinspection PhpUnusedParameterInspection */
		$arProperty, $arValue, $strHTMLControlName
	) {
		$strResult = 'Таблица';

		return $strResult;
	}

	public static function GetAdminFilterHTML(
		/** @noinspection PhpUnusedParameterInspection */
		$arProperty, $strHTMLControlName
	) {
		$strResult = 'Недоступно для этого свойства';

		return $strResult;
	}

	public static function GetPublicViewHTML(
		/** @noinspection PhpUnusedParameterInspection */
		$arProperty, $arValue, $strHTMLControlName
	) {
		$strResult = 'Таблица';

		return $strResult;
	}

	public static function GetPublicEditHtml(
		/** @noinspection PhpUnusedParameterInspection */
		$arProperty, $arValue, $strHTMLControlName
	) {
		$strResult = 'Недоступно для этого свойства';

		return $strResult;
	}

	public static function GetPublicFilterHTML(
		/** @noinspection PhpUnusedParameterInspection */
		$arProperty, $strHTMLControlName
	) {
		$strResult = 'Недоступно для этого свойства';

		return $strResult;
	}

	public static function PrepareSettings($arFields)
	{
		$arSettings = array();
		$userSettings = $arFields['USER_TYPE_SETTINGS'];

		if (isset($userSettings['COLUMNS'])) return $userSettings;

		foreach ($userSettings['CODE'] as $index => $code) {
			$name = $userSettings['NAME'][$index];
			$type = $userSettings['TYPE'][$index];
			$code = empty($code) ? StringUtils::translit($name) : $code;
			$sort = $userSettings['SORT'][$index] ? $userSettings['SORT'][$index] : 500;

			if (empty($code)) continue;

			$arSettings[$code] = array(
				'SORT' => $sort,
				'CODE' => $code,
				'TYPE' => $type,
				'NAME' => $name,
			);
		}

		uasort($arSettings, function ($a, $b) {
			return $a['SORT'] > $b['SORT'];
		});

		if (empty($arSettings)) {
			$arSettings = self::GetDefaultSettings();
		}

		return array("COLUMNS" => $arSettings);
	}

	public static function preparePropertyValue($value, $id, $iblock = null)
	{

	}

	protected static function GetDefaultSettings()
	{
		return array(
			array(
				'NAME' => 'Название',
				'CODE' => 'name',
				'SORT' => 100,
			),
			array(
				'NAME' => 'Описание',
				'CODE' => 'description',
				'SORT' => 200,
			),
		);
	}

	protected static function GetDefaultValue($settings)
	{
		$value = array();
		foreach ($settings as $col) {
			$value[$col['CODE']] = '';
		}

		return array('VALUE' => [$value]);
	}

	protected static function getTemplate($fileName)
	{

	}
}