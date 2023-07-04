<?php
/**
 * Created by PhpStorm.
 * User: geffest
 * Date: 19.07.2018
 * Time: 15:49
 */

namespace Uplab\Core;


use Bitrix\Main\Application;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Web\Uri;
use CIBlock;
use PHPParser;


class Hermitage
{

	public static function isDesignMode()
	{
		return $GLOBALS["APPLICATION"]->GetShowIncludeAreas();
	}

	/**
	 * Возвращает ссылку для открытия окна редактирования через интерфейс "Эрмитаж".
	 *
	 * Доступные типы ссылок:
	 * - элемент инфоблока
	 * - включаемая область
	 * - меню
	 *
	 * @param mixed $type
	 * @param array $data
	 *
	 * @return string
	 */
	public static function getUrl($type = "", $data = [])
	{
		global $APPLICATION;

		if (empty($type)) return "";

		// Legacy: устаревший способ вызова
		if (is_array($type)) {
			return self::getUrlOld($type);
		}

		$params = [
			"site"       => SITE_ID,
			"bxpublic"   => "Y",
			"back_url"   => $APPLICATION->GetCurPage(false),
			"templateID" => SITE_TEMPLATE_ID,
			"lang"       => LANGUAGE_ID,
		];

		if ($data["noBackUrl"]) {
			unset($params["back_url"]);
		}

		switch ($type) {
			case "component":
				/**
				 * "filePath" - файл, в котором находится компонент
				 *
				 * Прмер:
				 * \Uplab\Core\Hermitage::getUrl(array(
				 *     "type"      => "component",
				 *     "component" => "uplab.core:variables",
				 *     "template"  => "",
				 * ));
				 */
				if (empty($data["component"])) return "";
				if (empty($data["filePath"])) return "";

				$data["filePath"] = Helper::getFileInfo($data["filePath"])["SRC"];

				$url = "/bitrix/admin/component_props.php";

				$params["lang"] = LANGUAGE_ID;
				$params["src_site"] = SITE_ID;
				$params["siteTemplateId"] = SITE_TEMPLATE_ID;
				$params["template_id"] = SITE_TEMPLATE_ID;
				$params["bxsender"] = "core_window_cdialog";

				$params["component_name"] = $data["component"] ?? "";
				$params["component_template"] = $data["template"] ?: ".default";
				$params["component_number"] = $data["number"] ?? null;
				$params["src_path"] = $data["filePath"];
				$params["src_page"] = $data["filePath"];

				if (!empty($data["lineNumber"])) {
					$params["src_line"] = $data["lineNumber"];
				} else {
					$params["src_line"] = self::getLineNumberOfComponent($params);
				}

				break;

			case "includeFile":
				if (empty($data["filePath"])) return "";

				$url = "/bitrix/admin/public_file_edit.php";

				$params["from"] = "includefile";
				$params["bxsender"] = "core_window_cadmindialog";
				$params["path"] = $data["filePath"];
				break;

			case "iblockElement":
				if (empty($data["item"]["IBLOCK_ID"]) || empty($data["item"]["ID"])) return "";

				$arButton = CIBlock::GetPanelButtons(
					$data["item"]["IBLOCK_ID"],
					$data["item"]["ID"],
					0,
					array("SESSID" => false, "CATALOG" => true)
				);

				$type = isset($data["isDelete"]) && $data["isDelete"] ? "delete_element" : "edit_element";

				return $arButton["edit"][$type]["ACTION_URL"];

			case "iblockSection":
				if (empty($data["item"]["IBLOCK_ID"]) || empty($data["item"]["ID"])) return "";

				$arButton = CIBlock::GetPanelButtons(
					$data["item"]["IBLOCK_ID"],
					0,
					$data["item"]["ID"],
					array("SESSID" => false, "CATALOG" => true)
				);

				return $arButton["edit"]["edit_section"]["ACTION_URL"];

			/*
			Пример вызова:
			self::getUrl("menu", [
				// Название меню
				"menuName" => "top",

				// Путь к разделу, в котором находится файл меню.
				// По умолчанию - текущий путь.
				"path" => "",
			]);
			*/
			case "menu":
				if (empty($data["menuName"])) return "";
				$menuName = $data["menuName"];

				$url = "/bitrix/admin/fileman_menu_edit.php";

				$path = $data["path"] ?: $APPLICATION->GetCurDir();
				$filePath = pathinfo($APPLICATION->GetFileRecursive(".{$menuName}.menu.php", $path));

				$params["path"] = $filePath["dirname"];
				$params["name"] = "full";
				$params["extended"] = $data["menuExtended"] == "Y" ? "Y" : "N";

				// d([$filePath, pathinfo($filePath)], "menuPath");
				break;

			default:
				return "";
		}

		$uri = new Uri($url);
		$uri->addParams($params);

		return $uri->getUri();
	}

	/**
	 * Передать $params["isDelete"] = true чтобы получить ссылку на удаление элемента
	 *
	 * @param       $arItem
	 * @param array $params
	 *
	 * @return string
	 */
	public static function getIblockElementUrl(&$arItem, $params = [])
	{
		$params["item"] = &$arItem;

		return self::getUrl("iblockElement", $params);
	}

	public static function getUrlOld($data = [])
	{
		return self::getUrl($data["type"], $data);
	}

	public static function getAttribute($data)
	{
		$arButtons = self::getButtons([$data]);

		if (!empty($arButtons[0]["url"])) {
			return " data-hermitage-link='{$arButtons[0]["url"]}' ";
		}

		return "";
	}

	/**
	 * При добавлении возвращаемого атрибута к HTML-элементу,
	 * в режиме редактирования инициализируются элементы управления,
	 * позволяющие использовать интерфейс эрмитаж там,
	 * где нативный эрмитаж подключить невозможно
	 *
	 * Принимает на вход массив кнопок:
	 * каждый из элементов массива должен быть пригоден для передачи в self::getUrl()
	 *
	 * Также у каждого элемента должен быть определен параметр "text".
	 *
	 * @param array $buttons
	 *
	 * @return string
	 */
	public static function getAttributeMultiple($buttons = [])
	{
		global $APPLICATION;

		if (!$APPLICATION->GetShowIncludeAreas()) return "";

		$arButtons = self::getButtons($buttons);

		return " data-hermitage-link='" . Json::encode($arButtons) . "' ";
	}

	public static function getButtons($buttonsParams)
	{
		$arButtons = [];
		foreach ($buttonsParams as $button) {
			$arButtons[] = [
				"url"  => self::getUrl($button),
				"text" => !empty($button["text"]) ? $button["text"] : "",
			];
		}

		return $arButtons;
	}

	/**
	 * Получить номер строки компонента (используется в Hermitage::getUrl()),
	 * но при необходимости можно использовать в ином контексте
	 *
	 * Необходимые ключи массива $data:
	 * * $data["src_path"]           - Путь к файлу, в котором вызван компонент
	 * * $data["component_name"]     - Название компонента
	 * * $data["component_template"] - Шаблон компонента
	 * * $data["component_number"]   - Номер компонента в файле (если таких компонентов несколько)
	 *
	 * @param $data
	 *
	 * @return bool|int
	 * @see Hermitage::getUrl()
	 *
	 */
	public static function getLineNumberOfComponent($data)
	{
		if (empty($data["src_path"])) return false;
		$filePath = Helper::getFileInfo($data["src_path"])["PATH"];
		if (!is_readable($filePath)) return false;

		$data["component_number"] = (int)($data["component_number"] ?? 1);
		if (empty($data["component_number"]) || $data["component_number"] < 1) {
			$data["component_number"] = 1;
		}

		$content = file_get_contents($filePath);

		$arScripts = PHPParser::ParseFile($content);

		$indexOfComponent = 0;

		foreach ($arScripts as $script) {
			$componentParams = PHPParser::CheckForComponent2($script[2]);

			if (!$componentParams) continue;

			$componentParams["TEMPLATE_NAME"] = $componentParams["TEMPLATE_NAME"] ?: ".default";

			if ($componentParams["COMPONENT_NAME"] == $data["component_name"]) {
				if ($componentParams["TEMPLATE_NAME"] == $data["component_template"]) {
					$indexOfComponent++;

					if ($data["component_number"] == $indexOfComponent) {
						return substr_count(substr($content, 0, $script[0]), PHP_EOL) + 1;
					}
				}
			}
		}

		return false;
	}

}