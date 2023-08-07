<?

namespace __namespace__\__Entity__\AdminInterface;


use Bitrix\Main\Localization\Loc;
use DigitalWand\AdminHelper\Helper\AdminEditHelper;
use __namespace__\__Entity__\__Entity__Table;
use CMain;
use CUser;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 */


class __Entity__EditHelper extends AdminEditHelper
{
	protected static $model = __Entity__Table::class;

	/**
	 * @inheritdoc
	 */
	public function setTitle($title)
	{
		if (!empty($this->data)) {
			$title = Loc::getMessage(
				"__module_____ENTITY___EDIT_TITLE",
				['#ID#' => $this->data[$this->pk()]]
			);
		} else {
			$title = Loc::getMessage("__module_____ENTITY___NEW_TITLE");
		}

		parent::setTitle($title);
	}
}
