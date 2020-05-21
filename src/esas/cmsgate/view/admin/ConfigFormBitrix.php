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
        foreach ($bitrixStatusList as $statusKey => $statusName) {
            $this->orderStatuses[$statusKey] = new ListOption($statusKey, '[' . $statusKey . '] ' .  $statusName);
        }
    }

    public function generate()
    {
        return array(
            'NAME' => Registry::getRegistry()->getTranslator()->getConfigFieldDefault(ConfigFields::paymentMethodName()),
            'SORT' => 500,
            'CODES' => parent::generate());
    }

    public function generateFieldArray(ConfigField $configField, $addDefault = true)
    {
        $ret = array(
            'NAME' => $configField->getName(),
            'GROUP' => $this->getGroup($configField),
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


    public function getGroup(ConfigField $configField)
    {
        return 'PAYSYSTEM';
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

}