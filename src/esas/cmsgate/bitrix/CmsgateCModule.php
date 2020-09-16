<?php
/**
 * Created by IntelliJ IDEA.
 * User: nikit
 * Date: 13.05.2020
 * Time: 11:40
 */

namespace esas\cmsgate\bitrix;

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\IO\File;
use Bitrix\Main\ModuleManager;
use Bitrix\Sale\Internals\PaySystemActionTable;
use Bitrix\Sale\PaySystem\Manager;
use CFile;
use CModule;
use CSaleOrder;
use esas\cmsgate\ConfigFields;
use esas\cmsgate\messenger\MessagesBitrix;
use esas\cmsgate\Registry;
use Exception;

/**
 * ВАЖНО: при публикации в marketplace выполняется проверка,
 * что install/index является прямым наследником от CModule. Поэтому наследование от CmsgateCModule невозможно
 * Вместо этого, можно создавать внутреннюю переменную CmsgateCModule и дергать методы через нее
 * Class CmsgateCModule
 * @package esas\cmsgate\bitrix
 */
class CmsgateCModule extends CModule
{
    const MODULE_SUB_PATH = '/php_interface/include/sale_payment/';
    const MODULE_IMAGES_SUB_PATH = "/images/sale/sale_payments/";
    const OPTION_PAYSYSTEM_ID = "PAY_SYSTEM_ID";
    const OPTION_INSTALLED_PAYSYSTEMS_ID = "ADDED_PAY_SYSTEM_ID";
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
     * @var  CmsgatePaysystem[]
     */
    protected $installPaySystemsList;

    /**
     * CmsgateCModule constructor.
     */
    public function __construct()
    {
        $this->MODULE_ID = Registry::getRegistry()->getModuleDescriptor()->getModuleMachineName();
        $this->MODULE_PATH = $_SERVER['DOCUMENT_ROOT'] . '/bitrix' . self::MODULE_SUB_PATH . $this->getModuleActionName();
        $this->MODULE_VERSION = Registry::getRegistry()->getModuleDescriptor()->getVersion()->getVersion();
        $this->MODULE_VERSION_DATE = Registry::getRegistry()->getModuleDescriptor()->getVersion()->getDate();
        $this->MODULE_NAME = Registry::getRegistry()->getModuleDescriptor()->getModuleFullName();
        $this->MODULE_DESCRIPTION = Registry::getRegistry()->getModuleDescriptor()->getModuleDescription();
        $this->PARTNER_NAME = Registry::getRegistry()->getModuleDescriptor()->getVendor()->getFullName();
        $this->PARTNER_URI = Registry::getRegistry()->getModuleDescriptor()->getVendor()->getUrl();

        $this->installSrcDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->MODULE_ID . '/install';
        $this->createInstallFilesList();
        $this->createInstallPaySystemsList();
        CModule::IncludeModule("sale");
    }

    public function createInstallFilesList()
    {
        $this->installFilesList[] = self::MODULE_SUB_PATH . $this->getModuleActionName();
        $this->installFilesList[] = self::MODULE_IMAGES_SUB_PATH . $this->getModuleActionName() . ".png";
    }

    public function addToInstallFilesList($extFile)
    {
        $this->installFilesList[] = $extFile;
        return $this;
    }

    public function createInstallPaySystemsList()
    {
        $mainPaySystem = new CmsgatePaysystem();
        $mainPaySystem
            ->setName(Registry::getRegistry()->getTranslator()->getConfigFieldDefault(ConfigFields::paymentMethodName()))
            ->setDescription(Registry::getRegistry()->getTranslator()->getConfigFieldDefault(ConfigFields::paymentMethodDetails()))
            ->setActionFile($this->getModuleActionName())
            ->setType($this->getPaysystemType())
            ->setMain(true) // основная ПС модуля, ее ID будет храниться в OPTION_PAYSYSTEM_ID
            ->setSort(100);
        $this->installPaySystemsList[] = $mainPaySystem;
    }

    public function addToInstallPaySystemsList($extPaySystem)
    {
        $this->installPaySystemsList[] = $extPaySystem;
        return $this;
    }

    function InstallDB($arParams = array())
    {
        ModuleManager::RegisterModule($this->MODULE_ID);
        foreach ($this->installPaySystemsList as $paySystem) {
            $this->addPaysys($paySystem);
            if ($paySystem->getId() === false)
                throw new Exception(Registry::getRegistry()->getTranslator()->translate(MessagesBitrix::ERROR_PS_INSTALL));
            if ($paySystem->isMain()) {
                //сохранение paysystemId в настройках модуля
                Option::set($this->MODULE_ID, self::OPTION_PAYSYSTEM_ID, $paySystem->getId());
            }
        }
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
            $this->InstallEvents();
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
            $this->UnInstallEvents();
        } catch (Exception $e) {
            $GLOBALS["APPLICATION"]->ThrowException($e->getMessage());
            return false;
        }
    }

    /**
     * В bitrix есть однозначная связка между:
     * - именем директории в php_interface\include\sale_payment\
     * - именем класса в handler.php
     * - ACTION_FILE в БД в таблице b_sale_pay_system_action
     * При этом символ "." недопустим
     * @return mixed
     */
    public function getModuleActionName()
    {
        return str_replace('.', '_', Registry::getRegistry()->getModuleDescriptor()->getModuleMachineName());
    }

    /**
     * Первоначально тут был просто вызов Manager::Add, но в таком случае не происходит загрузка логотипа, как было в CSalePaySystem::Add
     * Поэтому взят пример кода из \Bitrix\Sale\PaySystem\Manager::createInnerPaySystem
     * @param CmsgatePaysystem $paySystem
     * @throws Exception
     */
    public function addPaysys(&$paySystem)
    {
        $paySystemSettings = array(
            "NAME" => $paySystem->getName(),
            "DESCRIPTION" => $paySystem->getDescription(),
            "ACTION_FILE" => $paySystem->getActionFile(),
            "ACTIVE" => $paySystem->isActive() ? "Y" : "N",
            "ENTITY_REGISTRY_TYPE" => $paySystem->getType(), // без этого созданная платежная система не отображается в списке
            "NEW_WINDOW" => "N",
            "HAVE_PREPAY" => "N",
            "HAVE_RESULT" => "N",
            "HAVE_ACTION" => "N",
            "HAVE_PAYMENT" => "Y",
            "HAVE_RESULT_RECEIVE" => "Y",
//            "ENCODING" => "utf-8", на системах с windows-1251 при установке из marketplace это приводит к двойной конвертации итоговой страницы и некорректоному отображению
            "SORT" => $paySystem->getSort(),
        );


        $imagePath = Application::getDocumentRoot() . '/bitrix/images/sale/sale_payments/' . $paySystem->getActionFile() . '.png';
        if (File::isFileExists($imagePath)) {
            $paySystemSettings['LOGOTIP'] = \CFile::MakeFileArray($imagePath);
            $paySystemSettings['LOGOTIP']['MODULE_ID'] = "sale";
            \CFile::SaveForDB($paySystemSettings, 'LOGOTIP', 'sale/paysystem/logotip');
        }

        $paySystemManagerResult = Manager::getList([
            'select' => [
                'ID'
            ],
            'filter' => [
                'ACTION_FILE' => $paySystem->getActionFile(),
                'ENTITY_REGISTRY_TYPE' => 'DELETED',
            ],
        ]);

        $previousVersionPSId = $paySystemManagerResult->fetch();

        if (!empty($previousVersionPSId) && $previousVersionPSId["ID"] > 0)
            $result = PaySystemActionTable::update($previousVersionPSId["ID"], $paySystemSettings);
        else
            $result = PaySystemActionTable::add($paySystemSettings);

        if ($result->isSuccess()) {
            $paySystem->setId($result->getId());
            //т.к. один плагин может добавлять сразу несколько ПС, то сохраняем идентификаторы через запятую в отдельной настройке (для возможности удаления)
            $option = Option::get($this->MODULE_ID, self::OPTION_INSTALLED_PAYSYSTEMS_ID);
            if (!empty($option))
                $alreadyInstalled = explode(",", $option);
            $alreadyInstalled[] = $paySystem->getId();
            Option::set($this->MODULE_ID, self::OPTION_INSTALLED_PAYSYSTEMS_ID, implode(",", $alreadyInstalled));
        }
    }

    protected function getPaysystemType()
    {
        return "ORDER";
    }

    /**
     * @return bool
     * @throws Exception
     */
    protected function deletePaysys()
    {
        $alreadyInstalled = explode(",", Option::get($this->MODULE_ID, self::OPTION_INSTALLED_PAYSYSTEMS_ID));
        if ($alreadyInstalled == null || sizeof($alreadyInstalled) == 0)
            return false;
        foreach ($alreadyInstalled as $psId) {
            $paySystemSettings = Manager::GetByID($psId);
            if (empty($psId) || $psId <= 0 || !$paySystemSettings)
                continue;
            $order = CSaleOrder::GetList(array(), array("PAY_SYSTEM_ID" => $psId))->Fetch();
            if ($order["ID"] > 0) {
                $paySystemSettingsDeleted = array(
                    "NAME" => $paySystemSettings["NAME"] . " #DELETED",
                    "ACTIVE" => "N",
                    "ENTITY_REGISTRY_TYPE" => "DELETED" //любое значение кроме order и invoice
                );
                if (!Manager::update($psId, $paySystemSettingsDeleted))
                    throw new Exception(Registry::getRegistry()->getTranslator()->translate(MessagesBitrix::ERROR_DELETE_EXCEPTION));
            } else {
                // verify that there is a payment system to delete
                if (!Manager::delete($psId))
                    throw new Exception(Registry::getRegistry()->getTranslator()->translate(MessagesBitrix::ERROR_DELETE_EXCEPTION));
            }

        }
        return true;
    }

}