<?php
/**
 * Created by IntelliJ IDEA.
 * User: nikit
 * Date: 15.07.2019
 * Time: 13:14
 */

namespace esas\cmsgate;

use Bitrix\Sale\BusinessValue;
use Exception;

class ConfigStorageBitrix extends ConfigStorageCms
{
    private $paymentSystems;

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * @param $key
     * @return string
     * @throws Exception
     */
    public function getConfig($key)
    {
        $paymentSystem = CmsConnectorBitrix::getInstance()->getCurrentPaymentSystem();
        return BusinessValue::get(strtoupper($key), 'PAYSYSTEM_' . $paymentSystem->getField("ID"),
            $paymentSystem->getField("PERSON_TYPE_ID"));
    }

    /**
     * @param $cmsConfigValue
     * @return bool
     * @throws Exception
     */
    public function convertToBoolean($cmsConfigValue)
    {
        return $cmsConfigValue == 'Y' || $cmsConfigValue == '1' || $cmsConfigValue == "true";
    }

    public function saveConfig($key, $value)
    {
        //todo implement
    }

    public function createCmsRelatedKey($key)
    {
        return Registry::getRegistry()->getPaySystemName() . "_" . $key;
    }


}