<?

namespace Uplab\Core\Data\Type;


use Bitrix\Main\Context;
use Bitrix\Main\Type\DateTime as BxDateTime;


class DateTime extends BxDateTime
{

	/**
	 * Расширяет стандартный метод createFromUserTime,
	 * позволяет отправляеть на вход любое время, которое поймет strtotime()
	 *
	 * @param string $timeString
	 *
	 * @return BxDateTime
	 */
	public static function createFromUserTime($timeString)
	{
		$format = self::getFormat(Context::getCurrent()->getCulture());
		$timeString = date($format, strtotime($timeString));

		return parent::createFromUserTime($timeString);
	}

}