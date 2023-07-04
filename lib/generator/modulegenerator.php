<?php
/**
 * Created by PhpStorm.
 * User: geffest
 * Date: 19.06.2018
 * Time: 16:00
 */

namespace Uplab\Core\Generator;


use Uplab\Core\Data\StringUtils;
use Uplab\Core\Helper;


/*
Пример вызова генератора:
Generator::generate("ModuleNamespace", "suffix");
*/


class ModuleGenerator extends GeneratorBase
{
	protected $CODE;
	protected $code;
	protected $moduleId;
	protected $moduleSuffix;
	protected $moduleNamespace;
	protected $moduleName;
	protected $moduleSrc;

	protected $tplPath = "__MODULE_PATH__/include/tpl/module";

	/**
	 * Generator constructor.
	 *
	 * @param string $codeInCamelCase Код вендора / проекта, первая часть нэймспейса модуля
	 * @param string $suffix
	 * @param bool   $standalone
	 */
	function __construct($codeInCamelCase = "", $suffix = "", $standalone = false)
	{
		$this->CODE = $codeInCamelCase;
		$this->moduleSuffix = $suffix;

		if ($standalone) {
			$this->tplPath .= ".standalone";
		}

		parent::__construct();
	}

	public static function generate($codeInCamelCase = "", $suffix = "", $standalone = false)
	{
		return new static($codeInCamelCase, $suffix, $standalone);
	}

	protected function prepare()
	{
		if (empty($this->CODE)) {
			$this->CODE = preg_replace("~\W~", "", Helper::getOption("project_code"));
		}
		if (empty($this->moduleSuffix)) {
			$this->moduleSuffix = preg_replace("~\W~", "", Helper::getOption("module_suffix")) ?: "tools";
		}

		$this->code = str_replace("\\", ".", strtolower($this->CODE));
		$this->CODE = ucfirst($this->CODE);

		if ($this->code && $this->moduleSuffix) {
			$this->moduleId = $this->code . "." . strtolower($this->moduleSuffix);
			$this->moduleName = "[{$this->CODE}] " . StringUtils::ucFirst($this->moduleSuffix);
			$this->moduleNamespace = "{$this->CODE}\\" . ucfirst($this->moduleSuffix);
			$this->moduleSrc = "/local/modules/{$this->moduleId}";

			$this->destinationPath = $_SERVER["DOCUMENT_ROOT"] . $this->moduleSrc;
			mkdir($this->destinationPath, 0777, true);
		} else return;

		// родительский метод вызывается только
		// при корректно заполненных параметрах
		parent::prepare();
	}

	protected function prepareReplaceArray()
	{
		return [
			"__MODULE_ID__"            => $this->moduleId,
			"__MODULE_ID_UNDERSCORE__" => str_replace(".", "_", $this->moduleId),
			"__MODULE_CODE_CAMEL__"    => $this->CODE,
			"__MODULE_NAME__"          => $this->moduleName,
			"__MODULE_NAMESPACE__"     => $this->moduleNamespace,
			"__DATE__"                 => date("Y-m-d H:i:s"),
		];
	}
}