<?php


namespace esas\cmsgate\bitrix;


class CmsgatePaysystem
{
    private $id;
    private $name;
    private $description;
    private $actionFile;
    private $type;
    private $sort = 100;
    /**
     * @var boolean
     */
    private $active = false;
    /**
     * @var boolean
     */
    private $main = false;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return CmsgatePaysystem
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
     * @return CmsgatePaysystem
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
     * @return CmsgatePaysystem
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
     * @return CmsgatePaysystem
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
     * @return CmsgatePaysystem
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
     * @return CmsgatePaysystem
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
     * @return CmsgatePaysystem
     */
    public function setActive(bool $active): CmsgatePaysystem
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
     * @return CmsgatePaysystem
     */
    public function setMain(bool $main): CmsgatePaysystem
    {
        $this->main = $main;
        return $this;
    }


}