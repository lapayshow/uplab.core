<?

namespace Uplab\Core\Module;


use Uplab\Core\Constant;
use Uplab\Core\Generator\ModuleGenerator;


class Options extends OptionsBase
{
	public  $moduleId      = "uplab.core";
	private $customOptions = [];

	public function __construct($filePath = false, $tabsSettings = false, $moduleId = null)
	{
		$tabsSettings[] = $this->getSettingsConstantsTab();

		parent::__construct($filePath, $tabsSettings, $moduleId);
	}

	public function onPostEvents()
	{
		$this->postCustomOptions();

		parent::onPostEvents();

		// при необходимости генерируем модуль инструментов проекта
		ModuleGenerator::generate();

		$this->updateModuleFiles();
	}

	/**
	 * настройки констант
	 */
	private function getSettingsConstantsTab()
	{
		$tab = [
			"DIV"     => "const",
			"TAB"     => "Константы",
			"OPTIONS" => array(),
		];

		$constantsArray = Constant::getInstance()->getConstantsArray();

		$isFirst = true;
		foreach ($constantsArray as $site => $array) {
			$style = "style=\"";
			if ($isFirst) {
				$style .= "display: block;";
			}
			$style .= "\"";

			ob_start();
			?>
			Константы для [<?= $site ?>] &nbsp;
			<input type='button' onclick="toggleSitesList('<?= $site ?>')" value="±">
			<?
			$tab["OPTIONS"][] = ob_get_clean();

			$tab["OPTIONS"][] = [
				"",
				"<div class='uplab-core-sites-list uplab-core-sites-list--{$site}' $style>" .
				$this->getCustomConstantsMarkup($site) .
				"<pre class='' style='width: 100%'>" .
				json_encode($array,
					JSON_PRETTY_PRINT |
					JSON_UNESCAPED_UNICODE |
					JSON_UNESCAPED_SLASHES
				) .
				"</pre></div>",
			];

			$isFirst = false;
		}

		return $tab;
	}

	private function getCustomConstantsMarkup($site)
	{
		$option = [
			"custom_const_{$site}",
			"Дополнительные константы" .
			"<br><sup><b>(в формате JSON)</b></sup>",
			serialize(["DEFAULT_PICTURE" => "/img/default.png"]),
			["text", 14, 70],
			"ext_data" => ["is_serialized" => true],
		];
		$this->customOptions[] = $option;

		ob_start();
		?>

		<table style="width: 100%;">
			<? $this->drawTabOptions([$option]); ?>
		</table>

		<br>
		<hr><br>

		<?
		return ob_get_clean();
	}

	private function postCustomOptions()
	{
		foreach ($this->customOptions as $arOption) {
			$this->postAnOption($arOption);
		}
	}

}