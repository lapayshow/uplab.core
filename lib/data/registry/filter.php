<?php

namespace Uplab\Core\Data\Registry;

use Uplab\Core\IblockHelper;
use Uplab\Core\Traits\RegistryTrait;


/**
 * Class Registry
 * Используется как хранилище параметров фильтра для передачи в компоненты
 *
 * @package Uplab\Core\Data
 */
class Filter extends Registry
{
	public function setNotEmptyPicture()
	{
		return $this->set(
			null,
			[
				"LOGIC" => "OR",
				["!PREVIEW_PICTURE" => false],
				["!DETAIL_PICTURE" => false],
			]
		);
	}

	public function setYear($year)
	{
		if ($year = intval($year)) {
			$filterJanuary1st = ConvertTimeStamp(mktime(0, 0, 0, 1, 1, $year));
			$filterDecember31st = ConvertTimeStamp(mktime(0, 0, 0, 12, 31, $year));
			$this->set(">=DATE_ACTIVE_FROM", $filterJanuary1st);
			$this->set("<=DATE_ACTIVE_FROM", $filterDecember31st);
		}

		return $this;
	}

	public function setSectionId($sectionId, $includeSubsections = true)
	{
		if ($sectionId = intval($sectionId)) {
			$this->set("SECTION_ID", $sectionId);
		}

		if ($includeSubsections) {
			$this->set("INCLUDE_SUBSECTIONS", "Y");
		}

		return $this;
	}

	public function setSectionCode($sectionCode, $includeSubsections = true)
	{
		if ($sectionCode = htmlspecialchars($sectionCode)) {
			$this->set("SECTION_CODE", $sectionCode);

			if ($includeSubsections) {
				$this->set("INCLUDE_SUBSECTIONS", "Y");
			}
		}

		return $this;
	}

	/**
	 * Получить фильтр для подразделов раздела
	 *
	 * @param array $params
	 * @param bool  $cache
	 *
	 * @return $this
	 * @throws \Bitrix\Main\LoaderException
	 */
	public function setSectionSubsections($params = [], $cache = true)
	{
		$params = $params ?? [];

		$params["select"] = array_unique(
			array_merge(
				(array)($params["select"] ?? []),
				[
					"LEFT_MARGIN",
					"RIGHT_MARGIN",
					"DEPTH_LEVEL",
				]
			)
		);

		$rootSect = $params["rootSection"] ?? [];

		if (
			!$rootSect ||
			!isset($rootSect["LEFT_MARGIN"]) ||
			!isset($rootSect["RIGHT_MARGIN"]) ||
			!isset($rootSect["DEPTH_LEVEL"])
		) {
			$rootParams = $params;
			$rootParams["limit"] = 1;

			$rootSect = IblockHelper::getSectionsList($rootParams, $cache);
		}

		if ($rootSect) {
			$this->set("IBLOCK_ID", $rootSect["IBLOCK_ID"]);
			$this->set(">LEFT_MARGIN", $rootSect["LEFT_MARGIN"]);
			$this->set("<RIGHT_MARGIN", $rootSect["RIGHT_MARGIN"]);
			$this->set("DEPTH_LEVEL", $rootSect["DEPTH_LEVEL"] + 1);
			$this->set("!ID", $rootSect["ID"]);
		}

		return $this;
	}
}
