<?
use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Web\Json;
use Uplab\Core\Helper;
use Uplab\Core\Data\FormResultModifier;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();


class UplabCoreFormComponent extends CBitrixComponent implements Controllerable
{
	const DEFAULT_CACHE_TYPE = "N";
	const DEFAULT_CACHE_TIME = 2592000;

	public $MODULE_ID = "uplab.core";

	/**
	 * Коллекция ошибок работы компонента
	 *
	 * @var ErrorCollection $errors
	 */
	protected $errors = [];

	protected $cacheKeys    = [
		"__RETURN_VALUE",
	];
	protected $signedParams = [
		"WEB_FORM_ID",
	];

	protected $requiredModules = ["uplab.core"];

	/**
	 * дополнительные параметры, от которых должен зависеть кеш
	 *
	 * @var array
	 */
	protected $additionalCacheID = [];

	public function addFormResultAction()
	{
		$request = Context::getCurrent()->getRequest();

		$this->setTemplateName(filter_var($request->get("templateName")));
		$this->includeFormResultNewComponent();

		ob_start();
		$this->includeComponentTemplate();
		$html = ob_get_clean();

		return [
			"HTML"        => $html,
			"FORM_RESULT" => $this->arResult["FORM_RESULT"] == "addok",
			"NOTE"        => $this->arResult["FORM_NOTE"],
			"ERRORS"      => $this->arResult["FORM_ERROR_FIELDS"],
		];
	}

	public function initComponentParameters(&$params)
	{
		$request = Context::getCurrent()->getRequest();

		$params["SIGNED_PARAMS"] = $this->getSignedParameters()
			?: filter_var($request->get("signedParameters"));

		$params["FORM_ACTION"] = isset($params["FORM_ACTION"])
			? $params["FORM_ACTION"]
			: filter_var($request->get("formAction"));

		$params["WEB_FORM_ID"] = (int)$params["WEB_FORM_ID"];
		$params["SEF_MODE"] = "N";
		$params["USE_EXTENDED_ERRORS"] = "Y";

		$params["LIST_URL"] = isset($params["LIST_URL"])
			? $params["LIST_URL"]
			: Helper::getCurPage();

		$params["EDIT_URL"] = isset($params["EDIT_URL"])
			? $params["EDIT_URL"]
			: Helper::getCurPage();

		$params["CACHE_TYPE"] = static::DEFAULT_CACHE_TYPE;

		$params["CACHE_TIME"] = isset($params["CACHE_TIME"])
			? $params["CACHE_TIME"]
			: static::DEFAULT_CACHE_TIME;

		$params["WEB_FORM_ID"] = isset($params["WEB_FORM_ID"])
			? $params["WEB_FORM_ID"]
			: null;
	}

	public function onPrepareComponentParams($params)
	{
		$this->errors = new ErrorCollection();

		self::initComponentParameters($params);

		return array_filter($params);
	}

	public function executeComponent()
	{
		$this->arResult["COMPONENT_ID"] = CAjax::GetComponentID(
			$this->getName(),
			$this->getTemplateName(),
			"form" . $this->arParams["WEB_FORM_ID"]
		);

		try {

			$this->executeProlog();
			$this->includeAssets();

			$this->includeModules();
			$this->checkRequiredParams();

			if (!$this->readDataFromCache()) {
				$this->putDataToCache();
				$this->prepareResult();

				$this->printTemplateWrapper(true);
				$this->includeComponentTemplate();
				$this->printTemplateWrapper(false);

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
	 * @return array
	 */
	public function configureActions()
	{
		return [
			'addFormResult' => [
				'prefilters'  => [
					// new ActionFilter\Authentication(),
					new ActionFilter\HttpMethod(
						array(
							ActionFilter\HttpMethod::METHOD_GET,
							ActionFilter\HttpMethod::METHOD_POST,
						)
					),
					new ActionFilter\Csrf(),
				],
				'postfilters' => [],
			],
		];
	}

	protected function printTemplateWrapper($isStart)
	{
		if ($isStart) {
			echo "<div data-uplab-form-wrapper 
				{$this->arParams["~WRAPPER_CUSTOM_ATTR"]} 
				data-id='{$this->arResult["COMPONENT_ID"]}'>";
		} else {
			echo "</div>";
		}
	}

	protected function listKeysSignedParameters()
	{
		return array_merge(
			(array)$this->signedParams,
			(array)$GLOBALS["UPLAB_FORM_ADD_SIGNED_PARAMS"]
		);
	}

	/**
	 * @param $arResult
	 * @param $arParams
	 */
	protected function includeFormResultNewComponent()
	{
		$arResult = &$this->arResult;
		$arParams = &$this->arParams;

		/** @noinspection PhpUnusedLocalVariableInspection */
		$componentName = $this->getName();
		include __DIR__ . "/formResultNewComponent.php";

		FormResultModifier::prepareForm($arResult, $arParams);

		if (!empty($arParams["FORM_ACTION"])) {
			$arResult["FORM_ACTION"] = $arResult["~FORM_ACTION"] = $arParams["FORM_ACTION"];
		}

		$arResult["~FORM_HEADER"] .= implode(PHP_EOL, [
			"<input type='hidden' name='templateName' value='{$this->getTemplateName()}'>",
			"<input type='hidden' name='signedParameters' value='{$arParams["SIGNED_PARAMS"]}'>",
			"<input type='hidden' name='formAction' value='{$arResult["FORM_ACTION"]}'>",
		]);

		if (is_array($arResult["FORM_ERRORS"]) && !empty($arResult["FORM_ERRORS"])) {
			foreach ($arResult["FORM_ERRORS"] as $key => $value) {
				if (isset($arResult["QUESTIONS"][$key])) {
					$arResult["FORM_MESSAGES"][] = [
						"type" => "error",
						"text" => "Не заполнено поле «" . $arResult["QUESTIONS"][$key]["CAPTION"] . "»",
					];
					$arResult["FORM_ERROR_FIELDS"][] = $key;
				} else {
					$arResult["FORM_MESSAGES"][] = [
						"type" => "error",
						"text" => $value,
					];
				}
			}
		}

		if ($arResult["isFormAddOk"] == "Y") {
			$arResult["FORM_MESSAGES"][] = [
				"type" => "ok",
				"text" => "Спасибо, заявка успешно принята",
			];
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
		$this->includeFormResultNewComponent();
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
		if (empty($this->arParams["WEB_FORM_ID"])) {
			throw new Exception("Не указан ID формы");
		}
	}

	protected function includeAssets()
	{
		if ($this->arParams["DISABLE_COMPONENT_JS"] === 'Y'
			&& file_exists(Application::getDocumentRoot() . SITE_TEMPLATE_PATH . "/assets-prog/src/js/form.js")) {
			$jsPath = SITE_TEMPLATE_PATH . "/assets-prog/src/js/form.js";
		} else {
			$jsPath = "/bitrix/js/uplab.core/form.js";
		}

		if (!file_exists(Application::getDocumentRoot() . $jsPath)) {
			$jsPath = "/local/modules/{$this->MODULE_ID}/install/assets/dist/js/form.js";
		}

		Asset::getInstance()->addJs($jsPath);

		/**
		 * В параметр компонента "JS_TRIGGER_EVENTS" можно передать список ивентов, которые будут триггериться
		 * после отправки формы.
		 * Но предпочтительно - заворачивать евенты в $(window).on('init.someEvent'), тогда они будут вызваны
		 * вместе с вызовом $(window).trigger('init')
		 */
		if ($this->arParams["JS_TRIGGER_EVENTS"]) {
			Asset::getInstance()->addString("<script>BX.message(" . Json::encode([
					"uplab.form:triggerEvents" => $this->arParams["JS_TRIGGER_EVENTS"],
				]) . ")</script>");
		}
	}
}
