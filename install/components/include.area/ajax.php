<?
define('STOP_STATISTICS', true);
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC', 'Y');
define('DisableEventsCheck', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

$siteId = isset($_REQUEST['SITE_ID']) && is_string($_REQUEST['SITE_ID']) ? $_REQUEST['SITE_ID'] : '';
$siteId = substr(preg_replace('/[^a-z0-9_]/i', '', $siteId), 0, 2);
if (!empty($siteId) && is_string($siteId)) {
	define('SITE_ID', $siteId);
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php');

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Security\Sign\Signer;
use Bitrix\Main\SystemException;
use Bitrix\Main\ObjectNotFoundException;
use Bitrix\Main\Application;
use Bitrix\Main\Entity;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Security;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web;
use Uplab\Core\Entities\Content\ContentTable;
use Uplab\Core\Helper;


try {
	$request = Application::getInstance()->getContext()->getRequest();
} catch (SystemException $e) {
	return;
}
$request->addFilter(new Web\PostDecodeFilter);

try {
	if (!Loader::includeModule('fileman') || !Loader::includeModule(Helper::MODULE_ID)) return;
} catch (LoaderException $e) {
	return;
}

Loc::loadMessages(dirname(__FILE__) . '/class.php');

$salt = ContentTable::getSalt();
$signer = new Signer;

try {
	$params = $signer->unsign($request->get('signedParamsString'), 'include.area' . $salt);
	$params = unserialize(base64_decode($params));
} catch (Security\Sign\BadSignatureException $e) {
	echo $e->getMessage();
	return;
} catch (ArgumentTypeException $e) {
	return;
}

if (!is_array($params)) {
	return;
}
$params['CACHE_TYPE'] = 'N';

$action = ToLower($request->get($params['CODE'] . '_ACTION'));
if (empty($action)) {
	return;
}

$params['FIELD_CODE'] = preg_replace("/[^a-zA-Z0-9_:\.]/is", "", $params['CODE']);

CBitrixComponent::includeComponentClass('uplab.core:include.area');


class IncludeAreaAjax extends IncludeAreaComponent
{
	/**
	 * выполяет действия перед кешированием
	 */
	protected function executeProlog()
	{

	}

	/**
	 * выполняет логику работы компонента
	 */
	public function executeComponent()
	{
		try {
			$this->checkModules();
			$this->checkParams();
			$this->executeProlog();
			$this->getResult();
			$this->executeEpilog();

			return $this->arResult;
		} catch (Exception $e) {
			$this->abortDataCache();
			ShowError($e->getMessage());
		}
	}

	/**
	 * выполняет действия после выполения компонента
	 */
	protected function executeEpilog()
	{
		$code = $this->arParams['CODE'];
		$fieldCode = $this->arParams['FIELD_CODE'] ?: $code;

		try {
			$request = Application::getInstance()->getContext()->getRequest();
		} catch (SystemException $e) {
			return;
		}
		$request->addFilter(new Web\PostDecodeFilter);

		$backUrl = $request->get('back_url');
		if (empty($backUrl)) {
			$backUrl = $this->arResult['PATH'];
		}
		$action = ToLower($request->get($fieldCode . '_ACTION'));
		$obContent = new ContentTable();

		$obResult = new Entity\Result();

		$useFields = $this->arParams['USE_FIELDS'] == 'Y' && !empty($this->arParams['FIELDS']);

		switch ($action) {
			case 'add':
			case 'edit':
				$confirmSave = $request->get($fieldCode . '_ACTION_SAVE') == 'Y';
				if ($confirmSave) {
					$spreadDeeper = $request->get($fieldCode . '_SPREAD_DEEPER');
					$content = $request->get($fieldCode . ($useFields ? '_FIELDS' : '_CONTENT'));

					if ($useFields) {
						if (!is_array($content)) {
							$content = [];
						} else {
							foreach ($content as $fieldCode => &$value) {
								$arField = $this->arParams['FIELDS'][$fieldCode];
								if (empty($arField)) {
									unset($content[$fieldCode]);
								} else {
									if ($arField['TYPE'] == 'checkbox') {
										$value = ToUpper($value) == 'Y' ? 'Y' : 'N';
									} else {
										if ($arField['TYPE'] == 'string') {
											$value = trim(strip_tags(htmlspecialchars_decode($value)));
										} else {
											if ($arField['TYPE'] == 'text') {
												$value = trim(htmlspecialchars_decode($value));
											}
										}
									}
								}
							}
							unset($value);
						}
					}

					if ($this->arResult['AREA_ID'] > 0) {
						$obResult = $obContent->UpdateEx($this->arResult['AREA_ID'], array(
							'UF_DATA' => $content,
							//'UF_SPREAD_DEEPER' => $spreadDeeper?1:0,
						));
					} else {
						$obResult = $obContent->AddEx(array(
							'UF_PATH'          => $this->arResult['PATH'],
							'UF_CODE'          => $code,
							'UF_TYPE'          => $useFields ? 'JSON' : 'PLAIN_TEXT',
							'UF_DATA'          => $content,
							'UF_SPREAD_DEEPER' => $spreadDeeper == 'Y' ? 1 : 0,
							'UF_SITE_ID'       => SITE_ID,
						));
					}
					if ($obResult->isSuccess()) {
						if (defined('BX_COMP_MANAGED_CACHE')) {
							global $CACHE_MANAGER;
							$CACHE_MANAGER->ClearByTag('ci_content');
						}

						$this->arResult['REDIRECT'] = $backUrl;
					}
				}
				break;
			case 'delete':
				$confirmDelete = $request->get($fieldCode . '_DELETE_CONFIRM') == 'Y';
				if ($confirmDelete && $this->arResult['AREA_ID'] > 0) {
					$obResult = $obContent->DeleteEx($this->arResult['AREA_ID']);
					if ($obResult->isSuccess()) {
						if (defined('BX_COMP_MANAGED_CACHE')) {
							global $CACHE_MANAGER;
							$CACHE_MANAGER->ClearByTag('ci_content');
						}

						$this->arResult['REDIRECT'] = $backUrl;
					}
				}
				break;
			default:
				break;
		}

		$this->arResult['SUCCESS'] = $obResult->isSuccess() ? 'Y' : 'N';
	}
}


$component = new IncludeAreaAjax();
try {
	$component->arParams = $component->onPrepareComponentParams($params);
} catch (ObjectNotFoundException $e) {
	return;
}
$arResult = $component->executeComponent();

$useFields = $component->arParams['USE_FIELDS'] == 'Y' && !empty($component->arParams['FIELDS']);

CUtil::InitJSCore(array('window', 'ajax'));
$popupWindow = new CJSPopup('', '');

$areaExists = false;
if (is_array($arResult) && !empty($arResult)) {
	$areaExists = !empty($arResult['AREA_EXISTS']) && $arResult['AREA_EXISTS'] == 'Y';
	if (in_array($action, array('edit', 'add'))) {
		if ($areaExists) {
			$action = 'edit';
			$popupWindow->ShowTitlebar(Loc::getMessage('UP_CORE_INCLUDE_AREA_TITLE_EDIT'));
		} else {
			$popupWindow->ShowTitlebar(Loc::getMessage('UP_CORE_INCLUDE_AREA_TITLE_ADD'));
		}
	} else {
		if ($action == 'delete') {
			$popupWindow->ShowTitlebar(Loc::getMessage('UP_CORE_INCLUDE_AREA_TITLE_DELETE'));
		}
	}

} else {
	$arResult = array();
}

if ($arResult['SUCCESS'] == 'Y' && !empty($arResult['REDIRECT'])) {
	$popupWindow->Close(true, $arResult['REDIRECT']);
}

$popupWindow->StartContent();

?>
	<input type="hidden" name="<?= $params['FIELD_CODE'] ?>_ACTION" value="<?= ToLower($action) ?>">
	<input type="hidden" name="signedParamsString" value="<?= $request->get('signedParamsString') ?>">
<?

if (in_array($action, array('edit', 'add'))) {
	?>
	<div class="adm-detail-content" id="edit1">
		<div class="adm-detail-content-item-block">
			<table class="adm-detail-content-table edit-table" id="edit1_edit_table">
				<tbody>
				<tr id="tr_CODE">
					<td class="adm-detail-content-cell-l"
					    style="width: 50%"><?= Loc::getMessage('UP_CORE_INCLUDE_AREA_FIELD_CODE') ?>:
					</td>
					<td class="adm-detail-content-cell-r"><?= $params['CODE'] ?></td>
				</tr>
				<tr id="tr_SPREAD_DEEPER">
					<td class="adm-detail-content-cell-l"
					    style="width: 50%"><?= Loc::getMessage('UP_CORE_INCLUDE_AREA_FIELD_SPREAD_DEEPER') ?>:
					</td>
					<td class="adm-detail-content-cell-r">
						<? if ($areaExists): ?>
							<?= $arResult['CONTENT']['SPREAD_DEEPER'] ? 'Да' : 'Нет' ?>
						<? else: ?>
							<input type="hidden" name="<?= $params['FIELD_CODE'] ?>_SPREAD_DEEPER" value="N">
							<input type="checkbox" name="<?= $params['FIELD_CODE'] ?>_SPREAD_DEEPER" value="Y"
							       id="designed_checkbox_0.628987401736399" class="adm-designed-checkbox">
							<label class="adm-designed-checkbox-label" for="designed_checkbox_0.628987401736399"
							       title=""></label>
						<? endif; ?>
					</td>
				</tr>
				</tbody>
			</table>
		</div>
	</div>

	<div class="adm-detail-content" id="edit2">
		<div class="adm-detail-content-item-block">
			<table class="adm-detail-content-table edit-table" id="edit2_edit_table">
				<tbody>
				<tr class="heading" id="tr_PREVIEW_TEXT_LABEL">
					<td colspan="2"><?= Loc::getMessage('UP_CORE_INCLUDE_AREA_FORM_SECTION_CONTENT') ?></td>
					<input type="hidden" name="<?= $params['FIELD_CODE'] ?>_ACTION_SAVE" value="Y">
				</tr>
				<? if ($useFields): ?>
					<? foreach ($component->arParams['FIELDS'] as $arField):
						$fieldTagName = $params['FIELD_CODE'] . '_FIELDS[' . $arField['CODE'] . ']';
						$arField['VALUE'] = $arResult['CONTENT']['DATA'][$arField['CODE']];
						?>
						<tr id="tr_FIELD_<?= $arField['CODE'] ?>">
							<td style="text-align: center">
								<?= $arField['NAME'] ?>
							</td>
							<td style="text-align: center">
								<?
								switch ($arField['TYPE']) {
									case 'text':
										?>
										<textarea name="<?= $fieldTagName ?>" cols="40"
										          rows="7"><?= $arField['VALUE'] ?></textarea>
										<?
										break;
									case 'checkbox':
										?>
										<input type="hidden" name="<?= $fieldTagName ?>" value="N">
										<input type="checkbox" name="<?= $fieldTagName ?>" value="Y"
										       id="designed_checkbox_<?= $arField['CODE'] ?>"
										       class="adm-designed-checkbox"<?= ($arField['VALUE'] == 'Y') ? ' checked="checked"' : '' ?>>
										<label class="adm-designed-checkbox-label"
										       for="designed_checkbox_<?= $arField['CODE'] ?>" title=""></label>
										<?
										break;
									default:
										//case 'string':
										?>
										<input type="text" name="<?= $fieldTagName ?>" size="30" maxlength="50"
										       value="<?= $arField['VALUE'] ?>">
										<?
										break;
								}
								?>
							</td>
						</tr>
					<? endforeach; ?>
				<? else: ?>
					<tr id="tr_PREVIEW_TEXT_EDITOR">
						<td colspan="2" style="text-align: center">
							<? CFileMan::AddHTMLEditorFrame(
								$params['FIELD_CODE'] . '_CONTENT',
								$arResult['CONTENT']['DATA'],
								"CONTENT_TEXT_TYPE",
								'html',
								array(
									'height' => 450,
									'width'  => '100%',
								),
								"N",
								0,
								"",
								"",
								SITE_ID,
								true,
								false,
								array(
									'toolbarConfig' => CFileMan::GetEditorToolbarConfig('public'),
								)
							);
							?>
						</td>
					</tr>
				<? endif; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?

} else {
	if ($action == 'delete') {
		?>
		<div class="adm-detail-content" id="edit1">
			<div class="adm-detail-content-item-block">
				<table class="adm-detail-content-table edit-table" id="edit1_edit_table">
					<tbody>
					<tr id="tr_PREVIEW_TEXT_EDITOR">
						<td colspan="2" style="text-align: center;">
							<? if ($areaExists): ?>
								<p>
									<?= Loc::getMessage('UP_CORE_INCLUDE_AREA_DIALOG_DELETE_CONFIRM_TEXT') ?>
									<? if ($arResult['CONTENT']['SPREAD_DEEPER']): ?>
										<br>
										<?= Loc::getMessage('UP_CORE_INCLUDE_AREA_DIALOG_DELETE_CONFIRM_TEXT_WARNING') ?> <?= $arResult['CONTENT']['PATH'] ?>
									<?endif; ?>
								</p>
								<br>
								<input type="hidden" name="<?= $params['FIELD_CODE'] ?>_DELETE_CONFIRM" value="Y">
								<input class="adm-btn" type="submit"
								       onclick="<? echo $popupWindow->jsPopup ?>.PostParameters();" value="Принять">
							<? else: ?>
								<p><?= Loc::getMessage('UP_CORE_INCLUDE_AREA_DIALOG_CONTENT_NOT_FOUND') ?></p>
							<?endif; ?>
						</td>
					</tr>
					</tbody>
				</table>
			</div>
		</div>
		<?
	}
}

$popupWindow->EndContent();

$popupWindow->StartButtons();

if ($action != 'delete') {
	?>
	<input type="submit" id="savebtn" name="savebtn" value="<? echo GetMessage("admin_lib_sett_save") ?>"
	       onclick="if((typeof window.BXHtmlEditor !== 'undefined') && (window.BXHtmlEditor.editors['<? echo $params['FIELD_CODE'] . '_CONTENT' ?>'])){
			       window.BXHtmlEditor.editors['<? echo $params['FIELD_CODE'] . '_CONTENT' ?>'].OnSubmit();<? echo $popupWindow->jsPopup ?>.PostParameters();
			       }else{
	       <? echo $popupWindow->jsPopup ?>.PostParameters();
			       }" class="adm-btn-save">
	<?
}

$popupWindow->ShowStandardButtons(array('close'));

$popupWindow->EndButtons();


require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
