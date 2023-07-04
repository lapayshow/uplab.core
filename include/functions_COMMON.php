<?

use Bitrix\Main\Application;
use Uplab\Core\Renderer;


/**
 * @param string                   $templateFile
 * @param array                    $arResult
 * @param array                    $arParams
 * @param array                    $arLangMessages
 * @param string                   $templateFolder
 * @param string                   $parentTemplateFolder
 * @param CBitrixComponentTemplate $template
 */
function renderUplabCoreTemplateFile(
	$templateFile,
	$arResult,
	$arParams,
	$arLangMessages,
	$templateFolder,
	$parentTemplateFolder,
	$template
) {
	global $APPLICATION;

	if (($f = Application::getDocumentRoot() . $templateFile) && file_exists($f)) {
		if ($_REQUEST["clear_cache"] == "Y") {
			touch($f);
		}
	}

	echo Renderer::render(
		$templateFile,
		array_merge(
			(array)compact(
				"arParams",
				"arResult",
				"arLangMessages",
				"template",
				"templateFolder",
				"parentTemplateFolder"
			),
			(array)$arResult["TEMPLATE_DATA"]
		)
	);

	$component_epilog = $templateFolder . "/component_epilog.php";
	if (file_exists($_SERVER["DOCUMENT_ROOT"] . $component_epilog)) {
		/** @var CBitrixComponent $component */
		$component = $template->getComponent();
		$component->SetTemplateEpilog([
			"epilogFile"     => $component_epilog,
			"templateName"   => $template->__name,
			"templateFile"   => $template->__file,
			"templateFolder" => $template->__folder,
			"templateData"   => false,
		]);
	}
}