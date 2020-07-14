<?php
/**
 * Created by IntelliJ IDEA.
 * User: nikit
 * Date: 13.04.2020
 * Time: 12:23
 */

namespace esas\cmsgate;


use Bitrix\Crm\Invoice\Invoice;
use Bitrix\Sale\Order;
use CSaleOrder;
use esas\cmsgate\descriptors\CmsConnectorDescriptor;
use esas\cmsgate\descriptors\VendorDescriptor;
use esas\cmsgate\descriptors\VersionDescriptor;
use esas\cmsgate\lang\LocaleLoaderBitrix;
use esas\cmsgate\view\admin\AdminViewFields;
use esas\cmsgate\wrappers\OrderWrapper;
use esas\cmsgate\wrappers\OrderWrapperBitrix;
use esas\cmsgate\wrappers\OrderWrapperBitrix24;

class CmsConnectorBitrix24 extends CmsConnectorBitrix
{
    /**
     * Для удобства работы в IDE и подсветки синтаксиса.
     * @return $this
     */
    public static function getInstance()
    {
        return Registry::getRegistry()->getCmsConnector();
    }

    /**
     * По локальному id заказа возвращает wrapper
     * @param $orderId
     * @return OrderWrapper
     * @throws \Bitrix\Main\ArgumentNullException
     */
    public function createOrderWrapperByOrderId($orderId)
    {
        $bitrixOrder = Invoice::load($orderId);
        return new OrderWrapperBitrix24($bitrixOrder);
    }

    public function createOrderWrapperByOrderNumber($orderNumber)
    {
        $orderByAccount = Invoice::loadByAccountNumber($orderNumber);
        if ($orderByAccount == null)
            $orderByAccount = Invoice::load($orderNumber);
        return new OrderWrapperBitrix24($orderByAccount);
    }

    public function createOrderWrapperByExtId($extId)
    {
        $parameters = [
            'filter' => [
                "COMMENTS" => $extId
            ]
        ];
        $dbRes = Invoice::loadByFilter($parameters);
        if ($dbRes == null || count($dbRes) != 1)
            return null;
        else
            return new OrderWrapperBitrix24($dbRes[0]); //todo check
    }
}