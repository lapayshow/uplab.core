<?
defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 * @var array    $strHTMLControlName
 * @var array    $arParams
 * @var array    $arResult
 */

$types = [
	"text"     => "Текст",
	"textarea" => "Textarea",
	"date"     => "Дата",
	"checkbox" => "Checkbox",
	"element"  => "Привязка к элементу",
];
?>

<table class="internal up-table">
	<tbody>

	<tr class="heading">
		<td>Порядок</td>
		<td>Код столбца</td>
		<td>Заголовок столбца</td>
		<td>Тип значения</td>
	</tr>

	<?
	foreach ($arSettings["COLUMNS"] as $row): ?>
		<tr class="table-cols">
			<td>
				<label>
					<input type="text"
					       class="up-table__input"
					       data-noclear
					       size="5"
					       name="<?= $strHTMLControlName['NAME'] ?>[SORT][]"
					       value="<?= $row['SORT'] ?>">
				</label>
			</td>
			<td>
				<label>
					<input type="text"
					       class="up-table__input"
					       size="20"
					       maxlength="20"
					       name="<?= $strHTMLControlName['NAME'] ?>[CODE][]"
					       value="<?= $row['CODE'] ?>">
				</label>
			</td>
			<td>
				<label>
					<input type="text"
					       class="up-table__input"
					       size="30"
					       maxlength="20"
					       name="<?= $strHTMLControlName['NAME'] ?>[NAME][]"
					       value="<?= $row['NAME'] ?>">
				</label>
			</td>
			<td>
				<label>
					<select name="<?= $strHTMLControlName['NAME'] ?>[TYPE][]"
					        class="up-table__input typeselect"
					        style="width: 120px;">
						<? foreach ($types as $key => $type): ?>
							<option value="<?= $key ?>"
								<?= $key == $row["TYPE"] ? "selected" : "" ?>
							><?= $type ?></option>
						<? endforeach; ?>
					</select>
				</label>
			</td>
		</tr>
	<? endforeach; ?>

	</tbody>
</table>

<div style="width: 100%; text-align: center; margin: 10px 0;">
	<input class="adm-btn-big" type="button"
	       data-clone=".up-table .table-cols:last"
	       value="Добавить">
</div>