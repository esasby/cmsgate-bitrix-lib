<?php
/**
 * Created by IntelliJ IDEA.
 * User: nikit
 * Date: 08.07.2020
 * Time: 11:24
 */

namespace esas\cmsgate\bitrix;


use esas\cmsgate\Registry;

class CmsgateEventHandler
{
    public static function onGetBusValueGroups()
    {
        $ret = array();
        foreach (Registry::getRegistry()->getManagedFieldsFactory()->getGroups() as $group)
            $ret[$group] = array('NAME' => Registry::getRegistry()->getTranslator()->translate($group), 'SORT' => 100);
        return $ret;
    }
}