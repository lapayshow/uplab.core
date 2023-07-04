<?

namespace __namespace__\__Entity__\AdminInterface;


use Bitrix\Main\Localization\Loc;
use DigitalWand\AdminHelper\Helper\AdminListHelper;
use __namespace__\__Entity__\__Entity__Table;
use CMain;
use CUser;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

class __Entity__ListHelper extends AdminListHelper
{
	protected static $model = __Entity__Table::class;

	public function __construct(array $fields, $isPopup = false)
	{
		parent::__construct($fields, $isPopup);

		$this->setTitle(Loc::getMessage("__module_____ENTITY___LIST_TITLE"));
	}
}
