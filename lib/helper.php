<?

namespace Uplab\Core;


use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Application;
use CFile;
use CHTTP;
use CIBlockElement;
use Cutil;
use Uplab\Core\Data\Cache;
use Uplab\Core\System\SystemUtils;
use Uplab\Core\Data\StringUtils;


use /** @noinspection PhpUndefinedClassInspection */
	/** @noinspection PhpUndefinedNamespaceInspection */
	Uplab\Editor\Surrogates;


/**
 * universal helper class for Bitrix projects
 */
class Helper
{
	const MODULE_ID             = "uplab.core";
	const SITE_TEMPLATE_PATH    = "/local/templates/main";
	const DEFAULT_TEMPLATE_PATH = "/local/templates/.default";
	const REDIRECT_STATUS_301   = "301 Moved Permanently";
	const REDIRECT_STATUS_302   = "302 Found";
	const TRANSPARENT_PIXEL     = "data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==";

	public static function isRoot()
	{
		return self::getCurPage(true) === SITE_DIR;
	}

	public static function set404()
	{
		@define('ERROR_404', 'Y');
		$GLOBALS["is404"] = 1;
		CHTTP::SetStatus("404 Not Found");
	}

	public static function getRealCurDir()
	{
		static $curDir = false;
		if (!empty($curDir)) return $curDir;

		if ($path = $_SERVER["REAL_FILE_PATH"]) {
			$path = pathinfo($path);
			$curDir = rtrim($path["dirname"], "/") . "/";
		} else {
			$curDir = self::getCurPage();
		}

		return $curDir;
	}

	public static function makeCorrectLink($link, $default = true)
	{
		if ($filterLink = filter_var($link, FILTER_VALIDATE_URL)) return $filterLink;

		$link = filter_var($link, FILTER_SANITIZE_URL);

		if (empty($link)) {
			$link = $default ? "javascript:;" : false;
		} else {
			$link = "http://{$link}";
		}

		return $link;
	}

	/**
	 * Возвращает информацию о файле.
	 * Может принимать на вход:
	 * - ID файла (из таблицы b_file)
	 * - Полный путь от корня сервера
	 * - Путь от корня сайта
	 *
	 * По умолчанию, если у файла не заполнено описание,
	 * подставляет в "DESCRIPTION" оригинальное название файла без расширения.
	 * Поведение можно переопределить, передав вторым параметром ["defaultDescription" => false]
	 *
	 * @param       $file
	 * @param array $options
	 *
	 * @return bool|mixed
	 */
	public static function getFileInfo($file, $options = array())
	{
		$defaultDescription = $options["defaultDescription"] ?? true;

		if (intval($file)) {
			$fileItem = CFile::GetFileArray($file);
		} elseif (empty(trim($file))) {
			return false;
		} else {
			$file = str_replace(Application::getDocumentRoot(), "", $file);

			$fileItem["SRC"] = $file;

			$fileItem["PATH"] = Application::getDocumentRoot() . $fileItem["SRC"];
			$fileItem["FILE_SIZE"] = filesize($fileItem["PATH"]);

			$pathInfo = pathinfo($fileItem["PATH"]);

			$fileItem["ORIGINAL_NAME"] = $pathInfo["basename"];
			$fileItem["FILE_NAME"] = $pathInfo["basename"];
		}

		if (!isset($fileItem["PATH"])) {
			$fileItem["PATH"] = Application::getDocumentRoot() . $fileItem["SRC"];
		}

		$fileItem["EXT"] = GetFileExtension($fileItem["SRC"]);
		$fileItem["CONTENT_TYPE"] = mime_content_type($fileItem["PATH"]);
		$fileItem["IS_SVG"] =
			stripos($fileItem["CONTENT_TYPE"], "text/") !== false ||
			stripos($fileItem["CONTENT_TYPE"], "/svg") !== false;

		if (
			empty($fileItem["DESCRIPTION"]) &&
			!empty($fileItem["ORIGINAL_NAME"]) &&
			$defaultDescription === true
		) {
			$originalName = pathinfo($fileItem["ORIGINAL_NAME"]);
			$originalName = $originalName["filename"];
			$fileItem["DESCRIPTION"] = StringUtils::cutString($originalName, 40);
		}

		$fileItem["SIZE"] = CFile::FormatSize($fileItem["FILE_SIZE"]);
		$fileItem["EXT"] = strtoupper($fileItem["EXT"]);

		return $fileItem;
	}

	public static function numberFormat($number, $digits = 2, $rtrim = false)
	{
		$number = floatval($number);
		$digits = (int)$number == $number ? 0 : $digits;

		$value = number_format($number, $digits, ',', '&nbsp;');

		if ($rtrim) {
			$value = rtrim($value, "0");
		}

		return $value;
	}

	/**
	 * @param string $picture Изображение, путь от корня
	 * @param array  $params  Массив параметров: ширина, высота, метод сжатия, качество
	 *
	 * @return mixed
	 */
	public static function resizeImageFile($picture, $params)
	{
		$pic = $originalPic = Helper::getFileInfo($picture);
		$md5 = substr(md5($pic["PATH"]), 0, 3);

		$params["width"] = (int)$params["width"] ?: 400;
		$params["height"] = (int)$params["height"] ?: 300;
		$params["method"] = $params["method"] ?: BX_RESIZE_IMAGE_PROPORTIONAL_ALT;
		$params["quality"] = (int)$params["quality"] ?: false;
		$params["src_only"] = (bool)$params["src_only"];
		$params["get_default"] = (bool)$params["get_default"];
		$params["get_size"] = isset($params["get_size"]) ? (bool)$params["get_size"] : true;

		$destinationFilename = preg_replace("~[\s(+)]+~", "_", $pic["FILE_NAME"]);

		$destinationSrc =
			"/upload/" .
			self::MODULE_ID .
			"/{$md5}/" .
			implode("_", array_filter((array)$params + (array)$destinationFilename));

		// $destinationSrc = preg_replace("~\.png$~", ".jpg", $destinationSrc);

		$destinationPath = "{$_SERVER["DOCUMENT_ROOT"]}{$destinationSrc}";

		if ($pic && $pic["PATH"] && file_exists($pic["PATH"])) {
			$imageSize = getimagesize($pic["PATH"]);
			$pic["SRC_WIDTH"] = $pic["WIDTH"] = $imageSize[0];
			$pic["SRC_HEIGHT"] = $pic["HEIGHT"] = $imageSize[1];

			if ($pic["SRC_WIDTH"] > $params["width"] && $pic["SRC_HEIGHT"] > $params["height"]) {
				CFile::ResizeImageFile(
					$pic["PATH"],
					$destinationPath,
					[
						"width"  => $params["width"],
						"height" => $params["height"],
					],
					$params["method"], [], $params["quality"]
				);
			} else {
				unset($destinationPath);
			}

			if (!empty($destinationPath) && file_exists($destinationPath)) {
				SystemUtils::delayedFileRemove($destinationPath, strtotime("+3 months"));
				$pic["PATH"] = $destinationPath;
				$pic["SRC"] = str_replace(Application::getDocumentRoot(), "", $destinationPath);

				$imageSize = getimagesize($pic["PATH"]);
				$pic["WIDTH"] = $imageSize[0];
				$pic["HEIGHT"] = $imageSize[1];
			}
		}


		if (empty($pic["SRC"]) && $params["get_default"] === true) {
			$pic = self::resizeImage(false, $params);
		}

		if (isset($pic["SRC"]) && $params["src_only"]) {
			return $pic["SRC"];
		}

		return $pic;
	}

	/**
	 * Основные особенности метода: может принимать как одно изображение, так и несколько.
	 *
	 * - Если изображений несколько, будет использовано первое непустое значение массива.
	 *   Пример: если передать массив из PREVIEW_PICTURE и DETAIL_PICTURE, то сможем решить
	 *   задачу отображения хотя бы одного
	 *
	 * - Доступен параметр "get_default", при установке которого возвращается изображение
	 *   по умолчанию нужных размеров.
	 *
	 * - Метод решает задачу с разнородным регистром ключей в массиве ResizeImageGet, ResizeImageFile и т.д.:
	 *   ключи возвращаемого значения всегда в верхнем регистре.
	 *
	 * - Метод возвращает ключ "DESCRIPTION" для изображения, если у него заполнено поле описания
	 *
	 * @param int|array $picture
	 * @param array     $params
	 *
	 * @return array|bool
	 */
	public static function resizeImage($picture, $params = array())
	{
		/**
		 * @var $picture          int|array    Исходное изображение
		 * @var $width            int          Ширина возвращаемого изображения
		 * @var $height           int          Высота возвращаемого изображения
		 * @var $capitalize       bool         Приводить ключи массива к верхнему регистру
		 * @var $getSize          bool         Возвращать размеры изображения
		 * @var $method           int          Алгоритм, по которому будет "ресайзиться" исходное изображение
		 *                                     По умолчанию BX_RESIZE_IMAGE_PROPORTIONAL_ALT
		 *
		 * @var $getDefault       bool         Возвращать дефолтное изображение требуемых размеров,
		 *                                     если исходное отсутствует.
		 *                                     Дефолтное изобрежение берется из константы DEFAULT_PICTURE
		 * @var $checkSize        bool         Если изображение меньше требуемых размеров,
		 *                                     будет возвращено дефолтное изображеие
		 * @var $defaultMethod    int          Алгоритм, по которому будет "ресайзиться" дефолтное изображение
		 *                                     По умолчанию BX_RESIZE_IMAGE_EXACT
		 *
		 * @var $srcOnly          bool         Если true, то будет возвращен только SRC, а не весь массив
		 */

		/**
		 * Это legacy для старого кода, в котором параметр метода был всего один
		 *
		 * @deprecated
		 */
		if (count(func_get_args()) === 1) {
			$params = $picture;
			$picture = $params["picture"];
		}

		$params = array_merge([
			"width"          => 200,
			"height"         => 200,
			"get_size"       => true,
			"get_default"    => false,
			"src_only"       => false,
			"check_size"     => false,
			"method"         => BX_RESIZE_IMAGE_PROPORTIONAL_ALT,
			"default_method" => BX_RESIZE_IMAGE_EXACT,
			"picture"        => false,
			"capitalize"     => true,
		], (array)$params);

		$width = $params["width"];
		$height = $params["height"];
		$getSize = $params["get_size"];
		$getDefault = $params["get_default"];
		$srcOnly = $params["src_only"];
		$checkSize = $params["check_size"];
		$method = $params["method"];
		$defaultMethod = $params["default_method"];
		$picture = $picture ?: $params["picture"];
		$capitalize = $params["capitalize"];
		$quality = $params["quality"] ?? false;

		$keyCase = $capitalize ? CASE_UPPER : CASE_LOWER;
		$getSize = $getSize || $checkSize;

		if (is_array($picture) && isset($picture["ID"])) {
			$srcPic = $picture;
		} else {
			foreach ((array)$picture as $key => $pic) {
				if (empty($pic)) continue;
				$srcPic = $pic;
				break;
			}
		}

		$size = array("width" => $width, "height" => $height);

		if (!empty($srcPic)) {
			// Склеиваем информацию о новом изображении с информацией о файле из БД
			// полезно, если хотим получить в результате DESCRIPTION
			if (is_numeric($srcPic)) $srcPic = CFile::GetFileArray($srcPic);

			$srcPic["ORIGINAL_SRC"] = $srcPic["SRC"];
			$srcPic = array_change_key_case($srcPic, $keyCase);

			if ($getSize) {
				$srcPic["SRC_WIDTH"] = $srcPic["WIDTH"];
				$srcPic["SRC_HEIGHT"] = $srcPic["HEIGHT"];
			}

			$newPic = array_change_key_case(
				CFile::ResizeImageGet(
					$srcPic,
					$size,
					$method,
					$getSize
				),
				$keyCase
			);

			if (!empty($newPic)) {
				$newPic = array_merge($srcPic, $newPic);

				if (!isset($newPic["~DESCRIPTION"]) && !empty($newPic["DESCRIPTION"])) {
					$newPic["~DESCRIPTION"] = htmlspecialchars_decode($newPic["DESCRIPTION"]);
				}
			}
		}

		// Если картинки нет, либо она не подходит,
		// получаем дефолтное изображение нужных размеров
		if (empty($newPic) ||
			(
				$newPic["WIDTH"] < $width &&
				$newPic["HEIGHT"] < $height &&
				$checkSize
			)) {

			if (!$getDefault) return false;

			$defaultPictureName = implode("_", [
					"default",
					SITE_ID,
					$defaultMethod,
					$width,
					$height,
					$quality ?: "0",
				]) . ".jpg";

			$newPic = UplabCache::cacheMethod(function () use (
				$defaultPictureName,
				$defaultMethod,
				$width,
				$height,
				$size,
				$quality
			) {
				$defaultSrc = defined("DEFAULT_PICTURE")
					? Application::getDocumentRoot() . DEFAULT_PICTURE
					: false;
				$src = Application::getDocumentRoot() . "/upload/{$defaultPictureName}";
				$newPic = [];

				if ($defaultSrc && file_exists($defaultSrc)) {
					$isResized = CFile::ResizeImageFile(
						$defaultSrc,
						$src,
						$size,
						$defaultMethod,
						[],
						$quality
					);

					if (!file_exists($src) || !$isResized) {
						$src = $defaultSrc;
					}
				} else {
					$src = "http://placehold.it/{$width}x{$height}";
					$skipCheck = true;
				}

				if (file_exists($src) || !empty($skipCheck)) {
					$src = CUtil::GetAdditionalFileURL(str_replace(Application::getDocumentRoot(), "", $src));
					$newPic = compact("src", "width", "height");
				}

				return $newPic;
			}, ["id" => $defaultPictureName]);

		}

		$result = array_change_key_case($newPic, $keyCase);

		if ($srcOnly) {
			return !empty($result["SRC"]) ? $result["SRC"] : "";
		}

		return $result;
	}

	/**
	 * Возвращает путь до изображения заданного размера
	 *
	 * @param int  $imageId ID изображения
	 * @param int  $width   Ширина
	 * @param int  $height  Высота
	 * @param bool $strict  Если true, то изображение будет подогнано под заданные размеры
	 *
	 * @return string
	 */
	public static function getResizedImageSrc($imageId, $width, $height, $strict = false)
	{
		return self::resizeImage($imageId, [
			"width"    => $width,
			"height"   => $height,
			"method"   => $strict ? BX_RESIZE_IMAGE_EXACT : BX_RESIZE_IMAGE_PROPORTIONAL_ALT,
			"src_only" => true,
		]);
	}

	public static function printJsonOnly($array)
	{
		$GLOBALS["APPLICATION"]->RestartBuffer();
		header('Content-Type: application/json');

		exit(Json::encode(
			$array,
			JSON_HEX_TAG |
			JSON_HEX_AMP |
			JSON_HEX_APOS |
			JSON_HEX_QUOT |
			JSON_UNESCAPED_UNICODE
		));
	}

	public static function ajaxBuffer($start = true, $buffer = true, $params = [], $ajaxKey = "")
	{

        global $APPLICATION;
		$request = Context::getCurrent()->getRequest();

		if (
			!(empty($ajaxKey) && $request->isAjaxRequest()) &&
			!(!empty($ajaxKey) && $request->get($ajaxKey) == "y")
		) {
			return false;
		}

		if ($start) {
			$APPLICATION->RestartBuffer();
			if ($buffer) ob_start();
		} else {
			// require_once $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php";
			if (!$buffer) exit();

			$params["removeParams"] = (array)$params["removeParams"];
			if ($ajaxKey) $params["removeParams"][] = $ajaxKey;

			if (!array_key_exists("diffParams", $params)) {
				$params["diffParams"] = true;
			}

			$html = trim(ob_get_clean());

			if (Loader::includeModule("uplab.editor")) {
				if (class_exists(Surrogates::class)) {
					Surrogates::replaceOnBuffer($html);
				}
			}
            $selectModel = [];
            if (!empty($_REQUEST['mark'])) {
                $_SESSION['mark'] = $_REQUEST['mark'];
                if($_SESSION['mark'] != $_REQUEST['mark']) {
                    unset($_SESSION['mark']);
                    unset($_SESSION['model']);
                }
                $idModelList = CIBlockElement::GetList(
                    [],
                    ['IBLOCK_CODE' => 'brand', 'ID' => $_REQUEST['mark'], 'ACTIVE' => 'Y'],
                    false,
                    [],
                    ['ID', 'IBLOCK_ID', 'PROPERTY_*']
                )->GetNextElement()->GetProperties();

                $arModelId = $idModelList['BRAND_MODEL']['VALUE'];
                $modelList = CIBlockElement::GetList(
                    [],
                    ['IBLOCK_CODE' => 'model', 'ID' => $arModelId, 'ACTIVE' => 'Y'],
                    false,
                    [],
                    ['ID', 'IBLOCK_ID', 'NAME']
                );
                while ($models = $modelList->GetNextElement()) {
                    $arFields = $models->GetFields();
                    foreach ($_SESSION['model'] as $key => $value) {
                        if ($arFields['ID'] == $value) {
                            $selectModel[$value] = [
                                'title' => $arFields['NAME'],
                                'value' => $arFields['ID']
                            ];
                        }
                    }
                }
            }

            $result = [
				"html" => $html,

				"title" => $APPLICATION->GetPageProperty("title"),
				"h1"    => htmlspecialchars_decode($APPLICATION->GetTitle(false)),

				"url" => Uri::initWithRequestUri()
					->deleteSystemParams()
					->deleteParams($params["removeParams"])
					->updateParams($_GET)
					->getUri($params["diffParams"]),

                'model' => array_values($selectModel)
			];

			if (!empty($params["data"])) {
				$result = array_merge($result, $params["data"]);
			}

			header('Content-Type: application/json');

			exit(Json::encode(($result)));
		}

		return true;
	}

	public static function isMobileAgent()
	{
		self::detectOS($isMobile);

		return $isMobile;
	}

	public static function detectOS(&$isMobile = null)
	{
		static $res = false;

		if (!empty($res) && is_array($res)) {
			$isMobile = $res["isMobile"];

			return $res["os"];
		}

		$uAgent = $_SERVER["HTTP_USER_AGENT"];

		$dataOS = [
			"windows"    => [
				"isMobile" => false,
				"words"    => [
					"Win",
				],
			],
			"android"    => [
				"isMobile" => true,
				"words"    => [
					"Android",
				],
			],
			"linux"      => [
				"isMobile" => false,
				"words"    => [
					"Linux",
					"Ubuntu",
					"UNIX",
					"Fedora",
					"CentOS",
				],
			],
			"ios"        => [
				"isMobile" => true,
				"words"    => [
					"iPhone",
				],
			],
			"ipad"       => [
				"isMobile" => false,
				"words"    => [
					"iPad",
				],
			],
			"mac"        => [
				"isMobile" => false,
				"words"    => [
					"Mac",
				],
			],
			"search_bot" => [
				"isMobile" => false,
				"words"    => [
					"nuhk",
					"Googlebot",
					"Yammybot",
					"Openbot",
					"Slurp",
					"Ask Jeeves/Teoma",
					"ia_archiver",
					"rambler",
					"googlebot",
					"aport",
					"yahoo",
					"msnbot",
					"turtle",
					"mail.ru",
					"omsktele",
					"yetibot",
					"picsearch",
					"sape.bot",
					"sape_context",
					"gigabot",
					"snapbot",
					"alexa.com",
					"megadownload.net",
					"askpeter.info",
					"igde.ru",
					"ask.com",
					"qwartabot",
					"yanga.co.uk",
					"scoutjet",
					"similarpages",
					"oozbot",
					"shrinktheweb.com",
					"aboutusbot",
					"followsite.com",
					"dataparksearch",
					"google-sitemaps",
					"appEngine-google",
					"feedfetcher-google",
					"liveinternet.ru",
					"xml-sitemaps.com",
					"agama",
					"metadatalabs.com",
					"h1.hrn.ru",
					"googlealert.com",
					"seo-rus.com",
					"yaDirectBot",
					"yandeG",
					"yandex",
					"yandexSomething",
					"Copyscape.com",
					"AdsBot-Google",
					"domaintools.com",
					"Nigma.ru",
					"bing.com",
					"dotnetdotcom",
				],
			],
		];

		foreach ($dataOS as $os => $systemData) {
			foreach ($systemData["words"] as $str) {
				if (stripos($uAgent, $str) !== false) {
					$isMobile = $systemData["isMobile"];
					$res = compact("os", "isMobile");

					return $os;
				}
			}
		}

		return "unknown_os";
	}

	public static function makeDomainUrl($url, $domain = false, $isHttps = null)
	{
		if (empty($url)) return "";
		if (stripos($url, "//") !== false) return $url;

		$request = Context::getCurrent()->getRequest();

		$isHttps = isset($isHttps) ? $isHttps : $request->isHttps();

		$prefix = $isHttps ? "https://" : "http://";
		$prefix .= $domain ?: self::getHost(true);

		return "{$prefix}{$url}";
	}

	public static function getOption($option, $module = "", $default = "", $cache = true)
	{
		$module = $module ?: static::MODULE_ID;

		if ($cache === true) {
			return UplabCache::cacheMethod(__METHOD__, [
				"arguments" => [$option, $module, $default, false],
				"tags"      => [$module],
			]);
		}

		return Option::get($module, $option, $default);
	}

	public static function setOption($option, $value, $module = "")
	{
		$module = $module ?: static::MODULE_ID;

		Option::set($module, $option, $value);

		Application::getInstance()->getTaggedCache()->clearByTag($module);
	}

	public static function getCurPage($isPage = false)
	{
		global $APPLICATION;

		if ($isPage) {
			return $APPLICATION->GetCurPage(false);
		} else {
			return $APPLICATION->GetCurDir();
		}
	}

	/**
	 * Определяет текущий хост на основе одного из доступных параметров сервера
	 *
	 * @param bool $getFromOptions
	 *
	 * @return false|mixed|string|string[]|null
	 */
	public static function getHost($getFromOptions = false)
	{
		static $host = [];
		$id = (int)$getFromOptions;

		if (!isset($host[$id])) {
			$hostVariants = [
				$_SERVER["SCRIPT_URI"],
				$_SERVER["HTTP_ORIGIN"],
				($v = $_SERVER["HTTP_HOST"]) ? "http://{$v}" : "",
			];

			if ($getFromOptions) {
				$hostVariants[] = ($v = SITE_SERVER_NAME) ? "http://{$v}" : "";
				$hostVariants[] = ($v = self::getOption("server_name", "main", "", false)) ? "http://{$v}" : "";
			}

			$hostUrl = current(array_filter($hostVariants));
			$host[$id] = Uri::init($hostUrl)->getHost();
		}

		return mb_strtolower($host[$id]);
	}

	public static function isDevMode($host = "", $cache = true)
	{
		if (empty($host)) {
			$host = self::getHost();
		}

		if ($cache === true) {
			return Cache::cacheMethod(__METHOD__, [
				"arguments" => [$host, false],
				"tags"      => [self::MODULE_ID],
			]);
		}

		if ($domains = self::getOption("debug_domains", "", "", false)) {
			$domains = preg_split("~\s+~", trim(mb_strtolower($domains)));

			if (!empty($host)) {
				$isDevMode = in_array($host, $domains);
			} else {
				$isDevMode = null;
			}
		} else {
			$isDevMode = self::getOption("is_dev_mode") == "Y";
		}

		if (isset($isDevMode)) {
			SystemUtils::setDevModeConfig($isDevMode);
		}

		return $isDevMode;
	}

}
