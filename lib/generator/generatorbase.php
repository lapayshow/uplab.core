<?php
/**
 * Created by PhpStorm.
 * User: geffest
 * Date: 19.06.2018
 * Time: 16:00
 */

namespace Uplab\Core\Generator;


class GeneratorBase
{
	/** change this moduleId if necessary */
	const MODULE_ID = "uplab.core";

	protected $currentModuleSrc;
	protected $currentModulePath;
	protected $replaceArray;
	protected $destinationPath;

	/** change this path */
	protected $tplPath = "__MODULE_PATH__/include/tpl";

	public function __construct()
	{
		$this->currentModuleSrc = getLocalPath("modules/" . static::MODULE_ID);
		$this->currentModulePath = $_SERVER["DOCUMENT_ROOT"] . $this->currentModuleSrc;
		$this->tplPath = str_replace("__MODULE_PATH__", $this->currentModulePath, $this->tplPath);

		$this->prepare();
		$this->putFiles();
	}

	// public static function generate()
	// {
	// 	return new static();
	// }

	protected function prepare()
	{
		/** write your code BEFORE! parent function call */
		$this->replaceArray = $this->prepareReplaceArray();
	}

	protected function prepareReplaceArray()
	{
		/** put your array here */
		return [];
	}

	protected function getStructure()
	{
		/** @noinspection PhpIncludeInspection */
		return include $this->tplPath . "/__structure.php" ?: [];
	}

	protected function getReplacedContent($content)
	{
		$content = str_replace(
			array_keys($this->replaceArray),
			array_values($this->replaceArray),
			$content
		);

		return $content;
	}

	protected function prepareFileContent($file)
	{
		return $this->getReplacedContent(file_get_contents("{$this->tplPath}/{$file}"));
	}

	protected function putFiles()
	{
		if (empty($this->destinationPath)) return false;

		$structure = $this->getStructure();

		foreach ($structure as $src => $dest) {
			$src = str_replace(array_keys($this->replaceArray), $this->replaceArray, $src);
			$dest = str_replace(array_keys($this->replaceArray), $this->replaceArray, $dest);

			$destPath = "{$this->destinationPath}/{$dest}";
			if (file_exists($destPath) && filesize($destPath)) continue;

			$pathInfo = pathinfo($destPath);
			mkdir($pathInfo["dirname"], 0777, true);

			file_put_contents(
				$destPath,
				$this->prepareFileContent($src)
			);
		}

		return true;
	}
}