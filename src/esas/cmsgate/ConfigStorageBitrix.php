<?php
/**
 * Created by IntelliJ IDEA.
 * User: nikit
 * Date: 15.07.2019
 * Time: 13:14
 */

namespace esas\cmsgate;

use Exception;

class ConfigStorageBitrix extends ConfigStorageCms
{
    private $psId;

    public function __construct()
    {
        parent::__construct();
        $this->psId = CmsConnectorBitrix::getInstance()->getPaysystemId();
    }


    /**
     * @param $key
     * @return string
     * @throws Exception
     */
    public function getConfig($key)
    {
        return \Bitrix\Sale\BusinessValue::get(strtoupper($key), 'PAYSYSTEM_' . $this->psId, null);
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