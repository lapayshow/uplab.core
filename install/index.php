<?
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\IO\Directory;


global $MESS;


Loc::loadMessages(__FILE__);


class uplab_core extends CModule
{
	public $MODULE_ID           = "uplab.core";
	public $MODULE_NAME;
	public $MODULE_VERSION;
	public $MODULE_VERSION_DATE;
	public $MODULE_DESCRIPTION;
	public $PARTNER_NAME;
	public $PARTNER_URI;
	public $MODULE_GROUP_RIGHTS = "Y";

	private $excludeFiles = array(
		"..",
		".",
		"menu.php",
	);

	function __construct()
	{
		$arModuleVersion = array();

		include(__DIR__ . "/version.php");

		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		$this->MODULE_NAME = Loc::getMessage("{$this->MODULE_ID}_MODULE_NAME");
		$this->MODULE_DESCRIPTION = Loc::getMessage("{$this->MODULE_ID}_MODULE_DESCRIPTION");
		$this->PARTNER_NAME = Loc::getMessage("{$this->MODULE_ID}_PARTNER_NAME");
		$this->PARTNER_URI = Loc::getMessage("{$this->MODULE_ID}_PARTNER_URI");
	}

	function DoInstall()
	{
		global $APPLICATION;

		if (!$this->isVersionD7()) {
			$APPLICATION->ThrowException(Loc::getMessage("{$this->MODULE_ID}_MODULE_NO_D7_ERROR"));

			return false;
		}

		ModuleManager::registerModule($this->MODULE_ID);

		$this->installEvents();
		$this->installFiles();

		return true;
	}

	public function getPath()
	{
		return $_SERVER["DOCUMENT_ROOT"] . getLocalPath("modules/$this->MODULE_ID");
	}

	function DoUninstall()
	{
		$this->unInstallEvents();
		$this->unInstallFiles();

		ModuleManager::unRegisterModule($this->MODULE_ID);
	}

	public function installEvents()
	{
		$eventManager = \Bitrix\Main\EventManager::getInstance();

		$eventManager->registerEventHandlerCompatible(
			"iblock",
			"OnIBlockPropertyBuildList",
			$this->MODULE_ID,
			\Uplab\Core\Properties\SmartGrid::class,
			"getUserTypeDescription"
		);
		$eventManager->registerEventHandlerCompatible(
			"iblock",
			"OnIBlockPropertyBuildList",
			$this->MODULE_ID,
			\Uplab\Core\Properties\ImagePosition::class,
			"getUserTypeDescription"
		);
		$eventManager->registerEventHandlerCompatible(
			"iblock",
			"OnIBlockPropertyBuildList",
			$this->MODULE_ID,
			\Uplab\Core\Properties\RelatedORM::class,
			"getUserTypeDescription"
		);

		# генерация констант
		$eventManager->registerEventHandler(
			"iblock",
			"OnAfterIBlockAdd",
			$this->MODULE_ID,
			\Uplab\Core\Constant::class,
			"update"
		);
		$eventManager->registerEventHandler(
			"iblock",
			"OnAfterIBlockUpdate",
			$this->MODULE_ID,
			\Uplab\Core\Constant::class,
			"update"
		);
		# ==========
	}

	public function unInstallEvents()
	{
		$eventManager = \Bitrix\Main\EventManager::getInstance();

		# генерация констант
		$eventManager->unRegisterEventHandler(
			"iblock",
			"OnAfterIBlockAdd",
			$this->MODULE_ID,
			\Uplab\Core\Constant::class,
			"update"
		);
		$eventManager->unRegisterEventHandler(
			"iblock",
			"OnAfterIBlockUpdate",
			$this->MODULE_ID,
			\Uplab\Core\Constant::class,
			"update"
		);
		# ==========

		$eventManager->unRegisterEventHandler(
			"iblock",
			"OnIBlockPropertyBuildList",
			$this->MODULE_ID,
			\Uplab\Core\Properties\SmartGrid::class,
			"getUserTypeDescription"
		);
		$eventManager->unRegisterEventHandler(
			"iblock",
			"OnIBlockPropertyBuildList",
			$this->MODULE_ID,
			\Uplab\Core\Properties\ImagePosition::class,
			"getUserTypeDescription"
		);
		$eventManager->unRegisterEventHandler(
			"iblock",
			"OnIBlockPropertyBuildList",
			$this->MODULE_ID,
			\Uplab\Core\Properties\RelatedORM::class,
			"getUserTypeDescription"
		);
	}

	public function installFiles()
	{
		CopyDirFiles(
			__DIR__ . "/assets/dist/css",
			"{$_SERVER["DOCUMENT_ROOT"]}/bitrix/css/{$this->MODULE_ID}",
			true, true
		);
		CopyDirFiles(
			__DIR__ . "/assets/dist/js",
			"{$_SERVER["DOCUMENT_ROOT"]}/bitrix/js/{$this->MODULE_ID}",
			true, true
		);
		CopyDirFiles(
			$this->getPath() . "/install/components/",
			"{$_SERVER["DOCUMENT_ROOT"]}/bitrix/components/{$this->MODULE_ID}/",
			true,
			true
		);

		$this->recursiveCopyFiles("admin");
		$this->recursiveCopyFiles("tools");

		$localComponentsFolder = "/local/components";
		$localComponentsPath = Application::getDocumentRoot() . "$localComponentsFolder";
		$thisComponentsPath = "$localComponentsPath/$this->MODULE_ID";

		$moduleComponentsSrc = getLocalPath("modules/$this->MODULE_ID/install/components");
		$moduleComponentsPath = Application::getDocumentRoot() . $moduleComponentsSrc;

		if (DIRECTORY_SEPARATOR === "/" && !file_exists($thisComponentsPath) && file_exists($moduleComponentsPath)) {
			exec(implode(" && ", [
				"mkdir -p " . $localComponentsPath,
				"cd " . $localComponentsPath,
				"ln -s ../..$moduleComponentsSrc $this->MODULE_ID",
			]));
		}
	}

	public function unInstallFiles()
	{
		Directory::deleteDirectory("{$_SERVER["DOCUMENT_ROOT"]}/bitrix/js/{$this->MODULE_ID}");
		Directory::deleteDirectory("{$_SERVER["DOCUMENT_ROOT"]}/bitrix/css/{$this->MODULE_ID}");
		Directory::deleteDirectory("{$_SERVER["DOCUMENT_ROOT"]}/bitrix/components/{$this->MODULE_ID}");

		$this->recursiveRemoveFiles("admin");
		$this->recursiveRemoveFiles("tools");
	}

	public function isVersionD7()
	{
		return
			is_callable([ModuleManager::class, "getVersion"]) &&
			CheckVersion(
				ModuleManager::getVersion("main"), "14.00.00"
			);
	}

	private function recursiveCopyFiles($prefix)
	{
		CopyDirFiles(
			$this->getPath() . "/install/{$prefix}/",
			"{$_SERVER["DOCUMENT_ROOT"]}/bitrix/{$prefix}/",
			false,
			true
		);

		if (Directory::isDirectoryExists($path = $this->getPath() . "/{$prefix}")) {
			if ($dir = opendir($path)) {
				while (false !== $item = readdir($dir)) {
					if (in_array($item, $this->excludeFiles)) {
						continue;
					}
					if (strpos($item, "_") === 0) continue;
					file_put_contents(
						$file =
							"{$_SERVER['DOCUMENT_ROOT']}/bitrix/{$prefix}/" .
							"{$this->MODULE_ID}_{$item}",

						"<" . "?" . PHP_EOL .

						"if (empty(\$" . "_SERVER[\"DOCUMENT_ROOT\"])) {" . PHP_EOL .
						"    " .
						"\$" . "_SERVER[\"DOCUMENT_ROOT\"] = " .
						"realpath(__DIR__ . \"/../..\");" . PHP_EOL .
						"}" . PHP_EOL . PHP_EOL .

						"require(\$" . "_SERVER[\"DOCUMENT_ROOT\"] . \"" .
						getLocalPath("modules/{$this->MODULE_ID}/{$prefix}/{$item}") .
						'");'
					);
				}
				closedir($dir);
			}
		}
	}

	private function recursiveRemoveFiles($prefix)
	{
		DeleteDirFiles(
			$this->getPath() . "/install/{$prefix}/",
			"{$_SERVER["DOCUMENT_ROOT"]}/bitrix/{$prefix}/"
		);

		if (Directory::isDirectoryExists($path = $this->getPath() . "/{$prefix}")) {
			if ($dir = opendir($path)) {
				while (false !== $item = readdir($dir)) {
					if (in_array($item, $this->excludeFiles)) {
						continue;
					}
					\Bitrix\Main\IO\File::deleteFile(
						"{$_SERVER['DOCUMENT_ROOT']}/bitrix/{$prefix}/" .
						"{$this->MODULE_ID}_{$item}"
					);
				}
				closedir($dir);
			}
		}
	}

}