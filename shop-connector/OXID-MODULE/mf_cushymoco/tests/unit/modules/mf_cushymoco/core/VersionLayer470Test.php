<?php

class Unit_Modules_mf_cushymoco_Core_VersionLayer470Test extends CushymocoTestCase {

    /**
     * @var versionLayer470
     */
    public $versionLayer;

    /**
     *
     */
    public function setup()
    {
        $this->versionLayer = new VersionLayer470();
    }

    /**
     * @return array
     */
    public function oxTypesProvider()
    {
        return array(
            array('getBasket',          'oxBasket'),
            array('getSession',         'oxSession'),
            array('getConfig',          'oxConfig'),
            // TODO: should this be oxDb or oxLegacyDb
            array('getDb',              'oxLegacyDb'),
            array('getDeliverySetList', 'oxDeliverySetList'),
            array('getUtils',           'oxUtils'),
            array('getLang',            'oxLang'),
            array('getUtilsServer',     'oxUtilsServer'),
            array('getUtilsUrl',        'oxUtilsUrl'),
            array('getUtilsView',       'oxUtilsView'),
            array('getUtilsObject',     'oxUtilsObject'),
            array('getUtilsDate',       'oxUtilsDate'),
            array('getUtilsString',     'oxUtilsString'),
            array('getUtilsFile',       'oxUtilsFile'),
            array('getUtilsPic',        'oxUtilsPic'),
            array('getUtilsCount',      'oxUtilsCount'),
        );
    }

    /**
     * Tests if the right type is returned (getBasket should return oxBasket,
     * getSession should return oxSession, etc.).
     *
     * @dataProvider oxTypesProvider
     */
    public function testGetOxTypes($fnName, $expectedClassName)
    {
        $actualClassName   = get_class(
            $this->versionLayer->$fnName()
        );

        $this->assertSame(
            $expectedClassName,
            $actualClassName
        );
    }

    /**
     *
     */
    public function testGetOxDbWithFetchModeNum()
    {
        // Fetch modes:
        //   false: numeric
        //   true:  assoc
        $fetchMode = false;

        // TODO: change type if it should be oxDb
        /** @var oxLegacyDb $oxDb */
        $oxDb = $this->versionLayer->getDb($fetchMode);

        $result = $oxDb->getRow('SHOW DATABASES');
        $arrayKeys = array_keys($result);
        $firstArrayKey = $arrayKeys[0];

        $this->assertTrue(
            is_numeric($firstArrayKey)
        );
    }

    /**
     *
     */
    public function testGetOxDbWithFetchModeAssoc()
    {
        // Fetch modes:
        //   false: numeric
        //   true:  assoc
        $fetchMode = true;

        // TODO: change type if it should be oxDb
        /** @var oxLegacyDb $oxDb */
        $oxDb = $this->versionLayer->getDb($fetchMode);

        $result = $oxDb->getRow('SHOW DATABASES');
        $arrayKeys = array_keys($result);
        $firstArrayKey = $arrayKeys[0];

        $this->assertTrue(
            is_string($firstArrayKey)
        );
    }

    /**
     *
     */
    public function testGetRequestParam()
    {
        $paramValue = new oxField('someRandomValue-' . rand());
        $this->setRequestParam('testParam', $paramValue);

        $this->assertSame(
            $paramValue,
            $this->versionLayer->getRequestParam('testParam')
        );
    }

    /**
     *
     */
    public function testGetRequestParamRaw()
    {
        $this->markTestSkipped(
            'Raw value can\'t be retrieved due to wrong implementation of modConfig::getParameter()'
        );

        $raw = true;

        $paramValue = 'someRandomValue-' . rand();
        $this->setRequestParam('testParam', new oxField($paramValue));

        $this->assertSame(
            $paramValue,
            $this->versionLayer->getRequestParam('testParam', null, $raw)
        );
    }

    /**
     *
     */
    public function testGetRequestParamDefaultValue()
    {
        $defaultValue = 'someRandomDefaultValue-' . rand();

        $this->setRequestParam('testParam', null);

        $this->assertSame(
            $defaultValue,
            $this->versionLayer->getRequestParam('testParam', $defaultValue)
        );
    }

}
