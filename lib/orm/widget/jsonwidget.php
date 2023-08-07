<?

namespace Uplab\Core\Orm\Widget;


use DigitalWand\AdminHelper\Widget\TextAreaWidget;


class JsonWidget extends TextAreaWidget
{
	public static function prepareToOutput($string, $hideTags = false)
	{
		return !empty($string) ? json_encode($string, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : "";
	}

	public function processEditAction()
	{
		parent::processEditAction();

		$this->setValue(json_decode($this->getValue(), true));
	}
}