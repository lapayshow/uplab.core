<?php
/**
 * Created by PhpStorm.
 * User: geffest
 * Date: 21/11/2018
 * Time: 00:31
 */

namespace Uplab\Core\Iblock;


use Bitrix\Main\Loader;
use CIBlock;
use CIBlockProperty;
use CIBlockPropertyEnum;
use CIBlockType;


class CopyIblockPage
{
	private $targetIblock;
	private $destinationIblockType;
	private $targetPropsIblock;
	private $newIblockId;

	private $hasErrors = false;

	function __construct(
		$targetIblock,
		$destinationIblockType,
		$targetPropsIblock = null,
		/** @noinspection PhpUnusedParameterInspection */
		$params = []
	) {
		$this->targetIblock = intval($targetIblock);
		$this->destinationIblockType = $destinationIblockType;
		$this->targetPropsIblock = intval($targetPropsIblock) ?: $this->targetIblock;

		if ($this->targetIblock <= 0) $this->hasErrors = true;

		$this->copy();
	}

	public static function executeScript()
	{
		Loader::IncludeModule("iblock");

		self::processData();

		if ($_REQUEST["success"] == "Y") {
			$resultType = "success";
		} elseif ($_REQUEST["error"] == "Y") {
			$resultType = "error";
		} else {
			$resultType = "";
		}

		self::printForm($resultType);
	}

	private static function printForm($resultType = "")
	{
		global $APPLICATION;

		$iblockList = [];
		$res = CIBlock::GetList(Array(), Array(), true);
		while ($item = $res->Fetch()) {
			$iblockList[] = [
				"ID"   => $item["ID"],
				"TEXT" => "[{$item["IBLOCK_TYPE_ID"]}] #{$item["ID"]}. {$item['NAME']}",
			];
		}

		$iblockTypes = [];
		$res = CIBlockType::GetList();
		while ($item = $res->Fetch()) {
			if ($arIBType = CIBlockType::GetByIDLang($item["ID"], LANG)) {
				$iblockTypes[] = [
					"ID"   => $item["ID"],
					"TEXT" => htmlspecialcharsex($arIBType["NAME"]),
				];
			}
		}

		?>
		<form action='<?= $APPLICATION->GetCurPageParam() ?>' method="post">
			<table>

				<? if ($resultType == "success"): ?>
					<tr>
						<td><strong style="color: green;">ИБ успешно скопирован</strong><b></td>
					</tr>
				<? elseif ($resultType == "error"): ?>
					<tr>
						<td><strong style="color: red;">Произошла ошибка</strong><br/></td>
					</tr>
				<? endif; ?>

				<tr>
					<td><strong>Копируем мета данные ИБ в новый ИБ</strong><br/></td>
				</tr>

				<tr>
					<td>
						<label>
							Копируем ИБ:<br>
							<select name="IBLOCK_ID_FIELDS" required>
								<? foreach ($iblockList as $item) : ?>
									<option value="<?= $item["ID"] ?>"><?= $item["TEXT"] ?></option>
								<? endforeach; ?>
							</select>
						</label>
					</td>

					<td>
						<label>
							Копируем в новый ИБ свойства другого ИБ: *<br>
							<select name="IBLOCK_ID_PROPS">
								<option value=""></option>
								<? foreach ($iblockList as $item) : ?>
									<option value="<?= $item["ID"] ?>"><?= $item["TEXT"] ?></option>
								<? endforeach; ?>
							</select>
						</label>
					</td>
				</tr>

				<tr>
					<td>
						<label>
							Копируем ИБ в тип:<br>
							<select name="IBLOCK_TYPE_ID" required>
								<option value="empty"></option>
								<? foreach ($iblockTypes as $item): ?>
									<option value="<?= $item["ID"] ?>"><?= $item["TEXT"] ?></option>
								<? endforeach; ?>
							</select>
						</label>
					</td>
				</tr>

				<tr>
					<td><br/>* если значение не указано мета данные ИБ секции "Свойства" берутся из ИБ первого поля</td>
				</tr>

				<tr>
					<td><input type="submit" value="копируем"></td>
				</tr>
			</table>
<<<<<<< HEAD
=======
            {{ form_rest(form) }}
>>>>>>> aaec764cb2a6e652b865ce41431c03ebca9b9a87
		</form>
		<?
	}

	private static function processData()
	{
		global $APPLICATION;

		if (empty($_REQUEST["IBLOCK_ID_FIELDS"])) return;

		$keys = [
			"IBLOCK_ID_FIELDS",
			"IBLOCK_TYPE_ID",
			"IBLOCK_ID_PROPS",
		];
		// $values = array_intersect_key($_REQUEST, array_flip($keys));

		$copy = new self($_REQUEST["IBLOCK_ID_FIELDS"], $_REQUEST["IBLOCK_TYPE_ID"], $_REQUEST["IBLOCK_ID_PROPS"]);

		if (!$copy->hasErrors()) {
			LocalRedirect($APPLICATION->GetCurPageParam("success=Y", array_merge(["success", "error"], $keys)));
		} else {
			LocalRedirect($APPLICATION->GetCurPageParam("error=Y", array_merge(["success", "error"], $keys)));
		}
	}

	/**
	 * @return bool
	 */
	public function hasErrors()
	{
		return $this->hasErrors;
	}

	private function copyIblock()
	{
		if ($this->hasErrors) return;

		$ib = new CIBlock;

		$arFields = CIBlock::GetArrayByID($this->targetIblock);

		$arFields["GROUP_ID"] = CIBlock::GetGroupPermissions($this->targetIblock);
		$arFields["NAME"] = $arFields["NAME"] . "_new";

		unset($arFields["ID"]);

		if ($this->destinationIblockType != "empty") {
			$arFields["IBLOCK_TYPE_ID"] = $this->destinationIblockType;
		}

		$this->newIblockId = $ib->Add($arFields);
		$this->newIblockId = intval($this->newIblockId);

		if ($this->newIblockId <= 0) $this->hasErrors = true;
	}

	private function copyProps()
	{
		if ($this->hasErrors) return;

		$ibpObject = new CIBlockProperty;

		$propsRes = CIBlockProperty::GetList(
			["sort" => "asc", "name" => "asc"],
			[
				"ACTIVE"    => "Y",
				"IBLOCK_ID" => $this->targetPropsIblock,
			]
		);

		while ($propertyItem = $propsRes->GetNext()) {

			if ($propertyItem["PROPERTY_TYPE"] == "L") {
				$propertyEnums = CIBlockPropertyEnum::GetList(
					["DEF" => "DESC", "SORT" => "ASC"],
					["IBLOCK_ID" => $this->targetPropsIblock, "CODE" => $propertyItem["CODE"]]
				);

				while ($enumFields = $propertyEnums->GetNext()) {
					$propertyItem["VALUES"][] = Array(
						"VALUE" => $enumFields["VALUE"],
						"DEF"   => $enumFields["DEF"],
						"SORT"  => $enumFields["SORT"],
					);
				}
			}

			$propertyItem["IBLOCK_ID"] = $this->newIblockId;
			unset($propertyItem["ID"]);

			foreach ($propertyItem as $k => $v) {
				if (!is_array($v)) $propertyItem[$k] = trim($v);
				if ($k{0} == '~') unset($propertyItem[$k]);
			}

			$PropID = intval($ibpObject->Add($propertyItem));
			if ($PropID <= 0) $this->hasErrors = true;

		}
	}

	private function copy()
	{
		$this->copyIblock();
		$this->copyProps();
	}

}