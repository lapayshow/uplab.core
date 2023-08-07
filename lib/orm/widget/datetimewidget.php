<?php
/**
 * Created by PhpStorm.
 * User: geffest
 * Date: 13.07.2018
 * Time: 17:23
 */

namespace Uplab\Core\Orm\Widget;


use CAdminCalendar;
use DigitalWand\AdminHelper\Widget;


class DateTimeWidget extends Widget\DateTimeWidget
{
	/**
	 * Сконвертируем дату в формат Mysql
	 *
	 * @return void
	 */
	public function processEditAction()
	{
		$primary =$this->helper->pk();
		$isExistElement = isset($_REQUEST[$primary]) || isset($this->data[$primary]);

		try {
			if ($time = $this->getValue()) {
				$time = new \Bitrix\Main\Type\Datetime($this->getValue());
				$this->setValue($time);
			} else {
				if ($isExistElement) {
					$this->setValue("");
				} else {
					unset($this->data[$this->getCode()]);
				}
			}
		} catch (\Exception $e) {
		}

		if (!$this->checkRequired()) {
			$this->addError('REQUIRED_FIELD_ERROR');
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
		if (empty($this->getValue())) {
			$time = "";
		} else {
			$time = ConvertTimeStamp(strtotime($this->getValue()), "FULL");
		}

		return CAdminCalendar::CalendarDate($this->getEditInputName(), $time, 10, true);
	}
}
