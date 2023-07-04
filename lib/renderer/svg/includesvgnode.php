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
use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\IncludeNode;
use Twig\Node\Node;
use Twig\Node\NodeOutputInterface;


/**
 * @see    \Twig\Node\IncludeNode
 * Represents an include node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class IncludeSvgNode extends IncludeNode implements NodeOutputInterface
{
	protected function addGetTemplate(Compiler $compiler)
	{
		$compiler->write('$this->loadTemplate(');

		if ($this->getNode('expr')->hasAttribute("value")) {
			$compiler->subcompile($this->getNode('expr'));
		} else {
			$compiler
				->raw(SvgTokenParser::class . "::getPreparedPath(")
				->subcompile($this->getNode('expr'))
				->raw(')');
		}

		$compiler->raw(', ');
		$compiler->repr($this->getTemplateName());
		$compiler->raw(', ');
		$compiler->repr($this->getTemplateLine());
		$compiler->raw(')');
	}
}
