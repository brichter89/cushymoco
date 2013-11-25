<?php

class Unit_Modules_mf_cushymoco_Application_cushymocoTest extends CushymocoTestCase {

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
            ->with('{"error":"Can\u0027t find any suitable version layer class for your shop.","result":null,"cartItemCount":null}');
        oxRegistry::set('oxUtils', $oxUtils);

        $oCushy = new cushymoco();
        $oCushy->init();
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
    public function testGetCategoryTitle()
    {
        $oCushy = new cushymoco();
        $oCushy->init();

        $expectedTitle = "Category 1";

        $cnid = 'CATEGORY_1';
        $this->setRequestParam('cnid', new oxField($cnid));

        $oxCategory1 = $this->generateCategoryMock(
            array(),
            $cnid,
            $expectedTitle,
            'http://url-to-icon.example/category_1.ico'
        );

        oxUtilsObject::setClassInstance('oxCategory', $oxCategory1);

        $oCushy->getCategoryTitle();

        $ajaxResponseValue = $this->getAjaxResponseValue($oCushy);

        $this->assertSame(
            $expectedTitle,
            $ajaxResponseValue['result']
        );
    }

    /**
     *
     */
    public function testGetCategoryTitleWithNoCategoryId()
    {
        $oCushy = new cushymoco();
        $oCushy->init();

        $oCushy->getCategoryTitle();

        $ajaxResponse = $this->getAjaxResponseValue($oCushy);

        $this->assertSame(
            'Category ID not given!',
            $ajaxResponse['error']
        );
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
                'hasChild'   => true
            ),
            array(
                'categoryId' => "CATEGORY_2",
                'title'      => "Category 2",
                'icon'       => "http://dummy.url/icon_2",
                'hasChild'   => false
            )
        );

        $oCushy = new cushymoco();
        $oCushy->init();

        $oxCategory_1 = $this->generateCategoryMock(
            array(),
            $expectedCategoryList[0]['categoryId'],
            $expectedCategoryList[0]['title'],
            $expectedCategoryList[0]['icon'],
            $expectedCategoryList[0]['hasChild']
        );

        // This should not be displayed because it is a subcategory of CATEGORY_1
        $oxCategory_1_1 = $this->generateCategoryMock(
            array(),
            'CATEGORY_1_1',
            'Category 1 > 1',
            'http://dummy.url/icon_1_1',
            false,
            $oxCategory_1
        );

        $oxCategory_2 = $this->generateCategoryMock(
            array(),
            $expectedCategoryList[1]['categoryId'],
            $expectedCategoryList[1]['title'],
            $expectedCategoryList[1]['icon'],
            $expectedCategoryList[1]['hasChild']
        );

        // This should not be displayed because it is set to be not visible
        $oxCategory_3 = $this->generateCategoryMock(
            array(),
            'CATEGORY_3',
            'Category 3',
            'http://dummy.url/icon_3',
            false,
            null,
            false
        );

        // mock an iterator
        $oxCategoryList = $this->getMock(
            'oxCategoryList',
            array('rewind', 'valid', 'current', 'key', 'next', 'count')
        );

        $oxCategoryList = $this->mockIterator(
            $oxCategoryList,
            array(
                 $oxCategory_1,
                 $oxCategory_1_1,
                 $oxCategory_2,
                 $oxCategory_3
            )
        );


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
            array(),
            'CATEGORY_1',
            'Category 1',
            'http://dummy.url/icon_1',
            true
        );

        $oxCategory_1_1 = $this->generateCategoryMock(
            array(),
            $expectedCategoryList[0]['categoryId'],
            $expectedCategoryList[0]['title'],
            $expectedCategoryList[0]['icon'],
            $expectedCategoryList[0]['hasChild'],
            $oxCategory_1
        );

        // This should not be displayed because it is set to be not visible
        $oxCategory_1_2 = $this->generateCategoryMock(
            array(),
            'CATEGORY_1_2',
            'Category 1 > 2',
            'http://dummy.url/icon_1_2',
            false,
            $oxCategory_1,
            false
        );

        // This should not be displayed because it is a top level category
        $oxCategory_2 = $this->generateCategoryMock(
            array(),
            'CATEGORY_2',
            'Category 2',
            'http://dummy.url/icon_2',
            true
        );

        // This should not be displayed because it is no sub category of CATEGORY_1
        $oxCategory_2_1 = $this->generateCategoryMock(
            array(),
            'CATEGORY_2_1',
            'Category 2 > 1',
            'http://dummy.url/icon_2_1',
            false,
            $oxCategory_2
        );


        $oxCategoryList = $this->getMock(
            'oxCategoryList',
            array('rewind', 'valid', 'current', 'key', 'next', 'count')
        );

        $oxCategoryList = $this->mockIterator(
            $oxCategoryList,
            array(
                $oxCategory_1,
                $oxCategory_1_1,
                $oxCategory_1_2,
                $oxCategory_2,
                $oxCategory_2_1
            )
        );


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

    /**
     *
     */
    public function testGetArticleList()
    {
        $currencySign = $actShopCurrencyObject = $this->getOxConfig()
            ->getActShopCurrencyObject()
            ->sign;

        $expectedArticleList = array (
            array (
                'productId' => 'ARTICLE_1',
                'title' => 'Article 1',
                'shortDesc' => 'Description 1',
                'price' => 1,
                'currency' => $currencySign,
                'formattedPrice' => '1 ' . $currencySign,
                'icon' => 'http://dummy.url/icon_1',
            )
        );

        $oCushy = new cushymoco();
        $oCushy->init();

        $actPage = 0;
        $perPage = $this->getConfig()->getConfigParam('iNrofCatArticles');

        $oxArticleList = $this->getMock(
            'oxarticlelist',
            array('setSqlLimit', 'rewind', 'valid', 'current', 'key', 'next', 'count')
        );

        $oxArticleList->expects($this->once())
            ->method('setSqlLimit')
            ->with($actPage * $perPage, $perPage);

        $oxArticle_1 = $this->generateArticleMock(
            array(),
            'ARTICLE_1',
            'Article 1',
            'http://dummy.url/icon_1',
            'Description 1',
            1
        );

        $oxArticleList = $this->mockIterator(
            $oxArticleList,
            array(
                 $oxArticle_1
            )
        );

        oxUtilsObject::setClassInstance('oxArticleList', $oxArticleList);

        $oCushy->getArticleList();

        $ajaxResponse = $this->getAjaxResponseValue($oCushy);
        $articleList  = $ajaxResponse['result'];

        $this->assertEquals(
            $expectedArticleList,
            $articleList
        );
    }

    /**
     *
     */
    public function testGetArticle()
    {
        $currencySign = $actShopCurrencyObject = $this->getOxConfig()
            ->getActShopCurrencyObject()
            ->sign;

        $expectedArticle = array (
            'productId'         => 'ARTICLE_1',
            'title'             => 'Article 1',
            'shortDesc'         => 'Description 1',
            'price'             => 1,
            'currency'          => $currencySign,
            'formattedPrice'    => '1 ' . $currencySign,
            'icon'              => 'http://dummy.url/icon_1',
            'longDesc'          => 'Long Description 1',
            'link'              => 'http://link-to-article.de/1',
            'hasVariants'       => false,
            'variantGroupCount' => 0
        );

        $oCushy = new cushymoco();
        $oCushy->init();

        $this->setRequestParam('anid', 'ARTICLE_1');

        $oxArticle = $this->generateArticleMock(
            array('load'),
            $expectedArticle['productId'],
            $expectedArticle['title'],
            $expectedArticle['icon'],
            $expectedArticle['shortDesc'],
            $expectedArticle['price'],
            $expectedArticle['longDesc'],
            $expectedArticle['link'],
            null,
            $expectedArticle['variantGroupCount']
        );

        $oxArticle->expects($this->any())
            ->method('load')
            ->with('ARTICLE_1')
            ->will($this->returnValue(true));

        oxUtilsObject::setClassInstance('oxArticle', $oxArticle);

        $oCushy->getArticle();

        $ajaxResponse = $this->getAjaxResponseValue($oCushy);
        $article  = $ajaxResponse['result'];

        $this->assertEquals(
            $expectedArticle,
            $article
        );
    }

    /**
     *
     */
    public function testGetArticleThatDoesNotExist()
    {
        $oCushy = new cushymoco();
        $oCushy->init();

        $this->setRequestParam('anid', new oxField('iDoNotExist'));
        $oCushy->getArticle();
        $ajaxResponse = $this->getAjaxResponseValue($oCushy);

        $this->assertSame(
            'article not found',
            $ajaxResponse['error']
        );
    }

    /**
     *
     */
    public function testGetArticleWithIdNotProvided()
    {
        $oCushy = new cushymoco();
        $oCushy->init();

        $oCushy->getArticle();
        $ajaxResponse = $this->getAjaxResponseValue($oCushy);

        $this->assertSame(
            'article not found',
            $ajaxResponse['error']
        );
    }

    /**
     *
     */
    public function testGetArticleVariantGroups()
    {
        $oCushy = new cushymoco();
        $oCushy->init();

        $this->setRequestParam('anid', new oxField('ARTICLE_1'));

        $oxArticle = $this->generateArticleMock(
            array('getVariantSelections', 'load'),
            'ARTICLE_1',
            'Article 1',
            'http://dummy.url/icon_1',
            'Description 1',
            1,
            'Long Description 1',
            'http://link-to-article.de/1',
            null,
            1
        );

        $oxVariantSelectList = $this->getMock(
            'oxVariantSelectList',
            array('getLabel'),
            array(),
            '',
            false
        );

        $oxVariantSelectList->expects($this->once())
            ->method('getLabel')
            ->will($this->returnValue('Choose color'));

        $oxArticle->expects($this->once())
            ->method('load')
            ->with(new oxField('ARTICLE_1'))
            ->will($this->returnValue(true));

        $oxArticle->expects($this->once())
            ->method('getVariantSelections')
            ->will($this->returnValue(
                   array('selections' => array($oxVariantSelectList))
                ));

        oxUtilsObject::setClassInstance('oxArticle', $oxArticle);

        $oCushy->getArticleVariantGroups();

        $ajaxResponse = $this->getAjaxResponseValue($oCushy);

        $this->assertEquals(
            array(
                 array(
                    'groupId' => 0,
                    'title'   => 'Choose color'
                )
            ),
            $ajaxResponse['result']
        );
    }

    /**
     *
     */
    public function testGetArticleVariantGroupsWithNoArticleSpecified()
    {
        $oCushy = new cushymoco();
        $oCushy->init();

        $oCushy->getArticleVariantGroups();

        $ajaxResponse = $this->getAjaxResponseValue($oCushy);

        $this->assertSame(
            'article id not provided',
            $ajaxResponse['error']
        );
    }

}
