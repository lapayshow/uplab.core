<?
use Bitrix\Main\AccessDeniedException;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();


class UplabCoreAdminGeneratorsPageComponent extends CBitrixComponent
{
	const DEFAULT_CACHE_TYPE = "A";
	const DEFAULT_CACHE_TIME = 2592000;

	/**
	 * Коллекция ошибок работы компонента
	 *
	 * @var ErrorCollection $errors
	 */
	protected $errors = [];

	protected $cacheKeys = [
		"ID",
		"NAME",
		"__RETURN_VALUE",
	];

	protected $requiredModules = ["uplab.core"];

	/**
	 * дополнительные параметры, от которых должен зависеть кеш
	 *
	 * @var array
	 */
	protected $additionalCacheID = [];

	public function onPrepareComponentParams($params)
	{
		$this->errors = new ErrorCollection();

		$params["CACHE_TYPE"] = isset($params["CACHE_TYPE"])
			? $params["CACHE_TYPE"]
			: static::DEFAULT_CACHE_TYPE;

		$params["CACHE_TIME"] = isset($params["CACHE_TIME"])
			? $params["CACHE_TIME"]
			: static::DEFAULT_CACHE_TIME;

		return array_filter($params);
	}

	public function executeComponent()
	{
		try {

			$this->executeProlog();

			$this->includeModules();
			$this->checkRequiredParams();

			if (!$this->readDataFromCache()) {
				$this->putDataToCache();
				$this->prepareResult();
				$this->includeComponentTemplate();
				$this->endResultCache();
			}

			$this->executeEpilog();

			return $this->arResult["__RETURN_VALUE"];

		} catch (Exception $exception) {
			$this->abortResultCache();
			$this->errors->setError(new Error($exception->getMessage()));
		}

		$this->showErrorsIfAny();

		return false;
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

		return !($this->StartResultCache(false, $this->additionalCacheID));
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
	 * выполяет действия перед кешированием
	 */
	protected function executeProlog()
	{
	}

	/**
	 * выполняет действия после выполения компонента, например установка заголовков из кеша
	 */
	protected function executeEpilog()
	{
	}

	protected function prepareResult()
	{
	}


	/**
	 * Отображает ошибки, возникшие при работе компонента, если они есть
	 */
	protected function showErrorsIfAny()
	{
		if ($this->errors->count()) {
			foreach ($this->errors as $error) {
				ShowError($error);
			}
		}
	}

	/**
	 * Подключает модули, необходимые для работы компонента
	 *
	 * @throws LoaderException
	 */
	protected function includeModules()
	{
		foreach ($this->requiredModules as $requiredModule) {
			if (empty($requiredModule)) continue;

			if (!Loader::includeModule($requiredModule)) {
				$this->errors->setError(new Error("Module `{$requiredModule}` is not installed."));
			}
		}
	}

	/**
	 * Проверяет выполнение всех необходимых условий для работы компонента
	 *
	 * @throws Exception
	 */
	protected function checkRequiredParams()
	{
		// if (!$this->arParams["USER_ID"]) {
		// 	throw new AccessDeniedException(Loc::getMessage("EXCEPTION_ACCESS_DENIED"));
		// }
		// $user = UserTable::getById($this->arParams["USER_ID"])->fetchObject();
		// if (!$user) {
		// 	throw new Exception(Loc::getMessage("EXCEPTION_USER_NOT_FOUND"));
		// }
	}
}
