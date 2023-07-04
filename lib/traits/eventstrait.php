<?

namespace Uplab\Core\Traits;


use Bitrix\Main\Context;
use Bitrix\Main\EventManager;
use Uplab\Core\Helper;
use Uplab\Core\UplabHelper;
use Uplab\Core\UplabIblock;
use Uplab\Core\Uri;


/**
 * Trait for Bitrix events
 */
trait EventsTrait
{
	/**
	 * Список доступных языков, используемых для обработчика редиректов страницы 404
	 *
	 * @var string[]
	 */
	public static $availableLanguagesList = [
		"ru",
		"en",
	];

	public static function bindEvents()
	{
		$event = EventManager::getInstance();
		$event->addEventHandler("main", "OnEpilog", [self::class, "redirect404"]);
	}

	public static function getElement($iblock, $id, $select = [], $props = false)
	{
		return UplabIblock::getList([
			"iblock"     => $iblock,
			"filter"     => [
				"ID" => $id,
			],
			"select"     => array_unique(array_merge(
				(array)$select,
				["CODE"]
			)),
			"properties" => $props,
			"limit"      => 1,
		]);
	}

	public static function addOrUpdateHandler(&$arFields)
	{
		$iblock = $arFields["IBLOCK_ID"];
		$id = $arFields["ID"];

		# ====== предотвращение повторного срабатывания события ====== >>>
		static $updated = false;
		if ($updated == $id) return;
		$updated = $id;
		# <<< ============================================================

		/** @noinspection PhpUnusedLocalVariableInspection */
		$element = self::getElement($iblock, $id);
	}

	public static function redirect404()
	{
		/** @noinspection PhpUnusedLocalVariableInspection */
		global $APPLICATION, $USER;

		$request = Context::getCurrent()->getRequest();
		$curDir = $APPLICATION->GetCurDir();

		if ($request->isAdminSection()) return;

		$from = isset($_GET["from"]) ? htmlspecialchars($_GET["from"]) : false;
		$isFromLanguage = !empty($from);
		if (!empty(static::$availableLanguagesList)) {
			$isFromLanguage = $isFromLanguage && in_array($from, static::$availableLanguagesList);
		}

		if (!defined("ERROR_404") && $isFromLanguage) {
			LocalRedirect(
				Uri::initWithRequestUri()
					->removeParams(["from"])
					->deleteSystemParams()
					->getUri(),
				true,
				Helper::REDIRECT_STATUS_301
			);

			return;
		}

		if (defined("ERROR_404")) {
			if ($isFromLanguage) {
				$adr = explode("/", $curDir);

				array_pop($adr);
				array_pop($adr);

				LocalRedirect(implode("/", $adr) . "/?from=" . $from);

				return;
			} else {
				$APPLICATION->RestartBuffer();

				Helper::set404();

				/** @noinspection PhpIncludeInspection */
				include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . "/header.php";
				if (file_exists($_SERVER['DOCUMENT_ROOT'] . SITE_DIR . "404.php")) {
					/** @noinspection PhpIncludeInspection */
					include $_SERVER['DOCUMENT_ROOT'] . SITE_DIR . "404.php";
				}
				/** @noinspection PhpIncludeInspection */
				include $_SERVER['DOCUMENT_ROOT'] . SITE_TEMPLATE_PATH . "/footer.php";

				return;
			}
		}
	}

}