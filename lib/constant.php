<?

namespace Uplab\Core;


use Bitrix\Iblock\IblockSiteTable;
use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;
use Bitrix\Main\SiteTable;
use CForm;
use Exception;
use Uplab\Core\Entities\Form\FormTable;
use Uplab\Core\Traits\SingletonTrait;


/**
 * class for defining and operating constants
 */
class Constant
{
	const MODULE_ID      = "uplab.core";
	const CONST_SETTINGS = "const_data.php";
	const CONST_FILE     = ".constants_for_ide.php";

	const CONSTANT_IBLOCK_TPL            = "#CODE#_IBLOCK";
	const CONSTANT_IBLOCK_TYPE_TPL       = "#CODE#_IBLOCK_TYPE";
	const CONSTANT_IBLOCK_ENTITY_TPL     = "#CODE#_IBLOCK_ENTITY";
	const CONSTANT_FORM_TPL              = "#CODE#_FORM";
	const CONSTANT_IBLOCK_TYPE_SHORT_TPL = "#CODE#_TYPE";

	private $siteId;
	private $isSitesType;

	private $constantIblockTpl;
	private $constantIblockTypeTpl;

	private $constantsArray = [];
	private $sitesList      = [];
	private $constantsDir;
	private $constantsFilePath;
	private $constantsSettingsPath;
	private $constantIblockTypeShortTpl;

	use SingletonTrait;

	function __construct()
	{
		$this->constantsDir = Application::getDocumentRoot() . getLocalPath("php_interface") . "/include";
		$this->constantsFilePath = Application::getDocumentRoot() . DIRECTORY_SEPARATOR . self::CONST_FILE;
		$this->constantsSettingsPath = $this->constantsDir . DIRECTORY_SEPARATOR . self::CONST_SETTINGS;

		mkdir($this->constantsDir, 0777, true);

		$this->siteId = SITE_ID;
		$this->isSitesType = true;

		$this->getConstantsData();
	}

	public static function update(
		/** @noinspection PhpUnusedParameterInspection */
		$arFields
	) {
		Application::getInstance()->getTaggedCache()->clearByTag(self::MODULE_ID);
	}

	public static function define()
	{
		foreach (self::getArray() as $key => $constantItem) {
			define($key, $constantItem["VALUE"]);
		}
	}

	public static function get($name, $siteId = "", &$constantItem = null)
	{
		$constantItem = self::getArray($siteId)[$name];

		return $constantItem["VALUE"];
	}

	public static function getRow($constantCode)
	{
		$arRow = [];
		foreach (self::getInstance()->constantsArray as $site => $arSiteConstants) {
			if (array_key_exists($constantCode, $arSiteConstants)) {
				$arRow[$site] = $arSiteConstants[$constantCode]["VALUE"];
			}
		}

		return $arRow;
	}

	public static function isInRow($constantValue, $constantCode)
	{
		return in_array($constantValue, self::getRow($constantCode));
	}

	/**
	 * @param $value
	 *
	 * @return bool|int|string
	 * @deprecated
	 *
	 */
	public static function getIblockLang($value)
	{
		foreach (self::getInstance()->constantsArray as $ln => $arLang) {
			foreach ($arLang as $lnKey => $lnVal) {
				if ($lnVal == $value &&
					strpos($lnKey, "_IBLOCK") !== false) {
					return $ln;
				}
			}
		}

		return false;
	}

	public static function getArray($siteId = "")
	{
		if (empty($siteId)) $siteId = self::getInstance()->siteId;
		$array = self::getInstance()->constantsArray;

		if (array_key_exists($siteId, $array)) {
			return $array[$siteId];
		} else {
			return current($array);
		}
	}

	public static function getAll()
	{
		return self::getInstance()->constantsArray;
	}

	public static function clearCodeForConstant($code, $trimValues = null)
	{
		$code = mb_strtoupper($code);
		$code = preg_replace("~[^\w\d]+~", "_", $code);
		$code = preg_replace("~[_]+~", "_", $code);
		$code = trim($code, "_\n\r ");

		if (!empty($trimValues)) {
			foreach ((array)$trimValues as $trimValue) {
				$trimValue = mb_strtoupper($trimValue);
				$code = preg_replace("~(^{$trimValue}_|_({$trimValue}$))~", "", $code);
			}
		}

		return $code;
	}

	public static function getConstNameFromTpl(&$code, $tpl, $trimValues = null)
	{
		$code = self::clearCodeForConstant($code, $trimValues);

		return str_replace("#CODE#", $code, $tpl);
	}

	public static function getIblockConstFromCode(&$code, $trimValues = null)
	{
		return self::getConstNameFromTpl($code, self::CONSTANT_IBLOCK_TPL, $trimValues);
	}

	public static function getIblock($constantCode)
	{
		$constantValue = constant(self::getIblockConstFromCode($constantCode));

		return intval($constantValue) ?: false;
	}

	public static function extract($str, $params = array(), $clean = true)
	{
		preg_match_all('/\#([a-zA-Z0-9_-]+)\#/', $str, $matches);
		foreach ($matches[0] as $key => $match) {
			$const = $matches[1][$key];
			if (isset($params[$const])) {
				$replace = $params[$const];
			} elseif (defined($const)) {
				$replace = constant($const);
			} else {
				continue;
			}
			// $replace = "";
			$str = str_replace($match, $replace, $str);
		}
		if ($clean) {
			// Удаляет двойные слеши, но при этом не трогает протоколы ссылок
			$str = preg_replace('/([^:])(\/+)/', '$1/', $str);
		}

		return $str;
	}

	public static function sortConstantsCallback($a, $b)
	{
		/*
		$lenA = strlen($a);
		$lenB = strlen($b);
		if ($lenA < $lenB) {
			return -1;
		} elseif ($lenA > $lenB) {
			return 1;
		} else return strcmp($a, $b);
		*/

		return strcmp($a, $b);
	}

	public function getConstantsData($isCache = true)
	{
		$cacheParams = UplabCache::getCacheParams(__METHOD__);
		$cache = Cache::createInstance();

		if ($isCache && $cache->initCache($cacheParams["time"], $cacheParams["id"], $cacheParams["dir"])) {

			// echo "cache valid";
			$this->constantsArray = $cache->getVars();

		} elseif (!$isCache || $cache->startDataCache()) {

			$cacheManager = Application::getInstance()->getTaggedCache();
			$cacheManager->startTagCache($cacheParams["dir"]);

			$this->prepareConstantsData();

			if ($isCache) {
				$cacheManager->registerTag(self::MODULE_ID);
				$cacheManager->registerTag("iblock_new");
				$cacheManager->endTagCache();

				$cache->endDataCache($this->constantsArray);
			}

		}

		return $this->constantsArray;
	}

	public function prepareConstantsData()
	{
		/** @noinspection PhpUnhandledExceptionInspection */
		if (!Loader::includeModule("iblock")) return;

		$this->constantsArray = [];

		if ($this->isSitesType) {
			$this->prepareSitesList();

			$this->prepareConstantsIblockBySite();
			$this->prepareConstantsForms();

			// TODO: константы из настроек - допилить в настройках
			$this->prepareConstantsFromSettings();
		} else {
			// TODO: допилить старые константы
			$this->prepareConstantsIblockByLang();
			$this->prepareConstantsFromSettingsOld();
		}

		$newConstantsArray = [];
		foreach ($this->constantsArray as $site => $constants) {
			uksort($constants, [self::class, "sortConstantsCallback"]);
			$newConstantsArray[$site] = $constants;
		}

		$this->constantsArray = $newConstantsArray;

		$this->makeConstantsFileForIDE();
	}

	/**
	 * @return mixed
	 */
	public function getConstantsArray()
	{
		return $this->constantsArray;
	}

	/**
	 * @param mixed $constantsArray
	 */
	public function setConstantsArray($constantsArray)
	{
		$this->constantsArray = $constantsArray;
	}

	private function prepareConstantsIblockBySite()
	{
		$res = IblockSiteTable::getList([
			"order"  => [
				"IBLOCK_ID"        => "asc",
				"IBLOCK_TYPE_LANG" => "desc",
			],
			"select" => [
				"*",

				"IBLOCK_ID",
				"IBLOCK_CODE"      => "IBLOCK.CODE",
				"IBLOCK_ENTITY"    => "IBLOCK.API_CODE",
				"IBLOCK_NAME"      => "IBLOCK.NAME",
				"IBLOCK_TYPE"      => "IBLOCK.TYPE.ID",
				"IBLOCK_TYPE_NAME" => "IBLOCK.TYPE.LANG_MESSAGE.NAME",
				"IBLOCK_TYPE_LANG" => "IBLOCK.TYPE.LANG_MESSAGE.LANGUAGE_ID",

				"SITE_ID",
				"SITE_LANG"        => "SITE.LANGUAGE_ID",
			],
		]);

		$constants = [];

		// echo "<pre>";
		// print_r($res->fetch());
		// echo "</pre>";
		// return;
		while ($iblock = $res->fetch()) {
			if (!($site = $iblock["SITE_ID"])) continue;

			// отправляется ссылкой в метод getIblockConstFromCode
			$iblockCode = $iblock["IBLOCK_CODE"];

			$iblockConst = $this->getIblockConstFromCode($iblockCode, [
				$site,
				$iblock["IBLOCK_TYPE_LANG"],
				$iblock["SITE_LANG"],
			]);

			$iblockTypeConst = str_replace("#CODE#", $iblockCode, self::CONSTANT_IBLOCK_TYPE_TPL);
			$iblockEntityConst = str_replace("#CODE#", $iblockCode, self::CONSTANT_IBLOCK_ENTITY_TPL);

			$iblockTypeShortConst = str_replace(
				"#CODE#",
				$this->clearCodeForConstant(
					$iblock["IBLOCK_TYPE"],
					[$site, $iblock["IBLOCK_TYPE_LANG"], $iblock["SITE_LANG"]]
				),
				self::CONSTANT_IBLOCK_TYPE_SHORT_TPL
			);

			isset($constants[$site][$iblockTypeShortConst]) ||
			$constants[$site][$iblockTypeShortConst] = [
				"TITLE" => $iblock["IBLOCK_TYPE_NAME"],
				"VALUE" => $iblock["IBLOCK_TYPE"],
			];

			isset($constants[$site][$iblockTypeConst]) ||
			$constants[$site][$iblockTypeConst] = [
				"TITLE" => $iblock["IBLOCK_TYPE_NAME"],
				"VALUE" => $iblock["IBLOCK_TYPE"],
			];

			isset($constants[$site][$iblockEntityConst]) ||
			empty($iblock["IBLOCK_ENTITY"]) ||
			$constants[$site][$iblockEntityConst] = [
				"TITLE" => sprintf("%s [API_CODE]", $iblock["IBLOCK_NAME"]),
				"VALUE" => $iblock["IBLOCK_ENTITY"],
			];

			isset($constants[$site][$iblockConst]) ||
			$constants[$site][$iblockConst] = [
				"TITLE" => $iblock["IBLOCK_NAME"],
				"VALUE" => $iblock["IBLOCK_ID"],
				"CODE"  => $iblock["IBLOCK_CODE"],
			];
		}

		$this->constantsArray = $constants;
	}

	private function prepareSitesList()
	{
		$res = SiteTable::getList([
			"select" => [
				"ID",
				"LANG" => "LANGUAGE_ID",
				"NAME",
				"DIR",
			],
		]);

		while ($site = $res->fetch()) {
			$this->sitesList[$site["ID"]] = $site;
		}

		// echo "<pre>";
		// print_r($this->sitesList);
		// echo "</pre>";
	}

	private function prepareConstantsFromSettings()
	{
		// $this->prepareConstantsFromSettingsOld();

		foreach ($this->sitesList as $site) {
			$data = unserialize(Helper::getOption("custom_const_{$site["ID"]}"));

			if (empty($data) || !is_array($data)) continue;

			foreach ($data as $constantKey => $constantValue) {
				$groupKey = $site["ID"];

				if (!is_scalar($constantValue)) continue;
				if (isset($this->constantsArray[$groupKey][$constantKey])) continue;

				$this->constantsArray[$groupKey][$constantKey] = [
					"VALUE" => $constantValue,
				];
			}
		}
	}

	private function prepareConstantsFromSettingsOld()
	{
		if (!file_exists($this->constantsSettingsPath)) return;

		/** @noinspection PhpIncludeInspection */
		$settings = include $this->constantsSettingsPath;

		foreach ($this->sitesList as $site) {
			foreach ($settings[$site["LANG"]]["add"] as $constant => $value) {
				$groupKey = $this->isSitesType ? $site["ID"] : $site["LANG"];

				if (isset($this->constantsArray[$groupKey][$constant])) continue;

				$this->constantsArray[$groupKey][$constant] = [
					"VALUE" => $value,
				];
			}
		}
	}

	private function prepareConstantsIblockByLang()
	{
	}

	private function prepareConstantsForms()
	{
		/** @noinspection PhpUnhandledExceptionInspection */
		if (Loader::includeModule("form")) {
			foreach ($this->sitesList as $siteId => $site) {
				$forms = FormTable::getList([
					"filter" => ["=SITES.LID" => $siteId],
					"select" => ["ID", "SID", "NAME"],
				])->fetchCollection();

				foreach ($forms as $form) {
					$constant = self::getConstNameFromTpl(
						$form->getSid(),
						self::CONSTANT_FORM_TPL,
						[
							$siteId,
							$site["LANG"],
						]
					);

					$this->constantsArray[$siteId][$constant] = [
						"TITLE" => $form->getName(),
						"VALUE" => $form->getId(),
					];
				}
			}
		}
	}

	/**
	 * Создаем файл с дефайнами констант для того,
	 * чтобы IDE знала наши константы
	 */
	private function makeConstantsFileForIDE()
	{
		$resultArray = [
			"<?",
			"",
			"/**",
			" * Файл сгенерирован автоматически и не предназначен для подключения на сайте.",
			" *",
			" * Константы, определенные в этом файле носят информацонный характер",
			" * и предназначены для IDE.",
			" *",
			" * РЕКОМЕНДУЕТСЯ хранить файл в GIT-репозитории, периодически делая коммиты с этим файлом.",
			" * Возникновение конфликтов в файле указывает на необходимость синхронизации изменений в структуре БД.",
			" *",
			" * Значения констант нигде не используются.",
			" * Данный файл нигде не подключается.",
			" */",
			"",
			"",
			"switch (SITE_ID) {",
			"",
		];

		foreach ($this->constantsArray as $site => $constants) {
			$resultArray[] = "	case \"$site\":";
			foreach ($constants as $constant => $constantItem) {
				$value = $constantItem["TITLE"];
				if (empty($value) && !is_numeric($constantItem["VALUE"])) {
					$value = $constantItem["VALUE"];
				}

				$resultArray[] = "		define(\"{$constant}\", \"{$value}\");";
			}
			$resultArray[] = "		break;";
			$resultArray[] = "";
		}

		$resultArray[] = "}";

		file_put_contents($this->constantsFilePath, implode(PHP_EOL, $resultArray));

		/*
		// Добавление файла в .gitignore
		$gitIgnorePath = $this->constantsDir . "/.gitignore";
		$gitIgnoreContent = (string)file_get_contents($gitIgnorePath);
		if (strpos($gitIgnoreContent, self::CONST_FILE) === false) {
			$gitIgnoreContent = implode(PHP_EOL . PHP_EOL, array_filter([
					trim($gitIgnoreContent),
					self::CONST_FILE,
				])) . PHP_EOL;
			file_put_contents($gitIgnorePath, $gitIgnoreContent);
		}
		*/
	}

}