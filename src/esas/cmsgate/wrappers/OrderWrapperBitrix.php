<?php

namespace esas\cmsgate\wrappers;

use Bitrix\Sale\Order;
use CSaleOrder;

class OrderWrapperBitrix extends OrderSafeWrapper
{
    private $order;
    private $products;

    /**
     * OrderWrapperJoomshopping constructor.
     * @param $order
     */
    public function __construct(Order $order)
    {
        parent::__construct();
        $this->order = $order;
    }

    /**
     * Уникальный номер заказ в рамках CMS
     * @return string
     */
    public function getOrderIdUnsafe()
    {
        return $this->order->getId();
    }

    public function getOrderNumberUnsafe()
    {
        // если включен шаблон генерации номера заказа, то подставляем этот номер
        $accountNumber = $this->order->getField('ACCOUNT_NUMBER');
        return !empty($accountNumber) ? $accountNumber : $this->getOrderId();
    }

    /**
     * Полное имя покупателя
     * @return string
     */
    public function getFullNameUnsafe()
    {
        return $this->order->getPropertyCollection()->getPayerName()->getValue();
    }

    /**
     * Мобильный номер покупателя для sms-оповещения
     * (если включено администратором)
     * @return string
     */
    public function getMobilePhoneUnsafe()
    {
        return $this->order->getPropertyCollection()->getPhone()->getValue();
    }

    /**
     * Email покупателя для email-оповещения
     * (если включено администратором)
     * @return string
     */
    public function getEmailUnsafe()
    {
        return $this->order->getPropertyCollection()->getUserEmail()->getValue();
    }

    /**
     * Физический адрес покупателя
     * @return string
     */
    public function getAddressUnsafe()
    {
        $address = $this->order->getPropertyCollection()->getAddress();
        if ($address == null)
            $address = $this->order->getPropertyCollection()->getDeliveryLocation();
        if ($address != null)
            $address->getValue();
        else
            return "";
    }

    /**
     * Общая сумма товаров в заказе
     * @return string
     */
    public function getAmountUnsafe()
    {
        return $this->order->getPrice();
    }

    /**
     * Валюта заказа (буквенный код)
     * @return string
     */
    public function getCurrencyUnsafe()
    {
//        $orderCurrency = isset($orderCurrency) ? $orderCurrency : $line_item['CURRENCY']; //TODO со временем можно сделать выставление разных счетов,
        return $this->order->getCurrency();
    }

    /**
     * Массив товаров в заказе
     * @return \esas\hutkigrosh\wrappers\OrderProductWrapper[]
     */
    public function getProductsUnsafe()
    {
        if ($this->products != null)
            return $this->products;
        $basket = $this->order->getBasket();
        foreach ($basket->getOrderableItems() as $basketItem)
            $this->products[] = new OrderProductWrapperBitrix($basketItem);
        return $this->products;
    }

    /**
     * Текущий статус заказа в CMS
     * @return mixed
     */
    public function getStatusUnsafe()
    {
        return $this->order->getField("STATUS_ID");
    }

    /**
     * Обновляет статус заказа в БД
     * @param $newStatus
     * @return mixed
     * @throws \Bitrix\Main\ArgumentException
     */
    public function updateStatus($newStatus)
    {
        if (!empty($newStatus) && $this->getStatus() != $newStatus) {
            CSaleOrder::Update($this->getOrderId(), array("STATUS_ID" => $newStatus));
            $this->order->setField("STATUS_ID", $newStatus);
        }
    }

    /**
     * Идентификатор клиента
     * @return string
     */
    public function getClientIdUnsafe()
    {
        // TODO: Implement getClientId() method.
    }

    /**
     * BillId (идентификатор хуткигрош) успешно выставленного счета
     * @return mixed
     */
    public function getExtIdUnsafe()
    {
        return $this->order->getField("COMMENTS"); //todo положит в какое-то именнованное поле в COMMENTS, для исключения конфликтов
    }

    /**
     * Сохраняет привязку внешнего идентификтора к заказу
     * @param $extId
     */
    public function saveExtId($extId)
    {
        CSaleOrder::Update($this->getOrderId(), array("COMMENTS" => $extId));
//        $collection = $this->order->getPaymentCollection(); // в идеале, надо сохранять в платежах, а не в заказе
        $this->order->setField("COMMENTS", $extId);
    }
}