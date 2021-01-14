<?php
/**
 * Created by PhpStorm.
 * User: nikit
 * Date: 14.03.2018
 * Time: 17:08
 */

namespace esas\cmsgate\wrappers;

use Bitrix\Sale\BasketItem;

class OrderProductWrapperBitrix extends OrderProductSafeWrapper
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
    public function getInvIdUnsafe()
    {
        return $this->basketItem->getField('ID');
    }

    /**
     * Название или краткое описание товара
     * @return string
     */
    public function getNameUnsafe()
    {
        return $this->basketItem->getField('NAME');
    }

    /**
     * Количество товароа в корзине
     * @return mixed
     */
    public function getCountUnsafe()
    {
        return round($this->basketItem->getField('QUANTITY'));
    }

    /**
     * Цена за единицу товара
     * @return mixed
     */
    public function getUnitPriceUnsafe()
    {
        return $this->basketItem->getField('PRICE');
    }
}