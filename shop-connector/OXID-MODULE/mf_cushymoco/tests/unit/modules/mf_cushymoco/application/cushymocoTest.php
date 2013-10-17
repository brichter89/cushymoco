<?php

class Unit_Modules_mf_cushymoco_Application_cushymocoTest extends CushymocoTestCase {

    /**
     * @param      $id
     * @param      $title
     * @param      $iconUrl
     * @param bool $hasSubCarts
     * @param null $parentCategory
     * @param bool $isVisible
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    public function generateCategoryMock($id, $title, $iconUrl, $hasSubCarts = false, $parentCategory = null, $isVisible = true)
    {
        $oxCategory = $this->getMock(
            'oxCategory',
            array('getParentCategory', 'getIsVisible', 'getId', 'getIconUrl', 'getHasSubCats')
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
     * Test if init method sets a custom exception handler.
     */
    public function testInitSetsExceptionHandler()
    {
        $oCushy = new cushymoco();
        $oCushy->init();

        $oldExceptionHandler = set_exception_handler(array($this, 'dummy'));
        $this->assertSame(
            array($oCushy, 'exceptionHandler'),
            $oldExceptionHandler
        );
    }

    /**
     * Test if init echos a json error when no VersionLayer is found for
     * the current shop version.
     */
    public function testInitExitsWithMessageWhenNoVersionLayerIsFound()
    {
        $this->setShopField('oxshops__oxversion', '1.0.0');

        $oxUtils = $this->getMock('oxutils', array('showMessageAndExit'));
        $oxUtils->expects($this->once())
            ->method('showMessageAndExit')
            ->with('{"error":"Can\u0027t find any shop version layer.","result":null,"cartItemCount":null}');
        oxRegistry::set('oxUtils', $oxUtils);

        $oCushy = new cushymoco();
        $oCushy->init();
    }

    /**
     * Test if init echos a json error when no suitable VersionLayer is found
     * for the current shop version.
     * Version layer for version 4.7.0 can be used for version 4.7.*
     * but version layer for version 4.7.1 can only be used for exactly that
     * version.
     */
    public function testInitExitsWithMessageWhenNoSuitableVersionLayerIsFound()
    {
        $this->markTestIncomplete("You need to be able to modify the version layer path first.");

//        $this->setShopField('oxshops__oxversion', '1.2.3');
//
//        $oxUtils = $this->getMock('oxutils', array('showMessageAndExit'));
//        $oxUtils->expects($this->once())
//            ->method('showMessageAndExit')
//            ->with('{"error":"Can\u0027t find suitable version layer class for your shop.","result":null,"cartItemCount":null}')
//            ->will($this->returnValue('ok, und exit'));
//        oxRegistry::set('oxUtils', $oxUtils);
//
//        $oCushy = new cushymoco();
//        $oCushy->init();
    }

    /**
     * Test if exceptionHandler echos a json error response.
     */
    public function testExceptionHandler()
    {
        $oCushy = new cushymoco();
        $oCushy->init();

        $sMessage = "I'm an exception! Yeah!";
        $exception = new \Exception($sMessage);

        ob_start();
        $oCushy->exceptionHandler($exception);
        $output = ob_get_clean();

        $sErrMessage = json_decode($output)->error;

        $this->assertSame(
            $sMessage,
            $sErrMessage
        );
    }

    /**
     * Test if render method returns template name.
     */
    public function testRenderReturnsTemplate()
    {
        $oCushy = new cushymoco();
        $oCushy->init();

        $this->assertEquals(
            'cushymoco.tpl',
            $oCushy->render()
        );
    }

    /**
     *
     */
    public function testLogin()
    {
        $expectedUserId = 'oxdefaultadmin';

        $oCushy = new cushymoco();
        $oCushy->init();

        $this->setRequestParam('lgn_usr', new oxField(oxADMIN_LOGIN));
        $this->setRequestParam('lgn_pwd', new oxField(oxADMIN_PASSWD));

        $oCushy->login();
        $ajaxResponse = $this->getAjaxResponseValue($oCushy);

        $oxSession = $this->getOxSession();
        $returnedUserId = $ajaxResponse['result']['userId'];
        $userUserId     = $oxSession->getUser()->getId();

        $this->assertNull($ajaxResponse['error']);
        $this->assertEquals($expectedUserId, $returnedUserId);
        $this->assertEquals($expectedUserId, $userUserId);
    }

    /**
     * @return array
     */
    public function provideInvalidCredentials()
    {
        return array(
            array(oxADMIN_LOGIN,                        'someRandomWrongPassword-' . md5(rand())),
            array('someRandomWrongUser-' . md5(rand()), oxADMIN_PASSWD),
            array('someRandomWrongUser-' . md5(rand()), 'someRandomWrongPassword-' . md5(rand()))
        );
    }

    /**
     * Test login with an invalid
     *
     * @dataProvider provideInvalidCredentials
     */
    public function testLoginWithIncorrectCredentials($user, $pass)
    {
        $oCushy = new cushymoco();
        $oCushy->init();

        $this->setRequestParam('lgn_usr', new oxField($user));
        $this->setRequestParam('lgn_pwd', new oxField($pass));

        ob_start();
        $oCushy->login();
        $output = ob_get_clean();

        $sErrMessage = json_decode($output)->error;

        $this->assertEquals(
            oxRegistry::getLang()->translateString('EXCEPTION_USER_NOVALIDLOGIN'),
            $sErrMessage
        );
    }

    /**
     * @return array
     */
    public function provideEmptyCredentials() {
        return array(
            array(new oxField('user'), null),
            array(null,                new oxField('pass')),
            array(null,                null)
        );
    }

    /**
     * Test login if username or password (or both) are empty
     *
     * @dataProvider provideEmptyCredentials
     */
    public function testLoginWithEmptyCredentials($user, $pass)
    {
        $oCushy = new cushymoco();
        $oCushy->init();

        $this->setRequestParam('lgn_usr', $user);
        $this->setRequestParam('lgn_pwd', $pass);

        $oCushy->login();
        $ajaxResponse = $this->getAjaxResponseValue($oCushy);

        $sErrMessage = $ajaxResponse['error'];

        $this->assertEquals(
            "User " . $user . " can not be logged in",
            $sErrMessage
        );
    }

    /**
     * @depends testLogin
     */
    public function testLogout()
    {
        $oCushy = new cushymoco();
        $oCushy->init();

        $this->setRequestParam('lgn_usr', new oxField(oxADMIN_LOGIN));
        $this->setRequestParam('lgn_pwd', new oxField(oxADMIN_PASSWD));

        $oCushy->login();

        $oCushy->logout();
        $ajaxResponse = $this->getAjaxResponseValue($oCushy);

        $oxSession = $this->getOxSession();
        $this->assertNull($ajaxResponse['error']);
        $this->assertTrue($ajaxResponse['result']['logout']);
        $this->assertFalse($oxSession->getUser());
    }

    /**
     *
     */
    public function testGetCategoryList()
    {
        $expectedCategoryList = array(
            array(
                'categoryId' => "CATEGORY_1",
                'title'      => "Category 1",
                'icon'       => "http://dummy.url/icon_1",
                'hasChild'   => false
            ),
            array(
                'categoryId' => "CATEGORY_2",
                'title'      => "Category 2",
                'icon'       => "http://dummy.url/icon_2",
                'hasChild'   => true
            )
        );

        $oCushy = new cushymoco();
        $oCushy->init();

        $oxCategory_1 = $this->generateCategoryMock(
            $expectedCategoryList[0]['categoryId'],
            $expectedCategoryList[0]['title'],
            $expectedCategoryList[0]['icon'],
            $expectedCategoryList[0]['hasChild']
        );

        // This should not be displayed because it is a subcategory of CATEGORY_1
        $oxCategory_1_1 = $this->generateCategoryMock(
            'CATEGORY_1_1',
            'Category 1 > 1',
            'http://dummy.url/icon_1_1',
            false,
            $oxCategory_1
        );

        $oxCategory_2 = $this->generateCategoryMock(
            $expectedCategoryList[1]['categoryId'],
            $expectedCategoryList[1]['title'],
            $expectedCategoryList[1]['icon'],
            $expectedCategoryList[1]['hasChild']
        );

        // This should not be displayed because it is set to be not visible
        $oxCategory_3 = $this->generateCategoryMock(
            'CATEGORY_3',
            'Category 3',
            'http://dummy.url/icon_3',
            false,
            null,
            false
        );


        $oxCategoryList = $this->getMock(
            'oxCategoryList',
            array('valid', 'current')
        );
        $oxCategoryList->expects($this->at(0))
            ->method('valid')
            ->will($this->returnValue(true));
        $oxCategoryList->expects($this->at(1))
            ->method('current')
            ->will($this->returnValue($oxCategory_1));

        $oxCategoryList->expects($this->at(2))
            ->method('valid')
            ->will($this->returnValue(true));
        $oxCategoryList->expects($this->at(3))
            ->method('current')
            ->will($this->returnValue($oxCategory_1_1));

        $oxCategoryList->expects($this->at(4))
            ->method('valid')
            ->will($this->returnValue(true));
        $oxCategoryList->expects($this->at(5))
            ->method('current')
            ->will($this->returnValue($oxCategory_2));

        $oxCategoryList->expects($this->at(6))
            ->method('valid')
            ->will($this->returnValue(true));
        $oxCategoryList->expects($this->at(7))
            ->method('current')
            ->will($this->returnValue($oxCategory_3));

        $oxCategoryList->expects($this->at(8))
            ->method('valid')
            ->will($this->returnValue(false));


        oxUtilsObject::setClassInstance('oxCategoryList', $oxCategoryList);


        $oCushy->getCategoryList();

        $ajaxResponse = $this->getAjaxResponseValue($oCushy);
        $categoryList = $ajaxResponse['result'];

        $this->assertEquals(
            $expectedCategoryList,
            $categoryList
        );
    }

    /**
     *
     */
    public function testGetCategoryListOfSubCategory()
    {
        $expectedCategoryList = array(
            array(
                'categoryId' => "CATEGORY_1_1",
                'title'      => "Category 1 > 1",
                'icon'       => "http://dummy.url/icon_1_1",
                'hasChild'   => false
            )
        );

        $oCushy = new cushymoco();
        $oCushy->init();

        // This should not be displayed because it is a top level category
        $oxCategory_1 = $this->generateCategoryMock(
            'CATEGORY_1',
            'Category 1',
            'http://dummy.url/icon_1',
            true
        );

        $oxCategory_1_1 = $this->generateCategoryMock(
            $expectedCategoryList[0]['categoryId'],
            $expectedCategoryList[0]['title'],
            $expectedCategoryList[0]['icon'],
            $expectedCategoryList[0]['hasChild'],
            $oxCategory_1
        );

        // This should not be displayed because it is set to be not visible
        $oxCategory_1_2 = $this->generateCategoryMock(
            'CATEGORY_1_2',
            'Category 1 > 2',
            'http://dummy.url/icon_1_2',
            false,
            $oxCategory_1,
            false
        );

        // This should not be displayed because it is a top level category
        $oxCategory_2 = $this->generateCategoryMock(
            'CATEGORY_2',
            'Category 2',
            'http://dummy.url/icon_2',
            true
        );

        // This should not be displayed because it is sub category of CATEGORY_2
        $oxCategory_2_1 = $this->generateCategoryMock(
            'CATEGORY_2_1',
            'Category 2 > 1',
            'http://dummy.url/icon_2_1',
            false,
            $oxCategory_2
        );


        $oxCategoryList = $this->getMock(
            'oxCategoryList',
            array('valid', 'current')
        );
        $oxCategoryList->expects($this->at(0))
            ->method('valid')
            ->will($this->returnValue(true));
        $oxCategoryList->expects($this->at(1))
            ->method('current')
            ->will($this->returnValue($oxCategory_1));

        $oxCategoryList->expects($this->at(2))
            ->method('valid')
            ->will($this->returnValue(true));
        $oxCategoryList->expects($this->at(3))
            ->method('current')
            ->will($this->returnValue($oxCategory_1_1));

        $oxCategoryList->expects($this->at(4))
            ->method('valid')
            ->will($this->returnValue(true));
        $oxCategoryList->expects($this->at(5))
            ->method('current')
            ->will($this->returnValue($oxCategory_1_2));

        $oxCategoryList->expects($this->at(6))
            ->method('valid')
            ->will($this->returnValue(true));
        $oxCategoryList->expects($this->at(7))
            ->method('current')
            ->will($this->returnValue($oxCategory_2));

        $oxCategoryList->expects($this->at(8))
            ->method('valid')
            ->will($this->returnValue(true));
        $oxCategoryList->expects($this->at(9))
            ->method('current')
            ->will($this->returnValue($oxCategory_2_1));

        $oxCategoryList->expects($this->at(10))
            ->method('valid')
            ->will($this->returnValue(false));


        oxUtilsObject::setClassInstance('oxCategoryList', $oxCategoryList);


        $this->setRequestParam('cnid', new oxField('CATEGORY_1'));

        $oCushy->getCategoryList();

        $ajaxResponse = $this->getAjaxResponseValue($oCushy);
        $categoryList = $ajaxResponse['result'];

        $this->assertEquals(
            $expectedCategoryList,
            $categoryList
        );
    }

}
