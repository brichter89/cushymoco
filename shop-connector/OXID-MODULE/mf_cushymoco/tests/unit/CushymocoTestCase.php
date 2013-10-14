<?php

class CushymocoTestCase extends OxidTestCase
{

    public function dummy() {
        return;
    }

    /**
     * @param $object
     * @param $propertyName
     * @param $value
     */
    public function setProtectedPropertyValue($object, $propertyName, $value)
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        $property->setValue($object, $value);
    }

    /**
     * @param $object
     * @param $propertyName
     *
     * @return mixed
     */
    public function getProtectedPropertyValue($object, $propertyName)
    {
        $reflection = new ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    public function setShopField($fieldName, $value) {
        /**
         * @var oxShop $shop
         */

        $shop = oxRegistry::getConfig()->getActiveShop();
        $shop->$fieldName = new oxField($value);

        $oConfig = oxRegistry::getConfig();

        $this->setProtectedPropertyValue($oConfig, '_oActShop', $shop);
    }

    /**
     * @param cushymoco $oCushymoco
     *
     * @return mixed
     */
    public function getAjaxResponseValue(cushymoco $oCushymoco)
    {
        return $this->getProtectedPropertyValue($oCushymoco, '_sAjaxResponse');
    }

}
