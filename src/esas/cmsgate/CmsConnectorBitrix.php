<?php
/**
 * Created by IntelliJ IDEA.
 * User: nikit
 * Date: 13.04.2020
 * Time: 12:23
 */

namespace esas\cmsgate;


use Bitrix\Main\Config\Option;
use Bitrix\Sale\Order;
use Bitrix\Sale\PaymentCollection;
use CSaleOrder;
use esas\cmsgate\bitrix\InstallHelper;
use esas\cmsgate\descriptors\CmsConnectorDescriptor;
use esas\cmsgate\descriptors\VendorDescriptor;
use esas\cmsgate\descriptors\VersionDescriptor;
use esas\cmsgate\lang\LocaleLoaderBitrix;
use esas\cmsgate\wrappers\OrderWrapper;
use esas\cmsgate\wrappers\OrderWrapperBitrix;

class CmsConnectorBitrix extends CmsConnector
{
    /**
     * Для удобства работы в IDE и подсветки синтаксиса.
     * @return $this
     */
    public static function getInstance()
    {
        return Registry::getRegistry()->getCmsConnector();
    }


    public function createCommonConfigForm($managedFields)
    {
        return null; //not implemented
    }

    public function createSystemSettingsWrapper()
    {
        return null; // not implemented
    }

    public function getPaysystemId()
    {
        return (int)Option::get(Registry::getRegistry()->getModuleDescriptor()->getModuleMachineName(), InstallHelper::OPTION_PAYSYSTEM_ID);
    }

    public function getInstalledPaysystemsIds()
    {
        $option = Option::get(Registry::getRegistry()->getModuleDescriptor()->getModuleMachineName(), InstallHelper::OPTION_INSTALLED_PAYSYSTEMS_ID);
        if (!empty($option))
            return explode(",", $option);
        return array();
    }

    /**
     * По локальному id заказа возвращает wrapper
     * @param $orderId
     * @return OrderWrapper
     * @throws \Bitrix\Main\ArgumentNullException
     */
    public function createOrderWrapperByOrderId($orderId)
    {
        $bitrixOrder = Order::load($orderId);
        return new OrderWrapperBitrix($bitrixOrder);
    }

    public function createOrderWrapperForCurrentUser()
    {
        global $USER;
        $orderId = $GLOBALS['ORDER_ID'];
        if (!isset($orderId) || $orderId == '') {
            $arFilter = array(
                "USER_ID" => $USER->GetID(),
            );
            $db_sales = CSaleOrder::GetList(array(), $arFilter);
            while ($ar_sales = $db_sales->Fetch()) {
                $orderId = $ar_sales['ID']; //присвоили переменной ID заказа
                break; //оборвали цикл
            }
        }
        return $this->createOrderWrapperByOrderId($orderId);
    }

    public function createOrderWrapperByOrderNumber($orderNumber)
    {
        $orderByAccount = Order::loadByAccountNumber($orderNumber);
        if ($orderByAccount == null)
            $orderByAccount = Order::load($orderNumber);
        return new OrderWrapperBitrix($orderByAccount);
    }

    //todo chack
    public function createOrderWrapperByExtId($extId)
    {

        $parameters = [
            'select' => ['ORDER_ID'],
            'filter' => [
                '=' . OrderWrapperBitrix::DB_EXT_ID_FIELD => $extId
            ]
        ];
        $dbRes = PaymentCollection::getList($parameters);
        $orderIdsArray = $dbRes->fetch();
        if ($orderIdsArray == null || count($orderIdsArray) != 1)
            return null;
        else
            return $this->createOrderWrapperByOrderId($orderIdsArray[0]);
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

    public function createConfigStorage()
    {
        return new ConfigStorageBitrix();
    }

    public function createLocaleLoader()
    {
        return new LocaleLoaderBitrix();
    }

    public function createCmsConnectorDescriptor()
    {
        return new CmsConnectorDescriptor(
            "cmsgate-bitrix-lib",
            new VersionDescriptor(
                "v1.17.1",
                "2022-06-30"
            ),
            "Cmsgate Bitrix connector",
            "https://bitbucket.esas.by/projects/CG/repos/cmsgate-bitrix-lib/browse",
            VendorDescriptor::esas(),
            "bitrix"
        );
    }

    public function getCurrentEncoding()
    {
        return (defined("LANG_CHARSET") ? LANG_CHARSET : "utf-8");
    }
}