<?

namespace Uplab\Core\Module;


use Bitrix\Main\Web\Uri;
use CAdminTabControl;
use CControllerClient;
use Exception;
use Uplab\Core;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;
use Bitrix\Main\Localization\Loc;


class OptionsBase
{
    public $moduleId = "uplab.core";
    public $modulePrefix;
    public $tabsSettings = [];

    function __construct($filePath = false, $tabsSettings = false, $moduleId = null)
    {
        if (empty($filePath)) {
            $filePath =
                Application::getDocumentRoot() .
                getLocalPath("modules/{$this->moduleId}/options.php");
        }

        Loc::loadMessages($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/options.php");
        Loc::loadMessages($filePath);

        if (!empty($moduleId)) {
            $this->moduleId = $moduleId;
        }
        $this->modulePrefix = str_replace(".", "_", $this->moduleId);

        if ($tabsSettings !== false) {
            $this->tabsSettings = $tabsSettings;
        } else {
            $this->prepareTabs();
        }

        $this->postData();
    }

    public function updateModuleFiles()
    {
        $className = '\\' . $this->modulePrefix;
        /** @noinspection PhpIncludeInspection */
        if (!class_exists($className)) {
            /** @noinspection PhpIncludeInspection */
            include $_SERVER["DOCUMENT_ROOT"] .
                getLocalPath("modules/{$this->moduleId}") .
                "/install/index.php";
        }
        try {
            $module = new $className;
            if (is_callable([$module, "installFiles"])) {
                /** @noinspection PhpUndefinedMethodInspection */
                $module->installFiles();
            }
        } catch (Exception $e) {
        }
    }

    public function onPostEvents()
    {
        // сбрасываем кеш, помеченный тегом модуля (например, это кеш настроек)
        $cache = Application::getInstance()->getTaggedCache();
        $cache->clearByTag($this->moduleId);
    }

    public function getTabsArray()
    {
        return [];
    }

    private function drawSerializedRow($option, &$arControllerOption)
    {
        $type = $option[3];
        $disabled = isset($option[4]) && $option[4] == "Y" ? " disabled " : "";

        $fieldId = $this->moduleId . "_" . random_bytes(6);

        $val = Core\Helper::getOption($option[0], $this->moduleId);
        if ($val === "") {
            $val = $option[2];
        }
        $val = json_encode(
            unserialize($val),
            JSON_PRETTY_PRINT |
            JSON_PRETTY_PRINT |
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );
        ?>
        <tr>
            <td class="adm-detail-valign-top" width="50%">
                <label for="<?= $fieldId ?>"><?= $option[1] ?></label>
            </td>
            <td>
				<textarea rows="<?= $type[1] ?>"
                          cols="<?= $type[2] ?>"
                          name="<?= htmlspecialchars($option[0]) ?>"
                          id="<?= $fieldId ?>"
					<?
                    if (isset($arControllerOption[$option[0]])) {
                        echo " disabled title=\"" . Loc::getMessage("MAIN_ADMIN_SET_CONTROLLER_ALT") . "\"";
                    }
                    echo " {$disabled} ";
                    ?>><?= $val ?></textarea>
            </td>
        </tr>
        <?
    }

    public function drawTabOptions($optionsArray)
    {
        $arControllerOption = CControllerClient::GetInstalledOptions($this->moduleId);
        foreach ($optionsArray as $option) {
//            if (empty($option["ext_data"])) {
//                continue;
//            }
            if (!empty($option["ext_data"]["is_serialized"])) {
                if ($option["ext_data"]["is_serialized"] === true) {
                    $this->drawSerializedRow($option, $arControllerOption);
                }
            } elseif (!empty($option["print_row"]) && is_callable($option["print_row"])) {
                if (!$option["print_row_no_tr"]) {
                    echo "<tr><td colspan='2'>";
                }
                $option["print_row"]();
                if (!$option["print_row_no_tr"]) {
                    echo "</td></tr>";
                }
            } else {
                __AdmSettingsDrawRow($this->moduleId, $option);
            }
        }
    }

    public function drawOptionsForm()
    {
        global $APPLICATION;

        if (empty($this->tabsSettings)) {
            return;
        }

        $tabControl = new CAdminTabControl("tabControl", $this->tabsSettings);
        $tabControl->Begin();

        $formAction = (new Uri($APPLICATION->GetCurPage()))
            ->addParams(["mid" => $this->moduleId, "lang" => LANGUAGE_ID])
            ->getUri();
        ?>

        <form action="<?= $formAction ?>"
              name="<?= "{$this->modulePrefix}_form" ?>"
              class="up-core-settings"
              method="post">

            <?
            foreach ($this->tabsSettings as $arTab) {
                if (empty($arTab)) {
                    continue;
                }
                $tabControl->BeginNextTab();
                $this->drawTabOptions($arTab["OPTIONS"]);
            }

            $tabControl->BeginNextTab();
            $tabControl->Buttons();
            ?>

            <input type="submit"
                   name="update"
                   class="adm-btn-green"
                   value="<?= Loc::getMessage("MAIN_SAVE") ?>">
            <input type="reset" value="<?= Loc::getMessage("MAIN_RESET") ?>">
            <?= bitrix_sessid_post() ?>

        </form>

        <?
        $tabControl->End();
    }

    protected
    function prepareTabs()
    {
        $this->tabsSettings = $this->getTabsArray();
    }

    /**
     * Записать необходимые настройки модуля
     */
    protected
    function postData()
    {
        $request = Application::getInstance()->getContext()->getRequest();

        if (
            !$request->isPost() ||
            !$request->get("update") ||
            !check_bitrix_sessid()
        ) {
            return;
        }

        foreach ($this->tabsSettings as $arTab) {
            foreach ($arTab["OPTIONS"] as $arOption) {
                $this->postAnOption($arOption);
            }
        }

        $this->onPostEvents();

        LocalRedirect($_SERVER["REQUEST_URI"], true);
    }

    protected
    function postAnOption(
        $arOption
    ) {
        $request = Application::getInstance()->getContext()->getRequest();

        if (!is_array($arOption)) {
            return;
        }
        $key = $arOption[0];
        if (empty($key)) {
            return;
        }

        $value = $request->get($key);

        // echo "<pre>";
        // var_export($arOption);
        // echo "</pre>";

        if (!empty($arOption["ext_data"]["is_serialized"])) {
            $oldValue = $value;
            $value = json_decode($value, true);

            // echo "<pre>";
            // var_export([
            // 	$oldValue, $value,
            // ]);
            // echo "</pre>";die();

            if (!empty($oldValue) && empty($value)) {
                return;
            }
            $value = serialize($value);
        } elseif (empty($arOption["ext_data"]["skip_encode"])) {
            $value = htmlspecialchars($value);
        }

        Option::set($this->moduleId, $key, $value);
    }
}