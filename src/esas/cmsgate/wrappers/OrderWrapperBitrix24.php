<?php

namespace esas\cmsgate\wrappers;

use Bitrix\Crm\Invoice\Compatible\Invoice;

class OrderWrapperBitrix24 extends OrderWrapperBitrix
{
    public function saveExtId($extId)
    {
        Invoice::Update($this->getOrderId(), array("COMMENTS" => $extId));
        $this->order->setField("COMMENTS", $extId);
    }

}