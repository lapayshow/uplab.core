<?php

namespace Uplab\Core\Orm;


use Bitrix\Main\UI\PageNavigation as BxPageNavigation;
use Uplab\Core\Helper;
use Uplab\Core\Uri;

/*
// Пример использования класса

$nav = \Uplab\Core\Orm\PageNavigation::init("PAGER")
->allowAllRecords(false)
->setPageSize(self::PAGE_ELEMENT_COUNT);

$nav->initFromUri();

$result = EntityTable::getList([
"offset" => $nav->getOffset(),
"limit"  => $nav->getLimit(),

"filter" => $this->arParams["REQUEST_FILTER"],
"select" => ["*", "COUNT"],
"order"  => $this->arParams["SORT"],
]);

$this->arResult = [
"FILTER" => $this->arParams["REQUEST_FILTER"],
"ITEMS"  => $result->fetchAll(),
];

$nav->setRecordCount($result->getCount());

$this->arResult["PAGER"] = $nav->getArray();
*/


/**
 * Класс-обертка над стнадратрной пагинацией в D7, помогает получить массив с нужными сслылками
 * без вызова компонента и сложной логики
 *
 * TODO: Добавить "Показать все"
 * TODO: Реализовать обратную пагинацию
 *
 * Class PageNavigation
 *
 * @property Uri uriObject
 */
class PageNavigation extends BxPageNavigation
{
	/** @var int */
	private $signsCount = 7;

	/** @var bool */
	private $isDomainInUrl = false;

	/** @var int */
	private $pagerItemsCount;

	/** @var int */
	private $pageCount;
	/** @var int */
	private $leftMargin;
	/** @var int */
	private $rightMargin;

	/** @var Uri */
	private $uriObject;
	/** @var array */
	private $urlParamsWhiteList;
	/** @var array */
	private $urlParamsToRemove;
	/** @var bool */
	private $urlParamsFiltered;
	/** @var string */
	private $baseUrl;
	/** @var string */
	private $urlTemplate;
	/** @var bool */
	private $isSefMode;

	/**
	 * Инициализация для статического вызова
	 *
	 * @param string $id
	 *
	 * @return PageNavigation
	 */
	public static function init($id)
	{
		return new static($id);
	}

	/**
	 * @return array Массив элементов ("LIST","PREV","NEXT","CUR")
	 */
	public function getArray()
	{
		$this->initParameters();

		$pagesList = [];
		$resultArray = [];
		$prevItem = null;
		$nextItem = null;

		if ($this->pageCount >= 2) {

			if ($this->pageCount <= $this->signsCount) {

				// Если помещаются все страницы сразу
				for ($i = 1; $i <= $this->pageCount; $i++) {
					$pagesList[] = $this->getPageArr($i);
				}

			} else {
				// Расставляем соседние значения
				// left
				for ($i = $this->leftMargin; $i > 0; $i--) {
					$pagesList[] = $this->getPageArr($this->currentPage - $i);
				}

				// current
				$pagesList[] = $this->getPageArr($this->currentPage);

				// right
				for ($i = 1; $i <= $this->rightMargin; $i++) {
					$pagesList[] = $this->getPageArr($this->currentPage + $i);
				}

				$pagesKeys = array_keys($pagesList);
				$pagesKeysRev = array_reverse($pagesKeys);

				// Расставляем пограничные значения и точки
				// left
				$pagesList[$pagesKeys[0]] = $this->getPageArr(1);

				if ($pagesList[$pagesKeys[2]]["NUM"] - $pagesList[$pagesKeys[0]]["NUM"] > 2) {


					$pagesList[$pagesKeys[1]] = "DOT";


				}

				// right
				$pagesList[$pagesKeysRev[0]] = $this->getPageArr($this->pageCount);

				if (
					$pagesList[$pagesKeysRev[0]]["NUM"] -
					$pagesList[$pagesKeysRev[2]]["NUM"] > 2
				) {

					$pagesList[$pagesKeysRev[1]] = "DOT";

				}

				$pagesCount = count($pagesList);
				for ($i = 0; $i <= $pagesCount; $i++) {
					if ($pagesList[$i] == 'DOT') {
						$prew = $pagesList[$i - 1]['NUM'];
						$next = $pagesList[$i + 1]['NUM'];

						$current = ($next - $prew) / 2;
						$current = ceil($current);

						$current = $this->getPageArr($current + $prew);
						$current['DOT'] = true;
						$pagesList[$i] = $current;
					} else {
						$pagesList[$i]['DOT'] = false;
					}
				}
			}

			// Получаем соседние значения
			if ($this->currentPage - 1 > 0) {
				$prevItem = $this->getPageArr($this->currentPage - 1);
			}

			if ($this->currentPage + 1 <= $this->pageCount) {
				$nextItem = $this->getPageArr($this->currentPage + 1);
			}

		}

		$resultArray["LIST"] = $pagesList;
		$resultArray["PREV"] = $prevItem;
		$resultArray["NEXT"] = $nextItem;
		$resultArray["CURR"] = $this->getPageArr($this->currentPage);
		$resultArray["COUNT"] = $this->getRecordCount();

		return $resultArray;
	}

	/**
	 * @param bool $isDomainInUrl
	 *
	 * @return PageNavigation
	 */
	public function setIsDomainInUrl($isDomainInUrl)
	{
		$this->isDomainInUrl = $isDomainInUrl;

		return $this;
	}

	/**
	 * @param int $signsCount
	 *
	 * @return PageNavigation
	 */
	public function setSignsCount(int $signsCount): PageNavigation
	{
		$this->signsCount = $signsCount;

		return $this;
	}

	public function removeAllUrlParams()
	{
		// TODO: найти более изящное решение для того, чтобы удалить все параметры
		$this->urlParamsWhiteList = ["-1"];

		return $this;
	}

	public function setUrlParamsWhiteList($UrlParamsWhiteList)
	{
		$this->urlParamsWhiteList = (array)$UrlParamsWhiteList;

		return $this;
	}

	public function setUrlParamsToRemove($urlParamsToRemove)
	{
		$this->urlParamsToRemove = (array)$urlParamsToRemove;

		return $this;
	}

	public function setUrlParamsFiltered($urlParamsFiltered)
	{
		$this->urlParamsFiltered = $urlParamsFiltered !== false;

		return $this;
	}

	public function getUriObject()
	{
		$this->prepareUri();

		return $this->uriObject;
	}

	public function replaceUrlTemplate($page, $size = "")
	{
		return str_replace(["--page--", "--size--"], [$page, $size], $this->urlTemplate);
	}

	private function preparePagerItemsCount()
	{
		$signs = $this->signsCount - 1;
		$this->pagerItemsCount = round($signs / 2);
	}

	/**
	 * Плучаем отступы слева и справа
	 */
	private function prepareRightLeftMargins()
	{
		$left = $this->pagerItemsCount;
		$right = $this->pagerItemsCount;

		if (($this->currentPage - 1) <= ($left - 1)) {
			$right = ($left + $right) - ($this->currentPage - 1);
			$left = $this->currentPage - 1;
		}

		if (($this->pageCount - $this->currentPage) <= ($right - 1)) {
			$left = ($left + $right) - ($this->pageCount - $this->currentPage);
			$right = $this->pageCount - $this->currentPage;
		}

		$this->leftMargin = $left;
		$this->rightMargin = $right;
	}

	private function getPageArr($targetPage)
	{
		$isActive = $targetPage == $this->currentPage;

		if ($targetPage <= $this->pageCount && $targetPage > 0) {

			if ($targetPage < $this->pageCount) {

				$elementsCount = $this->pageSize;

			} else {

				$elementsCount = (int)$this->recordCount - (($targetPage - 1) * $this->pageSize);

			}

			$item = [
				"NUM"       => $targetPage,
				"LENGTH"    => $elementsCount,
				"PAGER_KEY" => $this->id,
			];

			if ($isActive) {

				$item["IS_ACTIVE"] = "Y";

			} else {

				if ($targetPage == 1) {
					$url = $this->baseUrl;
				} else {
					$url = $this->replaceUrlTemplate($targetPage, $this->pageSize);
				}

				if ($this->isDomainInUrl) {
					$url = Helper::makeDomainUrl($url);
				}

				$item["LINK"] = $url;
			}

			return $item;

		} else {

			return false;

		}
	}

	private function initParameters()
	{
		$this->preparePagerItemsCount();
		$this->pageCount = $this->getPageCount();
		$this->currentPage = (int)$this->currentPage ?: 1;

		// Плучаем отступы слева и справа
		$this->prepareRightLeftMargins();
		$this->prepareUri();
	}

	private function prepareUri()
	{
		$removeParams = array_merge(
			[$this->id],
			(array)$this->urlParamsToRemove
		);

		$this->uriObject = Uri::initWithRequestUri()
			->deleteSystemParams()
			->deleteParams($removeParams)
			->filterParams($this->urlParamsFiltered !== false)
			->whiteListParams((array)$this->urlParamsWhiteList);

		$this->baseUrl = $this->uriObject
			->getUri(false);

		$uri = clone $this->uriObject;

		$this->urlTemplate = $this->addParams(
			$uri,
			$this->isSefMode,
			"--page--",
			(count($this->pageSizes) > 1 ? "--size--" : null)
		)->getUri();
	}

}