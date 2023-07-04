<?
defined("B_PROLOG_INCLUDED") && B_PROLOG_INCLUDED === true || die();
/**
 * @global CMain $APPLICATION
 * @var array    $arColumns
 * @var array    $arValue
 * @var string   $inpName
 * @var string   $inpDescriptionName
 * @var string   $inpDescriptionValue
 */
?>


<thead class="up-edit-head">
<tr>
	<td colspan="<?= count($arColumns) ?>">
		<? if ($inpDescriptionName): ?>
			<label for="<?= $inpDescriptionName ?>"
			       style="display: block;margin-bottom: 0;color: #000;"
			       title="Описание значения свойства">
				<sup>Описание:</sup>
			</label>
			<input type="text"
			       style="width: 100%; box-sizing: border-box; margin-bottom: 20px;"
			       name="<?= $inpDescriptionName ?>"
			       value="<?= $inpDescriptionValue ?? "" ?>"
			       id="<?= $inpDescriptionName ?>">
		<? endif; ?>
	</td>
</tr>
<tr>
	<? foreach ($arColumns as $column): ?>
		<th>
			<span>
				<?= $column["NAME"] ?>
			</span>
		</th>
	<? endforeach; ?>
	<th><span>&nbsp;</span></th>
	<th><span>&nbsp;</span></th>
</tr>
</thead>

<?
$randId = randString(6);
$tableId = "up-edit-table" . $randId;
$hiddenInput = "HIDDEN_INP_{$randId}";
?>

<tbody id="<?= $tableId ?>" class="up-edit-table" data-sortable>

<? $i = 0; ?>
<? foreach ($arValue["VALUE"] as $value): ?>

	<tr>


		<? foreach ($arColumns as $column): ?>
			<?
			$val = $value[$column["CODE"]];
			$htmlInputTpl = "{$inpName}[{$column["CODE"]}][__i__]";
			$htmlInputName = "{$inpName}[{$column["CODE"]}][{$i}]";
			?>


			<? if ($column["TYPE"] == "textarea"): ?>
				<td style="text-align: center;">
					<label>
					<textarea cols="45"
					          rows="8"
					          name="<?= $htmlInputName ?>"
					          data-tpl="<?= $htmlInputTpl ?>"
					          class="text-input text-input_textarea"
					          style="width: 100%; min-width: 100px;"
					><?= $val ?></textarea>
					</label>
				</td>
				<? continue; ?>
			<? endif; ?>


			<? if ($column["TYPE"] == "checkbox"): ?>
				<td style="text-align: center;">
					<input type="hidden" name="<?= $htmlInputName ?>" value="N">
					<input type="checkbox"
					       name="<?= $htmlInputName ?>"
					       data-tpl="<?= $htmlInputTpl ?>"
					       value="Y"
						<?= $val == "Y" ? "checked" : "" ?>
					>
				</td>
				<? continue; ?>
			<? endif; ?>


			<? if ($column["TYPE"] == "checkbox"): ?>
				<td style="text-align: center;">
					<!--<input type="hidden" name="<? /*= "{$inpName}[{$column["CODE"]}][]" */ ?>" value="N">-->
					<input type="checkbox"
					       name="<?= $htmlInputName ?>"
					       data-tpl="<?= $htmlInputTpl ?>"
					       value="Y"
						<?= $val == "Y" ? "checked" : "" ?>
					>
				</td>
				<? continue; ?>
			<? endif; ?>


			<? // если предыдущие continue; не сработали, приходим сюда ?>
			<td style="<?= $column["TYPE"] == "element" ? "width:150px;" : "" ?>">
				<? if ($column["TYPE"] == "element"): ?>
					<?= '<div class="up-edit-table__element">' ?>
				<? endif; ?>
				<input class="text-input text-input_<?= $column["TYPE"] ?>"
				       type="text"
				       name="<?= $htmlInputName ?>"
				       data-tpl="<?= $htmlInputTpl ?>"

					<?
					if ($column["TYPE"] == "date") {
						echo " onclick='BX.calendar({node: this, field: this});' ";
						echo " value='";
						echo empty($val) ? "" : $val;
						echo "' ";
						echo " placeholder='Дата' ";
						echo " data-noclear ";
					} else {
						echo "value='{$val}'";
					}
					?>
				/>

				<? if ($column["TYPE"] == "element"): ?>
					<!--suppress JSUnresolvedFunction, JSUnresolvedVariable -->
					<input type="button"
					       class="up-table__btn_element"
					       value="..."
					       data-noclear
					       data-input="<?= $hiddenInput ?>"
					       onclick="jsUtils.OpenWindow('<?=
					       '/bitrix/admin/iblock_element_search.php?',
					       'lang=ru&IBLOCK_ID=&',
					       "n={$hiddenInput}"
					       // "k=";
					       ?>', 900, 700);">
					<span></span>
					<?= '</div>' ?>
				<? endif; ?>
			</td>


		<? endforeach; ?>


		<td class="arrow-cell"><span class="arrow"></span></td>


		<td>
			<input type="button"
			       class="up-remove-btn"
			       data-remove="tr"
			       data-noclear
			       value="X"
			>
		</td>


	</tr>


	<? $i++; ?>
<? endforeach; ?>
</tbody>


<tbody>
<tr>
	<td class="up-more-btn" colspan="<?= count($arColumns) + 1 ?>">
		<input type="button"
		       data-clone="#<?= $tableId ?> tr:last"
		       value="Добавить строку">
		<label>
			<input type="text"
			       style="display: none;"
			       name="<?= $hiddenInput ?>"
			       id="<?= $hiddenInput ?>">
			<span style="display: none;" id="sp_<?= $hiddenInput ?>"></span>
		</label>
	</td>
</tr>
</tbody>


<!--suppress ES6ConvertVarToLetConst, JSUnresolvedFunction -->
<script>
	$(function () {
		var $sortable = $("[data-sortable]");
		$sortable.sortable({
			placeholder: "ui-state-highlight"
		});
		$sortable.disableSelection();
	});
</script>