<?
use Bitrix\Main\Loader;


/** @noinspection PhpIncludeInspection */
require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";


if (!$APPLICATION->GetShowIncludeAreas()) {
	echo "" .
		"Ошибка при загрузке редактора.<br>" .
		"<a href='javascript:location.reload();'>Перезагрузить страницу</a>.";
	die();
}


Loader::includeModule("fileman");


$bxCode = htmlspecialchars($_REQUEST["inputCode"]) ?: randString(6);
$bxCodeType = htmlspecialchars($_REQUEST["inputCodeType"]) ?: randString(6);


$defaultEditors = array(
	'text'   => 'text',
	'html'   => 'html',
	'editor' => 'editor',
);
$editors = [
	'html'   => 'html',
	'editor' => 'editor',
];
$defaultEditor = 'editor';
$contentType = 'editor';


CFileMan::AddHTMLEditorFrame(
	$bxCode,
	$_REQUEST["html"],
	$bxCodeType,
	$defaultEditor,
	array(
		'width'  => 300,
		'height' => 300,
	)
);


if (count($editors) > 1) {
	foreach ($editors as &$editor) {
		$editor = strtolower($editor);
		if (isset($defaultEditors[$editor])) {
			unset($defaultEditors[$editor]);
		}
	}
}


$script = '<script type="text/javascript">';
$script .= '$(document).ready(function() {';

foreach ($defaultEditors as $editor) {
	$script .= '$("#bxed_' . $bxCode . '_' . $editor . '").parent().hide();';
}

$script .= '$("#bxed_' . $bxCode . '_' . $defaultEditor . '").click();';
$script .= 'setTimeout(function() {$("#bxed_' . $bxCode . '_' . $defaultEditor . '").click(); }, 500);';
$script .= "});";
$script .= '</script>';


echo $script;
