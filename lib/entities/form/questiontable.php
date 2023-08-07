<?php

namespace Uplab\Core\Entities\Form;

use Bitrix\Main;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Data;
use Bitrix\Main\Localization\Loc;


Loc::loadMessages(__FILE__);


/**
 * Fields:
 * <ul>
 * <li> ID int mandatory
 * <li> FORM_ID int mandatory
 * <li> TIMESTAMP_X datetime optional
 * <li> ACTIVE bool optional default "Y"
 * <li> TITLE string optional
 * <li> TITLE_TYPE enum ("text", "html") optional default "text"
 * <li> SID string(50) optional
 * <li> C_SORT int optional default 100
 * <li> ADDITIONAL bool optional default "N"
 * <li> REQUIRED bool optional default "N"
 * <li> IN_FILTER bool optional default "N"
 * <li> IN_RESULTS_TABLE bool optional default "N"
 * <li> IN_EXCEL_TABLE bool optional default "Y"
 * <li> FIELD_TYPE string(50) optional
 * <li> IMAGE_ID int optional
 * <li> COMMENTS string optional
 * <li> FILTER_TITLE string optional
 * <li> RESULTS_TABLE_TITLE string optional
 * </ul>
 */
class QuestionTable extends Data\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return "b_form_field";
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
				"TIMESTAMP_X"
			),
			new Fields\BooleanField(
				"ACTIVE",
				[
					"values" => ["N", "Y"],
				]
			),
			new Fields\TextField(
				"TITLE"
			),
			new Fields\EnumField(
				"TITLE_TYPE",
				[
					"values" => ["text", "html"],
				]
			),
			new Fields\StringField(
				"SID",
				[
					"validation" => [self::class, "validateSid"],
				]
			),
			new Fields\IntegerField(
				"SORT",
				[
					"column_name" => "C_SORT",
				]
			),
			new Fields\BooleanField(
				"ADDITIONAL",
				[
					"values" => ["N", "Y"],
				]
			),
			new Fields\BooleanField(
				"REQUIRED",
				[
					"values" => ["N", "Y"],
				]
			),
			new Fields\BooleanField(
				"IN_FILTER",
				[
					"values" => ["N", "Y"],
				]
			),
			new Fields\BooleanField(
				"IN_RESULTS_TABLE",
				[
					"values" => ["N", "Y"],
				]
			),
			new Fields\BooleanField(
				"IN_EXCEL_TABLE",
				[
					"values" => ["N", "Y"],
				]
			),
			new Fields\StringField(
				"FIELD_TYPE",
				[
					"validation" => [self::class, "validateFieldType"],
				]
			),
			new Fields\IntegerField(
				"IMAGE_ID"
			),
			new Fields\TextField(
				"COMMENTS"
			),
			new Fields\TextField(
				"FILTER_TITLE"
			),
			new Fields\TextField(
				"RESULTS_TABLE_TITLE"
			),


			new Fields\IntegerField(
				"FORM_ID",
				[
					"required" => true,
				]
			),
			new Fields\Relations\Reference(
				"FORM",
				FormTable::class,
				[
					"=this.FORM_ID" => "ref.ID",
				]
			),

			new Fields\Relations\OneToMany(
				"ANSWERS",
				AnswerTable::class,
				"QUESTION"
			),
		];
	}

	/**
	 * Returns validators for SID field.
	 *
	 * @return array
	 */
	public static function validateSid()
	{
		return [
			new Fields\Validators\LengthValidator(null, 50),
		];
	}

	/**
	 * Returns validators for FIELD_TYPE field.
	 *
	 * @return array
	 */
	public static function validateFieldType()
	{
		return array(
			new Fields\Validators\LengthValidator(null, 50),
		);
	}
}