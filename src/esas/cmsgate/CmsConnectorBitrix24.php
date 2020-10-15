<?php
/**
 * Created by IntelliJ IDEA.
 * User: nikit
 * Date: 13.04.2020
 * Time: 12:23
 */

namespace esas\cmsgate;


use Bitrix\Crm\Invoice\Invoice;
use esas\cmsgate\wrappers\OrderWrapper;
use esas\cmsgate\wrappers\OrderWrapperBitrix;

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
        return new OrderWrapperBitrix($bitrixOrder);
    }

    public function createOrderWrapperByOrderNumber($orderNumber)
    {
        $orderByAccount = Invoice::loadByAccountNumber($orderNumber);
        if ($orderByAccount == null)
            $orderByAccount = Invoice::load($orderNumber);
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
        $dbRes = \Bitrix\Sale\PaymentCollection::getList($parameters);
        $orderIdsArray = $dbRes->fetch();
        if ($orderIdsArray == null || count($orderIdsArray) != 1)
            return null;
        else
            return $this->createOrderWrapperByOrderId($orderIdsArray[0]);
    }
}