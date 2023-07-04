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
use Twig\Compiler;
use Twig\Node\IncludeNode;
use Twig\Node\NodeOutputInterface;


/**
 * @see    \Twig\Node\IncludeNode
 * Represents an include node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class IncludeViewNode extends IncludeNode implements NodeOutputInterface
{
	protected function addGetTemplate(Compiler $compiler)
	{
		$compiler->write('$this->loadTemplate(');

		if ($this->getNode('expr')->hasAttribute("value")) {
			$compiler->subcompile($this->getNode('expr'));
		} else {
			$compiler
				->raw(ViewTokenParser::class . "::getPreparedPath(")
				->subcompile($this->getNode('expr'))
				->raw(')');
		}

		$compiler->raw(', ');
		$compiler->repr($this->getTemplateName());
		$compiler->raw(', ');
		$compiler->repr($this->getTemplateLine());
		$compiler->raw(')');
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
	 * @param Compiler $compiler
	 *
	 * @see \Uplab\Core\Renderer\View\ViewTokenParser::setPreparedTemplatePath
	 */
	protected function addTemplateArguments(Compiler $compiler)
	{
		$isOnly = false !== $this->getAttribute('only');

		$expr = $this->getNode("expr");

		if ($expr->hasAttribute("value")) {
			$defaultData2Php = ViewTokenParser::getDefaultData($expr->getAttribute("value"), false);
			$getDynamicData = false;
		} else {
			$defaultData2Php = false;
			$getDynamicData = true;
		}

		$addDefaultData = function () use ($expr, $compiler, $getDynamicData, $defaultData2Php) {
			if ($getDynamicData) {
				$compiler
					->raw(ViewTokenParser::class . "::getDefaultData(")
					->subcompile($expr)
					->raw(', true)');
			} else {
				$compiler->raw($defaultData2Php);
			}

			// $GLOBALS["APPLICATION"]->RestartBuffer();
			// die("<pre>" . print_r(compact("compiler"), true));
		};

		if (!$this->hasNode('variables')) {
			// AddMessage2Log("noVariables ...");

			if ($getDynamicData || $defaultData2Php) {
				$compiler->raw("twig_array_merge(");

				call_user_func($addDefaultData);

				$compiler->raw(", \$context)");
			} else {
				$compiler->raw(!$isOnly ? '$context' : '[]');
			}

		} elseif (!$isOnly) {
			// AddMessage2Log("hasVariables ... no only");

			/**
			 * TODO: twig_array_merge мерджит только по две переменных.
			 * Можно заменить на array_merge, но может потяряться какая-то сложная логика для объектов
			 * Поэтому лучше пока оставить так
			 *
			 * @see https://tinyurl.com/y4cj6h3s
			 */
			$compiler->raw("twig_array_merge(twig_array_merge(");

			call_user_func($addDefaultData);

			$compiler->raw(", \$context), ");
			$compiler->subcompile($this->getNode('variables'));
			$compiler->raw(')');

		} else {
			// AddMessage2Log("hasVariables ... only");

			$compiler->raw("twig_array_merge(");

			call_user_func($addDefaultData);

			$compiler->raw(", ");
			$compiler->subcompile($this->getNode('variables'));
			$compiler->raw(')');

		}


		// die("<pre>" . print_r(compact("compiler"), true));
	}
}