<?php
/**
 * Created by PhpStorm.
 * User: geffest
 * Date: 02.09.2018
 * Time: 17:58
 */

namespace Uplab\Core\Data;


use CForm;
use Uplab\Core\Uri;


/**
 * Класс модифицирует массив arResult компонента form.result.new
 * упрощая некоторые задачи интеграции форм.
 *
 * Вызывается так: \Uplab\Core\Forms::prepareForm($arResult);
 *
 * После вызова:
 *
 * 1) добавляет $arResult["~FORM_HEADER"], который содержит необходимые инпуты, но без лишнего тега <form>
 * *
 * 2) добавляет $arResult["~FORM_ERRORS"], где гарантированно находится массив ошибок
 *    (иначе $arResult["FORM_ERRORS"] может быть как массивом, так и строкой,
 *    зависит от параметров компонента)
 * *
 * 3) добавляет к элементам массива $arResult["QUESTIONS"] поле "NAME" -
 *    значение атрибута "name" для html-инпута
 * 4) добавляет к элементам массива $arResult["QUESTIONS"] поле "~CAPTION" -
 *    подпись к инпуту, со звездочкой или без
 * *
 * 5) добавляет к элементам массива $arResult["arAnswers"] и $arResult["QUESTIONS"]["STRUCTURE"]
 *    поле "FIELD_NAME" - значение атрибута "name" для html-инпута
 * *
 *
 * @package Uplab\Core
 */
class FormResultModifier
{

	public static function getErrors(&$arResult)
	{
		if ($arResult["isFormErrors"] != "Y") return null;
		if (is_array($arResult["~FORM_ERRORS"]) && !empty($arResult["~FORM_ERRORS"])) {
			return null;
		}

		if (is_array($arResult["FORM_ERRORS"])) {
			$arResult["~FORM_ERRORS"] = $arResult["FORM_ERRORS"];
		} else {
			$arResult["~FORM_ERRORS"] = CForm::Check(
				$arResult["arForm"]["ID"],
				$arResult["arrVALUES"],
				false,
				"Y",
				"Y"
			);
		}
	}

	public static function prepareForm(array &$arResult, array &$arParams = null)
	{
		self::getErrors($arResult);

		$arResult["~FORM_NOTE"] = nl2br($arResult["FORM_NOTE"]);

		preg_match("~action=\"([^\"]+)\"~", $arResult["FORM_HEADER"], $match);

		$formAction = str_replace("&amp;", "&", $match[1]);
		$arResult["~FORM_ACTION"] = $arResult["FORM_ACTION"] = Uri::init($formAction)->removeParams([
			"WEB_FORM_ID",
			"RESULT_ID",
			"formresult",
		]);

		$arResult["~FORM_HEADER"] = "";
		$arResult["~FORM_HEADER"] .= bitrix_sessid_post();
		$arResult["~FORM_HEADER"] .= "<input type=\"hidden\" name=\"WEB_FORM_ID\" value=\"{$arResult["arForm"]["ID"]}\" />";
		$arResult["~FORM_HEADER"] .= "<input type=\"hidden\" name=\"web_form_apply\" value=\"Y\" />";

		foreach ($arResult["QUESTIONS"] as $code => $question) {
			self::getQuestionData($code, $arResult, $arParams);
		}

		foreach ($arResult["arAnswers"] as $code => &$arAnswersList) {
			foreach ($arAnswersList as $i => &$arAnswer) {
				$parsedBxInputHtml = self::getOutputFromBxInputs($arResult, $arAnswer, $code);

				if ($arResult["isFormAddOk"] == "Y" && !empty($parsedBxInputHtml["value"])) {
					$parsedBxInputHtml["value"] = "";
				}

				if (!empty($parsedBxInputHtml["attrs"]["NAME"])) {
					$arAnswer["FIELD_NAME"] =
					$arResult["QUESTIONS"][$code]["STRUCTURE"][$i]["FIELD_NAME"] =
						$parsedBxInputHtml["attrs"]["NAME"];
				}


				unset($arAnswer);
			}

			unset($arAnswersList);
		}
	}

	public static function getQuestionData($code, array &$arResult, array &$arParams = null)
	{
		$question = &$arResult["QUESTIONS"][$code];

		if (empty($question["HTML_CODE"])) return false;

		if ($question["TYPE"] == "hidden") {
			$n = $question["NAME"];
			$v = $question["VALUE"];
			$arResult["~FORM_HEADER"] .= "<input type=\"hidden\" name=\"{$n}\" value=\"$v\">";
			unset($arResult["QUESTIONS"][$code]);

			return null;
		}

		$question["~CAPTION"] = $question["CAPTION"];
		$question["~CAPTION"] .= $question["REQUIRED"] == "Y" ? " *" : "";

		$structure = current($question["STRUCTURE"]);
		$question["TYPE"] = $structure["FIELD_TYPE"];

		$attrs = ["name"];

		$isCheckable = in_array($question["TYPE"], ["checkbox", "radio"]);
		$isSelectable = in_array($question["TYPE"], ["dropdown"]);

		if (!$isCheckable && !$isSelectable) {
			$attrs[] = ["value"];
		}

		$arAttributes = self::getAttributesFromHtml($question["HTML_CODE"], $attrs);
		foreach ($arAttributes as $key => $value) {
			if (!empty($question[$key])) continue;
			$question[$key] = $value;
		}

		if ($isCheckable) {
			$question["VALUES"] = [];
			$question["~NAME"] = str_replace("[]", "", $question["NAME"]);
			$input = (array)$arResult["arrVALUES"][$question["~NAME"]];

			// d($input, $question["NAME"]);
			foreach ($question["STRUCTURE"] as $answer) {
				$question["VALUES"][$answer["VALUE"]] = [
					"ID"      => $answer["ID"],
					"CHECKED" => in_array($answer["ID"], $input) ? "Y" : "",
				];
			}
			$value = current($question["VALUES"]);
			$question["VALUE"] = $value["ID"];
			$question["CHECKED"] = $value["CHECKED"];
		}

		if (
			empty($question["VALUE"]) &&
			!empty($question["NAME"]) &&
			array_key_exists($question["NAME"], $arResult["arrVALUES"])) {
			$question["VALUE"] = $arResult["arrVALUES"][$question["NAME"]];
		}

		if (empty($question["VALUE"]) && isset($arParams["FIELDS_DATA"][$code])) {
			$question["VALUE"] = $arParams["FIELDS_DATA"][$code];
		}

		if ($isSelectable) {
			foreach ($question["STRUCTURE"] as $i => $option) {
				$selected = $option["ID"] == $question["VALUE"] ? "Y" : "";
				$question["STRUCTURE"][$i]["SELECTED"] = $selected;
				$question["STRUCTURE"][$i]["SELECTED_ATTRIBUTE"] = $selected == "Y" ? " selected " : "";

				$question["STRUCTURE"][$i]["INPUT_VALUE"] = $option["ID"];
			}
		}

		$question["CODE"] = $code;

		$question["REQUIRED_ATTRIBUTE"] = $question["REQUIRED"] == "Y" ? " required " : "";

		$question["HTML_TAG"] = self::getHtmlTagByFieldType($question["TYPE"]);

		return $question;
	}

	public static function getHtmlTagByFieldType($fieldType)
	{
		switch ($fieldType) {
			case "radio":
				return "input";

			case "checkbox":
				return "input";

			case "dropdown":
				return "select";

			case "multiselect":
				return "select";

			case "text":
				return "input";

			case "hidden":
				return "input";

			case "password":
				return "input";

			case "email":
				return "input";

			case "url":
				return "input";

			case "textarea":
				return "textarea";

			case "date":
				return "input";

			case "image":
				return "input";

			case "file":
				return "input";
		}

		$attrs = self::getAttributesFromHtml($res);

		return compact("res", "value", "attrs", "html_tag");
	}

	public static function getOutputFromBxInputs(&$arResult, $arAnswer, $fieldCode)
	{
		$res = "";

		switch ($arAnswer["FIELD_TYPE"]) {
			case "radio":
				$value = CForm::GetRadioValue($fieldCode, $arAnswer, $arResult["arrVALUES"]);
				$html_tag = "input";

				$res = CForm::GetRadioField(
					$fieldCode,
					$arAnswer["ID"],
					$value
				);
				break;

			case "checkbox":
				$value = CForm::GetCheckBoxValue($fieldCode, $arAnswer, $arResult["arrVALUES"]);
				$html_tag = "input";

				$res = CForm::GetCheckBoxField(
					$fieldCode,
					$arAnswer["ID"],
					$value
				);
				break;

			case "dropdown":
				$value = CForm::GetDropDownValue($fieldCode, $arResult["arDropDown"], $arResult["arrVALUES"]);
				$html_tag = "select";

				$res = CForm::GetDropDownField(
					$fieldCode,
					$arResult["arDropDown"][$fieldCode],
					$value
				);
				break;

			case "multiselect":
				$value = CForm::GetMultiSelectValue($fieldCode, $arResult["arMultiSelect"], $arResult["arrVALUES"]);
				$html_tag = "select";

				$res = CForm::GetMultiSelectField(
					$fieldCode,
					$arResult["arMultiSelect"][$fieldCode],
					$value,
					$arAnswer["FIELD_HEIGHT"]
				);
				break;

			case "text":
				$value = CForm::GetTextValue($arAnswer["ID"], $arAnswer, $arResult["arrVALUES"]);
				$html_tag = "input";

				$res = CForm::GetTextField(
					$arAnswer["ID"],
					$value,
					$arAnswer["FIELD_WIDTH"]
				);
				break;

			case "hidden":
				$value = CForm::GetHiddenValue($arAnswer["ID"], $arAnswer, $arResult["arrVALUES"]);
				$html_tag = "input";

				$res = CForm::GetHiddenField(
					$arAnswer["ID"],
					$value,
					$arAnswer["FIELD_PARAM"]);
				break;

			case "password":
				$value = CForm::GetPasswordValue($arAnswer["ID"], $arAnswer, $arResult["arrVALUES"]);
				$html_tag = "input";

				$res = CForm::GetPasswordField(
					$arAnswer["ID"],
					$value,
					$arAnswer["FIELD_WIDTH"],
					$arAnswer["FIELD_PARAM"]);
				break;

			case "email":
				$value = CForm::GetEmailValue($arAnswer["ID"], $arAnswer, $arResult["arrVALUES"]);
				$html_tag = "input";

				$res = CForm::GetEmailField(
					$arAnswer["ID"],
					$value,
					$arAnswer["FIELD_WIDTH"],
					$arAnswer["FIELD_PARAM"]);
				break;

			case "url":
				$value = CForm::GetUrlValue($arAnswer["ID"], $arAnswer, $arResult["arrVALUES"]);
				$html_tag = "input";

				$res = CForm::GetUrlField(
					$arAnswer["ID"],
					$value,
					$arAnswer["FIELD_WIDTH"],
					$arAnswer["FIELD_PARAM"]);
				break;

			case "textarea":
				$value = CForm::GetTextAreaValue($arAnswer["ID"], $arAnswer, $arResult["arrVALUES"]);
				$html_tag = "textarea";

				$res = CForm::GetTextAreaField(
					$arAnswer["ID"],
					$arAnswer["FIELD_WIDTH"],
					$arAnswer["FIELD_HEIGHT"],
					"",
					$value
				);
				break;

			case "date":
				$value = CForm::GetDateValue($arAnswer["ID"], $arAnswer, $arResult["arrVALUES"]);
				$html_tag = "input";

				$res = CForm::GetDateField(
					$arAnswer["ID"],
					$arResult["arForm"]["SID"],
					$value,
					$arAnswer["FIELD_WIDTH"],
					""
				);
				break;

			case "image":
				$html_tag = "input";

				$res = CForm::GetFileField(
					$arAnswer["ID"],
					$arAnswer["FIELD_WIDTH"],
					"IMAGE"
				);
				break;

			case "file":
				$html_tag = "input";

				$res = CForm::GetFileField(
					$arAnswer["ID"],
					$arAnswer["FIELD_WIDTH"],
					"FILE"
				);
				break;
		}

		$attrs = self::getAttributesFromHtml($res);

		return compact("res", "value", "attrs", "html_tag");
	}

	private static function getAttributesFromHtml($html, $attrs = ["name", "value"])
	{
		$arAttributes = [];

		preg_match_all(
			"~((" . implode("|", $attrs) . ")" .
			"=[\'\"]([^\'\"]+)['\"]|checked)~",
			$html, $matches
		);

		foreach ($matches[2] as $i => $key) {
			if (empty($key)) {
				$key = $matches[1][$i];
				$value = "Y";
			} else {
				$value = $matches[3][$i];
			}
			if (empty($key)) continue;
			if (!empty($arAttributes[mb_strtoupper($key)])) continue;
			$arAttributes[mb_strtoupper($key)] = $value;
		}

		return $arAttributes;
	}

}