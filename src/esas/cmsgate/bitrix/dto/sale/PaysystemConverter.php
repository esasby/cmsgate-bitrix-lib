<?php


namespace esas\cmsgate\bitrix\dto\sale;


class PaysystemConverter
{

    /**
     * @param $paysystem Paysystem
     * @return array
     */
    public static function toArray($paysystem) {
        $fields = array(
            "NAME" => $paysystem->getName(),
            "DESCRIPTION" => $paysystem->getDescription(),
            "ACTION_FILE" => $paysystem->getActionFile(),
            "ACTIVE" => $paysystem->isActive() ? "Y" : "N",
            "ENTITY_REGISTRY_TYPE" => $paysystem->getType(), // без этого созданная платежная система не отображается в списке
            "NEW_WINDOW" => "N",
            "HAVE_PREPAY" => "N",
            "HAVE_RESULT" => "N",
            "HAVE_ACTION" => "N",
            "HAVE_PAYMENT" => "Y",
            "HAVE_RESULT_RECEIVE" => "Y",
//            "ENCODING" => "utf-8", на системах с windows-1251 при установке из marketplace это приводит к двойной конвертации итоговой страницы и некорректоному отображению
            "SORT" => $paysystem->getSort(),
        );
        if ($paysystem->getLogoPath() != '' && file_exists($paysystem->getLogoPath())) {
            $content = file_get_contents($paysystem->getLogoPath());
            $fields['LOGOTYPE'] = base64_encode($content);
        }
        return $fields;
    }

    /**
     * @param $dataArray
     * @return Paysystem
     */
    public static function fromArray($dataArray) {
        $paysystem = new Paysystem();
        $paysystem
            ->setId($dataArray['ID'])
            ->setName($dataArray['NAME'])
            ->setActionFile($dataArray['ACTION_FILE'])
            ->setSort($dataArray['SORT'])
            ->setActive($dataArray['ACTIVE'] == 'Y')
            ->setType($dataArray['ENTITY_REGISTRY_TYPE']);
        return $paysystem;
    }
}