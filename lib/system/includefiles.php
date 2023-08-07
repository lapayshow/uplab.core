<?php
/**
 * Created by PhpStorm.
 * User: geffest
 * Date: 2019-03-02
 * Time: 04:26
 */

namespace Uplab\Core\System;


use Uplab\Core\Constant;
use Uplab\Core\Data\StringUtils;
use Uplab\Core\Helper;
use Uplab\Core\Traits\SingletonTrait;


class IncludeFiles
{
	use SingletonTrait;

	const DEFAULT_INCLUDE_AREAS_PATH = "#SITE_DIR#/include";
	const DEFAULT_INCLUDE_VIEWS_PATH = "#SITE_TEMPLATE_PATH#/views";

	private $relativeLangDir;

	function __construct()
	{
		$this->relativeLangDir = Helper::getOption("relative_lang_dir") ?: SITE_DIR;
	}

	/**
	 * @deprecated
	 *
	 * TODO: Удалить эти методы
	 *
	 * Фуннкция для рекурсивного подключения элемента шаблона
	 * По умолчанию файлы "views" располагаются по пути
	 * #SITE_DIR#/include, путь может быть переопределен
	 * в настройках модуля Uplab.Core
	 *
	 * В отличие от includeView, отличается порядок параметорв
	 * Второй параметр - массив настроек для $APPLICATION->IncludeFile().
	 * Параметры, которые будут переданы в файл можно - третий параметр.
	 *
	 * Расширение файла ".php" дописывать не обязательно.
	 *
	 * Пример вызова:
	 * self::includeArea("test");
	 *
	 * Будет подключен файл #SITE_DIR#/include/test.php
	 *
	 * @param       $file
	 * @param array $params
	 * @param array $options
	 *
	 * @return mixed
	 */
	public static function includeArea($file, $options = array(), $params = array())
	{
		static $dir = false;

		if ($dir === false) {
			$dir = Helper::getOption("inc_areas_path");
			if (empty($dir)) {
				$dir = self::DEFAULT_INCLUDE_AREAS_PATH;
			}
			$dir = Constant::extract($dir);
		}

		return self::includeFileOld(
			$file,
			$dir . self::getInstance()->relativeLangDir,
			$params,
			$options
		);
	}

	/**
	 * @deprecated
	 *
	 * Фуннкция для рекурсивного подключения элемента шаблона
	 * По умолчанию файлы "views" располагаются по пути
	 * #SITE_TEMPLATE_PATH#/views, путь может быть переопределен
	 * в настройках модуля Uplab.Core
	 *
	 * Расширение файла ".php" дописывать не обязательно.
	 *
	 * Пример вызова:
	 * self::includeView("test");
	 *
	 * Будет подключен файл #SITE_TEMPLATE_PATH#/views/test.php
	 *
	 * @param       $file
	 * @param array $params
	 * @param array $options
	 *
	 * @return mixed
	 */
	public static function includeView($file, $params = array(), $options = array("SHOW_BORDER" => false))
	{
		static $dir = false;

		if ($dir === false) {
			if ($dir = Helper::getOption("inc_views_path")) {
				$dir = Constant::extract($dir);
			} else {
				$dir = self::DEFAULT_INCLUDE_VIEWS_PATH;
			}
		}

		return self::includeFileOld($file, $dir, $params, $options, false);
	}

	public static function includeFile($filePath, $arParams = [], $arOptions = [])
	{
		global $APPLICATION;

		$filePath = str_replace(
			[
				"#REAL_CUR_DIR#",
				"#CUR_DIR#",
				"#CUR_PAGE#",
				"#SITE_DIR#",
			],
			[
				Helper::getRealCurDir(),
				$APPLICATION->GetCurDir(),
				$APPLICATION->GetCurPage(false),
				SITE_DIR,
			],
			$filePath
		);

		$filePath = preg_replace("~/+~", "/", $filePath);

		$APPLICATION->IncludeFile($filePath, $arParams, $arOptions);
	}

	public static function includeWithoutButtons($filePath, $arParams = [], $arOptions = [])
	{
		$arOptions = array_merge(
			["SHOW_BORDER" => false],
			(array)$arOptions
		);

		self::includeFile($filePath, $arParams, $arOptions);
	}

	/**
	 * @deprecated
	 *
	 * Рекурсивно искать файл шаблона страницы в папке шаблона сайта
	 * Первый путь для поиска /include_pages/LN/ (LN - текущий язык)
	 * Общие для обоих языков шаблоны в корне папок /inc_pages, /inc_areas
	 *
	 * @param       $file
	 * @param bool  $dir
	 * @param array $params
	 * @param array $options
	 * @param bool  $recursive
	 *
	 * @return mixed
	 */
	private static function includeFileOld($file, $dir = false, $params = array(), $options = array(), $recursive = true)
	{
		global $APPLICATION;

		if (!StringUtils::isStringEndsWith($file, ".php")) $file = $file . '.php';

		if ($dir === false) $dir = Helper::getCurPage();
		$dir = rtrim($dir, "/") . "/";

		if ($recursive) {
			$file = $APPLICATION->GetFileRecursive($file, $dir);
		} else {
			$file = $dir . $file;
		}

		if (!empty($file)) {
			if ($options["RETURN_FILE_CONTENT"] == "Y") {

				unset($options["RETURN_FILE_CONTENT"]);
				ob_start();
				self::includeFileOld($file, $dir, $params, $options, $recursive);

				return ob_get_clean();

			} elseif ($options["RETURN_FILE_PATH"] == "Y") {

				return $file;

			} else {

				return $APPLICATION->IncludeFile($file, $params, $options);

			}
		}

		return false;
	}

}