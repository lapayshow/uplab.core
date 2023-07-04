<?php
/**
 * Created by PhpStorm.
 * User: geffest
 * Date: 09.08.2018
 * Time: 16:00
 */

namespace Uplab\Core\Generator;


use Uplab\Core\Data\StringUtils;


class ComponentGenerator extends GeneratorBase
{
	protected $tplPath = "__MODULE_PATH__/include/tpl/component";
	protected $destinationFolder;
	protected $destinationModule;
	protected $componentNameLang;
	protected $componentId;
	protected $componentClass;

	/**
	 * Generate constructor.
	 *
	 * @param string $componentId       Код компонента (название папки)
	 * @param string $componentNameLang Название компонента на русском языке
	 * @param string $destinationFolder Название модуля, где будет располагаться компонент
	 *                                  Важно: можно указать название, используя CamelCase,
	 *                                  тогда название класса будет более читаемым
	 */
	public function __construct($componentId, $componentNameLang, $destinationFolder)
	{
		$this->destinationFolder = $destinationFolder;
		$this->componentNameLang = $componentNameLang;
		$this->componentId = $componentId;
		parent::__construct();
	}

	public static function generate($componentId, $componentNameLang, $destinationFolder)
	{
		return new static($componentId, $componentNameLang, $destinationFolder);
	}

	protected function prepare()
	{
		if (true) {

			$this->destinationModule = mb_strtolower($this->destinationFolder);

			$this->componentClass = trim(strtoupper(
				StringUtils::convertCamelCaseToUnderScore(
					preg_replace("~\W+~", "_",
						implode("_", [
							$this->destinationFolder,
							$this->componentId,
							"component",
						])
					)
				)
			), "_");
			$this->componentClass = StringUtils::convertUnderScoreToCamelCase($this->componentClass, true, true);

			$this->destinationPath = mb_strtolower(
				$_SERVER["DOCUMENT_ROOT"] .
				"/local/components/" .
				$this->destinationFolder . "/" .
				$this->componentId
			);

		} else return;

		// родительский метод вызывается только
		// при корректно заполненных параметрах
		parent::prepare();
	}


	protected function prepareReplaceArray()
	{
		return [
			"__COMPONENT_CLASS__" => $this->componentClass,
			"__COMPONENT_NAME__"  => $this->componentNameLang,
			// используется, чтобы в @property $dependModules получить пустой массив
			"\"__MODULE_ID__\""   => $this->destinationModule ? "\"$this->destinationModule\"" : "",
			"__MODULE_ID__"       => $this->destinationModule ?: "",
			"__DATE__"            => date("Y-m-d H:i:s"),
		];
	}
}