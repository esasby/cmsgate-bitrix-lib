<?php
/**
 * Created by IntelliJ IDEA.
 * User: nikit
 * Date: 15.07.2019
 * Time: 13:14
 */

namespace esas\cmsgate;

use Bitrix\Main\Config\Option;
use CSalePaySystemAction;
use Exception;

class ConfigStorageBitrix extends ConfigStorageCms
{
    private $params;

    public function __construct()
    {
        parent::__construct();
        //получаем параметры платежной системы
        //может быть есть возможность сделать это как-то более красиво?
        $psId = (int)Option::get(Registry::getRegistry()->getModuleDescriptor()->getModuleMachineName(), "PAY_SYSTEM_ID");
        $this->params = CSalePaySystemAction::getParamsByConsumer('PAYSYSTEM_' . $psId, null);
    }


    /**
     * @param $key
     * @return string
     * @throws Exception
     */
    public function getConfig($key)
    {
        return trim(htmlspecialchars($this->params[$key]['VALUE']));
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