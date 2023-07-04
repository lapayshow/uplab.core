<?
use Bitrix\Main\Web\Json;


defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 * @var array    $arParams
 * @var array    $arResult
 */
?>

<div>


	<form action="<?= $arResult["FORM_ACTION"] ?>" data-uplab-form method="post">

		<? if ($arResult["isFormErrors"] == "Y"): ?>
			<?= $arResult["FORM_ERRORS_TEXT"]; ?>
		<? endif; ?>

		<? if ($arResult["isFormAddOk"] == "Y"):?>
		<?endif;?>

		<?= $arResult["FORM_NOTE"] ?>

		<?= $arResult["~FORM_HEADER"]; ?>

		<? foreach ($arResult["QUESTIONS"] as $arQuestion) { ?>
			<div>
				<label><?= $arQuestion["CAPTION"] ?></label>
				<?= $arQuestion["HTML_CODE"]; ?>
			</div>
		<? } ?>

		<input type="submit" value="Сохранить">
        {{ form_rest(form) }}
	</form>

</div>

