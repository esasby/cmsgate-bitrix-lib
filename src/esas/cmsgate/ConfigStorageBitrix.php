<?php
/**
 * Created by IntelliJ IDEA.
 * User: nikit
 * Date: 15.07.2019
 * Time: 13:14
 */

namespace esas\cmsgate;

use Bitrix\Sale\PaySystem\Manager;
use Bitrix\Sale\PaySystem\Service;
use Exception;

class ConfigStorageBitrix extends ConfigStorageCms
{
    private $paymentSystems;

    public function __construct()
    {
        parent::__construct();

        $paySystemManagerResult = Manager::getList([
            'select' => [
                'PAY_SYSTEM_ID', "PERSON_TYPE_ID"
            ],
            'filter' => [
                'ACTION_FILE' => CmsConnectorBitrix::getInstance()->getInstalledHandlers(),
            ],
        ]);
        $this->paymentSystems = $paySystemManagerResult->fetch();
    }


    /**
     * @param $key
     * @return string
     * @throws Exception
     */
    public function getConfig($key)
    {
//        /** @var Service $paymentSystem */
//        foreach (CmsConnectorBitrix::getInstance()->getServedPaymentSystems() as $paymentSystem) {
//            $option = \Bitrix\Sale\BusinessValue::get(strtoupper($key),
//                'PAYSYSTEM_' . $paymentSystem->getField("PAY_SYSTEM_ID"),
//                $paymentSystem->getField("PERSON_TYPE_ID"));
//            if ($option != null && $option != '')
//                break;
//        }
        foreach ($this->paymentSystems as $paymentSystem) {
            $option = \Bitrix\Sale\BusinessValue::get(strtoupper($key),
                'PAYSYSTEM_' . $paymentSystem["PAY_SYSTEM_ID"],
                $paymentSystem["PERSON_TYPE_ID"]);
            if ($option != null && $option != '')
                break;
        }
        return $option;
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