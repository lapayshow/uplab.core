<?
use Bitrix\Main\Loader;
use Uplab\Core\Helper;
use Uplab\Core\System\IncludeFiles;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 */


Loader::includeModule("uplab.core");
?>


<?
$arOptions = [];


array_push(
	$arOptions,

	"Модуль настроек и библиотек проекта",

	[
		"project_code",
		"Задайте код проекта (в <b>CamelCase</b>)<br><i>(Первая часть названия модуля)</i>",
		"",
		["text", 50],
	],
	[
		"module_suffix",
		"Укажите вторую часть названия модуля <br>(по умолчанию <b>Local</b>)<br>" .
		"Например, project.<b>tools</b>",
		"Local",
		["text", 50],
	]
);


array_push(
	$arOptions,

	implode("<br>", [
		"УПРАВЛЕНИЕ РЕЖИМОМ РАЗРАБОТКИ",
		"Использование – [ <em>Helper::isDevMode()</em> ]",
		"",
		"Текущий режим:  " . (Helper::isDevMode() ? "РАЗРАБОТКА" : "ПРОДАКШН"),
		"Текущий хост:   " . Helper::getHost(),
	]),

	[
		"debug_domains",
		"Укажите домены, на которых будет включен <br><b>РЕЖИМ РАЗРАБОТКИ</b>",
		"",
		["textarea", 6, 50],
	]
);


if (empty(Helper::getOption("debug_domains"))) {
	array_push(
		$arOptions,
		[
			"is_dev_mode",
			"<br>... <i>или</i><br>включите <b>РЕЖИМ РАЗРАБОТКИ</b> <br>на текущем домене",
			"N",
			["checkbox", "Y"],
		]
	);
}


$options = new Uplab\Core\Module\Options(__FILE__, [
	[
		"DIV"     => "common",
		"TAB"     => "Общие",
		"OPTIONS" => $arOptions,
	],
]);
?>


<!--suppress CssUnusedSymbol -->
<style>
	.uplab-core-sites-list {
		text-align: left;
		font-size: 12px;
		line-height: 1.5;
		display: none;
	}

	label[for="use_lang_const"] {
		display: block;
	}

	label[for="use_lang_const"],
	.uplab-core-sites-list {
		width: 750px;
	}
</style>


<!--suppress JSUnusedLocalSymbols -->
<script>
    function toggleSitesList(site) {
        const element = document.querySelector('.uplab-core-sites-list--' + site);
        if (!element) return;
        if (element.style.display !== 'block') {
            element.style.display = 'block';
        } else {
            element.style.display = 'none';
        }
    }
</script>


<? $options->drawOptionsForm(); ?>
