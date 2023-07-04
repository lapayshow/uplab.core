<?

namespace __MODULE_NAMESPACE__;


use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use CMain;
use CUser;
use Uplab\Core\Renderer;
use Uplab\Core\Traits\EventsTrait;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 * @global CUser $USER
 */


class Events
{
	use EventsTrait;

	public static function bindEvents()
	{
		$event = EventManager::getInstance();

		$event->addEventHandler("main", "OnProlog", [self::class, "setGlobalData"]);

		$event->addEventHandler("main", "OnEpilog", [self::class, "redirect404"]);

		// $event->addEventHandler("form", "onAfterResultAdd", [self::class, "formHandler"]);
		// $event->addEventHandler("form", "onAfterResultUpdate", [self::class, "formHandler"]);
	}

	public static function setGlobalData()
	{
		Loc::loadMessages(Application::getDocumentRoot() . Helper::DEFAULT_TEMPLATE_PATH . "/lang.php");

		if (Context::getCurrent()->getRequest()->isAdminSection()) {
			self::setAdminGlobalData();
		} else {
			self::setPublicGlobalData();
		}
	}

	private static function setAdminGlobalData()
	{
	}

	private static function setPublicGlobalData()
	{
		// Дополнительные параметры, передаваемые в шаблон
		Renderer::getInstance()->setRenderParams([
			"placeholder" => Helper::TRANSPARENT_PIXEL,
			"messages"     => [
				"error" => [
					"required"  => "Обязательное поле",
					"email"     => "Введите корректный e-mail адрес",
					"number"    => "Введите корректное число",
					"url"       => "Введите корректный URL",
					"tel"       => "Введите корректный номер телефона",
					"maxlength" => "This fields length must be < \${1}",
					"minlength" => "This fields length must be > \${1}",
					"min"       => "Minimum value for this field is \${1}",
					"max"       => "Maximum value for this field is \${1}",
					"pattern"   => "Input must match the pattern \${1}",
				],
			],
		]);

		$rendererIncludePath = Helper::isDevMode()
			? Helper::DEFAULT_TEMPLATE_PATH . "/frontend/src"
			: Helper::DEFAULT_TEMPLATE_PATH . "/dist";

		$rendererIncludePathFull = Application::getDocumentRoot() . $rendererIncludePath;

		// Здесь можно изменить список путей, с которыми будет инициализирован Twig
		Renderer::getInstance()->setLoaderPaths([
			$rendererIncludePathFull,
			Application::getDocumentRoot(),
			"template"  => Application::getDocumentRoot() . Helper::DEFAULT_TEMPLATE_PATH,
			"frontend"  => "{$rendererIncludePathFull}",
			"layout"    => "{$rendererIncludePathFull}/include/layout",
			"atoms"     => "{$rendererIncludePathFull}/include/@atoms",
			"molecules" => "{$rendererIncludePathFull}/include/^molecules",
			"organisms" => "{$rendererIncludePathFull}/include/&organisms",
		]);

		// Настройки для кастомного тега {% view '' %} в шаблонах Twig
		Renderer\View\ViewTokenParser::getInstance()->setPathParams([
			"srcExt"  => "twig",
			"dataExt" => "json",

			"viewsSrc" => "{$rendererIncludePath}/include/%s/%s.%s",
			"replace"  => [
				"~^@~"  => "@atoms/",
				"~^\^~" => "^molecules/",
				"~^&~"  => "&organisms/",
			],
		]);

		// Настройки для кастомного тега {% svg '' %} в шаблонах Twig
		Renderer\Svg\SvgTokenParser::getInstance()->setPathParams([
			"src" => [
				"{$rendererIncludePath}/img/%s.svg",
				"%s",
			],
		]);
	}

	/*
	public static function formHandler($WEB_FORM_ID, $RESULT_ID)
	{
		if ($WEB_FORM_ID != 2) return;

		$arAnswer = CFormResult::GetDataByID($RESULT_ID, array(), $arResult, $arAnswer2);
		$data = self::prepareFormAnswers($arAnswer);

		die("<pre>" . print_r(compact("data"), true));
	}

	private static function prepareFormAnswers($arAnswer)
	{
		$data = [];

		foreach ($arAnswer as $code => $answer) {
			$answerItem = current($answer);

			if ($answerItem["FIELD_TYPE"] == "file") {
				$value = CFile::GetPath($answerItem["USER_FILE_ID"]);
				$value = $value ? Helper::makeDomainUrl($value) : "";
			} else {
				$value = $answerItem["USER_TEXT"];
			}

			if (!empty($value)) {
				$data[$code] = $value;
			}
		}

		return $data;
	}
	*/
}
