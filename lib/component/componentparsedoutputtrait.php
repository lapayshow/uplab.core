<?php


namespace Uplab\Core\Component;


/**
 * Методы для парсинга результатов работы других компонентов
 * Trait ComponentParsedOutputTrait
 *
 * @deprecated
 * @package Uplab\Core\Component
 */
trait ComponentParsedOutputTrait
{

	public static function outputComponentResult($data)
	{
		echo "<!-- JSON:start -->";
		echo json_encode($data);
		echo "<!-- JSON:end -->";
	}

	public function setDataTarget($name)
	{
		// У нас есть колллбэк, который будет отправлен в обработчик для функции ob_start;
		// Этот коллбэк получает содержимое буферифированной области после завершения буфера.
		// В коллбэке мы парсим JSON, записываем результат в arParams и возвращаем пустую строк
		$callback = [$this, "addParsedData"];

		ob_start(function ($content) use ($name, $callback) {
			call_user_func($callback, $name, $content);

			return "";
		});
	}

	public function endDataTarget()
	{
		ob_end_clean();
	}

	public function addParsedData($name, $content)
	{
		$jsonContent = $content;

		$jsonContent = explode("<!-- JSON:start -->", $jsonContent);
		$jsonContent = array_pop($jsonContent);

		$jsonContent = explode("<!-- JSON:end -->", $jsonContent);
		$jsonContent = array_shift($jsonContent);

		$jsonContent = trim($jsonContent);

		// preg_match("~<!-- JSON:start -->(.+)<!-- JSON:end -->~", $content, $matches);

		$this->arResult["TEMPLATE_DATA"][$name] = json_decode($jsonContent, true);
		$this->arResult["TEMPLATE_DATA"]["~{$name}"] = $content;
	}


}