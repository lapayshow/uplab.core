<?

namespace __namespace__\__Entity__;


use Bitrix\Iblock\ElementTable;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Query;
use Bitrix\Main\Type;
use Bitrix\Main\UserTable;
use Uplab\Core\Orm;
use CMain;
use CUser;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 */


class __Entity__Table extends Data\DataManager
{
	private static $moduleId        = "__module__";
	private static $fileColumns     = [
		// "PICTURE",
	];
	private static $timestampColumn = true;

	use Orm\OrmTrait;

	public static function getMap()
	{
		return [
			new Fields\IntegerField(
				"ID",
				[
					"primary"      => true,
					"autocomplete" => true,
				]
			),
			new Fields\DateTimeField(
				"CREATED_AT",
				[
					"title"         => "Дата создания",
					"default_value" => new Type\DateTime,
					"required"      => true,
				]
			),
			new Fields\DateTimeField(
				"TIMESTAMP_X",
				[
					"title"         => "Дата изменения",
					"default_value" => new Type\DateTime,
				]
			),

			new Fields\StringField(
				"NAME",
				[
					"title"    => "Название",
					"required" => true,
				]
			),

			new Fields\IntegerField(
				"USER_ID",
				[
					"title"    => "Идентификатор пользователя",
					"required" => true,
				]
			),
			new Fields\Relations\Reference(
				"USER",
				UserTable::class,
				Query\Join::on("this.USER_ID", "ref.ID")
			),

			new Fields\IntegerField(
				"ELEMENT_ID",
				[
					"title"    => "Идентификатор элемента",
					"required" => true,
				]
			),
			new Fields\Relations\Reference(
				"ELEMENT",
				ElementTable::class,
				Query\Join::on("this.IBLOCK_ID", "ref.ID")
			),
		];
	}

	/**
	 * @return string
	 */
	public static function getObjectClass(): string
	{
		return __Entity__::class;
	}

	/**
	 * @return string
	 */
	public static function getCollectionClass(): string
	{
		return __Entity__s::class;
	}

	public static function unInstall()
	{
		self::_unInstall();
		self::defaultAddUpdateHandler();
	}
}
