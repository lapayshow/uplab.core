<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Uplab\Core\Renderer\View;

use Bitrix\Main\Application;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\IncludeTokenParser;
use Uplab\Core\Traits\SingletonTrait;


/**
 * @see IncludeTokenParser
 * Расширяет тег {% include %}
 *
 * {% view '@button' %} - для подключения компонентов
 * {% view '&someName' %} - для подключения частей страницы
 */
class ViewTokenParser extends IncludeTokenParser
{
	use SingletonTrait;

	private $pathParams;

	function __construct()
	{
		/*
		 * Дефолтные пути к views. Путь от корня сайта.
		 *
		 * Можно переопределить:
		 * ViewTokenParser::getInstance()->setPathParams(["..."]);
		 */
		$this->setPathParams([
			"srcExt"  => "twig",
			"dataExt" => "json",

			"viewsSrc" => SITE_TEMPLATE_PATH . "/dist/include/%s/%s.%s",
			"replace"  => [
				"~^@~" => "@components/",
				"~^$~" => "&parts/",
			],
		]);
	}

	public static function getPreparedPath($filePath, $ext = false)
	{
		$self = self::getInstance();
		$ext = $ext ?: $self->pathParams["srcExt"];

		// Защита от дурака
		$self->pathParams["viewsSrc"] = str_replace(
			Application::getDocumentRoot(),
			"",
			$self->pathParams["viewsSrc"]
		);

		$componentName = $filePath;

		foreach ($self->pathParams["replace"] as $key => $value) {
			$componentName = preg_replace($key, "", $componentName);
			$filePath = preg_replace($key, $value, $filePath);
		}

		$f = sprintf($self->pathParams["viewsSrc"], $filePath, $componentName, $ext);

		if (!file_exists(Application::getDocumentRoot() . $f)) {
			$f = sprintf($self->pathParams["viewsSrc"], $filePath, "index", $ext);
		}

		$filePath = $f;

		return $filePath;
	}

	/**
	 * @param      $templatePath
	 * @param bool $isDynamicMode Если true, значит путь уже готовый, указывает на twig-файл,
	 *                            а метод вызывается чтобы получить данные в строке
	 *
	 * @return mixed
	 */
	public static function getDefaultData($templatePath, $isDynamicMode = false)
	{
		$pathParams = self::getInstance()->pathParams;

		if (!$isDynamicMode) {
			$dataFilePath =
				Application::getDocumentRoot() .
				preg_replace(
					"~\.{$pathParams["srcExt"]}$~",
					".{$pathParams["dataExt"]}",
					$templatePath
				);
		} else {
			$dataFilePath =
				Application::getDocumentRoot() .
				self::getPreparedPath(
					$templatePath,
					$pathParams["dataExt"]
				);
		}

		$defaultData = [];

		if (file_exists($dataFilePath)) {
			$defaultData = json_decode(file_get_contents($dataFilePath), true);
		}

		if (empty($defaultData)) $defaultData = [];

		if (!$isDynamicMode) {
			$defaultData = var_export($defaultData, true);
		}

		// AddMessage2Log(compact("templatePath", "isDynamicMode", "dataFilePath", "defaultData"));

		return $defaultData;
	}

	public function parse(Token $token)
	{
		$expr = $this->parser->getExpressionParser()->parseExpression();

		// TODO: Здесь логика преобразования пути к файлу
		self::getInstance()->setPreparedTemplatePath($expr);

		list($variables, $only, $ignoreMissing) = $this->parseArguments();

		return new IncludeViewNode(
			$expr,
			$variables,
			$only,
			true || $ignoreMissing,
			$token->getLine(),
			$this->getTag()
		);
	}

	public function getTag()
	{
		return 'view';
	}

	/**
	 * @return mixed
	 */
	public function getPathParams()
	{
		return $this->pathParams;
	}

	/**
	 * @param mixed $pathParams
	 */
	public function setPathParams($pathParams)
	{
		$this->pathParams = $pathParams;
	}

	/**
	 * Готовим путь к подключаемому файлу
	 *
	 * @param Node $expr
	 */
	private function setPreparedTemplatePath($expr)
	{
		if ($expr->hasAttribute("value")) {
			$expr->setAttribute(
				"value",
				self::getPreparedPath($expr->getAttribute("value"))
			);
		}
	}
}