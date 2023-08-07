<?php


namespace Uplab\Core\Entities\Content;


use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use CUserTypeEntity;
use Exception;


class Migration
{
	/**
	 * Run the migration.
	 *
	 * @throws \Exception
	 */
	public static function up()
	{
		Loader::includeModule('highloadblock');

		$arFields = [
			'NAME'       => 'Content',
			'TABLE_NAME' => 'content_area',
		];

		$hlID = 0;

		$dbHL = HighloadBlockTable::getList([
			'filter' => ['TABLE_NAME' => $arFields['TABLE_NAME']],
			'select' => ['ID', 'TABLE_NAME'],
		]);

		if ($arHL = $dbHL->fetch()) {
			$hlID = (int)$arHL['ID'];
		} else {
			// FIX: повторный деплой не очищает полностью базу, а только накатывает поверх. нужно удалять кастомные таблицы.

			/** @noinspection SqlNoDataSourceInspection */
			/** @noinspection SqlDialectInspection */
			Application::getConnection()->queryExecute("DROP TABLE IF EXISTS `{$arFields['TABLE_NAME']}`");

			$obResult = HighloadBlockTable::add($arFields);
			if (!$obResult->isSuccess()) {
				throw new Exception('Ошибка при добавлении hl-блока [' . $arFields['NAME'] . ']: ' . implode(', ',
						$obResult->getErrorMessages()));
			} else {
				$hlID = (int)$obResult->getId();
			}
		}

		if (!$hlID) {
			throw new Exception('Не найден hl-блок [' . $arFields['NAME'] . ']');
		}

		$entityID = 'HLBLOCK_' . $hlID;

		$arUFields = array(
			array(
				'ENTITY_ID'         => $entityID,
				'FIELD_NAME'        => 'UF_PATH',
				'USER_TYPE_ID'      => 'string',
				'XML_ID'            => '',
				'SORT'              => '100',
				'MULTIPLE'          => 'N',
				'MANDATORY'         => 'N',
				'SHOW_FILTER'       => 'S',
				'SHOW_IN_LIST'      => 'Y',
				'EDIT_IN_LIST'      => 'Y',
				'IS_SEARCHABLE'     => 'N',
				'SETTINGS'          => '',
				'EDIT_FORM_LABEL'   =>
					array(
						'ru' => 'Путь от корня сайта',
						'en' => 'Path',
					),
				'LIST_COLUMN_LABEL' =>
					array(
						'ru' => 'Путь от корня сайта',
						'en' => 'Path',
					),
				'LIST_FILTER_LABEL' =>
					array(
						'ru' => 'Путь от корня сайта',
						'en' => 'Path',
					),
				'ERROR_MESSAGE'     =>
					array(
						'ru' => '',
						'en' => '',
					),
				'HELP_MESSAGE'      =>
					array(
						'ru' => '',
						'en' => '',
					),
			),
			array(
				'ENTITY_ID'         => $entityID,
				'FIELD_NAME'        => 'UF_CODE',
				'USER_TYPE_ID'      => 'string',
				'XML_ID'            => '',
				'SORT'              => '110',
				'MULTIPLE'          => 'N',
				'MANDATORY'         => 'N',
				'SHOW_FILTER'       => 'I',
				'SHOW_IN_LIST'      => 'Y',
				'EDIT_IN_LIST'      => 'Y',
				'IS_SEARCHABLE'     => 'N',
				'SETTINGS'          => '',
				'EDIT_FORM_LABEL'   =>
					array(
						'ru' => 'Символьный код',
						'en' => 'Code',
					),
				'LIST_COLUMN_LABEL' =>
					array(
						'ru' => 'Символьный код',
						'en' => 'Code',
					),
				'LIST_FILTER_LABEL' =>
					array(
						'ru' => 'Символьный код',
						'en' => 'Code',
					),
				'ERROR_MESSAGE'     =>
					array(
						'ru' => '',
						'en' => '',
					),
				'HELP_MESSAGE'      =>
					array(
						'ru' => '',
						'en' => '',
					),
			),
			array(
				'ENTITY_ID'         => $entityID,
				'FIELD_NAME'        => 'UF_TYPE',
				'USER_TYPE_ID'      => 'enumeration',
				'XML_ID'            => '',
				'SORT'              => '120',
				'MULTIPLE'          => 'N',
				'MANDATORY'         => 'N',
				'SHOW_FILTER'       => 'I',
				'SHOW_IN_LIST'      => 'Y',
				'EDIT_IN_LIST'      => 'Y',
				'IS_SEARCHABLE'     => 'N',
				'SETTINGS'          => '',
				'EDIT_FORM_LABEL'   =>
					array(
						'ru' => 'Тип контента',
						'en' => 'Content type',
					),
				'LIST_COLUMN_LABEL' =>
					array(
						'ru' => 'Тип контента',
						'en' => 'Content type',
					),
				'LIST_FILTER_LABEL' =>
					array(
						'ru' => 'Тип контента',
						'en' => 'Content type',
					),
				'ERROR_MESSAGE'     =>
					array(
						'ru' => '',
						'en' => '',
					),
				'HELP_MESSAGE'      =>
					array(
						'ru' => '',
						'en' => '',
					),
			),
			array(
				'ENTITY_ID'         => $entityID,
				'FIELD_NAME'        => 'UF_DATA',
				'USER_TYPE_ID'      => 'string',
				'XML_ID'            => '',
				'SORT'              => '130',
				'MULTIPLE'          => 'N',
				'MANDATORY'         => 'N',
				'SHOW_FILTER'       => 'S',
				'SHOW_IN_LIST'      => 'Y',
				'EDIT_IN_LIST'      => 'Y',
				'IS_SEARCHABLE'     => 'N',
				'SETTINGS'          => '',
				'EDIT_FORM_LABEL'   =>
					array(
						'ru' => 'Данные',
						'en' => 'Data',
					),
				'LIST_COLUMN_LABEL' =>
					array(
						'ru' => 'Данные',
						'en' => 'Data',
					),
				'LIST_FILTER_LABEL' =>
					array(
						'ru' => 'Данные',
						'en' => 'Data',
					),
				'ERROR_MESSAGE'     =>
					array(
						'ru' => '',
						'en' => '',
					),
				'HELP_MESSAGE'      =>
					array(
						'ru' => '',
						'en' => '',
					),
			),
			array(
				'ENTITY_ID'         => $entityID,
				'FIELD_NAME'        => 'UF_SPREAD_DEEPER',
				'USER_TYPE_ID'      => 'boolean',
				'XML_ID'            => '',
				'SORT'              => '140',
				'MULTIPLE'          => 'N',
				'MANDATORY'         => 'N',
				'SHOW_FILTER'       => 'I',
				'SHOW_IN_LIST'      => 'Y',
				'EDIT_IN_LIST'      => 'Y',
				'IS_SEARCHABLE'     => 'N',
				'SETTINGS'          => '',
				'EDIT_FORM_LABEL'   =>
					array(
						'ru' => 'Отображать внутри подразделов',
						'en' => 'View in subsections',
					),
				'LIST_COLUMN_LABEL' =>
					array(
						'ru' => 'Отображать внутри подразделов',
						'en' => 'View in subsections',
					),
				'LIST_FILTER_LABEL' =>
					array(
						'ru' => 'Отображать внутри подразделов',
						'en' => 'View in subsections',
					),
				'ERROR_MESSAGE'     =>
					array(
						'ru' => '',
						'en' => '',
					),
				'HELP_MESSAGE'      =>
					array(
						'ru' => '',
						'en' => '',
					),
			),
			array(
				'ENTITY_ID'         => $entityID,
				'FIELD_NAME'        => 'UF_SITE_ID',
				'USER_TYPE_ID'      => 'string',
				'XML_ID'            => '',
				'SORT'              => '150',
				'MULTIPLE'          => 'N',
				'MANDATORY'         => 'N',
				'SHOW_FILTER'       => 'I',
				'SHOW_IN_LIST'      => 'Y',
				'EDIT_IN_LIST'      => 'Y',
				'IS_SEARCHABLE'     => 'N',
				'SETTINGS'          => '',
				'EDIT_FORM_LABEL'   =>
					array(
						'ru' => 'Сайт (LID)',
						'en' => 'Site ID',
					),
				'LIST_COLUMN_LABEL' =>
					array(
						'ru' => 'Сайт (LID)',
						'en' => 'Site ID',
					),
				'LIST_FILTER_LABEL' =>
					array(
						'ru' => 'Сайт (LID)',
						'en' => 'Site ID',
					),
				'ERROR_MESSAGE'     =>
					array(
						'ru' => '',
						'en' => '',
					),
				'HELP_MESSAGE'      =>
					array(
						'ru' => '',
						'en' => '',
					),
			),
			array(
				'ENTITY_ID'         => $entityID,
				'FIELD_NAME'        => 'UF_CREATED_USER_ID',
				'USER_TYPE_ID'      => 'integer',
				'XML_ID'            => '',
				'SORT'              => '160',
				'MULTIPLE'          => 'N',
				'MANDATORY'         => 'N',
				'SHOW_FILTER'       => 'I',
				'SHOW_IN_LIST'      => 'Y',
				'EDIT_IN_LIST'      => 'Y',
				'IS_SEARCHABLE'     => 'N',
				'SETTINGS'          => 'a:4:{s:4:"SIZE";i:20;s:9:"MIN_VALUE";i:0;s:9:"MAX_VALUE";i:0;s:13:"DEFAULT_VALUE";s:0:"";}',
				'EDIT_FORM_LABEL'   =>
					array(
						'ru' => 'Пользователь (кто создал)',
						'en' => 'User (creator)',
					),
				'LIST_COLUMN_LABEL' =>
					array(
						'ru' => 'Пользователь (кто создал)',
						'en' => 'User (creator)',
					),
				'LIST_FILTER_LABEL' =>
					array(
						'ru' => 'Пользователь (кто создал)',
						'en' => 'User (creator)',
					),
				'ERROR_MESSAGE'     =>
					array(
						'ru' => '',
						'en' => '',
					),
				'HELP_MESSAGE'      =>
					array(
						'ru' => '',
						'en' => '',
					),
			),
			array(
				'ENTITY_ID'         => $entityID,
				'FIELD_NAME'        => 'UF_MODIFIED_USER_ID',
				'USER_TYPE_ID'      => 'integer',
				'XML_ID'            => '',
				'SORT'              => '170',
				'MULTIPLE'          => 'N',
				'MANDATORY'         => 'N',
				'SHOW_FILTER'       => 'N',
				'SHOW_IN_LIST'      => 'Y',
				'EDIT_IN_LIST'      => 'Y',
				'IS_SEARCHABLE'     => 'N',
				'SETTINGS'          => 'a:4:{s:4:"SIZE";i:20;s:9:"MIN_VALUE";i:0;s:9:"MAX_VALUE";i:0;s:13:"DEFAULT_VALUE";s:0:"";}',
				'EDIT_FORM_LABEL'   =>
					array(
						'ru' => 'Пользователь (кто изменил)',
						'en' => 'User (modifier)',
					),
				'LIST_COLUMN_LABEL' =>
					array(
						'ru' => 'Пользователь (кто изменил)',
						'en' => 'User (modifier)',
					),
				'LIST_FILTER_LABEL' =>
					array(
						'ru' => 'Пользователь (кто изменил)',
						'en' => 'User (modifier)',
					),
				'ERROR_MESSAGE'     =>
					array(
						'ru' => '',
						'en' => '',
					),
				'HELP_MESSAGE'      =>
					array(
						'ru' => '',
						'en' => '',
					),
			),
			array(
				'ENTITY_ID'         => $entityID,
				'FIELD_NAME'        => 'UF_DATE_INSERT',
				'USER_TYPE_ID'      => 'datetime',
				'XML_ID'            => '',
				'SORT'              => '180',
				'MULTIPLE'          => 'N',
				'MANDATORY'         => 'N',
				'SHOW_FILTER'       => 'I',
				'SHOW_IN_LIST'      => 'Y',
				'EDIT_IN_LIST'      => 'Y',
				'IS_SEARCHABLE'     => 'N',
				'SETTINGS'          => 'a:2:{s:13:"DEFAULT_VALUE";a:2:{s:4:"TYPE";s:4:"NONE";s:5:"VALUE";s:0:"";}s:10:"USE_SECOND";s:1:"Y";}',
				'EDIT_FORM_LABEL'   =>
					array(
						'ru' => 'Дата добавления',
						'en' => 'Date insert',
					),
				'LIST_COLUMN_LABEL' =>
					array(
						'ru' => 'Дата добавления',
						'en' => 'Date insert',
					),
				'LIST_FILTER_LABEL' =>
					array(
						'ru' => 'Дата добавления',
						'en' => 'Date insert',
					),
				'ERROR_MESSAGE'     =>
					array(
						'ru' => '',
						'en' => '',
					),
				'HELP_MESSAGE'      =>
					array(
						'ru' => '',
						'en' => '',
					),
			),
			array(
				'ENTITY_ID'         => $entityID,
				'FIELD_NAME'        => 'UF_DATE_UPDATE',
				'USER_TYPE_ID'      => 'datetime',
				'XML_ID'            => '',
				'SORT'              => '190',
				'MULTIPLE'          => 'N',
				'MANDATORY'         => 'N',
				'SHOW_FILTER'       => 'I',
				'SHOW_IN_LIST'      => 'Y',
				'EDIT_IN_LIST'      => 'Y',
				'IS_SEARCHABLE'     => 'N',
				'SETTINGS'          => 'a:2:{s:13:"DEFAULT_VALUE";a:2:{s:4:"TYPE";s:4:"NONE";s:5:"VALUE";s:0:"";}s:10:"USE_SECOND";s:1:"Y";}',
				'EDIT_FORM_LABEL'   =>
					array(
						'ru' => 'Дата изменения',
						'en' => 'Date create',
					),
				'LIST_COLUMN_LABEL' =>
					array(
						'ru' => 'Дата изменения',
						'en' => 'Date create',
					),
				'LIST_FILTER_LABEL' =>
					array(
						'ru' => 'Дата изменения',
						'en' => 'Date create',
					),
				'ERROR_MESSAGE'     =>
					array(
						'ru' => '',
						'en' => '',
					),
				'HELP_MESSAGE'      =>
					array(
						'ru' => '',
						'en' => '',
					),
			),
			array(
				'ENTITY_ID'         => $entityID,
				'FIELD_NAME'        => 'UF_SORT',
				'USER_TYPE_ID'      => 'integer',
				'XML_ID'            => '',
				'SORT'              => '200',
				'MULTIPLE'          => 'N',
				'MANDATORY'         => 'N',
				'SHOW_FILTER'       => 'I',
				'SHOW_IN_LIST'      => 'Y',
				'EDIT_IN_LIST'      => 'Y',
				'IS_SEARCHABLE'     => 'N',
				'SETTINGS'          => 'a:4:{s:4:"SIZE";i:20;s:9:"MIN_VALUE";i:0;s:9:"MAX_VALUE";i:0;s:13:"DEFAULT_VALUE";i:100;}',
				'EDIT_FORM_LABEL'   =>
					array(
						'ru' => 'Сортировка',
						'en' => 'Sort',
					),
				'LIST_COLUMN_LABEL' =>
					array(
						'ru' => 'Сортировка',
						'en' => 'Sort',
					),
				'LIST_FILTER_LABEL' =>
					array(
						'ru' => 'Сортировка',
						'en' => 'Sort',
					),
				'ERROR_MESSAGE'     =>
					array(
						'ru' => '',
						'en' => '',
					),
				'HELP_MESSAGE'      =>
					array(
						'ru' => '',
						'en' => '',
					),
			),
		);

		foreach ($arUFields as $arUField) {
			$id = self::getUFIdByCode($entityID, $arUField['FIELD_NAME']);
			if (!$id) {
				self::addUF($arUField);
			}
		}
	}

	/**
	 * Reverse the migration.
	 *
	 * @throws Exception
	 */
	public function down()
	{
		//
	}

	/**
	 * Add User Field.
	 *
	 * @param $fields
	 *
	 * @return int
	 * @throws Exception
	 *
	 */
	public static function addUF($fields)
	{
		if (!$fields['FIELD_NAME']) {
			throw new Exception('Не заполнен FIELD_NAME');
		}

		if (!$fields['ENTITY_ID']) {
			throw new Exception('Не заполнен код ENTITY_ID');
		}

		$oUserTypeEntity = new CUserTypeEntity();

		$fieldId = $oUserTypeEntity->Add($fields);

		if (!$fieldId) {
			throw new Exception("Не удалось создать пользовательское свойство с FIELD_NAME = {$fields['FIELD_NAME']} и ENTITY_ID = {$fields['ENTITY_ID']}");
		}

		return $fieldId;
	}

	/**
	 * Get UF by its code.
	 *
	 * @param string $entity
	 * @param string $code
	 *
	 * @return int
	 * @throws Exception
	 */
	public static function getUFIdByCode($entity, $code)
	{
		if (!$entity) {
			throw new Exception('Не задана сущность свойства');
		}

		if (!$code) {
			throw new Exception('Не задан код свойства');
		}

		$filter = [
			'ENTITY_ID'  => $entity,
			'FIELD_NAME' => $code,
		];

		$arField = CUserTypeEntity::GetList(['ID' => 'ASC'], $filter)->fetch();
		if (!$arField || !$arField['ID']) {
			return 0;
		}

		return $arField['ID'];
	}
}
