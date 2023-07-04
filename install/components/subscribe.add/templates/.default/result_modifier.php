<? defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();

/**
 * @var array $arParams
 * @var array $arResult
 */

$arResult["TEMPLATE_DATA"]["subscribe"] = [
	"title"         => \Bitrix\Main\Localization\Loc::getMessage("SUBSCRIBE.TITLE"),
	"text"          => \Bitrix\Main\Localization\Loc::getMessage("SUBSCRIBE.TEXT"),
	"text_done"     => "вы подписались на новости нашей компании",
	"action"        => "",
	"hidden_inputs" => implode(
		"\n",
		[
			bitrix_sessid_post(),
			'<input type="hidden" name="SITE_ID" value="' . SITE_ID . '">',
		]
	),
	"method"        => "post",
	"button"        => [
		"modificator" => ["compute"],
		"text"        => \Bitrix\Main\Localization\Loc::getMessage("SUBSCRIBE.SEND"),
		"type"        => "submit",
	],
	"field"         => [
		"input"        => true,
		"autocomplete" => "off",
		"date"         => false,
		"type"         => "email",
		"id"           => "email",
		"name"         => "email",
		"label"        => "E-mail",
		"required"     => true,
		"disabled"     => false,
		"readonly"     => false,
		"reset"        => true,
	],
];
