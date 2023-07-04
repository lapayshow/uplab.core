<?php


namespace Uplab\Core\Component;


use Bitrix\Main\Page\Asset;
use CFileMan;


trait ComponentCommonMethodsTrait
{
	/**
	 * Отображает ошибки, возникшие при работе компонента, если они есть
	 */
	protected function showErrorsIfAny()
	{
		if ($this->errors->count()) {
			foreach ($this->errors as $error) {
				ShowError($error);
			}
		}
	}
}