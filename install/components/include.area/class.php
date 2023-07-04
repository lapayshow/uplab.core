<?
defined('B_PROLOG_INCLUDED') && B_PROLOG_INCLUDED === true || die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ObjectNotFoundException;
use Uplab\Core\Entities\Content\ContentTable;
use Uplab\Core\Helper;
use Uplab\Core\Legacy\Users;


class IncludeAreaComponent extends CBitrixComponent
{
	/**
	 * кешируемые ключи arResult
	 *
	 * @var array()
	 */
	protected $cacheKeys = [
		'AREA_EXISTS',
		'AREA_ID',
		'AREA_CODE',
		'PATH',
		'CONTENT',
		'CONTENT_TYPES',
	];

	/**
	 * дополнительные параметры, от которых должен зависеть кеш
	 *
	 * @var array
	 */
	protected $cacheAddon = [];

	/**
	 * модули, которые необходимо подключить
	 *
	 * @var array
	 */
	protected $dependModules = ['highloadblock', Helper::MODULE_ID];

	/**
	 * параметры, которые необходимо проверить
	 *
	 * @var array
	 */
	protected $requiredParams = [
		'int'   => [],
		'isset' => ['CODE'],
	];

	/**
	 * внешний фильтр
	 *
	 * @var array
	 */
	protected $arFilter = array();

	/**
	 * возвращает типы полей поддерживавемые включаемой областью
	 *
	 * @return array
	 */
	public function getAllowedFieldsType()
	{
		return [
			'string'   => Loc::getMessage('UP_CORE_INCLUDE_AREA_FIELDS_TYPE_STRING'),
			'text'     => Loc::getMessage('UP_CORE_INCLUDE_AREA_FIELDS_TYPE_TEXT'),
			'checkbox' => Loc::getMessage('UP_CORE_INCLUDE_AREA_FIELDS_TYPE_CHECKBOX'),
		];
	}

	/**
	 * подключает языковые файлы
	 */
	public function onIncludeComponentLang()
	{
		$this->includeComponentLang(basename(__FILE__));
		Loc::loadMessages(__FILE__);
	}

	/**
	 * подготавливает входные параметры
	 *
	 * @param array $params
	 *
	 * @return array
	 * @throws ObjectNotFoundException
	 */
	public function onPrepareComponentParams($params)
	{
		$bDesignMode =
			$GLOBALS['APPLICATION']->GetShowIncludeAreas() &&
			is_object($GLOBALS['USER']) &&
			($GLOBALS['USER']->IsAdmin()
				|| ContentTable::checkRights()
			);

		if (!isset($params['CACHE_TIME']) && !$bDesignMode) {
			if (defined('CACHE_TIME')) {
				$params['CACHE_TIME'] = CACHE_TIME;
			} else {
				$params['CACHE_TIME'] = 3600;
			}
		}

		foreach ($this->requiredParams as $key => $requiredRow) {
			$this->requiredParams[$key] = array_merge(
				(array)$this->requiredParams[$key],
				(array)$params['REQUIRED_' . strtoupper($key) . '_PARAMS']
			);
		}

		/*$useFields = ($params['USE_FIELDS'] == 'Y' ? 'Y' : 'N');
		$arFields = [];
		if ($useFields) {
			foreach ($this->getAllowedFieldsType() as $fieldType => $typeLabel) {
				if (!empty($params['FIELDS_' . $fieldType . '_NAME'])) {
					foreach ($params['FIELDS_' . $fieldType . '_NAME'] as $key => $name) {
						$name = trim($name);
						$code = trim($params['FIELDS_' . $fieldType . '_' . $key . '_CODE']) ?: $key;
						if (!empty($name)) {
							$arFields[$code] = [
								'NAME' => $name,
								'CODE' => $code,
								'TYPE' => $fieldType,
							];
						}

					}
				}
			}
			if (empty($arFields)) {
				$useFields = false;
			}
		}*/

		$result = array(
			'DESIGN_MODE'  => $bDesignMode,
			'CODE'         => ToUpper(trim($params['CODE'])),
			'PATH'         => !empty($params['PATH']) ? $params['PATH'] : $GLOBALS['APPLICATION']->GetCurDir(),
			'USE_FILTER'   => (!empty($this->arFilter) ? 'Y' : 'N'),
			// 'USE_FIELDS'   => ($useFields ? 'Y' : 'N'),
			// 'FIELDS'       => ($useFields ? $arFields : []),
			'CACHE_GROUPS' => ($params['CACHE_GROUPS'] == 'Y' ? 'Y' : 'N'),
		);

		return array_merge($params, $result);
	}

	/**
	 * выполняет логику работы компонента
	 */
	public function executeComponent()
	{
		try {
			$this->checkModules();
			$this->checkParams();
			$this->executeProlog();
			if (!$this->readDataFromCache()) {
				$this->getResult();
				if (defined('BX_COMP_MANAGED_CACHE')) {
					global $CACHE_MANAGER;
					$CACHE_MANAGER->RegisterTag('ci_content');
				}
				$this->putDataToCache();
				$this->includeComponentTemplate();
			}
			$this->executeEpilog();

			return array('CONTENT' => $this->arResult['CONTENT']);
		} catch (Exception $e) {
			$this->abortDataCache();
			ShowError($e->getMessage());
		}

		return true;
	}

	/**
	 * проверяет подключение необходимых модулей
	 *
	 * @throws Main\LoaderException
	 */
	protected function checkModules()
	{
		foreach ($this->dependModules as $module) {
			if (!Main\Loader::includeModule($module)) {
				throw new Main\LoaderException(
					Loc::getMessage('UP_CORE_INCLUDE_AREA_MODULE_NOT_FOUND') . ' ' . $module
				);
			}
		}
	}

	/**
	 * проверяет заполнение обязательных параметров
	 *
	 * @throws Main\ArgumentNullException
	 */
	protected function checkParams()
	{
		foreach ($this->requiredParams['int'] as $param) {
			if (intval($this->arParams[$param]) <= 0) {
				throw new Main\ArgumentNullException($param);
			}
		}
		foreach ($this->requiredParams['isset'] as $param) {
			if (!isset($this->arParams[$param]) && !empty($this->arParams[$param])) {
				throw new Main\ArgumentNullException($param);
			}
		}
	}

	/**
	 * выполяет действия перед кешированием
	 */
	protected function executeProlog()
	{

	}

	/**
	 * определяет читать данные из кеша или нет
	 *
	 * @return bool
	 */
	protected function readDataFromCache()
	{
		if ($this->arParams['CACHE_TYPE'] == 'N') {
			return false;
		}
		if ($this->arParams['CACHE_FILTER'] == 'Y') {
			$this->cacheAddon[] = $this->arFilter;
		}
		if ($this->arParams['CACHE_GROUPS'] == 'Y' && is_object($GLOBALS['USER'])) {
			$this->cacheAddon[] = $GLOBALS['USER']->GetUserGroupArray();
		}
		$this->cacheAddon[] = $this->arParams['PATH'];
		$this->cacheAddon[] = $this->arParams['CODE'];

		return !($this->StartResultCache(false, $this->cacheAddon));
	}

	/**
	 * получение результатов
	 */
	protected function getResult()
	{
		$this->arResult = array(
			'AREA_ID'       => false,
			'AREA_CODE'     => $this->arParams['CODE'],
			'AREA_EXISTS'   => 'N',
			'PATH'          => '/',
			'CONTENT'       => array(),
			'CONTENT_TYPES' => array(),
		);

		// текущий путь страницы
		$this->arResult['PATH'] = $this->arParams['PATH'];
		$arPath = array_filter(explode('/', $this->arResult['PATH']));

		// фильтр по умолчанию
		$arFilter = array('UF_CODE' => $this->arParams['CODE'], 'UF_SITE_ID' => SITE_ID);

		// внешний фильтр
		if ($this->arParams['USE_FILTER'] == 'Y' && !empty($this->arFilter) && is_array($this->arFilter)) {
			$arFilter = array_merge($arFilter, $this->arFilter);
		}

		// дополняем фильтр - логика определения пути наследования контента
		$arPathFilter = array(
			'LOGIC' => 'OR',
			array(
				'UF_PATH' => $this->arResult['PATH'] // точное соответствие пути
			),
			array(
				'UF_PATH'          => '/', // наследование от корня сайта
				'UF_SPREAD_DEEPER' => 1 // указываем что ищем наследуемый контент
			),
		);
		array_pop($arPath); // удаляем последний путь т.к. фильтр для него по факту будет аналогичен вышезаданному
		if (count($arPath) > 0) {
			$fullPath = '/';
			foreach ($arPath as $path) {
				$fullPath .= $path . '/';
				$arPathFilter[] = array(
					'UF_PATH'          => $fullPath, // наследование от текущей директории по уровню
					'UF_SPREAD_DEEPER' => 1 // указываем что ищем наследуемый контент
				);
			}
		}
		$arFilter[] = $arPathFilter;
		unset($arPathFilter);

		// информация по сущности
		$obContent = new ContentTable();
		$this->arResult['CONTENT_TYPES'] = $obContent->getContentTypes();

		// получение контента
		$arContents = $obContent->getListEx(array(
			'order'  => array('UF_SORT' => 'ASC', 'ID' => 'DESC'),
			'filter' => $arFilter,
			'select' => array(
				'ID',
				'UF_PATH',
				'UF_CODE',
				'UF_TYPE',
				'UF_DATA',
				'UF_SPREAD_DEEPER',
				'UF_SITE_ID',
				'UF_SORT',
			),
			'limit'  => 1,
		));

		if (is_array($arContents) && !empty($arContents)) {
			$this->arResult['AREA_EXISTS'] = 'Y';
			$arContent = array_shift($arContents);
			unset($arContents);
			$this->arResult['AREA_ID'] = $arContent['ID'];
			$this->arResult['AREA_CODE'] = $arContent['UF_CODE'];
			$this->arResult['CONTENT'] = array(
				'TYPE'          => $arContent['CONTENT_TYPE'], // виртуальное поле на основе значения UF_TYPE
				'SPREAD_DEEPER' => $arContent['UF_SPREAD_DEEPER'],
				'PATH'          => $arContent['UF_PATH'],
				'DATA'          => $arContent['UF_DATA'],
			);
			unset($arContent);
		}
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
	 * формирование набора кнопок для эрмитажа в режиме правки
	 */
	protected function showEditButtons()
	{
		if (!$this->arParams['DESIGN_MODE']) return;

		CJSCore::Init(array('bx', 'ajax', 'window', 'popup'));

		global $APPLICATION;

		$componentPath = $this->getPath();
		$editor = '&SITE_ID=' . SITE_ID . '&back_url=' . urlencode($_SERVER['REQUEST_URI']) . '&templateID=' . urlencode(SITE_TEMPLATE_ID);

		$salt = ContentTable::getSalt();

		$signer = new Main\Security\Sign\Signer;
		$arParams = $this->arParams;
		foreach ($arParams as $key => $param) {
			if (strpos($key, '~') === 0) {
				unset($arParams[$key]);
			}
		}
		$signedParams = '';
		try {
			$signedParams = $signer->sign(base64_encode(serialize($arParams)), 'include.area' . $salt);
		} catch (Main\ArgumentTypeException $e) {
		}
		$arPopupParams = array(
			'URL'    => '',
			'PARAMS' => array(
				'width'      => 780,
				'height'     => 400,
				'resizable'  => true,
				'min_width'  => 780,
				'min_height' => 100,
			),
			'POST'   => array(
				'signedParamsString' => $signedParams,
				'sessid'             => bitrix_sessid(),
			),
		);
		$arButtons = array();
		if ($this->arResult['AREA_EXISTS'] && $this->arResult['AREA_EXISTS'] == 'Y') {
			$arPopupParams['URL'] = $componentPath . '/ajax.php?' . $this->arParams['CODE'] . '_ACTION=edit' . $editor;
			$arButtons[] = array(
				'URL'   => 'javascript:' . $APPLICATION->GetPopupLink($arPopupParams),
				'ICON'  => 'bx-context-toolbar-edit-icon',
				'SRC'   => '',
				'TITLE' => Loc::getMessage('UP_CORE_INCLUDE_AREA_MENU_EDIT'),
			);

			$arPopupParams['URL'] = $componentPath . '/ajax.php?' . $this->arParams['CODE'] . '_ACTION=delete' . $editor;
			$arPopupParams['PARAMS']['width'] = 400;
			$arPopupParams['PARAMS']['height'] = 100;
			$arPopupParams['PARAMS']['min_height'] = 100;
			$arPopupParams['PARAMS']['resizable'] = false;
			$arButtons[] = array(
				'URL'   => 'javascript:' . $APPLICATION->GetPopupLink($arPopupParams),
				'ICON'  => 'bx-context-toolbar-delete-icon',
				'SRC'   => '',
				'TITLE' => Loc::getMessage('UP_CORE_INCLUDE_AREA_MENU_DELETE'),
			);
		} else {
			$arPopupParams['URL'] = $componentPath . '/ajax.php?' . $this->arParams['CODE'] . '_ACTION=add' . $editor;
			$arButtons[] = array(
				'URL'   => 'javascript:' . $APPLICATION->GetPopupLink($arPopupParams),
				'ICON'  => 'bx-context-toolbar-create-icon',
				'SRC'   => '',
				'TITLE' => Loc::getMessage('UP_CORE_INCLUDE_AREA_MENU_ADD'),
			);
		}

		if (!empty($arButtons)) {
			$this->AddIncludeAreaIcons(
				$arButtons
			);
		}
	}

	/**
	 * выполняет действия после выполения компонента, например установка заголовков из кеша
	 */
	protected function executeEpilog()
	{
		$this->showEditButtons();
	}

	/**
	 * прерывает кеширование
	 */
	protected function abortDataCache()
	{
		$this->abortResultCache();
	}
}
