<?php
/**
 * Created by PhpStorm.
 * User: geffest
 * Date: 30.08.2019
 * Time: 17:00
 */

namespace Uplab\Core\Generator;


use Bitrix\Main\Application;
use Exception;
use Uplab\Core\Data\StringUtils;
use Uplab\Core\Helper;


/*
Пример вызова генератора:
Generator::generate("ModuleNamespace", "suffix");
*/


class EntityGenerator extends GeneratorBase
{
	protected $tplPath = "__MODULE_PATH__/include/tpl/entity";

	protected $namespacePrefix = "\\Entities";
	protected $pathPrefix      = "/entities";
	// protected $namespacePrefix = "";
	// protected $pathPrefix      = "";

	protected $moduleName;
	protected $modulePath;
	protected $entityName;
	protected $namespace;
	protected $entityPath;

	/**
	 * Generator constructor.
	 *
	 * @param $moduleName
	 * @param $entityName
	 * @param $namespace
	 */
	public function __construct($moduleName, $entityName, $namespace = "")
	{
		$this->moduleName = $moduleName;
		$this->entityName = $entityName;
		$this->namespace = $namespace;

		parent::__construct();
	}

	public static function generate($moduleName, $entityName, $namespace = "")
	{
		return new self($moduleName, $entityName, $namespace);
	}

	public function printHelp()
	{
		echo PHP_EOL, PHP_EOL;

		echo $this->getReplacedContent(implode(PHP_EOL, [
			"Скопируйте подписи локализации:",
			"\$MESS[\"__module_____ENTITY___TAB_TITLE\"] = \"Элемент\";",
			"\$MESS[\"__module_____ENTITY___LIST_TITLE\"] = \"Элементы\";",
			"\$MESS[\"__module_____ENTITY___NEW_TITLE\"] = \"Добавить элемент\";",
			"\$MESS[\"__module_____ENTITY___EDIT_TITLE\"] = \"Изменить элемент (##ID#)\";",
		]));

		echo PHP_EOL, PHP_EOL;

		echo $this->getReplacedContent(implode(PHP_EOL, [
			"Добавьте пункт в меню:",
			"",
			"array(",
			"	\"text\"     => \"__Entity__\",",
			"	\"icon\"     => \"iblock_menu_icon_iblocks\",",
			"	\"url\"      => \__namespace__\__Entity__\AdminInterface\__Entity__ListHelper::getUrl(),",
			"	\"more_url\" => array(",
			"		\__namespace__\__Entity__\AdminInterface\__Entity__EditHelper::getUrl(),",
			"	),",
			"),",
		]));


		echo PHP_EOL, PHP_EOL;
	}

	protected function prepare()
	{
		$module = strtolower($this->moduleName);

		$letter1 = substr($this->entityName, 0, 1);
		// $letter2 = substr($this->entityName, 1, 1);
		// if ($letter1 == mb_strtoupper($letter1) && $letter2 == mb_strtolower($letter2)) {

		if ($letter1 == mb_strtoupper($letter1)) {
			$Entity = $this->entityName;
		} else {
			$Entity = ucfirst($this->entityName);
		}

		$entity = strtolower($this->entityName);
		$ENTITY = mb_strtoupper(StringUtils::convertCamelCaseToUnderScore($this->entityName));

		$namespace = $this->namespace ?: implode('\\', array_map("ucfirst", explode(".", $module)));
		$namespace = implode("\\", array_filter(explode("\\", $namespace))) . $this->namespacePrefix;

		$this->moduleName = $module;
		$this->entityName = $entity;
		$this->namespace = $namespace;

		if (empty($this->moduleName)) {
			throw new Exception("Wrong input data");
		}

		$this->modulePath = getLocalPath("modules/{$this->moduleName}");
		$this->destinationPath = $this->getDestinationPath();

		$this->replaceArray = [
			"__module__"            => $module,
			"__Entity__"            => $Entity,
			"__entity__"            => $entity,
			"__ENTITY__"            => $ENTITY,
			"__entity_underscore__" => mb_strtolower($ENTITY),
			"__namespace__"         => $namespace,
		];

		$this->printHelp();
		// die("<pre>" . print_r($this->replaceArray, true));
	}

	protected function getDestinationPath()
	{
		return Application::getDocumentRoot() .
			$this->modulePath .
			"/lib{$this->pathPrefix}/{$this->entityName}";
	}

}