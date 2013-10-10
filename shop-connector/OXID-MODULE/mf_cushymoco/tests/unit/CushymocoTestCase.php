<?php

class CushymocoTestCase extends OxidTestCase
{

    public function void() {
        return;
    }

    public function setShopField($fieldName, $value) {
        /**
         * @var oxShop $shop
         */

        $shop = oxRegistry::getConfig()->getActiveShop();
        $shop->$fieldName = new oxField($value);

        $oConfig = oxRegistry::getConfig();

        $reflection = new ReflectionClass($oConfig);
        $property = $reflection->getProperty('_oActShop');
        $property->setAccessible(true);
        $property->setValue($oConfig, $shop);
    }

}
