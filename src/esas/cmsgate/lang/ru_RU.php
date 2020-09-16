<?php

use esas\cmsgate\messenger\MessagesBitrix;


return array(
    MessagesBitrix::ERROR_PS_INSTALL => "Не удалось создать платёжную систему",
    MessagesBitrix::ERROR_SALE_MODULE_NOT_INSTALLED => "Для работы модуля необходим модуль интернет-магазина (sale)",
    MessagesBitrix::ERROR_ORDERS_EXIST => "Невозможно удалить платежную систему, т.к. существуют заказы с данной платёжной системой.",
    MessagesBitrix::ERROR_DELETE_EXCEPTION => "Невозможно удалить платежную систему.",
    MessagesBitrix::ERROR_PS_ACTION_REG => "Ошибка регистрации обработчиков ПСОшибка регистрации обработчиков ПС",
    MessagesBitrix::PARENT_PS_CONFIG => "Данная платежная система использует общие настройки с системой ",
);