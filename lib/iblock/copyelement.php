<?

namespace Uplab\Core\Iblock;


use CFile;
use CIBlock;
use CIBlockElement;
use CIBlockProperty;


class CopyElement
{
	private $actionType;
	private $copyFields;
	private $copyProps;
	private $copyElementId;
	private $destinationIblockID;
	private $destinationIblock;
	private $destinationSection;
	private $sourceItem;
	private $item;
	private $itemProperties;
	private $isCopyToAnotherIblock;
	private $fieldsValues;
	private $propertyValues;

	function __construct($copyElementId, $destinationIblock, $params = [])
	{
		$params = array_merge([
			"actionType"         => "copy", // copy | move
			"copyFields"         => null,   // поля, которые будут скопированы (по умолчанию - все)
			"copyProps"          => null,
			"destinationSection" => null,
			"fieldsValues"       => [],
			"propertyValues"     => [],
		], (array)$params);

		$this->actionType = $params["actionType"];

		$this->copyElementId = $copyElementId;
		$this->copyFields = $params["copyFields"];
		$this->copyProps = $params["copyProps"];
		$this->fieldsValues = $params["fieldsValues"];
		$this->propertyValues = $params["propertyValues"];

		$this->destinationSection = intval($params["destinationSection"]);
		$this->destinationIblockID = intval($destinationIblock);
	}

	public function copy()
	{
		global $APPLICATION;

		if (empty($this->copyFields)) {
			$this->copyFields = false;
		} else {
			$this->copyFields = (array)$this->copyFields;

			$this->copyFields[] = "ACTIVE";
			$this->copyFields[] = "ACTIVE_FROM";
			$this->copyFields[] = "ACTIVE_TO";
			$this->copyFields[] = "SORT";
			$this->copyFields[] = "NAME";

			$this->copyFields[] = "WF_STATUS_ID";
			$this->copyFields[] = "CODE";
			$this->copyFields[] = "TAGS";
			$this->copyFields[] = "XML_ID";

			// $this->copyFields[] = "PREVIEW_PICTURE";
			// $this->copyFields[] = "DETAIL_PICTURE";

			if (array_key_exists("PREVIEW_TEXT", $this->copyFields)) {
				$this->copyFields[] = "PREVIEW_TEXT_TYPE";
			}
			if (array_key_exists("DETAIL_TEXT", $this->copyFields)) {
				$this->copyFields[] = "DETAIL_TEXT_TYPE";
			}

			$this->copyFields = array_unique($this->copyFields);
		}

		$el = new CIBlockElement;
		$res = CIBlockElement::GetList(false, ["ID" => $this->copyElementId], false, false, $this->copyFields);

		if ($ob = $res->GetNextElement()) {
			$this->item = $ob->GetFields();

			if ($this->copyProps !== false) {
				$this->itemProperties = $ob->GetProperties(false, array("EMPTY" => "N"));
				if (is_array($this->copyProps)) {
					$this->itemProperties = array_intersect_key(
						$this->itemProperties,
						array_flip($this->copyProps)
					);
				}
			} else {
				$this->itemProperties = [];
			}

			$this->prepareIblock();
			$this->prepareCopyElementFields();

			$this->sourceItem = $this->item;
			// $sourceIblock = $this->sourceItem["IBLOCK_ID"];

			$this->item = array(
				"IBLOCK_ID"         => $this->destinationIblockID,
				"ACTIVE"            => $this->item["ACTIVE"],
				"ACTIVE_FROM"       => $this->item["ACTIVE_FROM"],
				"ACTIVE_TO"         => $this->item["ACTIVE_TO"],
				"SORT"              => $this->item["SORT"],
				"NAME"              => $this->item["~NAME"],
				"PREVIEW_PICTURE"   => $this->item["PREVIEW_PICTURE"],
				"PREVIEW_TEXT"      => $this->item["~PREVIEW_TEXT"],
				"PREVIEW_TEXT_TYPE" => $this->item["PREVIEW_TEXT_TYPE"],
				"DETAIL_TEXT"       => $this->item["~DETAIL_TEXT"],
				"DETAIL_TEXT_TYPE"  => $this->item["DETAIL_TEXT_TYPE"],
				"DETAIL_PICTURE"    => $this->item["DETAIL_PICTURE"],
				"WF_STATUS_ID"      => $this->item["WF_STATUS_ID"],
				"CODE"              => $this->item["~CODE"],
				"TAGS"              => $this->item["~TAGS"],
				"XML_ID"            => $this->item["~XML_ID"],
				"PROPERTY_VALUES"   => array(),
			);


			if ($this->actionType == "move" && $this->isCopyToAnotherIblock) {
				$this->item["CREATED_BY"] = $this->sourceItem["CREATED_BY"];
				$this->item["SHOW_COUNTER"] = $this->sourceItem["SHOW_COUNTER"];
			}


			if ($this->destinationIblock["CODE"]["IS_REQUIRED"] == "Y") {
				if (!strlen($this->item["CODE"])) {
					$this->item["CODE"] = mt_rand(100000, 1000000);
				}
			}


			if (!empty($this->fieldsValues)) {
				foreach ($this->fieldsValues as $key => $value) {
					$this->item[$key] = $value;
				}
			}


			if (!empty($this->propertyValues)) {
				foreach ($this->propertyValues as $key => $value) {
					$this->item["PROPERTY_VALUES"][$key] = $value;
				}
			}


			if ($this->destinationIblock["IS_UNIQUE_CODE"]) {

				// Флаг уникальности символьного кода: если пустое значение,
				// то нужно дополнить
				$this->item["CODE"] = (string)$this->item["CODE"];
				$makeElementCodeUnique = empty($this->item["CODE"]);

				if (!$makeElementCodeUnique) {
					$itemWithSameCode = Helper::getList([
						"iblock" => $this->destinationIblockID,
						"limit"  => 1,
						"filter" => [
							"=CODE"             => $this->item["CODE"],
							"CHECK_PERMISSIONS" => "N",
						],
						"select" => ["ID", "IBLOCK_ID"],
					], false);

					if (!empty($itemWithSameCode)) $makeElementCodeUnique = true;
				}

				if ($makeElementCodeUnique) {
					$this->item["CODE"] .= mt_rand(100, 10000);
				}

			}

			if ($this->destinationSection > 0) {
				$this->item["IBLOCK_SECTION_ID"] = $this->destinationSection;
			}

			if (!$this->isCopyToAnotherIblock) {

				$arSectionList = array();
				$rsSections = CIBlockElement::GetElementGroups($this->copyElementId, true);
				while ($arSection = $rsSections->Fetch()) {
					$arSectionList[] = $arSection["ID"];
				}
				$this->item["IBLOCK_SECTION"] = $arSectionList;

			}

			self::prepareCopyElementProperties();

			// $seoTemplates = CASDIblockElementTools::getSeoFieldTemplates($this->sourceItem["IBLOCK_ID"], $this->copyElementId);
			// if (!empty($seoTemplates)) {
			// 	$this->item['IPROPERTY_TEMPLATES'] = $seoTemplates;
			// }
			// unset($seoTemplates);

			$intNewID = $el->Add($this->item, true, true, true);

			// echo "<pre>";
			// var_export($this->item);
			// echo "</pre>";

			if (!$intNewID) {
				// echo "<pre>";
				// var_export([
				// 	$el->LAST_ERROR,
				// 	$this->destinationIblock,
				// ]);
				// echo "</pre>";

				$APPLICATION->ThrowException("[{$this->copyElementId}] {$el->LAST_ERROR}");
			}

			return $intNewID;
		}

		return false;
	}

	private function prepareCopyElementFields()
	{
		$this->item["PREVIEW_PICTURE"] = (int)$this->item["PREVIEW_PICTURE"];
		if ($this->item["PREVIEW_PICTURE"] > 0) {
			$this->item["PREVIEW_PICTURE"] = CFile::MakeFileArray($this->item["PREVIEW_PICTURE"]);
			if (empty($this->item["PREVIEW_PICTURE"])) {
				$this->item["PREVIEW_PICTURE"] = false;
			} else {
				$this->item["PREVIEW_PICTURE"]["COPY_FILE"] = "Y";
			}
		} else {
			$this->item["PREVIEW_PICTURE"] = false;
		}

		$this->item["DETAIL_PICTURE"] = (int)$this->item["DETAIL_PICTURE"];
		if ($this->item["DETAIL_PICTURE"] > 0) {
			$this->item["DETAIL_PICTURE"] = CFile::MakeFileArray($this->item["DETAIL_PICTURE"]);
			if (empty($this->item["DETAIL_PICTURE"])) {
				$this->item["DETAIL_PICTURE"] = false;
			} else {
				$this->item["DETAIL_PICTURE"]["COPY_FILE"] = "Y";
			}
		} else {
			$this->item["DETAIL_PICTURE"] = false;
		}
	}

	private function prepareCopyElementProperties()
	{
		$arOldPropListCache = [];
		$arOldNamePropListCache = [];
		$arNamePropListCache = [];
		$arPropListCache = [];

		if ($this->isCopyToAnotherIblock && empty($arPropListCache)) {
			$rsProps = CIBlockProperty::GetList([], [
				"IBLOCK_ID"     => $this->destinationIblockID,
				"PROPERTY_TYPE" => "L", "ACTIVE" => "Y", "CHECK_PERMISSIONS" => "N",
			]);
			while ($arProp = $rsProps->Fetch()) {
				$arValueList = array();
				$arNameList = array();
				$rsValues = CIBlockProperty::GetPropertyEnum($arProp['ID']);
				while ($arValue = $rsValues->Fetch()) {
					$arValueList[$arValue['XML_ID']] = $arValue['ID'];
					$arNameList[$arValue['ID']] = trim($arValue['VALUE']);
				}
				if (!empty($arValueList)) {
					$arPropListCache[$arProp['CODE']] = $arValueList;
				}
				if (!empty($arNameList)) {
					$arNamePropListCache[$arProp['CODE']] = $arNameList;
				}
			}
		}

		if (empty($arOldPropListCache)) {
			$rsProps = CIBlockProperty::GetList([], [
				'IBLOCK_ID'         => $this->sourceItem["IBLOCK_ID"],
				'PROPERTY_TYPE'     => 'L',
				'ACTIVE'            => 'Y',
				'CHECK_PERMISSIONS' => 'N',
			]);
			while ($arProp = $rsProps->Fetch()) {
				$arValueList = array();
				$arNameList = array();
				$rsValues = CIBlockProperty::GetPropertyEnum($arProp['ID']);
				while ($arValue = $rsValues->Fetch()) {
					$arValueList[$arValue['ID']] = $arValue['XML_ID'];
					$arNameList[$arValue['ID']] = trim($arValue['VALUE']);
				}
				if (!empty($arValueList)) {
					$arOldPropListCache[$arProp['CODE']] = $arValueList;
				}
				if (!empty($arNameList)) {
					$arOldNamePropListCache[$arProp['CODE']] = $arNameList;
				}
			}
		}

		foreach ($this->itemProperties as &$arProp) {
			if ($arProp['USER_TYPE'] == 'HTML') {
				if (is_array($arProp['~VALUE'])) {
					if ($arProp['MULTIPLE'] == 'N') {
						$this->item['PROPERTY_VALUES'][$arProp['CODE']] = [
							'VALUE' => [
								'TEXT' => $arProp['~VALUE']['TEXT'],
								'TYPE' => $arProp['~VALUE']['TYPE'],
							],
						];
						if ($arProp['WITH_DESCRIPTION'] == 'Y') {
							$this->item['PROPERTY_VALUES'][$arProp['CODE']]['DESCRIPTION'] = $arProp['~DESCRIPTION'];
						}
					} else {
						if (!empty($arProp['~VALUE'])) {
							$this->item['PROPERTY_VALUES'][$arProp['CODE']] = array();
							foreach ($arProp['~VALUE'] as $propValueKey => $propValue) {
								$oneNewValue = array('VALUE' => array('TEXT' => $propValue['TEXT'], 'TYPE' => $propValue['TYPE']));
								if ($arProp['WITH_DESCRIPTION'] == 'Y') {
									$oneNewValue['DESCRIPTION'] = $arProp['~DESCRIPTION'][$propValueKey];
								}
								$this->item['PROPERTY_VALUES'][$arProp['CODE']][] = $oneNewValue;
								unset($oneNewValue);
							}
							unset($propValue, $propValueKey);
						}
					}
				}
			} elseif ($arProp['PROPERTY_TYPE'] == 'F') {
				if (is_array($arProp['VALUE'])) {
					$this->item['PROPERTY_VALUES'][$arProp['CODE']] = array();
					foreach ($arProp['VALUE'] as $propValueKey => $file) {
						if ($file > 0) {
							$tmpValue = CFile::MakeFileArray($file);
							if (!is_array($tmpValue)) {
								continue;
							}
							if ($arProp['WITH_DESCRIPTION'] == 'Y') {
								$tmpValue = array(
									'VALUE'       => $tmpValue,
									'DESCRIPTION' => $arProp['~DESCRIPTION'][$propValueKey],
								);
							}
							$this->item['PROPERTY_VALUES'][$arProp['CODE']][] = $tmpValue;
						}
					}
				} elseif ($arProp['VALUE'] > 0) {
					$tmpValue = CFile::MakeFileArray($arProp['VALUE']);
					if (is_array($tmpValue)) {
						if ($arProp['WITH_DESCRIPTION'] == 'Y') {
							$tmpValue = array(
								'VALUE'       => $tmpValue,
								'DESCRIPTION' => $arProp['~DESCRIPTION'],
							);
						}
						$this->item['PROPERTY_VALUES'][$arProp['CODE']] = $tmpValue;
					}
				}
			} elseif ($arProp['PROPERTY_TYPE'] == 'L') {
				if (!empty($arProp['VALUE_ENUM_ID'])) {
					if ($this->sourceItem["IBLOCK_ID"] == $this->item['IBLOCK_ID']) {
						$this->item['PROPERTY_VALUES'][$arProp['CODE']] = $arProp['VALUE_ENUM_ID'];
					} else {
						if (isset($arPropListCache[$arProp['CODE']]) && isset($arOldPropListCache[$arProp['CODE']])) {
							if (is_array($arProp['VALUE_ENUM_ID'])) {
								$this->item['PROPERTY_VALUES'][$arProp['CODE']] = array();
								foreach ($arProp['VALUE_ENUM_ID'] as &$intValueID) {
									$strValueXmlID = $arOldPropListCache[$arProp['CODE']][$intValueID];
									if (isset($arPropListCache[$arProp['CODE']][$strValueXmlID])) {
										$this->item['PROPERTY_VALUES'][$arProp['CODE']][] = $arPropListCache[$arProp['CODE']][$strValueXmlID];
									} else {
										$strValueName = $arOldNamePropListCache[$arProp['CODE']][$intValueID];
										$intValueKey = array_search($strValueName, $arNamePropListCache[$arProp['CODE']]);
										if ($intValueKey !== false) {
											$this->item['PROPERTY_VALUES'][$arProp['CODE']][] = $intValueKey;
										}
									}
								}
								if (isset($intValueID)) {
									unset($intValueID);
								}
								if (empty($this->item['PROPERTY_VALUES'][$arProp['CODE']])) {
									unset($this->item['PROPERTY_VALUES'][$arProp['CODE']]);
								}
							} else {
								$strValueXmlID = $arOldPropListCache[$arProp['CODE']][$arProp['VALUE_ENUM_ID']];
								if (isset($arPropListCache[$arProp['CODE']][$strValueXmlID])) {
									$this->item['PROPERTY_VALUES'][$arProp['CODE']] = $arPropListCache[$arProp['CODE']][$strValueXmlID];
								} else {
									$strValueName = $arOldNamePropListCache[$arProp['CODE']][$arProp['VALUE_ENUM_ID']];
									$intValueKey = array_search($strValueName, $arNamePropListCache[$arProp['CODE']]);
									if ($intValueKey !== false) {
										$this->item['PROPERTY_VALUES'][$arProp['CODE']] = $intValueKey;
									}
								}
							}
						}
					}
				}
			} elseif ($arProp['PROPERTY_TYPE'] == 'S' || $arProp['PROPERTY_TYPE'] == 'N') {
				if ($arProp['MULTIPLE'] == 'Y') {
					if (is_array($arProp['~VALUE'])) {
						if ($arProp['WITH_DESCRIPTION'] == 'Y') {
							$this->item['PROPERTY_VALUES'][$arProp['CODE']] = array();
							foreach ($arProp['~VALUE'] as $propValueKey => $propValue) {
								$this->item['PROPERTY_VALUES'][$arProp['CODE']][] = array(
									'VALUE'       => $propValue,
									'DESCRIPTION' => $arProp['~DESCRIPTION'][$propValueKey],
								);
							}
							unset($propValue, $propValueKey);
						} else {
							$this->item['PROPERTY_VALUES'][$arProp['CODE']] = $arProp['~VALUE'];
						}
					}
				} else {
					$this->item['PROPERTY_VALUES'][$arProp['CODE']] = (
					$arProp['WITH_DESCRIPTION'] == 'Y'
						? array('VALUE' => $arProp['~VALUE'], 'DESCRIPTION' => $arProp['~DESCRIPTION'])
						: $arProp['~VALUE']
					);
				}
			} else {
				$this->item['PROPERTY_VALUES'][$arProp['CODE']] = $arProp['~VALUE'];
			}
		}

		if (isset($arProp)) unset($arProp);
	}

	private function prepareIblock()
	{
		$this->isCopyToAnotherIblock = $this->item["IBLOCK_ID"] != $this->destinationIblockID;
		$this->destinationIblock = CIBlock::GetArrayByID($this->destinationIblockID);

		$this->destinationIblock["IS_UNIQUE_CODE"] = false;
		if ($this->destinationIblock['FIELDS']['CODE']['DEFAULT_VALUE']['UNIQUE'] == 'Y') {
			$this->destinationIblock["IS_UNIQUE_CODE"] = true;
			// $this->destinationIblock["IS_UNIQUE_CODE"] = !$this->isCopyToAnotherIblock;
		}

		$this->destinationIblock["IS_UNIQUE_SECT_CODE"] = false;
		if ($this->destinationIblock['FIELDS']['SECTION_CODE']['DEFAULT_VALUE']['UNIQUE'] == 'Y') {
			$this->destinationIblock["IS_UNIQUE_SECT_CODE"] = true;
			// $this->destinationIblock["IS_UNIQUE_SECT_CODE"] = !$this->isCopyToAnotherIblock;
		}
	}
}