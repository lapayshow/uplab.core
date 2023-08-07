<?

namespace Uplab\Core\Entities\ListRubric;


use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Fields\Validators;


/**
 * Класс-обертка над стандартной таблицей `b_list_rubric` (рубрики модуля рассылок)
 * Необходим, чтобы реализовать получения подписчиков и их подписок через D7 API
 *
 * Fields:
 *
 * <ul>
 *     <li> ID int mandatory
 *     <li> LID string(2) mandatory
 *     <li> CODE string(100) optional
 *     <li> NAME string(100) optional
 *     <li> DESCRIPTION string optional
 *     <li> SORT int optional default 100
 *     <li> ACTIVE bool optional default 'Y'
 *     <li> AUTO bool optional default 'N'
 *     <li> DAYS_OF_MONTH string(100) optional
 *     <li> DAYS_OF_WEEK string(15) optional
 *     <li> TIMES_OF_DAY string(255) optional
 *     <li> TEMPLATE string(100) optional
 *     <li> LAST_EXECUTED datetime optional
 *     <li> VISIBLE bool optional default 'Y'
 *     <li> FROM_FIELD string(255) optional
 * </ul>
 *
 * @package Uplab\Core\Entities\ListRubric
 */
class ListRubricTable extends Data\DataManager
{

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'b_list_rubric';
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
				'ID',
				[
					'primary'      => true,
					'autocomplete' => true,
				]
			),
			new Fields\StringField(
				'LID',
				[
					'required'   => true,
					'validation' => [self::class, 'validateLid'],
				]
			),
			new Fields\StringField(
				'CODE',
				[
					'validation' => [self::class, 'validateCode'],
				]
			),
			new Fields\StringField(
				'NAME',
				[
					'validation' => [self::class, 'validateName'],
				]
			),
			new Fields\TextField('DESCRIPTION', []),

			new Fields\IntegerField('SORT'),

			new Fields\BooleanField('ACTIVE', ['values' => ['N', 'Y']]),
			new Fields\BooleanField('AUTO', ['values' => ['N', 'Y']]),

			new Fields\StringField(
				'DAYS_OF_MONTH',
				[
					'validation' => [self::class, 'validateDaysOfMonth'],
				]
			),
			new Fields\StringField(
				'DAYS_OF_WEEK',
				[
					'validation' => [self::class, 'validateDaysOfWeek'],
				]
			),
			new Fields\StringField(
				'TIMES_OF_DAY',
				[
					'validation' => [self::class, 'validateTimesOfDay'],
				]
			),

			new Fields\StringField(
				'TEMPLATE',
				[
					'validation' => [self::class, 'validateTemplate'],
				]
			),

			new Fields\DatetimeField('LAST_EXECUTED'),

			new Fields\BooleanField('VISIBLE', ['values' => ['N', 'Y']]),

			new Fields\StringField(
				'FROM_FIELD',
				[
					'validation' => [self::class, 'validateFromField'],
				]
			),
		];
	}

	/**
	 * Returns validators for LID field.
	 *
	 * @return array
	 */
	public static function validateLid()
	{
		return array(
			new Validators\LengthValidator(null, 2),
		);
	}

	/**
	 * Returns validators for CODE field.
	 *
	 * @return array
	 */
	public static function validateCode()
	{
		return array(
			new Validators\LengthValidator(null, 100),
		);
	}

	/**
	 * Returns validators for NAME field.
	 *
	 * @return array
	 */
	public static function validateName()
	{
		return array(
			new Validators\LengthValidator(null, 100),
		);
	}

	/**
	 * Returns validators for DAYS_OF_MONTH field.
	 *
	 * @return array
	 */
	public static function validateDaysOfMonth()
	{
		return array(
			new Validators\LengthValidator(null, 100),
		);
	}

	/**
	 * Returns validators for DAYS_OF_WEEK field.
	 *
	 * @return array
	 */
	public static function validateDaysOfWeek()
	{
		return array(
			new Validators\LengthValidator(null, 15),
		);
	}

	/**
	 * Returns validators for TIMES_OF_DAY field.
	 *
	 * @return array
	 */
	public static function validateTimesOfDay()
	{
		return array(
			new Validators\LengthValidator(null, 255),
		);
	}

	/**
	 * Returns validators for TEMPLATE field.
	 *
	 * @return array
	 */
	public static function validateTemplate()
	{
		return array(
			new Validators\LengthValidator(null, 100),
		);
	}

	/**
	 * Returns validators for FROM_FIELD field.
	 *
	 * @return array
	 */
	public static function validateFromField()
	{
		return array(
			new Validators\LengthValidator(null, 255),
		);
	}

	/**
	 * @return string
	 */
	public static function getObjectClass(): string
	{
		return ListRubric::class;
	}

	/**
	 * @return string
	 */
	public static function getCollectionClass(): string
	{
		return ListRubrics::class;
	}

}
