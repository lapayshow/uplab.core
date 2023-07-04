<?

namespace __MODULE_NAMESPACE__\Module;


use CMain;
use CUser;
use Uplab\Core\Module\OptionsBase;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 */


class Options extends OptionsBase
{
	public $moduleId = "__MODULE_ID__";

	public function onPostEvents()
	{
		parent::onPostEvents();
		$this->updateModuleFiles();
	}

}