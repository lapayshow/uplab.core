<?

namespace __MODULE_NAMESPACE__\Module;


use CAdminTabControl;
use CControllerClient;
use Exception;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;


/**
 * Вспомогательный класс для генерации страницы настроек модуля
 *
 * Class OptionsBase
 *
 * @package __MODULE_NAMESPACE__\Module
 */
class OptionsBase
{
	public $moduleId     = "__MODULE_ID__";
	public $modulePrefix;
	public $tabsSettings = [];

	function __construct($filePath = false, $tabsSettings = false, $moduleId = null)
	{
		if (empty($filePath)) {
			$filePath =
				Application::getDocumentRoot() .
				getLocalPath("modules/{$this->moduleId}/options.php");
		}

		Loc::loadMessages($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/options.php");
		Loc::loadMessages($filePath);

		if (!empty($moduleId)) $this->moduleId = $moduleId;
		$this->modulePrefix = str_replace(".", "_", $this->moduleId);

		if ($tabsSettings !== false) {
			$this->tabsSettings = $tabsSettings;
		} else {
			$this->prepareTabs();
		}

		$this->postData();
	}

	/** @noinspection PhpUnused */
	public function updateModuleFiles()
	{
		$className = '\\' . $this->modulePrefix;
		if (!class_exists($className)) {
			/** @noinspection PhpIncludeInspection */
			include $_SERVER["DOCUMENT_ROOT"] .
				getLocalPath("modules/{$this->moduleId}") .
				"/install/index.php";
		}
		try {
			$module = new $className;
			if (is_callable([$module, "installFiles"])) {
				$module->installFiles();
			}
		} catch (Exception $e) {
		}
	}

	public function onPostEvents()
	{
		// сбрасываем кеш, помеченный тегом модуля (например, это кеш настроек)
		$cache = Application::getInstance()->getTaggedCache();
		$cache->clearByTag($this->moduleId);
	}

	public function getTabsArray()
	{
		return [];
	}

	public function drawTabOptions($optionsArray)
	{
		$arControllerOption = CControllerClient::GetInstalledOptions($this->moduleId);
		foreach ($optionsArray as $option) {
			if ($option["ext_data"]["is_serialized"] === true) {
				$type = $option[3];
				$disabled = isset($option[4]) && $option[4] == "Y" ? " disabled " : "";

				$fieldId = $this->moduleId . "_" . random_bytes(6);
                if ($option)
				$val = Option::get($this->moduleId, current($option));
				if ($val === "") $val = $option[2];
				$val = json_encode(
					unserialize($val),
					JSON_PRETTY_PRINT |
					JSON_PRETTY_PRINT |
					JSON_UNESCAPED_UNICODE |
					JSON_UNESCAPED_SLASHES
				);
				?>
				<tr>
					<!--suppress HtmlDeprecatedAttribute -->
					<td class="adm-detail-valign-top" width="50%">
						<label for="<?= $fieldId ?>"><?= $option[1] ?></label>
					</td>
					<td>
						<textarea rows="<?= $type[1] ?>"
						          cols="<?= $type[2] ?>"
						          name="<?= htmlspecialchars($option[0]) ?>"
						          id="<?= $fieldId ?>"
							<?
							if (isset($arControllerOption[$option[0]])) {
								echo " disabled title=\"" . Loc::getMessage("MAIN_ADMIN_SET_CONTROLLER_ALT") . "\"";
							}

							echo " {$disabled} ";
							?>><?= $val ?></textarea>
					</td>
				</tr>
				<?
			} else {
				__AdmSettingsDrawRow($this->moduleId, $option);
			}
		}
	}

	public function drawOptionsForm()
	{
		global $APPLICATION;

		if (empty($this->tabsSettings)) return;

		$tabControl = new CAdminTabControl("tabControl", $this->tabsSettings);
		$tabControl->Begin();

		$formAction = (new Uri($APPLICATION->GetCurPage()))
			->addParams(["mid" => $this->moduleId, "lang" => LANGUAGE_ID])
			->getUri();
		?>

		<form action="<?= $formAction ?>"
		      name="<?= "{$this->modulePrefix}_form" ?>"
		      class="up-core-settings"
		      method="post">

			<?
			foreach ($this->tabsSettings as $arTab) {
				if (empty($arTab)) continue;
				$tabControl->BeginNextTab();
				$this->drawTabOptions($arTab["OPTIONS"]);
			}

			$tabControl->BeginNextTab();
			$tabControl->Buttons();
			?>

			<input type="submit"
			       name="update"
			       class="adm-btn-green"
			       value="<?= Loc::getMessage("MAIN_SAVE") ?>">
			<input type="reset" value="<?= Loc::getMessage("MAIN_RESET") ?>">
			<?= bitrix_sessid_post() ?>
		</form>

		<?
		$tabControl->End();
	}

	protected function prepareTabs()
	{
		$this->tabsSettings = $this->getTabsArray();
	}

	/**
	 * Записать необходимые настройки модуля
	 */
	protected function postData()
	{
		$request = Application::getInstance()->getContext()->getRequest();

		if (
			!$request->isPost() ||
			!$request->get("update") ||
			!check_bitrix_sessid()
		) {
			return;
		}

		foreach ($this->tabsSettings as $arTab) {
			foreach ($arTab["OPTIONS"] as $arOption) {
				$this->postAnOption($arOption);
			}
		}

		$this->onPostEvents();

		LocalRedirect($_SERVER["REQUEST_URI"], true);
	}

	protected function postAnOption($arOption)
	{
		$request = Application::getInstance()->getContext()->getRequest();

		if (!is_array($arOption)) return;
		$key = $arOption[0];
		if (empty($key)) return;

		$value = $request->get($key);

		// echo "<pre>";
		// var_export($arOption);
		// echo "</pre>";

		if (!empty($arOption["ext_data"]["is_serialized"])) {

			$oldValue = $value;
			$value = json_decode($value, true);

			// echo "<pre>";
			// var_export([
			// 	$oldValue, $value,
			// ]);
			// echo "</pre>";die();

			if (!empty($oldValue) && empty($value)) return;
			$value = serialize($value);

		} elseif (empty($arOption["ext_data"]["skip_encode"])) {

			$value = htmlspecialchars($value);

		}

		Option::set($this->moduleId, $key, $value);
	}

}