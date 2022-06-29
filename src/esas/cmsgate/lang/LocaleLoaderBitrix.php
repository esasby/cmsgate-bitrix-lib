<?php
/**
 * Created by PhpStorm.
 * User: nikit
 * Date: 27.09.2018
 * Time: 13:09
 */

namespace esas\cmsgate\lang;

use \Bitrix\Main\Context;

class LocaleLoaderBitrix extends LocaleLoaderCms
{
    public function __construct()
    {
        $this->addExtraVocabularyDir(dirname(__FILE__));
    }

    public function getLocale()
    {
        $context = Context::getCurrent();
        if ($context !== null) {
            $this->locale = $context->getLanguage() . "_" . strtoupper($context->getLanguage());
        } else {
            $this->locale = "ru_RU";
        }
    }
}