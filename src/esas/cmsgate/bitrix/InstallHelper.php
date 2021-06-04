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
use Bitrix\Main\EventManager;
use Bitrix\Main\IO\File;
use Bitrix\Main\ModuleManager;
use Bitrix\Sale\Internals\PaySystemActionTable;
use Bitrix\Sale\PaySystem\Manager;
use CFile;
use CModule;
use CSaleOrder;
use esas\cmsgate\CmsConnectorBitrix;
use esas\cmsgate\ConfigFields;
use esas\cmsgate\messenger\MessagesBitrix;
use esas\cmsgate\Registry;
use Exception;

/**
 * ВАЖНО: при публикации в marketplace выполняется проверка,
 * что install/index является прямым наследником от CModule. Поэтому наследование от InstallHelper невозможно
 * Вместо этого, можно создавать внутреннюю переменную InstallHelper и дергать методы через нее
 * Class InstallHelper
 * @package esas\cmsgate\bitrix
 */
class InstallHelper
{
    const MODULE_SUB_PATH = '/php_interface/include/sale_payment/';
    const MODULE_IMAGES_SUB_PATH = "/images/sale/sale_payments/";
    const OPTION_PAYSYSTEM_ID = "PAY_SYSTEM_ID";
    const OPTION_INSTALLED_PAYSYSTEMS_ID = "ADDED_PAY_SYSTEM_ID";

    private $moduleId;
    private $installSrcDir;
    protected $installFilesList;


    /**
     * @var CModule
     */
    protected $managedCModule;
    /**
     * @var  CmsgatePaysystem[]
     */
    protected $installPaySystemsList;

    /**
     * InstallHelper constructor.
     */
    public function __construct(CModule $managedCModule)
    {
        $this->managedCModule = $managedCModule;
        $this->moduleId = Registry::getRegistry()->getModuleDescriptor()->getModuleMachineName();
        $this->installSrcDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . $this->moduleId . '/install';
        CModule::IncludeModule("sale");
    }

    public function addToInstallFilesList($extFile)
    {
        $this->installFilesList[] = $extFile;
        return $this;
    }

    public function createMainPaySystem()
    {
        $mainPaySystem = new CmsgatePaysystem();
        $mainPaySystem
            ->setName(Registry::getRegistry()->getTranslator()->getConfigFieldDefault(ConfigFields::paymentMethodName()))
            ->setDescription(Registry::getRegistry()->getTranslator()->getConfigFieldDefault(ConfigFields::paymentMethodDetails()))
            ->setActionFile(CmsConnectorBitrix::getInstance()->getModuleActionName())
            ->setType($this->getPaysystemType())
            ->setMain(true) // основная ПС модуля, ее ID будет храниться в OPTION_PAYSYSTEM_ID
            ->setSort(100);
        return $mainPaySystem;
    }

    public function createAndAddMainPaySystem() {
        $mainPaySystem = $this->createMainPaySystem();
        $this->addToInstallPaySystemsList($mainPaySystem);
    }

    public function addToInstallPaySystemsList(CmsgatePaysystem $extPaySystem, $addFiles = true)
    {
        $this->installPaySystemsList[] = $extPaySystem;
        if ($addFiles) {
            $this->addToInstallFilesList(self::MODULE_SUB_PATH . $extPaySystem->getActionFile());
            $this->addToInstallFilesList(self::MODULE_IMAGES_SUB_PATH . $extPaySystem->getActionFile() . ".png");
        }
        return $this;
    }

    function InstallDB($arParams = array())
    {
        ModuleManager::RegisterModule($this->moduleId);
        foreach ($this->installPaySystemsList as $paySystem) {
            $this->addPaysys($paySystem);
            if ($paySystem->getId() === false)
                throw new Exception(Registry::getRegistry()->getTranslator()->translate(MessagesBitrix::ERROR_PS_INSTALL));
            if ($paySystem->isMain()) {
                //сохранение paysystemId в настройках модуля
                Option::set($this->moduleId, self::OPTION_PAYSYSTEM_ID, $paySystem->getId());
            }
        }
        if ($this->managedCModule != null)
            $this->managedCModule->InstallDB();
    }

    /**
     * @param array $arParams
     * @return bool
     * @throws Exception
     */
    function UnInstallDB($arParams = array())
    {
        $this->deletePaysys();
        Option::delete($this->moduleId);
        ModuleManager::UnRegisterModule($this->moduleId);
        if ($this->managedCModule != null)
            $this->managedCModule->UnInstallDB();
    }

    function InstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandler('sale', 'OnGetBusinessValueGroups', $this->moduleId, CmsgateEventHandler::class, 'onGetBusValueGroups');
        if ($this->managedCModule != null)
            $this->managedCModule->InstallEvents();
    }

    function UnInstallEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler('sale', 'OnGetBusinessValueGroups', $this->moduleId, CmsgateEventHandler::class, 'onGetBusValueGroups');
        if ($this->managedCModule != null)
            $this->managedCModule->UnInstallEvents();
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
        if ($this->managedCModule != null)
            $this->managedCModule->InstallFiles();
    }

    function UnInstallFiles()
    {
        foreach ($this->installFilesList as $fileToDelete) {
            DeleteDirFilesEx('/bitrix' . $fileToDelete);
        }
        if ($this->managedCModule != null)
            $this->managedCModule->UnInstallFiles();
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
            $paySystemSettings['LOGOTIP'] = CFile::MakeFileArray($imagePath);
            $paySystemSettings['LOGOTIP']['MODULE_ID'] = "sale";
            CFile::SaveForDB($paySystemSettings, 'LOGOTIP', 'sale/paysystem/logotip');
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
            $option = Option::get($this->moduleId, self::OPTION_INSTALLED_PAYSYSTEMS_ID);
            if (!empty($option))
                $alreadyInstalled = explode(",", $option);
            $alreadyInstalled[] = $paySystem->getId();
            Option::set($this->moduleId, self::OPTION_INSTALLED_PAYSYSTEMS_ID, implode(",", $alreadyInstalled));
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
        $alreadyInstalled = explode(",", Option::get($this->moduleId, self::OPTION_INSTALLED_PAYSYSTEMS_ID));
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