<?

namespace Uplab\Core\Data;


use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache as BxCache;


class Cache extends BxCache
{
	const CACHE_ABORT_FLAG = "CACHE_ABORT_FLAG";

	/**
	 * Вспомогательный метод для работы с кешем: может подготовить такие параметры,
	 * как cacheDir, cacheId, cacheTime, чтобы каждый раз не брать их с потолка.
	 * Параметры подготавливаются но основании коллбэка, переданного в качестве второго параметра.
	 * Например, можно передать константу __METHOD__, если кешируется метод класса.
	 * В таком случае cacheId и cacheDir будут состоять из
	 * - идентификатора сайта,
	 * - нэймспейса класса (каждая часть нэймспейса - отдельная папка),
	 * - названия метода,
	 * - и хэша для массива параметров, влияющих на результат.
	 *
	 * @param array         $params
	 * @param callable|null $callback
	 *
	 * @return array
	 */
	public static function getCacheParams($params = [], callable $callback = null)
	{
		if (!empty($params["id"])) {
			$id = $params["id"];
		} elseif (is_array($callback)) {
			$id = implode(
				DIRECTORY_SEPARATOR,
				[
					is_object($callback[0]) ? get_class($callback[0]) : $callback[0],
					$callback[1],
				]
			);
		} elseif (is_string($callback)) {
			$id = $callback;
		} elseif (is_string($params)) {
			$id = $params;
			$params = (array)$params;
		} else {
			return null;
		}

		$arguments = (array)$params["arguments"] ?: [];

		if (empty($params["id"])) {
			if (!empty($arguments)) {
				$_args = array_filter($arguments);

				// FIXME: это написано, потому что были ошибки, но оно работает плохо
				// foreach ($arguments as $argument) {
				// 	if (is_scalar($argument)) $_args[] = $argument;
				// }

				// $ser = serialize($_args);
				// $md5 = md5(serialize($_args));
				$id .= DIRECTORY_SEPARATOR . substr(md5(serialize($_args)), 0, 8);
			}
		}

		$id = implode(DIRECTORY_SEPARATOR, [SITE_ID, $id]);
		$id = str_replace(["\\", "::"], DIRECTORY_SEPARATOR, $id);
		$id = str_replace(["(", ")"], "", $id);

		$dir = $id;

		// время кеширования по умолчанию  - 1000 часов
		$time = $params["time"] ?: 3600 * 1000;

		return compact("time", "id", "dir", "arguments", "_args", "ser", "md5");
	}

	/*
	Принимает в качестве callback массив: класс / метод, либо строку
	тогда $params["id"] необязателен.
	Если $callback - это замыкание, то обазателен $params["id"]

	Как испрользовать: добавляем в начале метода код.
	Идея в то, что мы дважды вызываем один и тот же метод, но только в случае,
	если хотим получить данные из кеша, передаем параметр $cache = true,
	а если хотим создать кеш, то передаем в массив $arguments параметр $cache = false

	// Если внутри функции-коллбэка вернуть константу self::CACHE_ABORT_FLAG,
	// то результат закеширован не будет

	// Пример использования:
	public static function cachedMethodName($params = [], $cache = true)
	{
		if ($cache === true) {
			return Cache::cacheMethod(__METHOD__, [
				"arguments" => [$params, false],
				"tags"      => [
					"iblock_id_" . TAG_1_IBLOCK,
					"iblock_id_" . TAG_2_IBLOCK,
					"iblock_new",
				],
			]);
		}
		// код кешируемого метода
		$data = [];
		return $data;
	}
	*/
	/**
	 * @param callable $callback
	 * @param array    $params
	 * @param null     $isCacheValid
	 *
	 * @return mixed
	 */
	public static function cacheMethod(callable $callback, $params = [], &$isCacheValid = null)
	{
		if (!is_callable($callback)) return false;

		$arData = array();

		if (is_string($params)) {
			$params = ["id" => $params];
		}

		$cache = Cache::createInstance();
		$cacheParams = self::getCacheParams($params, $callback);

		if (stripos($cacheParams["id"], "getCardsList") !== false) {
			AddMessage2Log($cacheParams);
		}

		if ($cache->initCache($cacheParams["time"], $cacheParams["id"], $cacheParams["dir"])) {
			$isCacheValid = true;
			$arData = $cache->getVars();
		} elseif ($cache->startDataCache()) {
			$isCacheValid = false;

			$taggedCache = Application::getInstance()->getTaggedCache();

			if (!empty($params["tags"])) {
				$taggedCache->startTagCache($cacheParams["dir"]);
			}

			$arData = call_user_func_array($callback, $cacheParams["arguments"]);

			if (!empty($params["tags"])) {
				foreach ((array)$params["tags"] as $tag) {
					$taggedCache->registerTag($tag);
				}
				$taggedCache->endTagCache();
			}

			if ($arData === self::CACHE_ABORT_FLAG) {
				$cache->abortDataCache();

				return false;
			} else {
				$cache->endDataCache($arData);
			}
		}

		return $arData;
	}
}