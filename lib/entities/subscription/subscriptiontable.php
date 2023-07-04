<?

namespace Uplab\Core\Entities\Subscription;


use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Uplab\Core\Entities\ListRubric\ListRubricTable;


/**
 * Class SubscriptionTable
 *
 * Это D7-обертка над стандартной таблицей,
 * для хранения подписчиков (в стандартном модуле рассылок)
 * Необходима для взаимодействия с сущностью подписчиков через D7
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> DATE_INSERT datetime mandatory
 * <li> DATE_UPDATE datetime optional
 * <li> USER_ID int optional
 * <li> ACTIVE bool optional default 'Y'
 * <li> EMAIL string(255) mandatory
 * <li> FORMAT string(4) mandatory default 'text'
 * <li> CONFIRM_CODE string(8) optional
 * <li> CONFIRMED bool optional default 'N'
 * <li> DATE_CONFIRM datetime mandatory
 * </ul>
 *
 * @package Bitrix\Subscription
 **/
class SubscriptionTable extends Data\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_subscription';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
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
			new Fields\DatetimeField(
				'DATE_INSERT',
				[
					'required' => true,
					'title'    => 'Дата добавления подписки',
				]
			),
			new Fields\DatetimeField(
				'DATE_UPDATE',
				[
					'title' => 'Дата обновления',
				]
			),
			new Fields\IntegerField(
				'USER_ID',
				[
					'title' => 'ID пользователя',
				]
			),
			new Fields\BooleanField(
				'ACTIVE',
				[
					'values' => ['N', 'Y'],
					'title'  => 'Активность',
				]
			),
			new Fields\StringField(
				'EMAIL',
				[
					'required'   => true,
					'validation' => [self::class, 'validateEmail'],
					'title'      => 'E-mail',
				]
			),
			new Fields\StringField(
				'FORMAT',
				[
					'required'   => true,
					'validation' => [self::class, 'validateFormat'],
					'title'      => 'Формат',
				]
			),
			new Fields\StringField(
				'CONFIRM_CODE',
				[
					'validation' => [self::class, 'validateConfirmCode'],
					'title'      => 'Код подтверждения',
				]
			),
			new Fields\BooleanField(
				'CONFIRMED',
				[
					'values' => ['N', 'Y'],
					'title'  => 'Подписка подтверждена?',
				]
			),
			new Fields\DatetimeField(
				'DATE_CONFIRM',
				[
					'required' => true,
					'title'    => 'Дата подтверждения',
				]
			),

			(new Fields\Relations\ManyToMany(
				"RUBRICS",
				ListRubricTable::class
			))
				->configureTableName("b_subscription_rubric")
				->configureJoinType("inner"),

		];
	}

	/**
	 * Returns validators for EMAIL field.
	 *
	 * @return array
	 */
	public static function validateEmail()
	{
		return array(
			new Fields\Validators\LengthValidator(null, 255),
		);
	}

	/**
	 * Returns validators for FORMAT field.
	 *
	 * @return array
	 */
	public static function validateFormat()
	{
		return array(
			new Fields\Validators\LengthValidator(null, 4),
		);
	}

	/**
	 * Returns validators for CONFIRM_CODE field.
	 *
	 * @return array
	 */
	public static function validateConfirmCode()
	{
		return array(
			new Fields\Validators\LengthValidator(null, 8),
		);
	}

	/**
	 * @return string
	 */
	public static function getObjectClass(): string
	{
		return Subscription::class;
	}

	/**
	 * @return string
	 */
	public static function getCollectionClass(): string
	{
		return Subscriptions::class;
	}
}