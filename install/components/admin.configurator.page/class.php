<?
use Bitrix\Main\AccessDeniedException;
use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();


class UplabCoreAdminConfiguratorPageComponent extends CBitrixComponent
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
		global $APPLICATION;

		$APPLICATION->SetAdditionalCSS("/local/modules/uplab.core/install/assets/dist/css/admin-configurator-page.css");
	}

	/**
	 * выполняет действия после выполения компонента, например установка заголовков из кеша
	 */
	protected function executeEpilog()
	{
	}

	protected function prepareResult()
	{
		$this->arResult["TEMPLATE_DATA"]["STEPS"] = [
			[
				"TITLE"  => "Установить менеджер пакетов Composer",
				"READY"  => $this->checkIfComposerInstalled(),
				"FIELDS" => array_filter([
					[
						"TEXT" => $this->checkIfComposerInstalled()
							? "Composer установлен на сервере"
							: "Чтобы установить Composer, необходимо выполнить через SSH под рутом команду",
					],
					$this->checkIfComposerInstalled() ? null : [
						"TYPE" => "code",
						"TEXT" => "php -r \"copy('https://getcomposer.org/installer', 'composer-setup.php');\" &&
HASH=\"$(wget -q -O - https://composer.github.io/installer.sig)\" &&
php -r \"if (hash_file('SHA384', 'composer-setup.php') === '\$HASH') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;\" &&
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer && rm composer-setup.php",
					],
				]),
			],
			[
				"TITLE"  => "Сконфигурировать `composer.json`",
				"READY"  => $this->checkIfComposerJsonExists(),
				"FIELDS" => [
					[
						"TEXT" => "Создать файл `composer.json` в папке `/local/`, опистаь там список зависимостей проекта",
					],
					[
						"TYPE"  => "checkbox",
						"LABEL" => "Модуль `digitalwand.admin_helper`",
					],
					[
						"TYPE"  => "checkbox",
						"LABEL" => "Модуль `sprint.migration`",
					],
					[
						"TYPE" => "button",
						"TEXT" => $this->checkIfComposerJsonExists()
							? "Обновить конфиг composer"
							: "Сгенерировать `composer.json`",
					],
				],
			],
			[
				"TITLE"  => "Сгенерировать модуль проекта",
				"FIELDS" => [
					[
						"TYPE"  => "input",
						"LABEL" => "Название модуля",
					],
					[
						"TYPE"  => "input",
						"LABEL" => "Левая часть неймспейса",
					],
					[
						"TYPE"  => "input",
						"LABEL" => "Правая часть неймспейса",
						"VALUE" => "Tools",
					],
					[
						"TYPE" => "button",
						"TEXT" => "Сгенерировать модуль",
					],
				],
			],
			[
				"TITLE"  => "Подключить модуль проекта в init.php",
				"FIELDS" => [
					[
						"TEXT" => "Добавить директиву Loader::includeModule('core.tools'); в /local/php_interface/init.php",
					],
					[
						"TYPE" => "button",
						"TEXT" => "Применить",
					],
				],
			],
			[
				"TITLE"  => "Базовая настройка шаблона проекта",
				"FIELDS" => [
					[
						"TEXT" => "Создать дефолтные header, footer",
					],
					[
						"TEXT" => "Настроить сборщик ресурсов в шаблоне",
					],
					[
						"TEXT" => "Создать вспомогательный package.json в корне",
					],
					[
						"TYPE" => "button",
						"TEXT" => "Применить",
					],
				],
			],
			[
				"TITLE" => "Сконфигурировать список зависимостей",
			],
			// - Наши модули
			// - Сторонние модули
			// - PHP-пакеты"
		];
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
		if (!$GLOBALS["USER"]->IsAdmin()) {
			throw new AccessDeniedException("Access denied");
		}
	}

	private function checkIfComposerInstalled()
	{
		return false;
		static $check = null;

		if (!isset($check)) {
			// TODO: Убедиться, что это самое надежное решение
			$check = file_exists("/usr/local/bin/composer") || file_exists("/usr/bin/composer");
		}

		return $check;
	}

	private function checkIfComposerJsonExists()
	{
		return false;
		static $check = null;

		if (!isset($check)) {
			$check = file_exists(Application::getDocumentRoot() . "/local/composer.json");
		}

		return $check;
	}
}
