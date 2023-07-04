<?

namespace Uplab\Core\Iblock;


use Bitrix\Main\Loader;
use Uplab\Core\Data\StringUtils;
use Uplab\Core\Constant;
use CIBlockElement;
use CFile;
use CIBlockSection;
use /** @noinspection PhpUndefinedClassInspection */
	phpQuery;


/**
 *
 */
class Fill
{
	protected $referatLink = "https://yandex.ru/referats/write/?t=mathematics+music+physics+philosophy";

	//protected $bigPicLink   = "http://lorempixel.com/800/600/technics/";
	//protected $smallPicLink = "http://lorempixel.com/285/177/technics/";

	protected $bigPicLink   = "http://unsplash.it/800/600/?random";
	protected $smallPicLink = "http://unsplash.it/300/200/?random";

	protected $pqPath       = "";
	protected $tmpSmPicPath = "";
	protected $tmpHqPicPath = "";

	function __construct()
	{
		if (!Loader::includeModule("iblock")) return;

		$this->pqPath = __DIR__ . "/../libs/phpQuery.php";
		$this->tmpSmPicPath = $_SERVER["DOCUMENT_ROOT"] . "/upload/tmp/up_fill_iblock_sm.jpg";
		$this->tmpHqPicPath = $_SERVER["DOCUMENT_ROOT"] . "/upload/tmp/up_fill_iblock_hq.jpg";
	}

	public static function init()
	{
		return new static();
	}

	public static function createSections($arNames, $iblock, $sort = 300)
	{
		if (!Loader::includeModule("iblock")) return;

		$sect = new CIBlockSection;

		foreach ($arNames as $key => $name) {
			if (is_numeric($key)) {
				$key = StringUtils::translit($name);
			}

			$arr = array(
				"NAME"      => $name,
				"CODE"      => $key,
				"IBLOCK_ID" => $iblock,
				"SORT"      => $sort,
			);

			if ($id = $sect->Add($arr)) {
				echo "New ID: ", $id, PHP_EOL, PHP_EOL;
			} else {
				echo "Error: ", $sect->LAST_ERROR, PHP_EOL, PHP_EOL;
			}

			$sort += 10;
		}
	}

	public static function createElements($arNames, $iblock, $sort = 300)
	{
		if (!Loader::includeModule("iblock")) return;

		$el = new CIBlockElement;

		foreach ($arNames as $key => $name) {
			if (is_numeric($key)) {
				$key = StringUtils::translit($name);
			}

			$arr = array(
				"NAME"      => $name,
				"CODE"      => $key,
				"IBLOCK_ID" => $iblock,
				"SORT"      => $sort,
			);

			if ($id = $el->Add($arr)) {
				echo "New ID: ", $id, PHP_EOL, PHP_EOL;
			} else {
				echo "Error: ", $el->LAST_ERROR, PHP_EOL, PHP_EOL;
			}

			$sort += 10;
		}
	}

	public function getFishContent($link = false)
	{
		if (!$link) {
			$link = $this->referatLink;
		}

		return file_get_contents($link);
	}

	public function parseFishContent($param = array())
	{
		$pics = true;
		extract($param);
		$html = $this->getFishContent();
		$doc = phpQuery::newDocumentHTML($html);
		$query = pq($doc)->find("p");

		$DETAIL_TEXT = "";

		foreach ($query as $key => $item) {
			$itemText = '<p>' . trim(pq($item)->html()) . '</p>';
			if ($key == 0) {
				$PREVIEW_TEXT = $itemText;
			}
			$DETAIL_TEXT .= $itemText;
		}

		$NAME = pq($doc)->find("strong")->text();
		$NAME = str_replace(array("Тема: «", "»"), "", $NAME);

		$res = compact("NAME", "PREVIEW_TEXT", "DETAIL_TEXT");

		if ($pics) {
			file_put_contents($this->tmpSmPicPath, file_get_contents($this->smallPicLink));
			$res["PREVIEW_PICTURE"] = CFile::makeFileArray($this->tmpSmPicPath);

			file_put_contents($this->tmpHqPicPath, file_get_contents($this->bigPicLink));
			$res["DETAIL_PICTURE"] = CFile::makeFileArray($this->tmpHqPicPath);
		}

		$res["PREVIEW_TEXT_TYPE"] = "html";
		$res["DETAIL_TEXT_TYPE"] = "html";

		return $res;
	}

	public function writeFishToIblock($iblock, $param = [])
	{
		$year = date('Y');
		$rndDate = true;
		$setCode = true;
		$pics = true;
		extract($param);

		$el = new CIBlockElement;

		if (!intval($iblock)) {
			$iblock = Constant::get($iblock);
		}

		$arr = $this->parseFishContent(compact("pics"));

		$arr["IBLOCK_ID"] = $iblock;

		if ($rndDate) {
			$d = rand(1, 31);
			$m = rand(1, 12);
			$y = rand(2009, $year);
			$arr["DATE_ACTIVE_FROM"] = MakeTimeStamp(strtotime("{$d}.{$m}.{$y}"), "SHORT");
		}

		if ($setCode) {
			$arr["CODE"] = implode(
				"_",
				array_filter([
					StringUtils::translit($arr["NAME"], '', false, 10),
					substr(md5($arr["NAME"]), 0, 4),
				])
			);

			if (CIBlockElement::GetList(
				false, array("CODE" => $arr["CODE"], "IBLOCK_ID" => $iblock), array(), false,
				array("ID", "IBLOCK_ID", "CODE"))) {
				$arr["CODE"] .= "_" . date("dmYhis");
			}
		}

		echo "<pre>";
		print_r($arr);
		echo "</pre>";

		ob_get_flush();

		if ($id = $el->Add($arr)) {
			echo "New ID: " . $id;
		} else {
			echo "Error: " . $el->LAST_ERROR;
		}
	}
}
