<?php

namespace Uplab\Core\Entities\Content;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Entity;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\SystemException;
use Bitrix\Main\ObjectException;
use Bitrix\Main\ArgumentException;
use Bitrix\Highloadblock;
use Bitrix\Main\Type\DateTime as DateTime;
use CUserFieldEnum;
use Exception;
use Uplab\Core\Helper;


if (!Application::getConnection()->isTableExists('content_area')) {
	Migration::up();
}


/**
 * Class ContentTable
 * Сущноть для работы с контентом. Используется компонентом uplab.core:include.area
 *
 * @package Uplab\Core\Entities
 */
class ContentTable extends Entity\DataManager
{
	/**
	 * $this->arContentTypes
	 * Считаем что как минимум 2 типа данных созданы в HL инфоблоке по умолчанию:
	 *  - PLAIN_TEXT (Обычный текст)
	 *  - JSON (JSON-объект)
	 */
	private $arContentTypes;

	/**
	 * $this->arHLBlock
	 * Информация по HL инфоблоку таблицу которого использует данная сущность
	 *
	 * $this->arHLFields
	 * Информация по полям HL инфоблока
	 */
	private $arHLBlock;
	private $arHLFields;

	/**
	 * Хранит таблицу сущности для объявления в ORM
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'content_area';
	}

	/**
	 * Поля сущности, помимо простых полей имеет связи с доступными на данный момент в D7 сущностями
	 *
	 * @return array
	 */
	public static function getMap()
	{
		$arFields = array();
		try {
			$arFields = array(
				new Entity\IntegerField('ID', array(
					'primary'      => true,
					'autocomplete' => true,
				)),
				new Entity\StringField('UF_PATH'),
				new Entity\StringField('UF_CODE'),
				new Entity\StringField('UF_TYPE'),
				new Entity\StringField('UF_DATA'),
				new Entity\StringField('UF_SPREAD_DEEPER'),
				new Entity\StringField('UF_SITE_ID'),
				new Entity\ReferenceField(
					'SITE',
					'Bitrix\Main\SiteTable',
					array('=this.UF_SITE_ID' => 'ref.LID')
				),
				new Entity\IntegerField('UF_CREATED_USER_ID'),
				new Entity\ReferenceField(
					'CREATED_USER',
					'Bitrix\Main\UserTable',
					array('=this.UF_CREATED_USER_ID' => 'ref.ID')
				),
				new Entity\IntegerField('UF_MODIFIED_USER_ID'),
				new Entity\ReferenceField(
					'MODIFIED_USER',
					'Bitrix\Main\UserTable',
					array('=this.UF_MODIFIED_USER_ID' => 'ref.ID')
				),
				new Entity\DatetimeField('UF_DATE_INSERT'),
				new Entity\DatetimeField('UF_DATE_UPDATE'),
				new Entity\IntegerField('UF_SORT'),
			);
		} catch (ArgumentException $e) {
		} catch (Exception $e) {
		}

		return $arFields;
	}

	/**
	 * Обертка над getList из ORM для манипуляций поверх (настройка параметров, модификация результатов выборки)
	 *
	 * @param array $arParams
	 *
	 * @return array
	 */
	public function getListEx($arParams = array())
	{
		if (empty($arParams['select']) || !is_array($arParams['select'])) {
			$arParams['select'] = array('*');
		} else {
			if (!in_array('UF_TYPE', $arParams['select'])) {
				/**
				 * обязательное поле при получении данных. нужно для форматирования контента из БД
				 */
				$arParams['select'][] = 'UF_TYPE';
			}
		}
		if (empty($arParams['filter']) || !is_array($arParams['filter'])) {
			$arParams['filter'] = array();
		}

		$arResult = array();
		try {
			$arResult = $this->getList($arParams)->fetchAll();
		} catch (ArgumentException $e) {
		} catch (Exception $e) {
		}

		if (is_array($arResult) && !empty($arResult)) {
			$arID2Code = $this->getContentTypeID2Code();
			foreach ($arResult as &$arItem) {
				/**
				 * указываем виртуальное поле CONTENT_TYPE
				 */
				$arItem['CONTENT_TYPE'] = 'PLAIN_TEXT';
				if (is_numeric($arItem['UF_TYPE']) && array_key_exists($arItem['UF_TYPE'], $arID2Code)) {
					$arItem['CONTENT_TYPE'] = $arID2Code[$arItem['UF_TYPE']];
				} else {
					if (is_string($arItem['UF_TYPE']) && in_array($arItem['UF_TYPE'], $arID2Code)) {
						$arItem['CONTENT_TYPE'] = $arItem['UF_TYPE'];
					}
				}
				/**
				 * форматируем контент
				 */
				switch ($arItem['CONTENT_TYPE']) {
					case 'JSON':
						$arItem['UF_DATA'] = json_decode($arItem['UF_DATA'], true);
						break;
					default: // PLAIN_TEXT - ничего не делаем c контентом
						break;
				}
			}
			unset($arItem);
		}

		return $arResult;
	}

	/**
	 * Модификатор данных перед записью в БД (валидация, автозаполняемые данные и пр.)
	 *
	 * @param array  $arFields
	 * @param string $method
	 *
	 * @return array
	 */
	public function Prepare2DB(&$arFields = array(), $method = 'ADD')
	{
		$arTypes = $this->getContentTypeCode2ID();
		if ($method == 'UPDATE') {
			if (!isset($arFields['UF_DATE_UPDATE'])) {
				$arFields['UF_DATE_UPDATE'] = null;
			}
			if (!isset($arFields['UF_MODIFIED_USER_ID'])) {
				$arFields['UF_MODIFIED_USER_ID'] = null;
			}
		} else {
			if (!isset($arFields['UF_DATE_INSERT'])) {
				$arFields['UF_DATE_INSERT'] = null;
			}
			if (!isset($arFields['UF_CREATED_USER_ID'])) {
				$arFields['UF_CREATED_USER_ID'] = null;
			}
			if (!isset($arFields['UF_SORT'])) {
				$arFields['UF_SORT'] = null;
			}
		}
		foreach ($arFields as $code => &$value) {
			switch ($code) {
				case 'UF_SPREAD_DEEPER':
					if (is_string($value) && $value == 'Y') {
						$value = 1;
					}
					$value = (int)$value;
					if (!in_array($value, array(0, 1))) {
						$value = 0;
					}
					break;
				case 'UF_DATA':
					if (is_array($value)) {
						$value = json_encode($value, JSON_UNESCAPED_UNICODE);
						$arFields['UF_TYPE'] = $arTypes['JSON'];
					}
					break;
				case 'UF_TYPE':
					/**
					 * значение типа контента можно передавать как ИД так и КОД.
					 * определение введенных данных как раз происходит здесь.
					 * если тип не определился у нового элемента - ставится по умолчанию PLAIN_TEXT (обычный текст)
					 * если тип не определился у существующего элемента - остается тот что в БД
					 */
					$valid = false;
					if (!empty($value)) {
						if (is_numeric($value) && in_array($value, $arTypes)) {
							$valid = true;
						} else {
							if (is_string($value) && array_key_exists($value, $arTypes)) {
								$valid = true;
								$value = $arTypes[$value];
							}
						}
					}
					if ($method == 'UPDATE') {
						if (!$valid) {
							unset($arFields[$code]);
						}
					} else {
						if (!$valid) {
							$value = $arTypes['PLAIN_TEXT'];
						}
					}
					break;
				case 'UF_DATE_INSERT':
					if ($method == 'UPDATE') {
						unset($arFields[$code]);
					} else {
						try {
							$value = new DateTime();
						} catch (ObjectException $e) {
						}
					}
					break;
				case 'UF_DATE_UPDATE':
					if ($method == 'UPDATE') {
						try {
							$value = new DateTime();
						} catch (ObjectException $e) {
						}
					} else {
						unset($arFields[$code]);
					}
					break;
				case 'UF_CREATED_USER_ID':
					if ($method == 'UPDATE') {
						unset($arFields[$code]);
					} else {
						$value = $GLOBALS['USER']->GetID();
					}
					break;
				case 'UF_MODIFIED_USER_ID':
					if ($method == 'UPDATE') {
						$value = $GLOBALS['USER']->GetID();
					} else {
						unset($arFields[$code]);
					}
					break;
				case 'UF_SORT':
					/**
					 * при наследовании контента по подразделам нужно учитывать вложенность разделов.
					 * поэтому чем больше "/" в пути - тем меньше сортировка и выше "вес".
					 * контент имеющий бОльщую вложенность разделов будет иметь приоритет над остальными при выводе.
					 * P.S. корень сайта будет иметь сортировку 100, подразделы <100.
					 * у нас никогда не будет уровень вложенности 100 разделов поэтому считаю эту цифру адекватной
					 * в данной реализации.
					 */
					$value = 100;
					if (!empty($arFields['UF_PATH'])) {
						$value = 101 - substr_count($arFields['UF_PATH'], '/');
					}
					break;
				default:
					break;
			}
		}
		unset($value);

		return $arFields;
	}

	/**
	 * Обертка над add/update из ORM для манипуляций поверх
	 * (подготовка данных, логика исключения дубликатов в таблице и пр.)
	 *
	 * @param array $arFields
	 *
	 * @return Entity\Result
	 */
	public function AddEx($arFields = array())
	{
		if (empty($arFields) || !is_array($arFields)) {
			$arFields = array();
		}

		$id = false;
		if (!empty($arFields['UF_PATH']) && !empty($arFields['UF_CODE']) && !empty($arFields['UF_SITE_ID'])) {
			$arExists = $this->GetListEx(array(
					'filter' => array(
						'UF_PATH'    => $arFields['UF_PATH'],
						'UF_CODE'    => $arFields['UF_CODE'],
						'UF_SITE_ID' => $arFields['UF_SITE_ID'],
					),
					'select' => array('ID', 'UF_PATH', 'UF_CODE', 'UF_SITE_ID'),
				)
			);
			if (!empty($arExists)) {
				$arExists = array_shift($arExists);
				if (!empty($arExists['ID'])) {
					$id = (int)$arExists['ID'];
				}
			}
		}

		if ($id > 0) {
			$obResult = $this->UpdateEx($id, $arFields);
		} else {
			$arFields = $this->Prepare2DB($arFields, 'ADD');
			$obResult = new Entity\Result();
			try {
				$obResult = $this->add($arFields);
			} catch (Exception $e) {
				$obResult->addError(new Entity\EntityError('Internal error'));
			}
		}

		/*if ($obResult->isSuccess()) {
			// $obResult->getId()
		}*/

		return $obResult;
	}

	/**
	 * Обертка над update из ORM для манипуляций поверх (подготовка данных и пр.)
	 *
	 * @param int   $id
	 * @param array $arFields
	 *
	 * @return Entity\Result
	 */
	public function UpdateEx($id, $arFields = array())
	{
		if (empty($arFields) || !is_array($arFields)) {
			$arFields = array();
		}
		$arFields = $this->Prepare2DB($arFields, 'UPDATE');
		$obResult = new Entity\Result();
		try {
			$obResult = $this->update($id, $arFields);
		} catch (Exception $e) {
			$obResult->addError(new Entity\EntityError('Internal error'));
		}

		/*if ($obResult->isSuccess()) {

		}*/

		return $obResult;
	}

	/**
	 * Обертка над delete из ORM для манипуляций поверх
	 *
	 * @param int $id
	 *
	 * @return Entity\Result
	 */
	public function DeleteEx($id)
	{
		$obResult = new Entity\Result();
		try {
			$obResult = $this->delete($id);
		} catch (Exception $e) {
			$obResult->addError(new Entity\EntityError('Internal error'));
		}

		/*if ($obResult->isSuccess()) {

		}*/

		return $obResult;
	}

	/**
	 * Получение информации о HL инфоблоке по таблице данной сущности
	 *
	 * @return null|array
	 */
	public function getHLBlock()
	{
		if (!is_null($this->arHLBlock)) return $this->arHLBlock;

		try {
			if (!Loader::includeModule('highloadblock')) return $this->arHLBlock;
		} catch (LoaderException $e) {
			return $this->arHLBlock;
		}

		try {
			$obHLBlocks = Highloadblock\HighloadBlockTable::getList(array(
					'filter' => array('TABLE_NAME' => $this->getTableName()),
				)
			);
			$this->arHLBlock = array_shift($obHLBlocks->fetchAll());
		} catch (ArgumentException $e) {
		} catch (Exception $e) {
		}

		return $this->arHLBlock;
	}

	/**
	 * Получение информации о полях HL инфоблока
	 *
	 * @return null|array
	 */
	public function getHLFields()
	{
		if (!is_null($this->arHLFields)) return $this->arHLFields;

		$arHLBlock = $this->getHLBlock();

		if (empty($arHLBlock)) return $this->arHLFields;

		$this->arHLFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('HLBLOCK_' . $arHLBlock['ID'], 0,
			LANGUAGE_ID);

		return $this->arHLFields;
	}

	/**
	 * Получение информации о типах данных (UF_TYPE) сущности по информации HL инфоблока
	 *
	 * @return array
	 */
	public function getContentTypes()
	{
		if (!is_null($this->arContentTypes)) return $this->arContentTypes;

		$this->arContentTypes = array();

		try {
			if (!Loader::includeModule('highloadblock')) return $this->arContentTypes;
		} catch (LoaderException $e) {
			return $this->arContentTypes;
		}

		$arFields = $this->getHLFields();

		if (empty($arFields) || !is_array($arFields) || empty($arFields['UF_TYPE'])) return $this->arContentTypes;

		$this->arContentTypes = array();
		$obUserFieldEnum = new CUserFieldEnum();
		$dbVariants = $obUserFieldEnum->GetList(array(), array('USER_FIELD_ID' => $arFields['UF_TYPE']['ID']));
		while ($arVariant = $dbVariants->Fetch()) {
			$this->arContentTypes[$arVariant['ID']] = $arVariant;
		}

		return $this->arContentTypes;
	}

	/**
	 * Получение связи ИД - КОД по типам данных (UF_TYPE)
	 *
	 * @return array
	 */
	public function getContentTypeID2Code()
	{
		$arTypes = $this->getContentTypes();
		if (!empty($arTypes)) {
			foreach ($arTypes as &$type) {
				$type = $type['XML_ID'];
			}
			unset($type);
		}

		return $arTypes;
	}

	/**
	 * Получение связи КОД - ИД по типам данных (UF_TYPE)
	 *
	 * @return array
	 */
	public function getContentTypeCode2ID()
	{
		return array_flip($this->getContentTypeID2Code());
	}

	/**
	 * Получить соль. Если ее нет, то сгенерить
	 */
	public static function getSalt()
	{
		$salt = Helper::getOption('components_content_salt', '', false);
		if (empty($salt)) {
			$salt = randString();
			Option::set(Helper::MODULE_ID, 'components_content_salt', $salt);
		}

		return $salt;
	}

	public static function getTableID()
	{
		if (!Loader::includeModule('highloadblock')) return false;
		$table = [];
		try {
			$table = Highloadblock\HighloadBlockTable::getList(array(
					'filter' => array('TABLE_NAME' => self::getTableName()),
				)
			)->fetchRaw();
		} catch (ArgumentException $e) {
		} catch (Exception $e) {
		}

		return $table["ID"];
	}

	public static function checkRights($userID = false, $right = "hl_element_write")
	{
		$rights = [];
		if (!$userID) $userID = $GLOBALS['USER']->GetID();
		if ($v = self::getTableID()) $rights = \Bitrix\HighloadBlock\HighloadBlockRightsTable::getOperationsName($v);

		return in_array($right, $rights);
	}

}
