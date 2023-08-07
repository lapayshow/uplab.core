<?php

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Uplab\Core\Uri;


class UplabCoreSubscribeAddComponent extends CBitrixComponent implements Controllerable
{
	/**
	 * @var ErrorCollection
	 */
	protected $errors;

	/**
	 * @var array
	 */
	protected $modules = ["subscribe", "uplab.core"];

	/**
	 * @throws LoaderException
	 */
	protected function loadModules()
	{
		foreach ($this->modules as $module) {
			if (!Loader::includeModule($module)) {
				throw new LoaderException(
					Loc::getMessage("SUBSCRIBE_ADD_MODULE_NOT_FOUND") . " " . $module
				);
			}
		}
	}

	protected function initParams()
	{

	}

	protected function checkRequiredParams()
	{

	}

	protected function prepareResult()
	{
		$this->arResult["RUBRICS"] = $this->prepareRubrics();
		$this->arResult["ACTION"] = Uri::init("/bitrix/services/main/ajax.php")
			->addParams(
				[
					"mode"   => "class",
					"c"      => "uplab.core:subscribe.add",
					"action" => "subscribe",
				]
			)
			->getUri();
	}

	public function executeComponent()
	{
		$this->errors = new ErrorCollection();
		try {
			$this->loadModules();
			$this->initParams();
			$this->checkRequiredParams();
			$this->prepareResult();
			$this->includeComponentTemplate();
		} catch (LoaderException $e) {
			$this->errors->setError(new Error($e->getMessage()));

			ShowError($e->getMessage());
		}

		// TODO: $this->errors
	}

	/**
	 * @return array
	 */
	public function prepareRubrics()
	{
		$request = \Bitrix\Main\Context::getCurrent()->getRequest();

		$filter = [
			"ACTIVE" => "Y",
			"LID"    => ($v = (array)$request->get("site_id")) ? $v : SITE_ID,
		];

		if (!empty($this->arParams["RUBRIC_ID"])) {
			$filter["=ID"] = $this->arParams["RUBRIC_ID"];
		} elseif (!empty($this->arParams["RUBRIC_CODE"])) {
			$filter["=CODE"] = $this->arParams["RUBRIC_CODE"];
		} elseif (!empty($request->get("rubrics"))) {
			$filter["=ID"] = $request->get("rubrics");
		}

		$rubricList = Uplab\Core\Entities\ListRubric\ListRubricTable::getList([
				"order"  => array(
					"SORT" => "ASC",
					"ID"   => "ASC",
				),
				"filter" => $filter,
				"cache"  => ["ttl" => 36000],
			]
		);

		$rubrics = [];
		while ($rubric = $rubricList->fetch()) {
			$rubric["~NAME"] = htmlspecialchars_decode($rubric["NAME"]);
			$rubrics[$rubric["ID"]] = $rubric;
		}

		return $rubrics;
	}

	/**
	 * @return array
	 */
	public function configureActions()
	{
		return [
			"subscribe" => [
				"prefilters"  =>
					[
						new HttpMethod(
							array(
								HttpMethod::METHOD_POST,
							)
						),
						new Csrf(),
					],
				"postfilters" => [],
			],
		];
	}

	/**
	 * @param $email
	 *
	 * @return array
	 * @throws LoaderException
	 * @throws Exception
	 * @noinspection PhpUnused
	 */
	public function subscribeAction($email)
	{
		$this->loadModules();

		if (empty($email)) {
			throw new Exception(Loc::getMessage("UPLAB_CORE_SUBSCRIBE_ADD_ERROR_WRONG_DATA"));
		}

		$subscription = CSubscription::GetByEmail($email)->Fetch();

		if (empty($this->arResult["RUBRICS"])) {
			$this->arResult["RUBRICS"] = $this->prepareRubrics();
		}

		$fields = [
			"USER_ID"   => $GLOBALS["USER"]->GetID(),
			"FORMAT"    => "html",
			"EMAIL"     => $email,
			"ACTIVE"    => "Y",
			"CONFIRMED" => "Y",
			"RUB_ID"    => array_keys($this->arResult["RUBRICS"]),
			"ALL_SITES" => "Y",
		];

		if (!$subscription) {
			$obSubscription = new CSubscription;
			if ($id = $obSubscription->Add($fields)) {
				return ["subscription_id" => $id];
			}

			throw new Exception(Loc::getMessage("UPLAB_CORE_SUBSCRIBE_ADD_ERROR_OTHER"));
		} else {
			$subscriptionExist = CSubscription::GetList(
				[],
				[
					"USER_ID" => $GLOBALS["USER"]->GetID(),
					"EMAIL"   => $email,
				],
				[]
			)->Fetch();

			if (!empty($subscriptionExist["ID"])) {

				$fields["RUB_ID"] = array_merge(
					$fields["RUB_ID"],
					// $this->arResult["RUBRICS"],
					array_map("intval", CSubscription::GetRubricArray($subscriptionExist["ID"]))
				);

				$subscr = new CSubscription();
				if (!($subscr->Update($subscriptionExist["ID"], $fields))) {
					throw new Exception($subscr->LAST_ERROR);
				} else {
					return ["subscription_id" => $subscriptionExist["ID"]];
				}

			}
		}

		throw new Exception(Loc::getMessage("UPLAB_CORE_SUBSCRIBE_ADD_ERROR_EMAIL_ALREADY_EXISTS"));
	}
}
