<?php

namespace Uplab\Core\Entities\Form;


use Bitrix\Main\ORM\Data;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\SiteTable;
use CMain;
use CUser;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 */


/**
 * Class FormTable
 *
 * Это D7 обертка над стандартной таблицей b_form
 * Если вдруг появится нативная сущность для этой таблицы в ядре Битрикса, следует перевести все вызовы
 * на нее избавиться от данного класса
 *
 * @see     \CForm
 *
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> TIMESTAMP_X datetime optional
 * <li> NAME string(255) mandatory
 * <li> SID string(50) mandatory
 * <li> BUTTON string(255) optional
 * <li> C_SORT int optional default 100
 * <li> FIRST_SITE_ID string(2) optional
 * <li> IMAGE_ID int optional
 * <li> USE_CAPTCHA bool optional default "N"
 * <li> DESCRIPTION string optional
 * <li> DESCRIPTION_TYPE enum ("text", "html") optional default "html"
 * <li> FORM_TEMPLATE string optional
 * <li> USE_DEFAULT_TEMPLATE bool optional default "Y"
 * <li> SHOW_TEMPLATE string(255) optional
 * <li> MAIL_EVENT_TYPE string(50) optional
 * <li> SHOW_RESULT_TEMPLATE string(255) optional
 * <li> PRINT_RESULT_TEMPLATE string(255) optional
 * <li> EDIT_RESULT_TEMPLATE string(255) optional
 * <li> FILTER_RESULT_TEMPLATE string optional
 * <li> TABLE_RESULT_TEMPLATE string optional
 * <li> USE_RESTRICTIONS bool optional default "N"
 * <li> RESTRICT_USER int optional
 * <li> RESTRICT_TIME int optional
 * <li> RESTRICT_STATUS string(255) optional
 * <li> STAT_EVENT1 string(255) optional
 * <li> STAT_EVENT2 string(255) optional
 * <li> STAT_EVENT3 string(255) optional
 * </ul>
 *
 * @package Bitrix\Form
 **/
class FormTable extends Data\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return "b_form";
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			new Fields\IntegerField(
				"ID",
				[
					"primary"      => true,
					"autocomplete" => true,
				]
			),
			new Fields\DatetimeField(
				"TIMESTAMP_X"
			),
			new Fields\StringField(
				"NAME",
				[
					"required"   => true,
					"validation" => [self::class, "validateName"],
				]
			),
			new Fields\StringField(
				"SID",
				[
					"required"   => true,
					"validation" => [self::class, "validateSid"],
				]
			),
			new Fields\StringField(
				"BUTTON",
				[
					"validation" => [self::class, "validateButton"],
				]
			),
			new Fields\IntegerField(
				"SORT",
				[
					"column_name" => "C_SORT",
				]
			),
			new Fields\StringField(
				"FIRST_SITE_ID",
				[
					"validation" => [self::class, "validateFirstSiteId"],
				]
			),
			new Fields\IntegerField(
				"IMAGE_ID"
			),
			new Fields\BooleanField(
				"USE_CAPTCHA",
				[
					"values" => ["N", "Y"],
				]
			),
			new Fields\TextField(
				"DESCRIPTION"
			),
			new Fields\EnumField(
				"DESCRIPTION_TYPE",
				[
					"values" => ["text", "html"],
				]
			),
			new Fields\TextField(
				"FORM_TEMPLATE"
			),
			new Fields\BooleanField(
				"USE_DEFAULT_TEMPLATE",
				[
					"values" => ["N", "Y"],
				]
			),
			new Fields\StringField(
				"SHOW_TEMPLATE",
				[
					"validation" => [self::class, "validateShowTemplate"],
				]
			),
			new Fields\StringField(
				"MAIL_EVENT_TYPE",
				[
					"validation" => [self::class, "validateMailEventType"],
				]
			),
			new Fields\StringField(
				"SHOW_RESULT_TEMPLATE",
				[
					"validation" => [self::class, "validateShowResultTemplate"],
				]
			),
			new Fields\StringField(
				"PRINT_RESULT_TEMPLATE",
				[
					"validation" => [self::class, "validatePrintResultTemplate"],
				]
			),
			new Fields\StringField(
				"EDIT_RESULT_TEMPLATE",
				[
					"validation" => [self::class, "validateEditResultTemplate"],
				]
			),
			new Fields\TextField(
				"FILTER_RESULT_TEMPLATE"
			),
			new Fields\TextField(
				"TABLE_RESULT_TEMPLATE"
			),
			new Fields\BooleanField(
				"USE_RESTRICTIONS",
				[
					"values" => ["N", "Y"],
				]
			),
			new Fields\IntegerField(
				"RESTRICT_USER"
			),
			new Fields\IntegerField(
				"RESTRICT_TIME"
			),
			new Fields\StringField(
				"RESTRICT_STATUS",
				[
					"validation" => [self::class, "validateRestrictStatus"],
				]
			),
			new Fields\StringField(
				"STAT_EVENT1",
				[
					"validation" => [self::class, "validateStatEvent1"],
				]
			),
			new Fields\StringField(
				"STAT_EVENT2",
				[
					"validation" => [self::class, "validateStatEvent2"],
				]
			),
			new Fields\StringField(
				"STAT_EVENT3",
				[
					"validation" => [self::class, "validateStatEvent3"],
				]
			),

			(new Fields\Relations\ManyToMany("SITES", SiteTable::class))
				->configureTableName("b_form_2_site")
				->configureRemotePrimary("LID", "SITE_ID"),

			new Fields\Relations\OneToMany(
				"ANSWERS",
				AnswerTable::class,
				"FORM"
			),

			new Fields\Relations\OneToMany(
				"QUESTIONS",
				QuestionTable::class,
				"FORM"
			),
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
			new Fields\Validators\LengthValidator(null, 255),
		);
	}

	/**
	 * Returns validators for SID field.
	 *
	 * @return array
	 */
	public static function validateSid()
	{
		return array(
			new Fields\Validators\LengthValidator(null, 50),
		);
	}

	/**
	 * Returns validators for BUTTON field.
	 *
	 * @return array
	 */
	public static function validateButton()
	{
		return array(
			new Fields\Validators\LengthValidator(null, 255),
		);
	}

	/**
	 * Returns validators for FIRST_SITE_ID field.
	 *
	 * @return array
	 */
	public static function validateFirstSiteId()
	{
		return array(
			new Fields\Validators\LengthValidator(null, 2),
		);
	}

	/**
	 * Returns validators for SHOW_TEMPLATE field.
	 *
	 * @return array
	 */
	public static function validateShowTemplate()
	{
		return array(
			new Fields\Validators\LengthValidator(null, 255),
		);
	}

	/**
	 * Returns validators for MAIL_EVENT_TYPE field.
	 *
	 * @return array
	 */
	public static function validateMailEventType()
	{
		return array(
			new Fields\Validators\LengthValidator(null, 50),
		);
	}

	/**
	 * Returns validators for SHOW_RESULT_TEMPLATE field.
	 *
	 * @return array
	 */
	public static function validateShowResultTemplate()
	{
		return array(
			new Fields\Validators\LengthValidator(null, 255),
		);
	}

	/**
	 * Returns validators for PRINT_RESULT_TEMPLATE field.
	 *
	 * @return array
	 */
	public static function validatePrintResultTemplate()
	{
		return array(
			new Fields\Validators\LengthValidator(null, 255),
		);
	}

	/**
	 * Returns validators for EDIT_RESULT_TEMPLATE field.
	 *
	 * @return array
	 */
	public static function validateEditResultTemplate()
	{
		return array(
			new Fields\Validators\LengthValidator(null, 255),
		);
	}

	/**
	 * Returns validators for RESTRICT_STATUS field.
	 *
	 * @return array
	 */
	public static function validateRestrictStatus()
	{
		return array(
			new Fields\Validators\LengthValidator(null, 255),
		);
	}

	/**
	 * Returns validators for STAT_EVENT1 field.
	 *
	 * @return array
	 */
	public static function validateStatEvent1()
	{
		return array(
			new Fields\Validators\LengthValidator(null, 255),
		);
	}

	/**
	 * Returns validators for STAT_EVENT2 field.
	 *
	 * @return array
	 */
	public static function validateStatEvent2()
	{
		return array(
			new Fields\Validators\LengthValidator(null, 255),
		);
	}

	/**
	 * Returns validators for STAT_EVENT3 field.
	 *
	 * @return array
	 */
	public static function validateStatEvent3()
	{
		return array(
			new Fields\Validators\LengthValidator(null, 255),
		);
	}

	/**
	 * @return string
	 */
	public static function getObjectClass(): string
	{
		if (class_exists(__NAMESPACE__ . '\EO_Form')) {
			return Form::class;
		} else {
			return parent::getObjectClass();
		}
	}

	/**
	 * @return string
	 */
	public static function getCollectionClass(): string
	{
		if (class_exists(__NAMESPACE__ . '\EO_Form_Collection')) {
			return Forms::class;
		} else {
			return parent::getCollectionClass();
		}
	}

	/**
	 * @param        $sid
	 * @param string $siteId
	 * @param array  $params
	 *
	 * @return \Bitrix\Main\ORM\Query\Result|EO_Form_Result
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function getByBySid($sid, $siteId = SITE_ID, $params = [])
	{
		$params["limit"] = 1;

		$params["filter"] = [
			"SID" => "FEEDBACK_FORM",
		];

		if ($siteId) {
			$params["filter"]["=SITES.LID"] = $siteId;
		}

		$params["select"] = array_unique(
			array_merge(
				(array)$params["select"],
				[
					'ID',
					'SORT',
					'NAME',
					'SID',
					'BUTTON',
					'FIRST_SITE_ID',
					'IMAGE_ID',
					'USE_CAPTCHA',
					'DESCRIPTION',
					'DESCRIPTION_TYPE',
					'USE_RESTRICTIONS',
					'RESTRICT_USER',
					'RESTRICT_TIME',
					'RESTRICT_STATUS',
					'STAT_EVENT1',
					'STAT_EVENT2',
					'STAT_EVENT3',
				]
			)
		);

		$params["order"] =
			$params["order"]
			??
			[
				"SORT" => "asc",
				"ID"   => "asc",
			];

		return self::getList($params);
	}

}
