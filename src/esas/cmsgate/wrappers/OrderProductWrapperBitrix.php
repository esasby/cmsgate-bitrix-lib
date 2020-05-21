<?php
/**
 * Created by PhpStorm.
 * User: nikit
 * Date: 14.03.2018
 * Time: 17:08
 */

namespace esas\cmsgate\wrappers;

use Bitrix\Sale\BasketItem;
use esas\hutkigrosh\lang\TranslatorBitrix;

class OrderProductWrapperBitrix extends OrderProductWrapper
{
    private $basketItem;

    /**
     * OrderProductWrapperJoomshopping constructor.
     * @param $product
     */
    public function __construct(BasketItem $product)
    {
        parent::__construct();
        $this->basketItem = $product;
    }

    /**
     * Артикул товара
     * @return string
     */
    public function getInvId()
    {
        return $this->basketItem->getField('ID');
    }

    /**
     * Название или краткое описание товара
     * @return string
     */
    public function getName()
    {
        return $this->basketItem->getField('NAME');
    }

    /**
     * Количество товароа в корзине
     * @return mixed
     */
    public function getCount()
    {
        return round($this->basketItem->getField('QUANTITY'));
    }

    /**
     * Цена за единицу товара
     * @return mixed
     */
    public function getUnitPrice()
    {
        return $this->basketItem->getField('QUANTITY') * $this->basketItem->getField('PRICE');
    }
}