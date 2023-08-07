<?php

namespace Uplab\Core\Data;


use CIBlockResult;
use Uplab\Core\Helper;
use Uplab\Core\Uri;


/**
 * TODO: перенести в компонент
 *
 * Class Pagination
 *
 * Пример использования: 
 * $arPager = \Uplab\Core\Data::init($arResult["NAV_RESULT"])->getPagerArray();
 *
 * @property Uri uriObject
 */
class Pagination
{
	private $navParams;
	private $navObject;
	private $uriObject;
	private $signs;

	private $defaultUrl;
	private $currentPage;
	private $pagesCount;
	private $navNum;
	private $paginationSize;
	private $paginationKey;
	private $isDomainInUrl = false;

	// private $hasPagination = true;

	/**
	 * Генерирует массив, содержащий элементы постраничной навигации
	 *
	 * @param CIBlockResult $navObject Объект постраничной навигации CDBResult
	 * @param array $navParams Массив с параметрами
	 * @param int $signs Размерность постраничной навигации, включая текущую страницу и "..."
	 */
	function __construct($navObject = null, $navParams = null, $signs = 7)
	{
		/**
		 * @var int $cur Номер текущей страницы
		 * @var int $count Колличество страниц
		 * @var int $num Номер постраничной навигации
		 * *
		 * @var bool $isFilterParams Убирать или нет пустые ключи из ссылки
		 * @var array $removeParams GET-параметры, которые следует удалить из ссылки
		 * @var array $paramsWhiteList Белый список GET-параметров, которые следует оставить в ссылке
		 */

		$this->navObject = $navObject;
		$this->navParams = (array)$navParams;
		$this->signs = $signs;

		if (is_object($this->navObject)) {
			$this->currentPage = $navObject->NavPageNomer;
			$this->pagesCount = $navObject->NavPageCount;
			$this->navNum = $navObject->NavNum;
		} elseif (!empty($this->navParams)) {
			$this->currentPage = $navParams["NavPageNomer"];
			$this->pagesCount = $navParams["NavPageCount"];
			$this->navNum = $navParams["NavNum"];
		} else {
			return;
		}

		// Получаем размерность слева и справа из общей размерности
		$this->paginationSize = $this->getDimension($signs);
	}

	/**
	 *
	 * Инициализация для статического вызова
	 *
	 * @param null $navObject
	 * @param null $navParams
	 * @param int $signs
	 *
	 * @return bool|Pagination
	 */
	public static function init($navObject = null, $navParams = null, $signs = 7)
	{
		if ($navObject) {
			return new static($navObject, false, $signs);
		} elseif ($navParams) {
			return new static(false, $navParams, $signs);
		} else {
			return false;
		}
	}

	/**
	 *
	 * Инициализация навигационным объектом для статического вызова
	 *
	 * @param $navObject
	 *
	 * @return Pagination
	 */
	public static function initWithObject($navObject)
	{
		return new static($navObject);
	}

	/**
	 *
	 * Возвращает массив элементов ("LIST","PREV","NEXT","CUR")
	 *
	 * @return array Массив элементов ("LIST","PREV","NEXT","CUR")
	 */
	public function getArray()
	{
		$this->prepareUri();
		//TODO: допилить линки многоточий
		// Плучаем отступы слева и справа
		$rest = $this->getRest([$this->paginationSize, $this->paginationSize]);
		$left = $rest["left"];
		$right = $rest["right"];

		$pagesList = [];
		$resultArray = [];
		$prevItem = null;
		$nextItem = null;

		if ($this->pagesCount >= 2) {
			if ($this->pagesCount <= $this->signs) {
				// Если помещаются все страницы сразу
				for ($i = 1; $i <= $this->pagesCount; $i++) {
					$pagesList[] = $this->getPageArr($i);
				}
			} else {
				// Расставляем соседние значения
				// left
				for ($i = $left; $i > 0; $i--) {
					$pagesList[] = $this->getPageArr($this->currentPage, -1 * $i);
				}

				// current
				$pagesList[] = $this->getPageArr($this->currentPage);

				// right
				for ($i = 1; $i <= $right; $i++) {
					$pagesList[] = $this->getPageArr($this->currentPage, $i);
				}

				// Расставляем пограничные значения и точки
				// left
				$pagesList[0] = $this->getPageArr(1);

				if ($pagesList[2]["NUM"] - $pagesList[0]["NUM"] > 2) {
					$pagesList[1] = "DOT";
				}

				// right
				$pagesList[count($pagesList) - 1] = $this->getPageArr($this->pagesCount);

				if ($pagesList[count($pagesList) - 1]["NUM"] - $pagesList[count($pagesList) - 3]["NUM"] > 2) {
					$pagesList[count($pagesList) - 2] = "DOT";
				}

				$pagesCount = count($pagesList) - 1;
				for ($i = 0; $i <= $pagesCount; $i++) {
					if ($pagesList[$i] == "DOT") {
						$prew = $pagesList[$i - 1]["NUM"];
						$next = $pagesList[$i + 1]["NUM"];

						$current = ($next - $prew) / 2;
						$current = ceil($current);
						$current = $this->getPageArr($current + $prew);

						// TODO: удалить параметр DOT, оставить только IS_DOT
						$current["DOT"] = true;
						$current["IS_DOT"] = true;

						$pagesList[$i] = $current;
					} else {
						// TODO: удалить параметр DOT, оставить только IS_DOT
						$pagesList[$i]["DOT"] = false;
						$pagesList[$i]["IS_DOT"] = false;
					}
				}

			}

			foreach ($pagesList as &$item) {
				if ($item["NUM"] == $this->currentPage) {
					$item["IS_ACTIVE"] = "Y";
					unset($item["LINK"]);
				}
			}

			// Получаем соседние значения
			if ($this->currentPage - 1 > 0) {
				$prevItem = $this->getPageArr($this->currentPage, -1);
			}

			if ($this->currentPage + 1 <= $this->pagesCount) {
				$nextItem = $this->getPageArr($this->currentPage, 1);
			}

		}

		$resultArray["LIST"] = $pagesList;
		$resultArray["PREV"] = $prevItem;
		$resultArray["NEXT"] = $nextItem;
		$resultArray["CURR"] = $this->getPageArr($this->currentPage);

		return $resultArray;
	}

	public function setParamsWhiteList($whiteList)
	{
		$this->navParams["paramsWhiteList"] = (array)$whiteList;

		return $this;
	}

	public function removeAllParams()
	{
		// TODO: найти более изящное решение для того, чтобы удалить все параметры
		$this->navParams["paramsWhiteList"] = ["1"];

		return $this;
	}

	public function setParamsToRemove($removeParams)
	{
		$this->navParams["removeParams"] = (array)$removeParams;

		return $this;
	}

	public function setParamsFilter($isFilterParams)
	{
		$this->navParams["isFilterParams"] = $isFilterParams !== false;

		return $this;
	}

	public function getUriObject()
	{
		$this->prepareUri();

		return $this->uriObject;
	}

	/**
	 * @param mixed $paginationKey
	 *
	 * @return Pagination
	 */
	public function setPaginationKey($paginationKey)
	{
		$this->paginationKey = $paginationKey;

		return $this;
	}

	/**
	 * @param bool $isDomainInUrl
	 *
	 * @return Pagination
	 */
	public function setIsDomainInUrl($isDomainInUrl)
	{
		$this->isDomainInUrl = $isDomainInUrl;

		return $this;
	}

	private function getDimension($signs)
	{
		$signs = $signs - 1;
		$paginationSize = round($signs / 2);

		return $paginationSize;
	}

	private function getRest($arr = array(3, 3))
	{
		$left = $arr[0];
		$right = $arr[1];

		if (($this->currentPage - 1) <= ($left - 1)) {
			$right = ($left + $right) - ($this->currentPage - 1);
			$left = $this->currentPage - 1;
		}

		if (($this->pagesCount - $this->currentPage) <= ($right - 1)) {
			$left = ($left + $right) - ($this->pagesCount - $this->currentPage);
			$right = $this->pagesCount - $this->currentPage;
		}

		return array(
			"left"  => $left,
			"right" => $right,
		);
	}

	private function getPageArr($navPageNomer, $offset = 0)
	{
		$targetPage = $navPageNomer + $offset;
		$elementsCount = null;

		if ($targetPage <= $this->pagesCount && $targetPage > 0) {
			if ($targetPage == 1) {
				$url = $this->defaultUrl;
			} else {
				$url = $this->uriObject->addParams([$this->paginationKey => $targetPage])->getUri();
			}

			if (is_object($this->navObject)) {
				if ($targetPage < $this->navObject->NavPageCount) {
					$elementsCount = $this->navObject->NavPageSize;
				} else {
					$elementsCount =
						(int)$this->navObject->NavRecordCount -
						(($targetPage - 1) * $this->navObject->NavPageSize);
				}
			}

			if ($this->isDomainInUrl) {
				$url = Helper::makeDomainUrl($url);
			}

			return [
				"NUM"      => $targetPage,
				"LINK"     => $url,
				"LENGTH"   => $elementsCount,
				"PAGE_KEY" => $this->paginationKey,
			];
		} else {
			return false;
		}
	}

	private function prepareUri()
	{
		$this->navParams = (array)$this->navParams;

		$isFilterParams = $this->navParams["isFilterParams"] !== false;
		$paramsWhiteList = (array)$this->navParams["paramsWhiteList"];
		$removeParams = (array)$this->navParams["removeParams"];

		if (empty($this->paginationKey)) {
			$this->paginationKey = "PAGEN_{$this->navNum}";
		}

		$removeParams = array_merge(
			[$this->paginationKey],
			(array)$removeParams
		);

		$this->uriObject = Uri::initWithRequestUri()
			->deleteParams($removeParams)
			->deleteSystemParams()
			->filterParams($isFilterParams)
			->whiteListParams($paramsWhiteList);
		$this->defaultUrl = $this->uriObject->getUri(false);
	}
}