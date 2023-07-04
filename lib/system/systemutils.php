<?
/**
 * Created by PhpStorm.
 * User: geffest
 * Date: 2019-03-02
 * Time: 04:13
 */


namespace Uplab\Core\System;


use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\IO\Directory;
use CAgent;
use PHPParser;
use Uplab\Core\UplabCache;


class SystemUtils
{

	/**
	 * Функция для отложенного удаления папок
	 *
	 * @param      $folder
	 * @param bool $setTime
	 *
	 * @return string
	 */
	public static function delayedFolderRemove($folder, $setTime = false)
	{
		if (!file_exists($folder)) return "";

		$dir = new Directory($folder);

		if ($setTime) {
			// die("<pre>" . var_export(compact("file", "setTime"), true));
			$agent = __METHOD__ . "('{$folder}');";
			CAgent::RemoveAgent($agent, "");
			CAgent::AddAgent($agent, "", "N", 0, "", "Y", ConvertTimeStamp($setTime, 'FULL'));

			return "";
		}

		$dir->delete();

		return "";
	}

	/**
	 * Функция для отложенного удаления файлов,
	 * может использоваться как для создания агента,
	 * так и для непосредственно вызова агентом
	 *
	 * @param      $file
	 * @param bool $setTime
	 *
	 * @return string
	 */
	public static function delayedFileRemove($file, $setTime = false)
	{
		if (!file_exists($file)) return "";

		if ($setTime) {
			// die("<pre>" . var_export(compact("file", "setTime"), true));

			$agent = self::class . "::delayedFileRemove('{$file}');";
			CAgent::RemoveAgent($agent, "");
			CAgent::AddAgent($agent, "", "N", 0, "", "Y", ConvertTimeStamp($setTime, 'FULL'));

			return "";
		}

		unlink($file);

		// Очищаем кеш, чтобы не было битых картинок
		// BXClearCache(true);
		BXClearCache();

		return "";
	}

	/**
	 * @param array $addRule
	 *
	 * @return bool|int - true если правило добавлено, false если нет
	 */
	public static function addSefRuleIfNotExists($addRule = [])
	{
		if (empty($addRule["CONDITION"]) || empty($addRule["PATH"])) return false;

		$addRule = array_merge([
			'CONDITION' => "",
			'RULE'      => "",
			'ID'        => "",
			'PATH'      => "",
			'SORT'      => 90,
		], (array)$addRule);

		$arUrlRewrite = [];
		$urlRewritePath = Application::getDocumentRoot() . "/urlrewrite.php";
		/** @noinspection PhpIncludeInspection */
		include $urlRewritePath;

		foreach ($arUrlRewrite as $rule) {
			if ($rule["CONDITION"] == $addRule["CONDITION"]) return false;
		}

		$arUrlRewrite[] = $addRule;

		return file_put_contents(
			$urlRewritePath,
			"<?php" . PHP_EOL . "\$arUrlRewrite=" . var_export($arUrlRewrite, true) . ";" . PHP_EOL
		);
	}

	public static function prepareParseFilePath(&$filePath)
	{
		global $APPLICATION;

		if (empty($filePath)) {
			$filePath = $APPLICATION->GetCurPage(true);
		}

		if (strpos($filePath, Application::getDocumentRoot()) !== 0) {
			$filePath = Application::getDocumentRoot() . $filePath;
		}
	}

	public static function parseFileContent($filePath, $cache = true)
	{
		self::prepareParseFilePath($filePath);

		if ($cache === true) {
			return UplabCache::cacheMethod(__METHOD__, [
				"arguments" => [$filePath, false],
				"tags"      => [],
			]);
		}

		return self::parseFileByRawContent(file_get_contents($filePath));
	}

	public static function parseFileByRawContent($fileContent)
	{
		$fileContent = ParseFileContent($fileContent);
		$fileContent["PROPERTIES"]["h1"] = $fileContent["TITLE"];
		if (!isset($fileContent["PROPERTIES"]["title"])) {
			$fileContent["PROPERTIES"]["title"] = $fileContent["PROPERTIES"]["h1"];
		}

		return array_intersect_key(
			$fileContent,
			array_flip(["PROPERTIES", "TITLE"])
		);
	}

	public static function getPropertyFromFile($filePath, $property)
	{
		$fileContent = self::parseFileContent($filePath);

		if (isset($fileContent["PROPERTIES"][$property])) {
			return $fileContent["PROPERTIES"][$property];
		}

		return "";
	}

	public static function getComponentsFromFile($filePath)
	{
		self::prepareParseFilePath($filePath);

		$content = file_get_contents($filePath);
		$arScripts = PHPParser::ParseFile($content);

		$result = [];

		foreach ($arScripts as $script) {
			$componentParams = PHPParser::CheckForComponent2($script[2]);

			if (!$componentParams) continue;

			$componentParams["TEMPLATE_NAME"] = $componentParams["TEMPLATE_NAME"] ?: ".default";
			$componentParams["LINE_NUMBER"] = substr_count(substr($content, 0, $script[0]), "\n") + 1;

			$result[] = $componentParams;
		}

		return $result;
	}

	public static function setDevModeConfig($isDevMode)
	{
		if (!isset($isDevMode)) return;


		$value = $isDevMode ? "Y" : "N";


		switch ($isDevMode) {
			case true:
				Option::set("main", "error_reporting", "85");
				break;

			case false;
				Option::set("main", "error_reporting", "0");
				break;
		}


		/*
		if (
			($f = Application::getDocumentRoot() . "/bitrix/php_interface/dbconn.php") &&
			file_exists($f)
		) {
			$const = "UP_CORE_IS_DEV_MODE";
			$define = "define(\"{$const}\", \"{$value}\");";
			$content = file_get_contents($f);

			if (preg_match("~\s*define\(['\"]{$const}['\"],\s*['\"][^'\"]+['\"]\);\s*~", $content, $matches)) {
				$content = str_replace($matches[0], "\n{$define}\n", $content);
			} else {
				$content = preg_replace("~^(<\?|<\?php)\s*~", "<?\n{$define}\n", $content);
			}

			if (!empty($content)) file_put_contents($f, $content);
		}
		*/


		if (
			($f = Application::getDocumentRoot() . "/bitrix/.settings.php") &&
			file_exists($f)
		) {
			/** @noinspection PhpIncludeInspection */
			$content = include $f;

			if (!empty($content) && is_array($content)) {
				if (
					!isset($content["exception_handling"]["value"]["debug"]) ||
					$content["exception_handling"]["value"]["debug"] != $isDevMode
				) {
					$content["exception_handling"]["value"]["debug"] = $isDevMode;
					file_put_contents(
						$f,
						"<?php\n/* Ansible managed */\nreturn " .
						var_export($content, true) .
						";\n"
					);
				}
			}
		}
	}

}