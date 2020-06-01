<?php
/**
 * Created by IntelliJ IDEA.
 * User: nikit
 * Date: 13.05.2020
 * Time: 11:40
 */

namespace esas\cmsgate\bitrix;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use CFile;
use CModule;
use CSaleOrder;
use CSalePaySystem;
use CSalePaySystemAction;
use esas\cmsgate\ConfigFields;
use esas\cmsgate\messenger\MessagesBitrix;
use esas\cmsgate\Registry;
use Exception;


class CmsgateCModule extends CModule
{
    const MODULE_SUB_PATH = '/php_interface/include/sale_payment/';
//    const MODULE_SUB_PATH = '/bitrix/modules/sale/payment/';
    var $MODULE_PATH;
    var $MODULE_ID;
    var $MODULE_VERSION = '';
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_GROUP_RIGHTS = 'Y';
    var $PARTNER_NAME;
    var $PARTNER_URI;
    private $installSrcDir;
    protected $installFilesList;

    /**
     * CmsgateCModule constructor.
     */
    public function __construct()
    {
        $this->MODULE_ID = Registry::getRegistry()->getModuleDescriptor()->getModuleMachineName();
        $this->MODULE_PATH = $_SERVER['DOCUMENT_ROOT'] . '/bitrix' . self::MODULE_SUB_PATH . Registry::getRegistry()->getPaySystemName();
        $this->MODULE_VERSION = Registry::getRegistry()->getModuleDescriptor()->getVersion()->getVersion();
        $this->MODULE_VERSION_DATE = Registry::getRegistry()->getModuleDescriptor()->getVersion()->getDate();
        $this->MODULE_NAME = Registry::getRegistry()->getModuleDescriptor()->getModuleFullName();
        $this->MODULE_DESCRIPTION = Registry::getRegistry()->getModuleDescriptor()->getModuleDescription();
        $this->PARTNER_NAME = Registry::getRegistry()->getModuleDescriptor()->getVendor()->getFullName();
        $this->PARTNER_URI = Registry::getRegistry()->getModuleDescriptor()->getVendor()->getUrl();


        $this->installSrcDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install';
        $this->addFilesToInstallList();
        CModule::IncludeModule("sale");
    }

    protected function addFilesToInstallList()
    {
        $this->installFilesList[] = self::MODULE_SUB_PATH . Registry::getRegistry()->getPaySystemName();
        $this->installFilesList[] = "/images/sale/sale_payments/" . Registry::getRegistry()->getPaySystemName() . ".png";
    }

    function InstallDB($arParams = array())
    {
        ModuleManager::RegisterModule($this->MODULE_ID);

        $psId = $this->addPaysys();
        if ($psId === false)
            throw new Exception(Registry::getRegistry()->getTranslator()->translate(MessagesBitrix::ERROR_PS_INSTALL));

        //сохранение paystsemId в настройках модуля
        Option::set($this->MODULE_ID, "PAY_SYSTEM_ID", $psId);

        //регистрируем обработчик пл. системы
        $handlersIds = $this->addPaysysHandler($psId);
        if (empty($handlersIds))
            throw new Exception(Registry::getRegistry()->getTranslator()->translate(MessagesBitrix::ERROR_PS_ACTION_REG));
        //сохраняем id обработчиков пл. системы
        Option::set($this->MODULE_ID, "handlers_ids", implode("|", $handlersIds));

        return true;
    }

    /**
     * @param array $arParams
     * @return bool
     * @throws Exception
     */
    function UnInstallDB($arParams = array())
    {
        $this->deletePaysys();
        $this->deletePaysysHandler();
        Option::delete($this->MODULE_ID);
        ModuleManager::UnRegisterModule($this->MODULE_ID);
        return true;
    }

    function InstallEvents()
    {
        return true;
    }

    function UnInstallEvents()
    {
        return true;
    }


    function InstallFiles($arParams = array())
    {
        # /bitrix/php_interface/include/sale_payment
        foreach ($this->installFilesList as $fileToInstall) {
            $from = $this->installSrcDir . $fileToInstall;
            $to = $_SERVER['DOCUMENT_ROOT'] . '/bitrix' . $fileToInstall;
            if (is_dir($from)) {
                if (!is_dir($to)) {
                    mkdir($to, 0755);
                }
            } else {
                $toDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix' . substr($fileToInstall, 0, strrpos($fileToInstall, '/'));
                if (!is_dir($toDir)) {
                    mkdir($to, 0755);
                }
            }
            CopyDirFiles($from, $to, true, true);
        }
        return true;
    }

    function UnInstallFiles()
    {
        foreach ($this->installFilesList as $fileToDelete) {
            DeleteDirFilesEx('/bitrix' . $fileToDelete);
        }
        return true;
    }

    function DoInstall()
    {
        try {
            if (!IsModuleInstalled("sale"))
                throw new Exception(Registry::getRegistry()->getTranslator()->translate(MessagesBitrix::ERROR_SALE_MODULE_NOT_INSTALLED));
            $this->PreInstallCheck();
            $this->InstallFiles();
            $this->InstallDB();
        } catch (Exception $e) {
            $this->DoUninstall();
            $GLOBALS["APPLICATION"]->ThrowException($e->getMessage());
            return false;
        }
    }

    public function PreInstallCheck()
    {
        if (!function_exists("curl_init"))
            throw new Exception(Registry::getRegistry()->getTranslator()->translate(MessagesBitrix::ERROR_CURL_NOT_INSTALLED));
    }

    function DoUninstall()
    {
        try {
            $this->UnInstallDB();
            $this->UnInstallFiles();
        } catch (Exception $e) {
            $GLOBALS["APPLICATION"]->ThrowException($e->getMessage());
            return false;
        }
    }

    protected function addPaysys()
    {
        return CSalePaySystem::Add(
            array(
                "NAME" => Registry::getRegistry()->getTranslator()->getConfigFieldDefault(ConfigFields::paymentMethodName()),
                "DESCRIPTION" => Registry::getRegistry()->getTranslator()->getConfigFieldDefault(ConfigFields::paymentMethodDetails()),
                "LOGOTIP" => CFile::MakeFileArray('/bitrix/images/sale/sale_payments/' . Registry::getRegistry()->getPaySystemName() . '.png'),
                "ACTIVE" => "N",
                "ENTITY_REGISTRY_TYPE" => "ORDER", // без этого созданная платежная система не отображается в списке
                "SORT" => 100,
            )
        );
    }

    protected function addPaysysHandler($psId)
    {
        $handlersIds = array();
        $fields = array(
            "PAY_SYSTEM_ID" => $psId,
            "NAME" => Registry::getRegistry()->getTranslator()->getConfigFieldDefault(ConfigFields::paymentMethodName()),
            "DESCRIPTION" => Registry::getRegistry()->getTranslator()->getConfigFieldDefault(ConfigFields::paymentMethodDetails()),
            "ACTION_FILE" => Registry::getRegistry()->getPaySystemName(),
            "NEW_WINDOW" => "N",
            "HAVE_PREPAY" => "N",
            "HAVE_RESULT" => "N",
            "HAVE_ACTION" => "N",
            "HAVE_PAYMENT" => "Y",
            "HAVE_RESULT_RECEIVE" => "Y",
            "ENCODING" => "utf-8",
        );
        $id = CSalePaySystemAction::Add($fields);
        $handlersIds[] = $id;
//        $personTypes = CSalePersonType::GetList(
//            array("SORT" => "ASC", "NAME" => "ASC"),
//            array()
//        );
//        while($pt = $personTypes->Fetch())
//        {
//            $fields["PERSON_TYPE_ID"] = $pt["ID"];
//            $id = CSalePaySystemAction::Add($fields);
//            if($id != false)
//                $handlersIds[] = $id;
//
//        }

        return $handlersIds;
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function deletePaysys()
    {
        $psId = (int)Option::get($this->MODULE_ID, "PAY_SYSTEM_ID");
        if ($psId == '0')
            return false;
        $order = CSaleOrder::GetList(array(), array("PAY_SYSTEM_ID" => $psId))->Fetch();
        if ($order["ID"] > 0)
            throw new Exception(Loc::getMessage("hutkigrosh_ERROR_ORDERS_EXIST"));
        // verify that there is a payment system to delete
        if ($arPaySys = CSalePaySystem::GetByID($psId)) {
            if (!CSalePaySystem::Delete($psId))
                throw new Exception(Registry::getRegistry()->getTranslator()->translate(MessagesBitrix::ERROR_DELETE_EXCEPTION));
        }
        return true;
    }

    protected function deletePaysysHandler()
    {
        $handlersIds = Option::get($this->MODULE_ID, "handlers_ids");
        if (!empty($handlersIds)) {
            $handlersIds = explode("|", $handlersIds);
            foreach ($handlersIds as $id)
                CSalePaySystemAction::Delete($id);
        }
        return true;
    }
}