<?php

namespace Uplab\Core\Orm\Widget;


use Bitrix\Main\Localization\Loc;
use DigitalWand\AdminHelper;


Loc::loadMessages(__FILE__);

/**
 * Виджет "галочка"
 */
class CheckboxWidget extends AdminHelper\Widget\CheckboxWidget
{
	/**
	 * Получить тип чекбокса по типу поля.
	 *
	 * @return mixed
	 */
	public function getCheckboxType()
	{
		$entity = $this->getEntityName();
		$entityMap = $entity::getMap();
		$columnName = $this->getCode();

		if ($fieldType = $this->getSettings('FIELD_TYPE')) {
			return $fieldType;
		}

		if (isset($entityMap[$columnName])) {
			$fieldType = $entityMap[$columnName]['data_type'];
		}

		return $fieldType;
	}
}