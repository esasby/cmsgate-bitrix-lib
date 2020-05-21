<?php
/**
 * Created by IntelliJ IDEA.
 * User: nikit
 * Date: 15.07.2019
 * Time: 13:14
 */

namespace esas\cmsgate;

use Exception;
use Bitrix\Main\Config\Option;
use CSalePaySystemAction;

class ConfigStorageBitrix extends ConfigStorageCms
{
    private $params;

    public function __construct()
    {
        parent::__construct();
        //получаем параметры платежной системы
        //может быть есть возможность сделать это как-то более красиво?
        $psId = (int)Option::get(CmsConnectorBitrix::getInstance()->getModuleId(), "PAY_SYSTEM_ID");
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
        return $cmsConfigValue == '1' || $cmsConfigValue == "true";
    }

    public function saveConfig($key, $value)
    {
        //todo implement
    }
    }