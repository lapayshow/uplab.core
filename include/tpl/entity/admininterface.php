<?

namespace __namespace__\__Entity__\AdminInterface;


use Bitrix\Main\Localization\Loc;
use CMain;
use CUser;
use DigitalWand\AdminHelper\Helper\AdminInterface;
use DigitalWand\AdminHelper\Widget;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 */


/**
 * Описание интерфейса (табов и полей) админки для сущности __Entity__.
 * {@inheritdoc}
 */
class __Entity__AdminInterface extends AdminInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function fields()
	{
		return [
			"MAIN" => [
				"NAME"   => Loc::getMessage("__module_____ENTITY___LIST_TITLE"),
				"FIELDS" => [
					"ID"          => [
						"WIDGET"           => new Widget\NumberWidget(),
						"READONLY"         => true,
						"FILTER"           => true,
						"HIDE_WHEN_CREATE" => true,
					],
					"CREATED_AT"  => [
						"WIDGET"           => new Widget\DateTimeWidget(),
						"READONLY"         => true,
						"HIDE_WHEN_CREATE" => true,
						"HEADER"           => false,
					],
					"TIMESTAMP_X" => [
						"WIDGET"           => new Widget\DateTimeWidget(),
						"READONLY"         => true,
						"HIDE_WHEN_CREATE" => true,
						// "HEADER"           => false,
					],

					"NAME" => [
						"WIDGET"   => new Widget\StringWidget(),
						// "SIZE"     => 60,
						"REQUIRED" => true,
					],
				],
			],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function helpers()
	{
		return array(
			__Entity__ListHelper::class,
			__Entity__EditHelper::class,
		);
	}
}
