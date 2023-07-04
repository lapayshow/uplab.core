<?

namespace Uplab\Core;


use CBitrixComponent;
use Uplab\Core\Components\TemplateBlock;


CBitrixComponent::includeComponentClass("uplab.core:template.block");


class VariablesComponent extends CBitrixComponent
{
	/**
	 * кешируемые ключи arResult
	 *
	 * @var array()
	 */
	protected $cacheKeys = array(
		"__RETURN_VALUE",
	);

	public static function initParameters($set)
	{
		return TemplateBlock::initParameters($set);
	}

	public function executeComponent()
	{
		if (!$this->readDataFromCache()) {
			$this->putDataToCache();
			$this->includeComponentTemplate();
			$this->endResultCache();
		}

		if (!empty($this->arResult["__RETURN_VALUE"])) {
			$GLOBALS["uplab.core:variables_RESULT"] = $this->arResult["__RETURN_VALUE"];
		}
	}

	/**
	 * подготавливает входные параметры
	 *
	 * @param $params
	 *
	 * @return array
	 */
	public function onPrepareComponentParams($params)
	{
		foreach ($params as &$param) {
			if (!is_array($param)) continue;
			$param = array_diff($param, [""]);
		}

		if (empty($params["CACHE_TIME"])) {
			if (defined("CACHE_TIME")) {
				$params["CACHE_TIME"] = CACHE_TIME;
			} else {
				$params["CACHE_TIME"] = 360000;
			}
		}
		$params["CACHE_TYPE"] = empty($params["CACHE_TYPE"]) ? $params["CACHE_TYPE"] : "A";

		return $params;
	}

	/**
	 * кеширует ключи массива arResult
	 */
	protected function putDataToCache()
	{
		if (is_array($this->cacheKeys) && sizeof($this->cacheKeys) > 0) {
			$this->SetResultCacheKeys($this->cacheKeys);
		}
	}

	/**
	 * определяет читать данные из кеша или нет
	 *
	 * @return bool
	 */
	protected function readDataFromCache()
	{
		if ($this->arParams["CACHE_TYPE"] == "N") {
			return false;
		}

		return !($this->StartResultCache(false));
	}
}
