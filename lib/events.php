<?

namespace Uplab\Core;


use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\EventManager;
use CJSCore;
use Bitrix\Main\Page\Asset;
use Uplab\Core\Traits\EventsTrait;


/**
 * Class for Bitrix events
 */
class Events
{
	use EventsTrait;


	public static function bindEvents()
	{
		self::onModuleInit();

		$event = EventManager::getInstance();
		$event->addEventHandler("main", "OnProlog", [self::class, "registerGlobals"]);
		$event->addEventHandler("main", "OnProlog", [self::class, "includeAdminScript"]);
		$event->addEventHandler("main", "OnEndBufferContent", [self::class, "previewSvgMutator"]);

		Properties\RelatedFromORM::bindEvents();
		Properties\UserTypeFileExt::bindEvents();
		Properties\ImagePolygon::bindEvents();

		Renderer::bindEvents();
	}

	public static function includeAdminScript()
	{
		if (Context::getCurrent()->getRequest()->isAdminSection() && $GLOBALS["USER"]->IsAdmin()) {
			CJSCore::Init([
				// "jquery",
				"popup",
			]);
			Asset::getInstance()->addJs("/bitrix/js/uplab.core/admin.js");
		}
	}

	public static function registerGlobals()
	{
		global $APPLICATION;

		CJSCore::RegisterExt("uplab_hermitage", [
			"js" => "/bitrix/js/uplab.core/bitrix-hermitage.js",
		]);

		CJSCore::RegisterExt("uplab_editable", [
			"js" => "/bitrix/js/uplab.core/bitrix-editable.js",
		]);

		if ($APPLICATION->GetShowIncludeAreas()) {
			CJSCore::Init("uplab_editable");

			if (Helper::getOption("hermitage_disable") != "Y") {
				CJSCore::Init("uplab_hermitage");
			}
		}
	}

	private static function onModuleInit()
	{
	}

	public static function previewSvgMutator(&$content)
	{
		global $APPLICATION;

		if ($APPLICATION->GetCurPage(false) == "/bitrix/admin/file_dialog.php") {
			$mutationJs = file_get_contents(
				Application::getDocumentRoot() .
				"/bitrix/js/uplab.core/preview-svg-mutator.explorer.js"
			);
			if ($mutationJs) {
				$content = str_replace(
					"arFDPermission[",
					PHP_EOL . $mutationJs .
					PHP_EOL . "arFDPermission[",
					$content
				);
			}
		}

		if ($APPLICATION->GetCurPage(false) == "/bitrix/admin/fileman_medialib.php") {
			if (strpos($content, "window.MLItems[") !== false) {
				$mutationJs = file_get_contents(
					Application::getDocumentRoot() .
					"/bitrix/js/uplab.core/preview-svg-mutator.medialib.js"
				);
				if ($mutationJs) {
					$content .=
						PHP_EOL . "<script>" .
						PHP_EOL . "// " . var_export(strpos($content, "window.MLItems["), 1) .
						PHP_EOL . $mutationJs .
						PHP_EOL . "</script>";
				}
			}
		}
	}

}
