<?php
/**
 * Created by IntelliJ IDEA.
 * User: nikit
 * Date: 13.05.2020
 * Time: 11:40
 */

namespace esas\cmsgate\bitrix;

use esas\cmsgate\Registry;

class CmsgateCModuleBitrix24 extends CmsgateCModule
{
    protected function getPaysystemType()
    {
        return "CRM_INVOICE";
    }

    function InstallEvents()
    {
        parent::UnInstallEvents();
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->registerEventHandler('sale', 'OnGetBusinessValueGroups', Registry::getRegistry()->getModuleDescriptor()->getModuleMachineName(), CmsgateEventHandler::class, 'onGetBusValueGroups');
    }

    function UnInstallEvents()
    {
        parent::UnInstallEvents();
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        $eventManager->unRegisterEventHandler('sale', 'OnGetBusinessValueGroups', Registry::getRegistry()->getModuleDescriptor()->getModuleMachineName(), CmsgateEventHandler::class, 'onGetBusValueGroups');
    }


}