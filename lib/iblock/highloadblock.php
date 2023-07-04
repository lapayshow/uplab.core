<?

namespace Uplab\Core\Iblock;


use Bitrix\Highloadblock\HighloadBlockLangTable;
use Bitrix\Main\DB\Result;
use Bitrix\Highloadblock\DataManager;
use CIBlockProperty;
use CDBResult;
use Bitrix\Highloadblock\HighloadBlockTable as HlTable;
use Bitrix\Main\Loader;
use Uplab\Core\UplabCache;


if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();


/**
 * Класс для упрощения работы с Highload-блоками
 *
 * @property Result      $result
 * @property DataManager $entity
 */
class HighloadBlock
{
	private $entity;
	private $hlParams = [];

	private $params = [
		"select" => ["*"],
		"filter" => [],
		"order"  => [],
	];
	private $result;

	/**
	 * HighloadBlock constructor.
	 */
	function __construct($params)
	{
		Loader::includeModule("iblock");
		Loader::includeModule("highloadblock");

		$entityName = isset($params["entityName"]) ? $params["entityName"] : false;
		$tableName = isset($params["tableName"]) ? $params["tableName"] : false;
		$id = isset($params["id"]) ? $params["id"] : false;

		$iblock = isset($params["iblock"]) ? $params["iblock"] : false;
		$propertyCode = isset($params["propertyCode"]) ? $params["propertyCode"] : false;

		if (!empty($entityName)) {

			$this->hlParams = ["NAME" => $entityName];

		} elseif (!empty($tableName)) {

			$this->hlParams = ["TABLE_NAME" => $tableName];

		} elseif ($iblock && $propertyCode) {

			$this->hlParams = $this->getInitFilterFromProperty($iblock, $propertyCode);

		} elseif ($v = (int)$id) {

			$this->hlParams = ["ID" => $v];

		} else {

			return null;

		}

		$this->initEntity();
	}

	/**
	 * Инициализация объекта по названию таблицы
	 *
	 * @param $tableName
	 *
	 * @return HighloadBlock
	 */
	public static function initByTableName($tableName)
	{
		return new self(compact("tableName"));
	}

	/**
	 * Инициализация объекта по названию сущности
	 *
	 * @param $entityName
	 *
	 * @return HighloadBlock
	 */
	public static function initByEntityName($entityName)
	{
		return new self(compact("entityName"));
	}

	/**
	 * Позволяет инициализировать для хайлоад-блока,
	 * который привязан к какому-либо инфоблоку в качестве справочника.
	 * Удобно, что не требуется привязываться к конкретному ХЛ-блоку по его ID
	 * или символьному коду, достаточно знать, к какому инфоблоку привязан ХЛ-блок
	 * и в каком свойстве используется. Поэтому, если в настройках свойства выбрать
	 * другой ХЛ-блок для справочника, код продолжит работать.
	 *
	 * @param int    $iblock       ID инфоблока
	 * @param string $propertyCode Код свойства типа "Справочник"
	 *
	 * @return HighloadBlock
	 */
	public static function initByIblockProperty($iblock, $propertyCode)
	{
		return new static(compact("iblock", "propertyCode"));
	}

	/**
	 * Позволяет инициализировать объект класса по ID хайлоад-блока
	 *
	 * @param $id
	 *
	 * @return HighloadBlock
	 */
	public static function initById($id)
	{
		return new static(compact("id"));
	}

	/**
	 * @return DataManager
	 */
	public function getEntity()
	{
		return $this->entity;
	}

	/**
	 * Метод для получения своства типа "Справочник".
	 * Получает список элементов HL-блока, привязанного к Инфоблоку в качестве справочника.
	 * Отличительной особенностью является то, что метод возвращает элементы HL-блока
	 * в более понятном формате: ["ID", "NAME", "CODE", "SORT"],
	 * вместо ["UF_NAME", "UF_XML_ID", "UF_SORT"]
	 *
	 * Пример вызова:
	 * self::iblockProperty($iblockId, $propertyCode)->getDirectory();
	 *
	 * @param array $params Массив параметров, которые будут перданы в getList
	 *
	 * @return array|bool|mixed
	 */
	public function getDirectory($params = [])
	{
		$entity = $this->entity;
		if (!$entity) return false;

		$params["order"] = array_merge(
			array("UF_SORT" => "asc"),
			(array)$params["order"]
		);

		$params["select"] = array_merge(
			array(
				"ID",
				"NAME" => "UF_NAME",
				"CODE" => "UF_XML_ID",
				"SORT" => "UF_SORT",
			),
			(array)$params["select"]
		);

		$params["filter"] = array_merge(array(), (array)$params["filter"]);

		$array = array();

		$byKey = !empty($params["byKey"]) ? $params["byKey"] : "CODE";


		$res = $entity::getList(array_intersect_key($params, array_flip([
			"select",
			"order",
			"filter",
		])));
		while ($item = $res->fetch()) {
			$array[$item[$byKey]] = $item;
		}

		return $array;
	}

	public function prepareHlParams()
	{
		if (!Loader::includeModule("highloadblock")) return;

		# получить Хайлоад-блок
		$hl = HlTable::getList(array(
			"select" => array("ID", "NAME", "TABLE_NAME"),
			"filter" => $this->hlParams,
		))->fetch();

		if ($hl) {
			$hl["LANG"] = HighloadBlockLangTable::getList([
				"filter" => [
					"LID" => LANGUAGE_ID,
					"ID"  => $hl["ID"],
				],
				"select" => ["NAME"],
			])->fetch();
		}

		$this->hlParams = $hl;
	}

	public function setParams($params = [])
	{
		$this->params = array_merge($this->params, $params);
	}

	public function getNavString(&$data = false)
	{
		$entity = $this->entity;
		$count = $this->params["limit"];
		$params = $this->params;


		# не смог заставить эту штуку работать с уже имеющимся CDBResult
		# при наличии лимита и оффсета она некорректно работает,
		# поэтому придется делать два запроса
		unset($params["limit"]);
		unset($params["offset"]);

		$data = new CDBResult($entity::getList($params));
		$data->NavStart($count);


		$navString = $data->GetPageNavStringEx($navComponentObject, "");
		# не нашел как передать что-то вроде PAGER_BASE_LINK,
		# чтобы не подставлялся путь к ajax-файлу, удаляю его
		$navString = explode("?", $navString);
		$navString = array_pop($navString);


		return $navString;
	}

	/**
	 * @param bool $key
	 *
	 * @return mixed
	 */
	public function getList_($key = false)
	{
		$array = [];
		$this->prepareResult();
		if (empty($this->result)) return false;
		while ($item = $this->result->fetch()) {
			if (!$key || !array_key_exists($key, $item)) {
				$array[] = $item;
			} else {
				$array[$item[$key]] = $item;
			}
		}

		// d($array,"array");
		return $array;
	}

	public function getById_($id)
	{
		$this->setParams([
			"filter" => ["ID" => $id],
		]);

		return $this->getList_()[0];
	}

	protected function getInitFilterFromProperty($iblock, $code)
	{
		if (!Loader::includeModule("iblock")) return false;

		$prop = CIBlockProperty::GetList(
			array(),
			array("ACTIVE" => "Y", "IBLOCK_ID" => $iblock, "CODE" => $code)
		)->fetch();

		$tableName = $prop['USER_TYPE_SETTINGS']['TABLE_NAME'];

		if (!$tableName) return false;

		return ["TABLE_NAME" => $tableName];
	}

	protected function initEntity()
	{
		if (!Loader::includeModule("highloadblock")) return;

		$this->prepareHlParams();

		if (!$this->hlParams) return;

		// преобразовать ХЛ-блок в сущность
		$entity = HlTable::compileEntity($this->hlParams);
		$this->entity = $entity->getDataClass();
	}

	protected function prepareResult()
	{
		if (!empty($this->result)) return;
		$entity = $this->entity;

		if (isset($this->params["limit"])) {
			$iNumPage = is_set($_GET['PAGEN_1']) ? $_GET['PAGEN_1'] : 1;
			$this->params = array_merge([
				"offset" => ($iNumPage - 1) * $this->params["limit"],
			], $this->params);
		}

		if (!empty($entity)) {
			$this->result = new CDBResult($entity::getList($this->params));
		}
	}

}