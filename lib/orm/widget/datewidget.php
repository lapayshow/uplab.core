<?php
/**
 * Created by PhpStorm.
 * User: geffest
 * Date: 13.07.2018
 * Time: 17:23
 */

namespace Uplab\Core\Orm\Widget;


use CAdminCalendar;
use CAdminListRow;
use DigitalWand\AdminHelper\Widget\DateTimeWidget;


class DateWidget extends DateTimeWidget
{
	/**
	 * Генерирует HTML для поля в списке
	 *
	 * @see AdminListHelper::addRowCell();
	 *
	 * @param CAdminListRow $row
	 * @param array         $data - данные текущей строки
	 *
	 * @return void
	 */
	public function generateRow(&$row, $data)
	{
		if (isset($this->settings["EDIT_IN_LIST"]) && $this->settings["EDIT_IN_LIST"]) {
			$row->AddCalendarField($this->getCode());
		} else {
			$arDate = ParseDateTime($this->getValue());

			if ($arDate["YYYY"] < 10) {
				$stDate = '-';
			} else {
				$stDate = ConvertDateTime($this->getValue(), "DD.MM.YYYY", "ru");
			}

			$row->AddViewField($this->getCode(), $stDate);
		}
	}

	/**
	 * Генерирует HTML для редактирования поля
	 *
	 * @see AdminEditHelper::showField();
	 * @return mixed
	 */
	protected function getEditHtml()
	{
		return CAdminCalendar::CalendarDate(
			$this->getEditInputName(),
			ConvertTimeStamp(strtotime($this->getValue()), "SHORT"),
			10, true
		);
	}
}
