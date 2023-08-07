<?

namespace Uplab\Core\Data\Search;


use Bitrix\Main\Entity;
use Bitrix\Main\Type;
use Uplab\Core\Data\Cache;
use Uplab\Core\Data\Type\DateTime;
use Uplab\Core\Orm;


/**
 * Created by PhpStorm.
 * User: geffest
 * Date: 2019-07-11
 * Time: 17:49
 */
class SearchPhraseTable extends Entity\DataManager
{
	private static $moduleId        = "uplab.core";
	private static $timestampColumn = true;

	use Orm\OrmTrait;

	public static function getTableName()
	{
		return "b_search_phrase";
	}

	public static function getMap()
	{
		/** @noinspection PhpUnhandledExceptionInspection */
		return [
			new Entity\IntegerField("ID", [
				"primary"      => true,
				"autocomplete" => true,
			]),
			new Entity\DateTimeField("TIMESTAMP_X", array(
				"title"         => "Дата изменения",
				"default_value" => new Type\DateTime,
			)),

			new Entity\StringField("PHRASE", [
				"title"    => "Phrase",
				"required" => true,
			]),

			new Entity\IntegerField("RESULT_COUNT", [
				"title" => "Количество результатов",
			]),

			new Entity\StringField("SITE_ID", [
				"title" => "ID сайта",
			]),
			new Entity\ReferenceField(
				"SITE", \Bitrix\Main\SiteTable::class,
				["=this.SITE_ID" => "ref.LID"],
				["title" => "Привязка к сайту"]
			),
		];
	}

	/**
	 * Возвращает часто используемые поисковые фразы
	 *
	 * @param int  $count Колличество фраз
	 * @param int  $minLength
	 * @param bool $cache
	 *
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function getFreqPhrases($count = 6, $minLength = 3, $cache = true)
	{
		if ($cache === true) {
			return Cache::cacheMethod(__METHOD__, [
				"arguments" => [$count, $minLength, false],
				"tags"      => [],
			]);
		}

		$_phrases = self::getList([
			"limit"   => 50,
			"filter"  => [
				">RESULT_COUNT" => 1,
				"SITE_ID"       => SITE_ID,
				">TIMESTAMP_X"  => DateTime::createFromUserTime("-2 months"),
			],
			"order"   => [
				// "CNT"          => "desc",
				"RESULT_COUNT" => "desc",
				"PHRASE"       => "asc",
				"TIMESTAMP_X"  => "desc",
			],
			"group"   => "PHRASE",
			"runtime" => array(
				new Entity\ExpressionField('CNT', 'COUNT(*)'),
			),
			"select"  => [
				"PHRASE",
				"CNT",
				"RESULT_COUNT",
			],
		])->fetchAll();

		$arStatistic = [];
		foreach ($_phrases as $phrase) {
			$phrase["COUNT"] = (int)$phrase["CNT"];
			unset($phrase["CNT"]);

			$strPhrase = strip_tags($phrase["PHRASE"]);
			$strPhrase = preg_replace("~['\"</\>]~", "", $strPhrase);
			$strPhrase = preg_replace("~[\+\s]+~", " ", $strPhrase);
			$phrase["PHRASE"] = $strPhrase;

			if (strlen($strPhrase) < $minLength) continue;

			if (isset($arStatistic[$strPhrase])) {
				$arStatistic[$strPhrase]["COUNT"] = !empty($arStatistic[$strPhrase]["COUNT"])
					? $arStatistic[$strPhrase]["COUNT"]
					: 0;

				$arStatistic[$strPhrase]["COUNT"] += $phrase["COUNT"];
			} else {
				$arStatistic[$strPhrase] = $phrase;
			}
		}

		// usort($arStatistic, function ($a, $b) {
		// 	return ($b['COUNT'] - $a['COUNT']);
		// });

		$arStatistic = array_slice($arStatistic, 0, $count);

		return $arStatistic;
	}

}