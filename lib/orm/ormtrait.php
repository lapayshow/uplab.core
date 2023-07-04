<? /** @noinspection PhpUnusedParameterInspection */

namespace Uplab\Core\Orm;


use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\Type;
use CFile;


/**
 * Некоторые необходимые методы и инструменты для работы с ORM
 *
 * @method static getByPrimary
 * @method static getMap
 *
 * @package Uplab\Core\ORM
 */
trait OrmTrait
{
	public static function getClassName()
	{
		static $className = false;
		$currentClass = get_called_class();

		if ($className === false) {
			$className = substr($currentClass, strrpos($currentClass, '\\') + 1);
		}

		return $className;
	}

	public static function getEntityName()
	{
		static $entityName = false;

		if ($entityName === false) {
			$entityName = str_replace(
				"\\",
				"",
				strtolower(
					Entity\Base::normalizeName(self::getClassName())
				)
			);
		}

		return $entityName;
	}

	/**
	 * Генерирует дефолтное название таблицы  (для корректной работы DigitalWand\AdminHelper)
	 * Логика генерации основана на \Bitrix\Main\ORM\Entity::postInitialize
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		static $tableName = null;

		if ($tableName === null) {
			$classNameWithoutTable = ltrim(Entity\Base::normalizeName(get_called_class()), "\\");
			$classPath = explode("\\", $classNameWithoutTable);

			if ($classPath[0] == "Bitrix") unset($classPath[0]);
			if ($classPath[1] == "Main") unset($classPath[1]);

			// Стандартный неймспейс для сущностей: Entities, убираем его из названия таблицы
			if ($classPath[2] == "Entities" && count($classPath) > 3) unset($classPath[2]);

			array_unshift($classPath, "b");

			$classPath = array_map("strtolower", $classPath);

			$lastIndex = count($classPath) - 1;
			if ($classPath[$lastIndex] == $classPath[$lastIndex - 1]) {
				array_pop($classPath);
			}

			// $tableName = implode("_", array_unique($classPath));
			$tableName = implode("_", $classPath);
		}

		return $tableName;
	}

	public static function install()
	{
		$instance = Entity\Base::getInstance(static::class);
		$name = $instance->getDBTableName();

		if (!Application::getConnection()->isTableExists($name)) {
			$instance->createDbTable();
		}

		self::updateDBStructureByEntity();
	}

	public static function _unInstall()
	{
		$instance = Entity\Base::getInstance(static::class);
		$name = $instance->getDBTableName();
		/** @noinspection SqlDialectInspection */
		Application::getConnection()->queryExecute("DROP TABLE IF EXISTS `{$name}`");
	}

	public static function unInstall()
	{
		self::_unInstall();
		self::defaultAddUpdateHandler();
	}

	public static function truncate()
	{
		$instance = Entity\Base::getInstance(static::class);
		Application::getConnection()->truncateTable($instance->getDBTableName());
	}

	public static function parseDBColumnsFromCreateQuery()
	{
		$instance = Entity\Base::getInstance(self::class);

		$sql = $instance->compileDbTableStructureDump();
		$sql = current((array)$sql);

		preg_match("~CREATE TABLE `([^`]+)` \((.+)\)~", $sql, $match);

		$fields = explode(", ", $match[2]);

		$columnsMap = [];

		if (is_callable(self::class, "getMap")) {
			// Собираем информацию о полях таблицы

			$map = self::getMap();

			/** @var Entity\StringField $column */
			foreach ($map as $column) {
				if (is_object($column)) {
					$name = $column->getName();
					$columnsMap[$name] = [];

					if (is_callable([$column, "getSize"])) {
						$columnsMap[$name]["size"] = $column->getSize();
					}
				}
			}
		}

		foreach ($fields as $i => $field) {
			if (strpos($field, "PRIMARY KEY") === 0) {
				unset($fields[$i]);
			} else {
				// Даже если указать размер поля в getMap,
				// при автоматическом создании таблицы, ставится значение по умолчанию (255).
				// Исправляем это.

				preg_match("~`(.+)`.*\((\d+)\)~", $field, $match);

				if (!empty($match)) {
					// print_r($match);

					$columnName = $match[1];
					$size = $match[2];

					if (!empty($size) && !empty($columnsMap[$columnName]["size"])) {
						$fields[$i] = str_replace(
							"({$size})",
							"({$columnsMap[$columnName]["size"]})",
							$field
						);
					}
				}
			}
		}

		// print_r($fields);

		return $fields;
	}

	public static function getExistingDBColumns()
	{
		$instance = Entity\Base::getInstance(self::class);
		$name = $instance->getDBTableName();

		$columns = [];

		$res = Application::getConnection()->query("SHOW COLUMNS FROM `$name`");
		while ($column = $res->fetch()) {
			$columns[$column["Field"]] = $column["Field"];
		}

		return $columns;
	}

	public static function getDBColumnFromSql($columnSql)
	{
		preg_match("~`(.+)`~", $columnSql, $match);
		$column = $match[1];

		return $column;
	}

	public static function isDBColumnExists($column = false, $columnSql = false)
	{
		if (empty($column) && empty($columnSql)) return false;
		if (empty($column)) $column = self::getDBColumnFromSql($columnSql);

		$instance = Entity\Base::getInstance(self::class);
		$name = $instance->getDBTableName();

		$res = Application::getConnection()->query(
			"SHOW COLUMNS FROM `$name` LIKE '$column'"
		);

		return !empty($res->fetch());
	}

	public static function updateDBStructureByEntity()
	{
		$connection = Application::getConnection();
		$instance = Entity\Base::getInstance(self::class);
		$name = $instance->getDBTableName();

		$columnsSqlList = self::parseDBColumnsFromCreateQuery();
		$currentColumns = self::getExistingDBColumns();

		foreach ($columnsSqlList as $columnSql) {
			$column = self::getDBColumnFromSql($columnSql);

			if (!empty($currentColumns[$column])) {
				$connection->queryExecute(
					"ALTER TABLE `{$name}` MODIFY {$columnSql};"
				);
				unset($currentColumns[$column]);
			} else {
				$connection->queryExecute(
					"ALTER TABLE `{$name}` ADD {$columnSql};"
				);
			}
		}

		// удаляем лишние поля таблицы
		foreach ($currentColumns as $currentColumn) {
			/** @noinspection SqlDialectInspection */
			$connection->queryExecute(
				"ALTER TABLE `{$name}` DROP COLUMN `{$currentColumn}`;"
			);
		}
	}

	/**
	 * Обработчик, удаляющий файлы из БД при удалении элементов,
	 * для корректной работы нужно объявить массив с полями типа Файл.
	 * Метод может быть переопределен или скопирован для расширения функциональности.
	 * -
	 * Пример:
	 * -
	 *    private static $fileColumns = [
	 *      "PICTURE",
	 *    ];
	 *
	 * @param Entity\Event $event
	 */
	public static function onBeforeDelete(Entity\Event $event)
	{
		self::getEventData($event, $id, $item);
		self::removeFilesOnDelete($item);
	}

	public static function onBeforeUpdate(Entity\Event $event)
	{
		$result = new Entity\EventResult;

		self::setTimestamp($event, $result);

		// if (!empty(self::$fileColumns)) {
		// 	self::getEventData($event, $id, $item);
		// 	self::removeFilesOnChange($event, $result, $item);
		// }

		return $result;
	}

	/**
	 * Метод, изменяющий TIMESTAMP.
	 * Вызывыется в обработчике onBeforeUpdate.
	 * Для работы необходимо указать переменную $timestampColumn
	 * (значения - либо true, либо строка с названием поля).
	 * По умолчанию поле - TIMESTAMP_X
	 * -
	 * Пример:
	 * -
	 *    private static $timestampColumn = true;
	 *    // или
	 *    private static $timestampColumn = "OTHER_TIMESTAMP_COLUMN";
	 *
	 * @param Entity\Event       $event
	 * @param Entity\EventResult $result
	 *
	 * @throws \Bitrix\Main\ObjectException
	 */
	public static function setTimestamp(Entity\Event $event, Entity\EventResult $result)
	{
		if (empty(self::$timestampColumn)) return;
		if (!is_string(self::$timestampColumn)) {
			self::$timestampColumn = "TIMESTAMP_X";
		}

		$result->modifyFields([self::$timestampColumn => new Type\DateTime]);
	}

	public static function removeFilesOnDelete(&$item)
	{
		/** @noinspection PhpUndefinedFieldInspection */
		if (empty(self::$fileColumns)) return;

		/** @noinspection PhpUndefinedFieldInspection */
		foreach (self::$fileColumns as $fileColumn) {
			if ($item[$fileColumn]) CFile::Delete($item[$fileColumn]);
		}
	}

	public static function getEventData(Entity\Event $event, &$id = false, &$item = -1)
	{
		$params = $event->getParameters();

		if (empty($id)) $id = current($params["primary"]);

		if ($item !== -1) {
			/** @noinspection
			 * PhpUndefinedMethodInspection
			 * PhpMethodParametersCountMismatchInspection
			 */
			$item = self::getByPrimary($id)->fetch();
		}
	}

	public static function clearEntityCacheTag()
	{
		$cacheManager = Application::getInstance()->getTaggedCache();
		$cacheManager->clearByTag(self::class);
	}

	public static function defaultAddUpdateHandler()
	{
		self::clearEntityCacheTag();
	}

}