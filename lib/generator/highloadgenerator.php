<?php

namespace Uplab\Core\Generator;


use Bitrix\Main\Application;
use Exception;
use Uplab\Core\Data\StringUtils;
use Uplab\Core\Helper;


/*
Пример вызова генератора:
Generator::generate("ModuleNamespace", "suffix");
*/


class HighloadGenerator extends EntityGenerator
{
	protected $tplPath = "__MODULE_PATH__/include/tpl/highload";

	protected $namespacePrefix = "\\Entities\\Highload";
	protected $pathPrefix      = "/entities/highload";
	// protected $namespacePrefix = "";
	// protected $pathPrefix      = "";

	/**
	 * Generator constructor.
	 *
	 * @param $moduleName
	 * @param $entityName
	 * @param $namespace
	 */
	public function __construct($moduleName, $entityName, $namespace = "")
	{
		parent::__construct($moduleName, $entityName, $namespace);
	}

	public function printHelp()
	{
		echo $this->getReplacedContent(
			implode(PHP_EOL, [
				"",
				"===",
				"Теперь можно работать с сущностью __Entity__,",
				"используя класс __namespace__\\__Entity__\\__Entity__Table",
				"",
				"",
			])
		);
	}

	protected function getDestinationPath()
	{
		return Application::getDocumentRoot() .
			"{$this->modulePath}/lib{$this->pathPrefix}";
	}

}