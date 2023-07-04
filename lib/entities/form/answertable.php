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
 * <li> FIELD_ID int mandatory
 * <li> TIMESTAMP_X datetime optional
 * <li> MESSAGE string optional
 * <li> C_SORT int optional default 100
 * <li> ACTIVE bool optional default 'Y'
 * <li> VALUE string(255) optional
 * <li> FIELD_TYPE string(255) mandatory default 'text'
 * <li> FIELD_WIDTH int optional
 * <li> FIELD_HEIGHT int optional
 * <li> FIELD_PARAM string optional
 * </ul>
 **/
class AnswerTable extends Data\DataManager
{

	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return "b_form_answer";
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\SystemException
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
			new Fields\TextField(
				"MESSAGE"
			),
			new Fields\IntegerField(
				"SORT",
				[
					"column_name" => "C_SORT",
				]
			),
			new Fields\BooleanField(
				"ACTIVE",
				[
					"values" => ["N", "Y"],
				]
			),
			new Fields\StringField(
				"VALUE",
				[
					"validation" => [self::class, "validateValue"],
				]
			),
			new Fields\StringField(
				"FIELD_TYPE",
				[
					"required"   => true,
					"validation" => [self::class, "validateFieldType"],
				]
			),
			new Fields\IntegerField(
				"FIELD_WIDTH"
			),
			new Fields\IntegerField(
				"FIELD_HEIGHT"
			),
			new Fields\TextField(
				"FIELD_PARAM"
			),

			/**
			 * Конструируем значение name для HTML-инпута ответа в форме
			 *
			 * @see https://dev.1c-bitrix.ru/api_help/form/htmlnames.php
			 */
			new Fields\ExpressionField(
				"FIELD_NAME",
				implode(" ", [
					"CASE",

					"WHEN %2\$s = 'checkbox' OR %2\$s = 'multiselect' THEN",
					"   CONCAT('form_', %2\$s, '_', %3\$s, '[]')",

					"WHEN %2\$s = 'dropdown' OR %2\$s = 'radio' THEN",
					"   CONCAT('form_', %2\$s, '_', %3\$s)",

					"ELSE",
					"   CONCAT('form_', %2\$s, '_', %1\$s)",

					"END",
				]),
				[
					"ID",
					"FIELD_TYPE",
					"QUESTION.SID",
				]
			),

			new Fields\IntegerField(
				"QUESTION_ID",
				[
					"required"    => true,
					"column_name" => "FIELD_ID",
				]
			),
			new Fields\Relations\Reference(
				"QUESTION",
				QuestionTable::class,
				[
					"=this.QUESTION_ID" => "ref.ID",
				]
			),

			new Fields\ExpressionField(
				"FORM_ID",
				"(%s)",
				[
					"QUESTION.FORM_ID",
				]
			),
			new Fields\Relations\Reference(
				"FORM",
				FormTable::class,
				[
					"=this.FORM_ID" => "ref.ID",
				]
			),

		];
	}

	/**
	 * Returns validators for VALUE field.
	 *
	 * @return array
	 */
	public static function validateValue()
	{
		return array(
			new Fields\Validators\LengthValidator(null, 255),
		);
	}

	/**
	 * Returns validators for FIELD_TYPE field.
	 *
	 * @return array
	 */
	public static function validateFieldType()
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
		return Answer::class;
	}

	/**
	 * @return string
	 */
	public static function getCollectionClass(): string
	{
		return Answers::class;
	}

	/**
	 * @param Form  $form
	 * @param array $params
	 *
	 * @return Main\ORM\Query\Result|EO_Answer_Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getByForm($form, $params = [])
	{
		if (!($form instanceof Form)) {
			throw new \Exception("\$form must be an object of Form");
		}

		return self::getByFormId($form->getId(), $params);
	}

	/**
	 * @param int   $formId
	 * @param array $params
	 *
	 * @return Main\ORM\Query\Result|EO_Answer_Result
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 */
	public static function getByFormId($formId, $params = [])
	{
		$params["filter"] = array_unique(
			array_merge(
				[
					"=FORM_ID"         => $formId,
					"=QUESTION.ACTIVE" => true,
					"=ACTIVE"          => true,
				],
				(array)$params["filter"]
			)
		);

		$params["select"] = array_unique(
			array_merge(
				(array)$params["select"],
				[
					"ID",
					"ACTIVE",
					"SORT",
					"MESSAGE",
					"VALUE",
					"FIELD_TYPE",
					"FIELD_WIDTH",
					"FIELD_HEIGHT",
					"FIELD_PARAM",
					"FIELD_NAME",

					"QUESTION.ID",
					"QUESTION.FORM_ID",
					"QUESTION.IMAGE_ID",
					"QUESTION.SID",
					"QUESTION.ACTIVE",
					"QUESTION.SORT",
					"QUESTION.TITLE",
					"QUESTION.TITLE_TYPE",
					"QUESTION.ADDITIONAL",
					"QUESTION.REQUIRED",
					"QUESTION.COMMENTS",
				]
			)
		);

		$params["order"] =
			$params["order"]
			??
			[
				"QUESTION.SORT" => "asc",
				"SORT"          => "asc",
			];

		return self::getList($params);
	}

}
