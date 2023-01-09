<?php


namespace esas\cmsgate\bitrix\dto\sale;


class Paysystem
{
    private $id;
    private $name;
    private $description;
    private $actionFile;
    private $type;
    private $sort = 100;
    private $logoPath;
    /**
     * @var boolean
     */
    private $active = false;
    /**
     * @var boolean
     */
    private $main = false;

    public static function newInstance() {
        return new Paysystem();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return Paysystem
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return Paysystem
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     * @return Paysystem
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getActionFile()
    {
        return $this->actionFile;
    }

    /**
     * @param mixed $actionFile
     * @return Paysystem
     */
    public function setActionFile($actionFile)
    {
        $this->actionFile = $actionFile;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     * @return Paysystem
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param mixed $sort
     * @return Paysystem
     */
    public function setSort($sort)
    {
        $this->sort = $sort;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     * @return Paysystem
     */
    public function setActive(bool $active): Paysystem
    {
        $this->active = $active;
        return $this;
    }



    /**
     * @return bool
     */
    public function isMain(): bool
    {
        return $this->main;
    }

    /**
     * @param bool $main
     * @return Paysystem
     */
    public function setMain(bool $main): Paysystem
    {
        $this->main = $main;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLogoPath()
    {
        return $this->logoPath;
    }

    /**
     * @param mixed $logoPath
     * @return Paysystem
     */
    public function setLogoPath($logoPath)
    {
        $this->logoPath = $logoPath;
        return $this;
    }


}