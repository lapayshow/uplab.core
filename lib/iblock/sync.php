<?

namespace Uplab\Core\Iblock;


use CIBlock;
use Uplab\Core\Data\StringUtils;
use Uplab\Core\Helper;
use Uplab\Core\Constant;
use Bitrix\Main\Loader;
use CIblockElement;
use CIBlockSection;
use CFile;
use Bitrix\Main\IO\File;
use Uplab\Core\Iblock\Helper as IBHelper;


class Sync
{
	const CONFIG_DIR     = "config";
	const LAST_DATE_FILE = "last_sync_date";

	protected $fileFields = [
		"PREVIEW_PICTURE",
		"DETAIL_PICTURE",
	];

	protected $textFields = [
		"CODE",
		"ACTIVE",
		"NAME",
		"DATE_ACTIVE_FROM",
		"PREVIEW_TEXT",
		"DETAIL_TEXT",
	];

	protected $select = [
		"ID",
		"ACTIVE",
		"IBLOCK_ID",
		"IBLOCK_SECTION_ID",
		"CODE",
		"NAME",
		"TIMESTAMP_X",
		"DATE_ACTIVE_FROM",
		"PREVIEW_PICTURE",
		"DETAIL_PICTURE",
		"PREVIEW_TEXT",
		"DETAIL_TEXT",
	];

	protected $sectCodes = [];
	protected $sections  = [];
	protected $result    = [];
	private   $addFields;
	private   $addProps;
	private   $replaceProps;
	private   $enumLists = [];
	private   $iblock;
	private   $lastSyncDateSrc;
	private   $date;
	private   $domain;
	private   $filter;
	private   $ignoreSects;

	function __construct($params = [])
	{
		if (!Loader::includeModule("iblock")) return;

		$date = false;
		$ignoreSects = false;
		$select = [];
		$filter = [];
		$iblock = "";
		$domain = "http://{$_SERVER["SERVER_NAME"]}";
		extract($params);

		if (!empty($iblock)) $this->iblock = $iblock;

		$this->lastSyncDateSrc = implode("/", [
			getLocalPath("php_interface"),
			self::CONFIG_DIR,
			self::LAST_DATE_FILE . "_{$iblock}.txt",
		]);

		if (empty($date)) {
			$date = file_get_contents($this->lastSyncDateSrc);
			if (empty($date)) $date = false;
		}

		if (!empty($date)) $date = FormatDate($date);

		$this->date = $date;
		$this->domain = $domain;
		$this->filter = $filter;
		$this->ignoreSects = $ignoreSects;

		$this->select = array_merge($this->select, (array)$select);
	}

	public function getElements()
	{
		$this->result = [];
		$arOrder = [];
		$arFilter = array_merge(
			(array)$this->filter,
			array("IBLOCK_ID" => $this->iblock, "ACTIVE" => "Y", "ACTIVE_DATE" => "Y")
		);

		if (!empty($this->date)) {
			$arFilter[] = array(
				"LOGIC"              => "OR",
				">=TIMESTAMP_X"      => $this->date,
				">=DATE_ACTIVE_FROM" => $this->date,
			);
		}

		//		echo "<pre>";
		//		print_r($arFilter);
		//		echo "</pre>"; die();

		$arSelect = $this->select;
		$res = CIblockElement::GetList($arOrder, $arFilter, false, false, $arSelect);
		while ($ob = $res->GetNextElement()) {
			$item = $ob->GetFields();
			$item["PROPERTIES"] = $ob->GetProperties();

			$this->result["CODES"][] = $item["CODE"];
			$this->result["ITEMS"][] = $this->prepareFields($item);
		}

		$this->prepareSections();
	}

	public function exportElements()
	{
		$this->getElements();
		Helper::printJsonOnly($this->result);
	}

	public function readElements(
		/** @noinspection PhpUnusedParameterInspection */
		$url, &$data, &$arID, &$arSects
	) {
		$content = file_get_contents($url);
		$data = json_decode($content, true);

		$arID = [];
		if (!empty($data["CODES"])) {
			$arID = $this->getElementsID($data["CODES"]);
		}

		if (!empty($data["SECTIONS"])) {
			$this->getSectionsID($data["SECTIONS"]);
		}
	}

	public function importElements($url, $addFields = [], $addProps = [], $replaceProps = [])
	{
		$data = $arID = $arSects = null;

		$this->readElements($url, $data, $arID, $arSects);
		$this->prepareIblockProps();

		$updCnt = 0;
		$addCnt = 0;

		$this->addFields = $addFields;
		$this->addProps = $addProps;
		$this->replaceProps = $replaceProps;

		echo $url, PHP_EOL;

		echo count($data["CODES"]), PHP_EOL;

		$el = new CIblockElement;
		foreach ($data["ITEMS"] as $item) {
			$id = $arID[$item["CODE"]];

			$this->prepareWriteItem($item);

			if (!empty($id)) {
				$updCnt++;
				$el->Update($id, $item);
			} else {
				$addCnt++;
				$item["IBLOCK_ID"] = $this->iblock;
				$id = $el->Add($item);
			}


			echo "upd: {$updCnt}; add: {$addCnt}";

			if ($id) {
				echo "[last element {$id}]";
			} else {
				echo "[last error: {$el->LAST_ERROR}]", PHP_EOL;
				unset($item["PREVIEW_TEXT"]);
				unset($item["DETAIL_TEXT"]);
				print_r($item);
				echo PHP_EOL;
			}

			echo PHP_EOL;

			if ($GLOBALS["debug"] === true) ob_flush();
		}


		$log = compact("addCnt", "updCnt");
		AddMessage2Log($log);
		$this->setLastSyncDate();
	}

	public function setLastSyncDate($timestamp = false)
	{
		$timestamp = $timestamp ? $timestamp : time();
		File::putFileContents($this->lastSyncDateSrc, $timestamp);
	}

	public function getLastSyncDate()
	{
		return file_get_contents($this->lastSyncDateSrc);
	}

	protected function prepareSections()
	{
		if (empty($this->sectCodes)) return;

		$arFilter = array("ID" => array_keys($this->sectCodes));
		$res = CIBlockSection::GetList([], $arFilter, false, ["ID", "NAME", "CODE"]);
		while ($sect = $res->Fetch()) {
			$id = $sect["ID"];
			unset($sect["ID"]);
			$this->sectCodes[$id] = $sect["NAME"];
			$this->sections[$sect["NAME"]] = $sect;
		}

		$this->result["SECTIONS"] = $this->sections;
	}

	protected function prepareFields($item)
	{
		$preparedItem = [];

		foreach ($this->textFields as $code) {
			if (empty($item[$code])) continue;
			$preparedItem[$code] = $this->prepareText($item[$code]);
		}

		if (!empty($sect = $item["IBLOCK_SECTION_ID"])) {
			$preparedItem["SECTION"] = &$this->sectCodes[$sect];
		}

		foreach ($this->fileFields as $code) {
			if (empty($item[$code])) continue;
			$preparedItem[$code] = $this->domain . CFile::GetPath($item[$code]);
		}

		$this->prepareProperties($item, $preparedItem);

		return $preparedItem;
	}

	protected function prepareText($text)
	{
		$matches = StringUtils::findPicturesInText($text);

		if (!empty($matches[0])) {
			$replace = [];
			foreach ($matches[2] as $src) {
				$replace["from"][] = $src;
				$replace["to"][] = $this->domain . $src;
			}
			$text = str_replace($replace["from"], $replace["to"], $text);
		}

		return $text;
	}

	protected function prepareProperties($item, &$preparedItem)
	{
		// TODO: предусмотреть различные варианты свойств
		foreach ($item["PROPERTIES"] as $code => $prop) {
			if (empty($prop["VALUE"])) continue;
			$preparedItem["PROPERTY_VALUES"][$code]["VALUE"] = $prop["VALUE"];
		}
	}

	protected function prepareIblockProps()
	{
		$res = CIBlock::GetProperties($this->iblock);
		while ($prop = $res->Fetch()) {
			if ($prop["PROPERTY_TYPE"] == "L") {
				$enumList = IBHelper::getEnumList([
					"iblock" => $this->iblock,
					"code"   => $prop["CODE"],
				]);
				foreach ($enumList as $item) {
					$this->enumLists[$prop["CODE"]][$item["VALUE"]] = $item["ID"];
				}
			}
		}

		// echo "<pre>";
		// print_r($this->enumLists);
		// echo "</pre>";
		// die();
	}

	protected function prepareWriteItem(&$item)
	{
		$propValues = [];

		$item["PREVIEW_TEXT_TYPE"] = "html";
		$item["DETAIL_TEXT_TYPE"] = "html";

		if (!empty($item["DATE_ACTIVE_FROM"])) {
			$item["DATE_ACTIVE_FROM"] = FormatDate(strtotime($item["DATE_ACTIVE_FROM"]));
		}

		$item["NAME"] = htmlspecialchars_decode($item["NAME"]);

		foreach ($this->fileFields as $code) {
			$item[$code] = CFile::MakeFileArray($item[$code]);

			if (empty($item[$code])) continue;

			$name = explode(".", $item[$code]["name"]);
			$name[0] = randString(4);
			$name = implode(".", $name);
			$item[$code]["name"] = $name;
		}

		$item = array_merge((array)$this->addFields, $item);

		if (!empty($sect = $item["SECTION"]) && empty($item["IBLOCK_SECTION_ID"])) {
			$item["IBLOCK_SECTION_ID"] = $this->sections[$sect]["ID"];
			unset($item["SECTION"]);
		}

		foreach ($item["PROPERTY_VALUES"] as $from => $prop) {
			if (!($to = $this->replaceProps[$from])) {

				// TODO: добавить автоматическое добавление пунктов списка,
				// если при синхронизации добавились пункты, которых не было
				if (!empty($this->enumLists[$from])) {
					// die("<pre>" . print_r(compact("prop"), true));
					if (is_array($prop["VALUE"])) {
						$tmpPropValue = [];
						foreach ($prop["VALUE"] as $propVal) {
							$tmpPropValue[] = $this->enumLists[$from][$propVal];
						}
					} else {
						$tmpPropValue = $this->enumLists[$from][$prop["VALUE"]];
					}
					$propValues[$from] = $tmpPropValue;
				} else {
					$propValues[$from] = $prop;
				}
			} else {
				if (is_array($to)) {
					$propValues[$to["CODE"]] = Constant::extract($to["TEXT"], $prop);
				} else {
					$propValues[$to] = $prop;
				}
			}
		}

		$item["PROPERTY_VALUES"] = $propValues;
		$item["PROPERTY_VALUES"] = array_merge($item["PROPERTY_VALUES"], $this->addProps);

		// echo "<pre>";
		// print_r($item["PROPERTY_VALUES"]);
		// echo "</pre>", PHP_EOL;
	}

	protected function getElementsID($codes)
	{
		$arItems = [];
		$arOrder = [];
		$arFilter = array(
			"IBLOCK_ID" => $this->iblock,
			"CODE"      => $codes, "ACTIVE" => "Y", "ACTIVE_DATE" => "Y",
		);
		$arSelect = array("ID", "IBLOCK_ID", "CODE");
		$res = CIblockElement::GetList($arOrder, $arFilter, false, false, $arSelect);
		while ($item = $res->GetNext()) {
			$arItems[$item["CODE"]] = $item["ID"];
		}

		return $arItems;
	}

	protected function getSectionsID($sections)
	{
		if ($this->ignoreSects === true) return;

		$arFilter = array("IBLOCK_ID" => $this->iblock, "NAME" => array_keys($sections));
		$res = CIBlockSection::GetList([], $arFilter, false, ["ID", "NAME", "CODE"]);
		while ($sect = $res->Fetch()) {
			$this->sections[$sect["NAME"]] = $sect;
		}

		$bs = new CIBlockSection;

		foreach ($sections as $sect) {
			if (!empty($this->sections[$sect["NAME"]])) continue;
			$id = $bs->Add([
				"IBLOCK_ID" => $this->iblock,
				"NAME"      => $sect["NAME"],
				"CODE"      => $sect["CODE"],
			]);
			$sect["ID"] = $id;
			$this->sections[$sect["NAME"]] = $sect;
		}
	}

}