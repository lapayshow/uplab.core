<?
$MESS["relatedFromOrm_PROPERTY_NAME"] = "Свойство «Привязка к Элементу из ORM»";

$MESS["relatedFromOrm_SETTINGS_HTML"] = <<<HTML
<tr valign="top">
		<td>Класс AdminInterface для отображения элементов сущности</td>
		<td><input type="text" size="50" name="#CONTROL_NAME#[className]" value="#className#"></td>
	</tr>
	<tr valign="top">
		<td>Поле привязки</td>
		<td><input type="text" size="50" name="#CONTROL_NAME#[relatedField]" value="#relatedField#"></td>
	</tr>
	<tr valign="top">
		<td>Подпись кнопки добавления</td>
		<td><input type="text" size="50" name="#CONTROL_NAME#[addButton]" value="#addButton#"></td>
	</tr>
	<tr valign="top">
		<td>Подпись кнопки списка</td>
		<td><input type="text" size="50" name="#CONTROL_NAME#[listButton]" value="#listButton#"></td>
	</tr>
HTML;
