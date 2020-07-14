<?php

/**
 * Created by PhpStorm.
 * User: nikit
 * Date: 30.09.2018
 * Time: 15:19
 */

namespace esas\cmsgate\view\admin;

use esas\cmsgate\ConfigFields;
use esas\cmsgate\Registry;
use esas\cmsgate\view\admin\fields\ConfigField;
use esas\cmsgate\view\admin\fields\ConfigFieldCheckbox;
use esas\cmsgate\view\admin\fields\ConfigFieldList;
use esas\cmsgate\view\admin\fields\ListOption;
use esas\cmsgate\utils\htmlbuilder\Attributes as attribute;
use esas\cmsgate\utils\htmlbuilder\Elements as element;

class ConfigFormBitrix extends ConfigFormArray
{
    private $orderStatuses;

    /**
     * ConfigFieldsRenderWoo constructor.
     */
    public function __construct($formKey, $managedFields)
    {
        parent::__construct($formKey, $managedFields);
        $bitrixStatusList = \Bitrix\Sale\Internals\StatusLangTable::getList(array(
            'order' => array('STATUS.SORT'=>'ASC'),
            'filter' => array('STATUS.TYPE'=>'O','LID'=>LANGUAGE_ID),
            'select' => array('STATUS_ID','NAME'),

        ));
        while($status=$bitrixStatusList->fetch()) {
            $statusKey = $status["STATUS_ID"];
            $statusName = $status["NAME"];
            $this->orderStatuses[$statusKey] = new ListOption($statusKey, '[' . $statusKey . '] ' .  $statusName);
        }
    }

    public function generate()
    {
        return array(
            'NAME' => Registry::getRegistry()->getTranslator()->getConfigFieldDefault(ConfigFields::paymentMethodName()),
            'SORT' => 500,
            'CODES' => $this->generateCodes());
    }

    public function generateCodes() {
        return parent::generate();
    }

    public function generateFieldArray(ConfigField $configField, $addDefault = true)
    {
        $ret = array(
            'NAME' => $configField->getName(),
            'GROUP' => $this->getFormKey(),
            'DESCRIPTION' => $configField->getDescription(),
            'SORT' => $configField->getSortOrder()
        );
        if ($addDefault && $configField->hasDefault()) {
            $ret['DEFAULT'] = array(
                'PROVIDER_VALUE' => $configField->getDefault(),
                'PROVIDER_KEY' => 'VALUE'
            );
        }
        return $ret;
    }


    public function generateTextField(ConfigField $configField)
    {
        return $this->generateFieldArray($configField);
    }


    public function generateCheckboxField(ConfigFieldCheckbox $configField)
    {
        $ret = $this->generateFieldArray($configField, false);
        $ret['INPUT'] = array(
            'TYPE' => 'Y/N'
        );
        return $ret;
    }

    public function generateListField(ConfigFieldList $configField)
    {
        $ret = $this->generateFieldArray($configField, false);
        $options = array();
        foreach ($configField->getOptions() as $option)
            $options[$option->getValue()] = $option->getName();
        $ret['INPUT'] = array(
            'TYPE' => 'ENUM',
            'OPTIONS' => $options
        );
        return $ret;
    }

    /**
     * @return ListOption[]
     */
    public function createStatusListOptions()
    {
        return $this->orderStatuses;
    }

    public static function generateModuleDescription() {
        return element::table(
            element::tr(
                element::td("Module: "),
                element::td(
                    element::a(
                        attribute::href(Registry::getRegistry()->getModuleDescriptor()->getModuleUrl()),
                        element::content(Registry::getRegistry()->getModuleDescriptor()->getModuleMachineName())
                    ))
            ),
            element::tr(
                element::td("Version: "),
                element::td(Registry::getRegistry()->getModuleDescriptor()->getVersion()->getVersion())
            ),
            element::tr(
                element::td("Vendor: "),
                element::td(
                    element::a(
                        attribute::href(Registry::getRegistry()->getModuleDescriptor()->getVendor()->getUrl()),
                        element::content(Registry::getRegistry()->getModuleDescriptor()->getVendor()->getFullName())
                    )
                )
            )
        );
    }

}