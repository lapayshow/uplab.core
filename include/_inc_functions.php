<?
use Bitrix\Main\Application;
use Bitrix\Main\Web\Json;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 */


if (empty($array)) {
	$array = 'false';
} elseif (is_array($array)) {
	$array['__keys__'] = array_keys($array);
}


try {
	$array = Json::encode(unserialize(serialize($array)));

	echo '<script data-skip-moving=true>';
	if ($name) {
		$name = str_replace(Application::getDocumentRoot(), "", $name);
		echo 'console.info("', $name, ':",', $array, ');';
	} else {
		echo 'console.info(', $array, ');';
	}
	echo '</script>', PHP_EOL;

} catch (Exception $e) {
	$error = true;
}
