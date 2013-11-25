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

    /**
     * @return OxConfig
     */
    public function getOxConfig()
    {
        return oxRegistry::getConfig();
    }

    /**
     * @return OxSession
     */
    public function getOxSession()
    {
        return oxRegistry::getSession();
    }

    /**
     * Setup methods required to mock an iterator
     *
     * @param PHPUnit_Framework_MockObject_MockObject $iteratorMock The mock to attach the iterator methods to
     * @param array $items The mock data we're going to use with the iterator
     * @return PHPUnit_Framework_MockObject_MockObject The iterator mock
     */
    public function mockIterator(PHPUnit_Framework_MockObject_MockObject $iteratorMock, array $items)
    {
        $iteratorData = new \stdClass();
        $iteratorData->array = $items;
        $iteratorData->position = 0;

        $iteratorMock->expects($this->any())
            ->method('rewind')
            ->will(
                $this->returnCallback(
                    function() use ($iteratorData) {
                        $iteratorData->position = 0;
                    }
                )
            );

        $iteratorMock->expects($this->any())
            ->method('current')
            ->will(
                $this->returnCallback(
                    function() use ($iteratorData) {
                        return $iteratorData->array[$iteratorData->position];
                    }
                )
            );

        $iteratorMock->expects($this->any())
            ->method('key')
            ->will(
                $this->returnCallback(
                    function() use ($iteratorData) {
                        return $iteratorData->position;
                    }
                )
            );

        $iteratorMock->expects($this->any())
            ->method('next')
            ->will(
                $this->returnCallback(
                    function() use ($iteratorData) {
                        $iteratorData->position++;
                    }
                )
            );

        $iteratorMock->expects($this->any())
            ->method('valid')
            ->will(
                $this->returnCallback(
                    function() use ($iteratorData) {
                        return isset($iteratorData->array[$iteratorData->position]);
                    }
                )
            );

        $iteratorMock->expects($this->any())
            ->method('count')
            ->will(
                $this->returnCallback(
                    function() use ($iteratorData) {
                        return sizeof($iteratorData->array);
                    }
                )
            );

        return $iteratorMock;
    }

    /**
     * @param array  $mockMethodsArray
     * @param int    $id
     * @param string $title
     * @param string $iconUrl
     * @param bool   $hasSubCarts
     * @param null   $parentCategory
     * @param bool   $isVisible
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    public function generateCategoryMock(
        Array $mockMethodsArray = Array(),
        $id,
        $title,
        $iconUrl,
        $hasSubCarts = false,
        $parentCategory = null,
        $isVisible = true
    ) {
        $mockMethodsArray = array_merge(
            $mockMethodsArray,
            array(
                 'getParentCategory',
                 'getIsVisible',
                 'getId',
                 'getIconUrl',
                 'getHasSubCats'
            )
        );

        $oxCategory = $this->getMock(
            'oxCategory',
            $mockMethodsArray
        );
        $oxCategory->expects($this->any())
            ->method('getParentCategory')
            ->will($this->returnValue($parentCategory));
        $oxCategory->expects($this->any())
            ->method('getIsVisible')
            ->will($this->returnValue($isVisible));
        $oxCategory->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
        $oxCategory->expects($this->any())
            ->method('getIconUrl')
            ->will($this->returnValue($iconUrl));
        $oxCategory->expects($this->any())
            ->method('getHasSubCats')
            ->will($this->returnValue($hasSubCarts));

        $oxCategory->oxcategories__oxtitle = new oxField($title);

        return $oxCategory;
    }

    /**
     * @param array  $mockMethodsArray
     * @param int    $id
     * @param string $title
     * @param string $iconUrl
     * @param string $shortDesc
     * @param int    $price
     * @param null   $longDesc
     * @param null   $link
     * @param null   $parentID
     * @param null   $variantsCount
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    public function generateArticleMock(
        Array $mockMethodsArray = Array(),
        $id,
        $title,
        $iconUrl,
        $shortDesc,
        $price,
        $longDesc=null,
        $link=null,
        $parentID=null,
        $variantsCount=null
    ) {
        $mockMethodsArray = array_merge(
            $mockMethodsArray,
            array(
                 'getFPrice',
                 'getIconUrl',
                 'getLongDesc',
                 'getLink'
            )
        );

        $oxArticle = $this->getMock(
            'oxArticle',
            $mockMethodsArray
        );
        $oxArticle->expects($this->any())
            ->method('getIconUrl')
            ->will($this->returnValue($iconUrl));
        $oxArticle->expects($this->any())
            ->method('getFPrice')
            ->will($this->returnValue($price));
        $oxArticle->expects($this->any())
            ->method('getLongDesc')
            ->will($this->returnValue($longDesc));
        $oxArticle->expects($this->any())
            ->method('getLink')
            ->will($this->returnValue($link));

        $oxArticle->oxarticles__oxid        = new oxField($id);
        $oxArticle->oxarticles__oxtitle     = new oxField($title);
        $oxArticle->oxarticles__oxshortdesc = new oxField($shortDesc);
        $oxArticle->oxarticles__oxtitle     = new oxField($title);
        $oxArticle->oxarticles__oxparentid  = new oxField($parentID);
        $oxArticle->oxarticles__oxvarcount  = new oxField($variantsCount);

        return $oxArticle;
    }

}
