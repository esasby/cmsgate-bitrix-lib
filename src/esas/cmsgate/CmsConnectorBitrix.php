<?php
/**
 * Created by IntelliJ IDEA.
 * User: nikit
 * Date: 13.04.2020
 * Time: 12:23
 */

namespace esas\cmsgate;


use Bitrix\Sale\Order;
use CSaleOrder;
use esas\cmsgate\descriptors\CmsConnectorDescriptor;
use esas\cmsgate\descriptors\VendorDescriptor;
use esas\cmsgate\descriptors\VersionDescriptor;
use esas\cmsgate\lang\LocaleLoaderBitrix;
use esas\cmsgate\view\admin\AdminViewFields;
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
        $configForm = new ConfigFormJoomshopping(
            $managedFields,
            AdminViewFields::CONFIG_FORM_COMMON,
            null,
            null);
        $configForm->addSubmitButton(AdminViewFields::CONFIG_FORM_BUTTON_SAVE);
        $configForm->addSubmitButton(AdminViewFields::CONFIG_FORM_BUTTON_DOWNLOAD_LOG);
        $configForm->addSubmitButton(AdminViewFields::CONFIG_FORM_BUTTON_CANCEL);
        return $configForm;
    }

    public function createSystemSettingsWrapper()
    {
        return new SystemSettingsWrapperJoomshopping();
    }

    /**
     * По локальному id заказа возвращает wrapper
     * @param $orderId
     * @return OrderWrapper
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
            $arFilter = Array(
                "USER_ID" => $USER->GetID(),
            );
            $db_sales = CSaleOrder::GetList(array(), $arFilter);
            while ($ar_sales = $db_sales->Fetch()) {
                $orderId = $ar_sales['ID']; //присвоили переменной ID заказа
                break; //оборвали цикл
            }
        }
        return new OrderWrapperBitrix(Order::load($orderId));
    }

    public function createOrderWrapperByOrderNumber($orderNumber)
    {
        $orderByAccount = Order::loadByAccountNumber($orderNumber);
        if ($orderByAccount == null)
            $orderByAccount = Order::load($orderNumber);
        return new OrderWrapperBitrix($orderByAccount);
    }

    public function createOrderWrapperByExtId($extId)
    {
        $parameters = [
            'filter' => [
                "COMMENTS" => $extId
            ]
        ];
        $dbRes = Order::loadByFilter($parameters);
        if ($dbRes == null || count($dbRes) != 1)
            return null;
        else
            return new OrderWrapperBitrix($dbRes[0]); //todo check
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
                "v1.10.2",
                "2020-06-03"
            ),
            "Cmsgate Bitrix connector",
            "https://bitbucket.esas.by/projects/CG/repos/cmsgate-bitrix-lib/browse",
            VendorDescriptor::esas(),
            "bitrix"
        );
    }
}