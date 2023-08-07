<?php
/**
 * Created by Artmix.
 * User: Oleg Maksimenko <oleg.39style@gmail.com>
 * Date: 02.06.2016. Time: 14:14
 */

namespace Uplab\Core\Properties;


use Bitrix\Main\EventManager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\FileInputUtility;
use CDatabase;
use CLang;
use CUserTypeDate;
use CUserTypeDateTime;
use CUserTypeEnum;
use CUserTypeFile;
use Uplab\Core\Helper;


Loc::loadMessages(__FILE__);


/**
 * Class UserTypeFileExt
 *
 * Класс скопирован из: https://marketplace.1c-bitrix.ru/solutions/artmix.usertypefileext/
 */
class UserTypeFileExt extends CUserTypeFile
{
	const WITH_DESCRIPTION = "Выводить поле для описания значения";

	public static function bindEvents()
	{
		// Какой-то костыль, потому что перестала работать привязка к элементу
		if (strpos($_SERVER["REQUEST_URI"], "iblock_element_search.php") !== false) {
			return;
		}

		/** @noinspection PhpIncludeInspection */
		require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/iblock/admin_tools.php';

		$event = EventManager::getInstance();

		$event->addEventHandler("main", "OnUserTypeBuildList", [self::class, "getUserTypeDescription"]);
	}

	/**
	 * @param $tmpFileName
	 *
	 * @return string
	 */
	static function getTmpFilePath($tmpFileName)
	{
		$io = \CBXVirtualIo::GetInstance();
		$docRoot = \Bitrix\Main\Application::getDocumentRoot();

		if (strpos($tmpFileName, \CTempFile::GetAbsoluteRoot()) === 0) {
			$tempFilePath = $tmpFileName;
		} elseif (strpos($io->CombinePath($docRoot, $tmpFileName), \CTempFile::GetAbsoluteRoot()) === 0) {
			$tempFilePath = $io->CombinePath($docRoot, $tmpFileName);
		} else {
			$tempFilePath = $io->CombinePath(\CTempFile::GetAbsoluteRoot(), $tmpFileName);
		}

		return $tempFilePath;
	}

	protected static function getFieldValue($arUserField, $arAdditionalParameters = array())
	{
		if (!$arAdditionalParameters["bVarsFromForm"]) {
			if ($arUserField["ENTITY_VALUE_ID"] <= 0) {
				switch ($arUserField['USER_TYPE_ID']) {
					case CUserTypeDate::USER_TYPE_ID:
					case CUserTypeDateTime::USER_TYPE_ID:

						$full = $arUserField['USER_TYPE_ID'] === CUserTypeDateTime::USER_TYPE_ID;
						if ($arUserField["SETTINGS"]["DEFAULT_VALUE"]["TYPE"] == "NOW") {
							$value = $full
								? \ConvertTimeStamp(time() + \CTimeZone::getOffset(), "FULL")
								: \ConvertTimeStamp(time(), 'SHORT');
						} else {
							$value = $full ?
								str_replace(
									" 00:00:00",
									"",
									CDatabase::formatDate(
										$arUserField["SETTINGS"]["DEFAULT_VALUE"]["VALUE"],
										"YYYY-MM-DD HH:MI:SS",
										CLang::getDateFormat("FULL")
									)
								) :
								CDatabase::formatDate(
									$arUserField["SETTINGS"]["DEFAULT_VALUE"]["VALUE"],
									$full ? '' : "YYYY-MM-DD", CLang::getDateFormat('SHORT')
								);
						}

						break;

					case CUserTypeEnum::USER_TYPE_ID:

						$value = $arUserField['MULTIPLE'] === 'Y' ? array() : null;
						foreach ($arUserField['ENUM'] as $enum) {
							if ($enum['DEF'] === 'Y') {
								if ($arUserField['MULTIPLE'] === 'Y') {
									$value[] = $enum['ID'];
								} else {
									$value = $enum['ID'];
									break;
								}
							}
						}

						break;

					default:

						$value = $arUserField["SETTINGS"]["DEFAULT_VALUE"];

						break;
				}
			} else {
				$value = $arUserField["VALUE"];
			}
		} else {
			$value = $_REQUEST[$arUserField["FIELD_NAME"]];
		}

		return static::normalizeFieldValue($value);
	}

	protected static function normalizeFieldValue($value)
	{
		if (!is_array($value)) {
			$value = array($value);
		}
		if (empty($value)) {
			$value = array(null);
		}

		return $value;
	}

	/**
	 * @param array $arUserField
	 * @param array $arHtmlControl
	 *
	 * @return string
	 */
	protected static function getEditFormHTMLBase($arUserField, $arHtmlControl)
	{
		ob_start();

		\_ShowFilePropertyField(
			(
			$arHtmlControl['NAME'] = str_replace('[]', '', $arHtmlControl['NAME'])

			),
			array(
				'MULTIPLE'         => $arUserField['MULTIPLE'],
				'WITH_DESCRIPTION' => $arUserField['SETTINGS']['WITH_DESCRIPTION'],
				'FILE_TYPE'        => $arUserField['SETTINGS']['EXTENSIONS'],
			),
			(!is_array($arHtmlControl['VALUE']) && !empty($arHtmlControl['VALUE'])
				? array($arHtmlControl['VALUE'])
				: $arHtmlControl['VALUE'])
		);

		$result = ob_get_clean();

		return $result;
	}

	/**
	 * @return array
	 */
    public static function getUserTypeDescription()
    {
        // die(1);
        // echo "123";
        // return;

        return array(
            'USER_TYPE_ID' => 'file_ext_uplab',
            'CLASS_NAME'   => __CLASS__,
            'DESCRIPTION'  => 'Файл с поддержкой Drag & Drop',
            'BASE_TYPE'    => 'file',
        );
    }

	/**
	 * @param array $arUserField
	 *
	 * @return array
	 */
	function prepareSettings($arUserField)
	{
		$settings = parent::PrepareSettings($arUserField);
		$settings['WITH_DESCRIPTION'] = isset($arUserField['SETTINGS']['WITH_DESCRIPTION'])
		&& in_array($arUserField['SETTINGS']['WITH_DESCRIPTION'], array('N', 'Y'))
			? $arUserField['SETTINGS']['WITH_DESCRIPTION']
			: 'N';

		return $settings;
	}

	/**
	 * @param array $arUserField
	 * @param array $arHtmlControl
	 *
	 * @return string
	 */
	function getEditFormHTML($arUserField, $arHtmlControl)
	{
		return static::getEditFormHTMLBase($arUserField, $arHtmlControl);
	}

	/**
	 * @param $arUserField
	 * @param $arHtmlControl
	 *
	 * @return string
	 */
	function getEditFormHTMLMulty($arUserField, $arHtmlControl)
	{
		return static::getEditFormHTMLBase($arUserField, $arHtmlControl);
	}

	public function onBeforeSave(
		$userField,
		$value
	) {
		$valueCheck = self::getFieldValue($userField, ['bVarsFromForm' => true]);

		if (reset($valueCheck)) {
			$r = static::OnBeforeSaveAll($userField, $value);
		} else {
			$filesData = reset($value);

			$filesData['MODULE_ID'] = Helper::MODULE_ID;

			$r = parent::OnBeforeSave($userField, $filesData);
		}

		return $r;
	}

	public function checkFields($arUserField, $value)
	{
		$aMsg = [];
		$valueCheck = self::getFieldValue($arUserField, ['bVarsFromForm' => true]);

		if (reset($valueCheck)) {
			$value = $valueCheck;

			if ($arUserField['MULTIPLE'] != 'Y') {
				$value = reset($valueCheck);
			}
		}

		if (!is_array($value)) {
			if ($value > 0) {
				/** @var FileInputUtility $fileInputUtility */
				$fileInputUtility = FileInputUtility::instance();
				/** @noinspection PhpUndefinedMethodInspection */
				$controlId = $fileInputUtility->getUserFieldCid($arUserField);

				if ($value > 0) {
					$checkResult = $fileInputUtility->checkFiles($controlId, array($value));

					if (!in_array($value, $checkResult)) {
						$value = false;
					}
				}

				if ($value > 0) {
					/** @noinspection PhpUndefinedMethodInspection */
					$delResult = $fileInputUtility->checkDeletedFiles($controlId);
					if (in_array($value, $delResult)) {
						$value = false;
					}
				}

				/** @noinspection PhpUndefinedMethodInspection */
				$checkResult = $fileInputUtility->checkFiles(
					$fileInputUtility->getUserFieldCid($arUserField),
					array($arUserField['ID'])
				);

				if (!in_array($value, $checkResult)) {
					$aMsg[] = array(
						"id"   => $arUserField["FIELD_NAME"],
						"text" => GetMessage("FILE_BAD_TYPE"),
					);
				}
			}

		}

		if (is_array($value)) {

			if ($arUserField["SETTINGS"]["MAX_ALLOWED_SIZE"] > 0 && $value["size"] > $arUserField["SETTINGS"]["MAX_ALLOWED_SIZE"]) {
				$aMsg[] = array(
					"id"   => $arUserField["FIELD_NAME"],
					"text" => GetMessage("USER_TYPE_FILE_MAX_SIZE_ERROR",
						array(
							"#FIELD_NAME#"       => $arUserField["EDIT_FORM_LABEL"],
							"#MAX_ALLOWED_SIZE#" => $arUserField["SETTINGS"]["MAX_ALLOWED_SIZE"],
						)
					),
				);
			}

			//Extention check
			if (is_array($arUserField["SETTINGS"]["EXTENSIONS"]) && count($arUserField["SETTINGS"]["EXTENSIONS"])) {
				foreach ($arUserField["SETTINGS"]["EXTENSIONS"] as $ext => $tmp_val) {
					$arUserField["SETTINGS"]["EXTENSIONS"][$ext] = $ext;
				}
				$error = \CFile::CheckFile($value, 0, false, implode(",", $arUserField["SETTINGS"]["EXTENSIONS"]));
			} else {
				$error = "";
			}

			if (strlen($error)) {
				$aMsg[] = array(
					"id"   => $arUserField["FIELD_NAME"],
					"text" => $error,
				);
			}

			//For user without edit php permissions
			//we allow only pictures upload
			global $USER;
			if (!is_object($USER) || !$USER->IsAdmin()) {
				if (HasScriptExtension($value["name"])) {
					$aMsg[] = array(
						"id"   => $arUserField["FIELD_NAME"],
						"text" => GetMessage("FILE_BAD_TYPE") . " (" . $value["name"] . ").",
					);
				}
			}
		}

		return $aMsg;
	}

	public function onBeforeSaveAll(
		/** @noinspection PhpUnusedParameterInspection */
		$arUserField, $value
	) {

		$delFilesData =
			isset($_POST[$arUserField['FIELD_NAME'] . '_del']) && is_array($_POST[$arUserField['FIELD_NAME'] . '_del']) ?
				$_POST[$arUserField['FIELD_NAME'] . '_del'] :
				array();

		$descriptionFilesData =
			isset($_POST[$arUserField['FIELD_NAME'] . '_descr']) && is_array($_POST[$arUserField['FIELD_NAME'] . '_descr']) ?
				$_POST[$arUserField['FIELD_NAME'] . '_descr'] :
				array();

		$filesData = self::getFieldValue($arUserField, ['bVarsFromForm' => true]);

		$files = array();

		foreach ($filesData as $key => $fileData) {

			$delFile = false;

			if (is_array($fileData)) {

				$tempFile = static::getTmpFilePath($fileData['tmp_name']);
				if (isset($fileData['error']) && $fileData['error'] > 0) {

				} else {
					$files[] = \CFile::SaveFile(
						array_merge(
							$fileData,
							array(
								'tmp_name'  => $tempFile,
								'MODULE_ID' => Helper::MODULE_ID,
							)
						),
						'uf'
					);

				}

				@unlink($tempFile);

				@rmdir(dirname($tempFile));

			} elseif (intval($fileData) > 0) {

				if (isset($delFilesData[$key])) {
					\CFile::Delete($fileData);

					$delFile = true;
				} else {
					$files[] = $fileData;
				}

			}

			if (
				!$delFile
				&& !is_array($fileData)
				&& intval($fileData) > 0
				&& isset($descriptionFilesData[$key])
				&& strlen(trim($descriptionFilesData[$key]))
			) {
				\CFile::UpdateDesc($fileData, trim($descriptionFilesData[$key]));
			}

		}

		$files = array_values(
			array_filter(
				array_map('trim', $files)
			)
		);

		if ($arUserField['MULTIPLE'] != 'Y') {
			$files = reset($files);
		}

		return $files;

	}

	/**
	 * @param bool|false $arUserField
	 * @param            $arHtmlControl
	 * @param            $bVarsFromForm
	 *
	 * @return string
	 */
	public function getSettingsHTML($arUserField = false, $arHtmlControl = [], $bVarsFromForm = true)
	{
		//        $result = parent::GetSettingsHTML($arUserField, $arHtmlControl, $bVarsFromForm);

		$result = '';

		if ($bVarsFromForm) {
			$value = htmlspecialcharsbx($GLOBALS[$arHtmlControl['NAME']]['EXTENSIONS']);
			$result .= '
			<tr>
				<td>' . Loc::getMessage('USER_TYPE_FILE_EXTENSIONS') . ':</td>
				<td>
					<input type="text" size="20" name="' . $arHtmlControl['NAME'] . '[EXTENSIONS]" value="' . $value . '">
				</td>
			</tr>
			';
		} else {
			if (is_array($arUserField)) {
				$arExt = $arUserField['SETTINGS']['EXTENSIONS'];
			} else {
				$arExt = '';
			}

			$value = array();

			if (is_array($arExt)) {
				foreach ($arExt as $ext => $flag) {
					$value[] = htmlspecialcharsbx($ext);
				}
			}

			$result .= '
			<tr>
				<td>' . Loc::getMessage('USER_TYPE_FILE_EXTENSIONS') . ':</td>
				<td>
					<input type="text" size="20" name="' . $arHtmlControl['NAME'] . '[EXTENSIONS]" value="' . implode(', ', $value) . '">
				</td>
			</tr>
			';
		}


		if ($bVarsFromForm) {
			$withDescription = ($GLOBALS[$arHtmlControl['NAME']]['WITH_DESCRIPTION'] == 'Y');
		} elseif (is_array($arUserField)) {
			$withDescription = ($arUserField['SETTINGS']['WITH_DESCRIPTION'] == 'Y');
		} else {
			$withDescription = false;
		}

		$result .= '
		<tr>
			<td><label>' . self::WITH_DESCRIPTION . '</label></td>
			<td>
				<input type="hidden" name="' . $arHtmlControl['NAME'] . '[WITH_DESCRIPTION]" value="N">
				<input type="checkbox" name="' . $arHtmlControl['NAME'] . '[WITH_DESCRIPTION]" value="Y"' . ($withDescription ? ' checked' : '') . '>
			</td>
		</tr>
		';

		return $result;
	}

	/**
	 * @param array $arUserField
	 * @param array $arHtmlControl
	 *
	 * @return string
	 */
	function getAdminListEditHTMLMulty($arUserField, $arHtmlControl)
	{
		return '';
	}

	/**
	 * @param array $arUserField
	 * @param array $arHtmlControl
	 *
	 * @return string
	 */
	function getAdminListEditHTML($arUserField, $arHtmlControl)
	{
		return '';
	}

	/**
	 * @param array $arUserField
	 * @param array $arHtmlControl
	 *
	 * @return string
	 */
	function getAdminListViewHTML($arUserField, $arHtmlControl)
	{
		return '';
	}

	/**
	 * @param array $arUserField
	 *
	 * @return string
	 */
	public function OnSearchIndex($arUserField)
	{
		return '';
	}

}