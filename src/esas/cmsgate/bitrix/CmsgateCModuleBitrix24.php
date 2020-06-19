<?php
/**
 * Created by IntelliJ IDEA.
 * User: nikit
 * Date: 13.05.2020
 * Time: 11:40
 */

namespace esas\cmsgate\bitrix;

class CmsgateCModuleBitrix24 extends CmsgateCModule
{
    protected function getPaysystemType()
    {
        return "CRM_INVOICE";
    }


}