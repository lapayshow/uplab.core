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

namespace Uplab\Core\Renderer\Svg;

use Bitrix\Main\Application;
use Twig\Node\IncludeNode;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\IncludeTokenParser;
use Uplab\Core\Traits\SingletonTrait;


/**
 * @see     IncludeTokenParser
 *
 * Расширяет тег {% include %}
 *
 * Может принимать на вход как код иконки (располагается в директории фронтенда),
 *   так и путь к svg-файлу от корня
 *
 *   - в первом случае принимает строку без .svg
 *   - во втором - полный путь, заканчивается на .svg
 *
 * @example {% svg 'icon-name' %} - для подключения SVG-иконки
 */
class SvgTokenParser extends IncludeTokenParser
{
	use SingletonTrait;

	private $pathParams;

	function __construct()
	{
		/*
		 * Дефолтные пути. Путь от корня сайта.
		 *
		 * Можно переопределить:
		 * SvgTokenParser::getInstance()->setPathParams(["..."]);
		 */
		$this->setPathParams([
			"src" => [
				SITE_TEMPLATE_PATH . "/dist/img/%s.svg",
				"%s",
			],
		]);
	}

	public static function getPreparedPathForSrc($filePath, $src)
	{
		$src = str_replace(Application::getDocumentRoot(), "", $src);

		// $filePath = $expr->getAttribute("value");
		return sprintf($src, $filePath);
	}

	public static function getPreparedPath($filePath)
	{
		$self = self::getInstance();

		$self->pathParams["src"] = (array)$self->pathParams["src"];

		foreach ($self->pathParams["src"] as $src) {
			$preparedPath = self::getPreparedPathForSrc($filePath, $src);

			if (file_exists(Application::getDocumentRoot() . $preparedPath) && preg_match("~\.svg$~", $preparedPath)) {
				return $preparedPath;
			}
		}

		return false;
	}

	public function parse(Token $token)
	{
		$expr = $this->parser->getExpressionParser()->parseExpression();

		list($variables, $only, $ignoreMissing) = $this->parseArguments();

		$this->setPreparedTemplatePath($expr);

		return new IncludeSvgNode(
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
		return 'svg';
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
	 * Двойная логика: если есть атрибут value, это значит,
	 * что в качестве пути к файлу передана обычная строка,
	 * с которой мы можем заранее работать до компиляции.
	 *
	 * Если же атрибута нет, значит что в качестве пути использовалась какая-то переменная,
	 * которую мы узнаем только на этапе компиляции. Поэтому всю логику обработки путей
	 * нужно включать в скомпилированный PHP-файл. В первом случае в PHP-файл
	 * включаем готовый результат, чтобы не тратить напрасно ресурсы.
	 *
	 * @param Node $expr
	 *
	 * @see \Uplab\Core\Renderer\Svg\IncludeSvgNode::addGetTemplate
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
