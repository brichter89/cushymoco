<?php

class Unit_Modules_mf_cushymoco_Application_cushymocoTest extends CushymocoTestCase {

    /** @var  cushymoco $cushymoco */
    protected $cushymoco;

    /**
     * @return null|void
     */
    public function setUp()
    {
        $this->cushymoco = new cushymoco();
        $this->cushymoco->init();
    }
    /**
     * Test if init method sets a custom exception handler.
     */
    public function testInitSetsExceptionHandler()
    {
        $oldExceptionHandler = set_exception_handler(array($this, 'dummy'));
        $this->assertSame(
            array($this->cushymoco, 'exceptionHandler'),
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
        $sMessage = "I'm an exception! Yeah!";
        $exception = new \Exception($sMessage);

        ob_start();
        $this->cushymoco->exceptionHandler($exception);
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
        $this->assertEquals(
            'cushymoco.tpl',
            $this->cushymoco->render()
        );
    }

    /**
     *
     */
    public function testLogin()
    {
        $expectedUserId = 'oxdefaultadmin';

        $this->setRequestParam('lgn_usr', new oxField(oxADMIN_LOGIN));
        $this->setRequestParam('lgn_pwd', new oxField(oxADMIN_PASSWD));

        $this->cushymoco->login();
        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

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
        $this->setRequestParam('lgn_usr', new oxField($user));
        $this->setRequestParam('lgn_pwd', new oxField($pass));

        ob_start();
        $this->cushymoco->login();
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
        $this->setRequestParam('lgn_usr', $user);
        $this->setRequestParam('lgn_pwd', $pass);

        $this->cushymoco->login();
        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

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
        $this->loginAsAdmin();

        $this->cushymoco->logout();
        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $oxSession = $this->getOxSession();
        $this->assertNull($ajaxResponse['error']);
        $this->assertTrue($ajaxResponse['result']['logout']);
        $this->assertFalse($oxSession->getUser());
    }

    /**
     *
     */
    public function testGetUserData()
    {
        $expected = array(
            'username'  => 'admin@dummy.url',
            'firstname' => 'John',
            'lastname'  => 'Doe',
            'company'   => 'Your Company Name',
        );

        $this->loginAsAdmin();

        // Set admin username
        $oxUser = oxNew( 'oxuser' );
        $oxUser->loadActiveUser();
        $oxUser->oxuser__oxusername = new oxField($expected['username']);

        $this->getOxSession()->unitCustModUser = $oxUser;

        $this->cushymoco->getUserData();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);
        $result = $ajaxResponse['result'];

        $this->assertSame(
            $expected,
            $result
        );
    }

    /**
     *
     */
    public function testGetUserDataWhenUserNotLoggedIn()
    {
        $this->cushymoco->getUserData();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertSame(
            'user not logged on',
            $ajaxResponse['error']
        );
    }

    /**
     *
     */
    public function testGetAccountData()
    {
        $expected = array(
            'user'     => array(
                'username'     => 'admin@dummy.url',
                'firstname'    => 'John',
                'lastname'     => 'Doe',
                'customerNo'   => 1,
                'company'      => 'Your Company Name',
                'phone'        => '217-8918712',
                'fax'          => '217-8918713',
                'privatePhone' => '',
                'mobile'       => '',
            ),
            'billing'  => array(
                'street'     => 'Maple Street',
                'streetNo'   => 2425,
                'additional' => '',
                'city'       => 'Any City',
                'zip'        => 9041,
                'state'      => '',
                'country'    => 'Deutschland',
            ),
            'shipping' => array(
                array(
                    'firstName'  => 'John',
                    'lastName'   => 'Doe',
                    'company'    => 'Your Company Name',
                    'street'     => 'Maple Street',
                    'streetNo'   => 2425,
                    'additional' => '',
                    'city'       => 'Any City',
                    'zip'        => 9041,
                    'country'    => 'Deutschland',
                    'state'      => '',
                    'phone'      => '217-8918712',
                    'fax'        => '217-8918713',
                ),
                array(
                    'firstName'  => 'Jane',
                    'lastName'   => 'Doe',
                    'company'    => 'Another Company Name',
                    'street'     => 'Foo Street',
                    'streetNo'   => 1234,
                    'additional' => 'a',
                    'city'       => 'Some Other City',
                    'zip'        => 9001,
                    'country'    => 'Kanada',
                    'state'      => 'Prince Edward Island',
                    'phone'      => '257-2948210',
                    'fax'        => '257-2948211',
                )
            ),
        );

        $this->loginAsAdmin();

        $oxAddress1 = oxNew('oxAddress');
        $oxAddress1->oxaddress__oxfname     = new oxField($expected['shipping'][0]['firstName']);
        $oxAddress1->oxaddress__oxlname     = new oxField($expected['shipping'][0]['lastName']);
        $oxAddress1->oxaddress__oxcompany   = new oxField($expected['shipping'][0]['company']);
        $oxAddress1->oxaddress__oxstreet    = new oxField($expected['shipping'][0]['street']);
        $oxAddress1->oxaddress__oxstreetnr  = new oxField($expected['shipping'][0]['streetNo']);
        $oxAddress1->oxaddress__oxaddinfo   = new oxField($expected['shipping'][0]['additional']);
        $oxAddress1->oxaddress__oxcity      = new oxField($expected['shipping'][0]['city']);
        $oxAddress1->oxaddress__oxzip       = new oxField($expected['shipping'][0]['zip']);
        $oxAddress1->oxaddress__oxcountryid = new oxField('a7c40f631fc920687.20179984');
        $oxAddress1->oxaddress__oxfon       = new oxField($expected['shipping'][0]['phone']);
        $oxAddress1->oxaddress__oxfax       = new oxField($expected['shipping'][0]['fax']);

        $oxAddress2 = oxNew('oxAddress');
        $oxAddress2->oxaddress__oxfname     = new oxField($expected['shipping'][1]['firstName']);
        $oxAddress2->oxaddress__oxlname     = new oxField($expected['shipping'][1]['lastName']);
        $oxAddress2->oxaddress__oxcompany   = new oxField($expected['shipping'][1]['company']);
        $oxAddress2->oxaddress__oxstreet    = new oxField($expected['shipping'][1]['street']);
        $oxAddress2->oxaddress__oxstreetnr  = new oxField($expected['shipping'][1]['streetNo']);
        $oxAddress2->oxaddress__oxaddinfo   = new oxField($expected['shipping'][1]['additional']);
        $oxAddress2->oxaddress__oxcity      = new oxField($expected['shipping'][1]['city']);
        $oxAddress2->oxaddress__oxzip       = new oxField($expected['shipping'][1]['zip']);
        $oxAddress2->oxaddress__oxcountryid = new oxField('8f241f11095649d18.02676059');
        $oxAddress2->oxaddress__oxstateid   = new oxField('PE');
        $oxAddress2->oxaddress__oxfon       = new oxField($expected['shipping'][1]['phone']);
        $oxAddress2->oxaddress__oxfax       = new oxField($expected['shipping'][1]['fax']);

        $addresses = $this->getMock(
            'oxList',
            array('rewind', 'valid', 'current', 'key', 'next', 'count')
        );
        $addresses = $this->mockIterator(
            $addresses,
            array(
                 $oxAddress1,
                 $oxAddress2
            )
        );

        $oxUser = $this->getMock('oxUser', array('getUserAddresses'));
        $oxUser->expects($this->once())
            ->method('getUserAddresses')
            ->will($this->returnValue($addresses));
        $oxUser->loadActiveUser();
        // set admin username
        $oxUser->oxuser__oxusername = new oxField($expected['user']['username']);

        $this->getOxSession()->unitCustModUser = $oxUser;

        $this->cushymoco->getAccountData();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);


        $this->assertEquals(
            $expected,
            $ajaxResponse['result']
        );
    }

    /**
     *
     */
    public function testGetAccountDataWhenUserNotLoggedIn()
    {
        $this->cushymoco->getAccountData();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertSame(
            'user not logged on',
            $ajaxResponse['error']
        );
    }

    /**
     *
     */
    public function testGetCategoryTitle()
    {
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

        $this->cushymoco->getCategoryTitle();

        $ajaxResponseValue = $this->getAjaxResponseValue($this->cushymoco);

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
        $this->cushymoco->getCategoryTitle();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

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


        $this->cushymoco->getCategoryList();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);
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

        $this->cushymoco->getCategoryList();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);
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
        $expectedArticleList = array (
            array (
                'productId' => 'ARTICLE_1',
                'title' => 'Article 1',
                'shortDesc' => 'Description 1',
                'price' => 1,
                'currency' => $this->getCurrencySign(),
                'formattedPrice' => '1 ' . $this->getCurrencySign(),
                'icon' => 'http://dummy.url/icon_1',
            )
        );

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

        $this->cushymoco->getArticleList();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);
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
        $expectedArticle = array (
            'productId'         => 'ARTICLE_1',
            'title'             => 'Article 1',
            'shortDesc'         => 'Description 1',
            'price'             => 1,
            'currency'          => $this->getCurrencySign(),
            'formattedPrice'    => '1 ' . $this->getCurrencySign(),
            'icon'              => 'http://dummy.url/icon_1',
            'longDesc'          => 'Long Description 1',
            'link'              => 'http://link-to-article.de/1',
            'hasVariants'       => false,
            'variantGroupCount' => 0
        );

        $this->setRequestParam('anid', new oxField('ARTICLE_1'));

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
            ->with(new oxField('ARTICLE_1'))
            ->will($this->returnValue(true));

        oxUtilsObject::setClassInstance('oxArticle', $oxArticle);

        $this->cushymoco->getArticle();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);
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
        $this->setRequestParam('anid', new oxField('iDoNotExist'));
        $this->cushymoco->getArticle();
        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

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
        $this->cushymoco->getArticle();
        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

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

        $this->cushymoco->getArticleVariantGroups();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

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
        $this->cushymoco->getArticleVariantGroups();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertSame(
            'article id not provided',
            $ajaxResponse['error']
        );
    }

    /**
     *
     */
    public function testGetArticleVariants()
    {
        $expectedResult = array(
            array(
                'groupId'   => 0,
                'variantId' => '5d4bc935f54e8f1f2cf08741638e1fcd',
                'title'     => 'W 31/L 34'
            ),
            array(
                'groupId'   => 0,
                'variantId' => '8a34d35131d5f014799a4115a815116d',
                'title'     => 'W 32/L 32'
            ),
            array(
                'groupId'   => 0,
                'variantId' => '67b65ed314181c4e222b662ef7dfc6d0',
                'title'     => 'W 34/L 32'
            ),
            array(
                'groupId'   => 0,
                'variantId' => 'df1ca9142e8bb6bacb61fe5e2c22abb9',
                'title'     => 'W 30/L 30'
            ),
            array(
                'groupId'   => 0,
                'variantId' => 'c2ba09fb91c16482ea1e981354e7fef7',
                'title'     => 'W 31/L 30'
            ),
            array(
                'groupId'   => 1,
                'variantId' => '560173ba980b9f5f7f8d9d3cce1c2446',
                'title'     => 'Dark Blue'
            ),
            array(
                'groupId'   => 1,
                'variantId' => '6f1979fb2d97601ca6f4dc09587dc9af',
                'title'     => 'Schwarz'
            )
        );

        $this->setRequestParam('anid', new oxField('6b63456b3abeeeccd9b085a76ffba1a3'));

        $this->cushymoco->getArticleVariants();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertSame(
            $expectedResult,
            $ajaxResponse['result']
        );
    }

    /**
     *
     */
    public function testGetArticleVariantsWhenFirstVariantSelected()
    {
        $expectedResult = array(
            array(
                'groupId'   => 0,
                'variantId' => '5d4bc935f54e8f1f2cf08741638e1fcd',
                'title'     => 'W 31/L 34'
            ),
            array(
                'groupId'   => 0,
                'variantId' => '8a34d35131d5f014799a4115a815116d',
                'title'     => 'W 32/L 32'
            ),
            array(
                'groupId'   => 0,
                'variantId' => '67b65ed314181c4e222b662ef7dfc6d0',
                'title'     => 'W 34/L 32'
            ),
            array(
                'groupId'   => 0,
                'variantId' => 'df1ca9142e8bb6bacb61fe5e2c22abb9',
                'title'     => 'W 30/L 30'
            ),
            array(
                'groupId'   => 0,
                'variantId' => 'c2ba09fb91c16482ea1e981354e7fef7',
                'title'     => 'W 31/L 30'
            ),
            array(
                'groupId'   => 1,
                'variantId' => '560173ba980b9f5f7f8d9d3cce1c2446',
                'title'     => 'Dark Blue'
            )
        );

        $this->setRequestParam('anid', new oxField('6b63456b3abeeeccd9b085a76ffba1a3'));
        $this->setRequestParam('selectedVariant', array('5d4bc935f54e8f1f2cf08741638e1fcd'));

        $this->cushymoco->getArticleVariants();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertSame(
            $expectedResult,
            $ajaxResponse['result']
        );
    }

    /**
     *
     */
    public function testGetArticleVariantsWhenSecondVariantSelected()
    {
        $expectedResult = array (
            array(
                'groupId'   => 0,
                'variantId' => '5d4bc935f54e8f1f2cf08741638e1fcd',
                'title'     => 'W 31/L 34'
            ),
            array(
                'groupId'   => 0,
                'variantId' => '8a34d35131d5f014799a4115a815116d',
                'title'     => 'W 32/L 32'
            ),
            array(
                'groupId'   => 0,
                'variantId' => '67b65ed314181c4e222b662ef7dfc6d0',
                'title'     => 'W 34/L 32'
            ),
            array(
                'groupId'   => 0,
                'variantId' => 'df1ca9142e8bb6bacb61fe5e2c22abb9',
                'title'     => 'W 30/L 30'
            ),
            array(
                'groupId'   => 0,
                'variantId' => 'c2ba09fb91c16482ea1e981354e7fef7',
                'title'     => 'W 31/L 30'
            )
        );

        $this->setRequestParam('anid', new oxField('6b63456b3abeeeccd9b085a76ffba1a3'));
        $this->setRequestParam('selectedVariant', array('6f1979fb2d97601ca6f4dc09587dc9af'));

        $this->cushymoco->getArticleVariants();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertSame(
            $expectedResult,
            $ajaxResponse['result']
        );
    }

    /**
     *
     */
    public function testGetArticleVariantsWithNoArticleSpecified()
    {
        $this->cushymoco->getArticleVariants();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertSame(
            'article id not provided',
            $ajaxResponse['error']
        );
    }



    /**
     *
     */
    public function testGetVariantProductId()
    {
        $this->setRequestParam('anid', new oxField('6b63456b3abeeeccd9b085a76ffba1a3'));
        $this->setRequestParam(
            'selectedVariant',
            array(
                 'c2ba09fb91c16482ea1e981354e7fef7',
                 '560173ba980b9f5f7f8d9d3cce1c2446'
            )
        );

        $this->cushymoco->getVariantProductId();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertSame(
            '6b66f538ede23a41f0598a3bc38e8b52',
            $ajaxResponse['result']
        );
    }

    /**
     *
     */
    public function testGetVariantProductIdWithNoArticleSpecified()
    {
        $this->cushymoco->getVariantProductId();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertSame(
            'article id not provided',
            $ajaxResponse['error']
        );
    }

    /**
     *
     */
    public function testGetArticleMedia()
    {
        $oxMedia1 = $this->getMock('oxMediaUrl', null, array(), '', false);
        $oxMedia1->oxmediaurls__oxid         = new oxField('MEDIA_1');
        $oxMedia1->oxmediaurls__oxurl        = new oxField('http://example.url/media1');
        $oxMedia1->oxmediaurls__oxdesc       = new oxField('Media 01');
        $oxMedia1->oxmediaurls__oxisuploaded = new oxField(true);

        $oxMedia2 = $this->getMock('oxMediaUrl', null, array(), '', false);
        $oxMedia2->oxmediaurls__oxid         = new oxField('MEDIA_2');
        $oxMedia2->oxmediaurls__oxurl        = new oxField('http://example.url/media2');
        $oxMedia2->oxmediaurls__oxdesc       = new oxField('Media 02');
        $oxMedia2->oxmediaurls__oxisuploaded = new oxField(false);


        $oxList = $this->getMock(
            'oxList',
            array('rewind', 'valid', 'current', 'key', 'next', 'count')
        );
        $oxList = $this->mockIterator(
            $oxList,
            array(
                 $oxMedia1,
                 $oxMedia2
            )
        );

        $oxArticle = $this->generateArticleMock(
            array('load', 'getMediaUrls'),
            'MyMediaArticle',
            'My Media Article',
            'http://example.url/icon',
            'Mock Article with media Urls',
            12
        );
        $oxArticle->expects($this->once())
            ->method('load')
            ->with(new oxField('MyMediaArticle'))
            ->will($this->returnValue(true));
        $oxArticle->expects($this->any())
            ->method('getMediaUrls')
            ->will($this->returnValue($oxList));

        $this->setRequestParam('anid', new oxField('MyMediaArticle'));

        oxUtilsObject::setClassInstance('oxArticle', $oxArticle);

        $this->cushymoco->getArticleMedia();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertEquals(
            array(
                 array(
                     'id'     => 'MEDIA_1',
                     'url'    => 'http://example.url/media1',
                     'desc'   => 'Media 01',
                     'upload' => true
                 ),
                 array(
                     'id'     => 'MEDIA_2',
                     'url'    => 'http://example.url/media2',
                     'desc'   => 'Media 02',
                     'upload' => false
                 )
            ),
            $ajaxResponse['result']
        );
    }

    /**
     *
     */
    public function testGetArticleMediaWithNoArticleSpecified()
    {
        $this->cushymoco->getArticleMedia();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertSame(
            'article id not provided',
            $ajaxResponse['error']
        );
    }

    /**
     *
     */
    public function testGetArticleImages()
    {
        $expected = array(
            array(
                'productId' => '943ed656e21971fb2f1827facbba9bec',
                'pictureId' => 1,
                'icon'      => 'http://localhost/out/pictures/generated/product/1/87_87_75/front_z1.jpg',
                'image'     => 'http://localhost/out/pictures/generated/product/1/380_340_75/front_z1.jpg',
                'bigImage'  => 'http://localhost/out/pictures/generated/product/1/665_665_75/front_z1.jpg',
            ),
            array(
                'productId' => '943ed656e21971fb2f1827facbba9bec',
                'pictureId' => 2,
                'icon'      => 'http://localhost/out/pictures/generated/product/2/87_87_75/back_z2.jpg',
                'image'     => 'http://localhost/out/pictures/generated/product/2/380_340_75/back_z2.jpg',
                'bigImage'  => 'http://localhost/out/pictures/generated/product/2/665_665_75/back_z2.jpg',
            ),
            array(
                'productId' => '943ed656e21971fb2f1827facbba9bec',
                'pictureId' => 3,
                'icon'      => 'http://localhost/out/pictures/generated/product/3/87_87_75/detail1_z3.jpg',
                'image'     => 'http://localhost/out/pictures/generated/product/3/380_340_75/detail1_z3.jpg',
                'bigImage'  => 'http://localhost/out/pictures/generated/product/3/665_665_75/detail1_z3.jpg',
            ),
            array(
                'productId' => '943ed656e21971fb2f1827facbba9bec',
                'pictureId' => 4,
                'icon'      => 'http://localhost/out/pictures/generated/product/4/87_87_75/detail2_z4.jpg',
                'image'     => 'http://localhost/out/pictures/generated/product/4/380_340_75/detail2_z4.jpg',
                'bigImage'  => 'http://localhost/out/pictures/generated/product/4/665_665_75/detail2_z4.jpg',
            ),
        );

        $this->setRequestParam('anid', new oxField('943ed656e21971fb2f1827facbba9bec'));

        $this->cushymoco->getArticleImages();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertSame(
            $expected,
            $ajaxResponse['result']
        );
    }

    /**
     *
     */
    public function testGetArticleImagesWithNoArticleSpecified()
    {
        $this->cushymoco->getArticleImages();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertSame(
            'article id not provided',
            $ajaxResponse['error']
        );
    }

    /**
     *
     */
    public function testGetArticleDocumentsIsNotImplemented()
    {
        $this->cushymoco->getArticleDocuments();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertSame(
            'NOT_IMPLEMENTED',
            $ajaxResponse['error']
        );
    }

    /**
     *
     */
    public function testGetArticleVideosIsNotImplemented()
    {
        $this->cushymoco->getArticleVideos();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertSame(
            'NOT_IMPLEMENTED',
            $ajaxResponse['error']
        );
    }

    /**
     *
     */
    public function testSearchProductsByName()
    {
        $expected = array(
            'count'    => 5,
            'articles' => array(
                '531b537118f5f4d7a427cdb825440922' => array(
                    'id'    => '531b537118f5f4d7a427cdb825440922',
                    'title' => 'Kuyichi Jeans Anna',
                    'short' => '',
                    'price' => 99.9,
                ),
                '531f91d4ab8bfb24c4d04e473d246d0b' => array(
                    'id'    => '531f91d4ab8bfb24c4d04e473d246d0b',
                    'title' => 'Kuyichi Jeans Cole',
                    'short' => '',
                    'price' => 89.9,
                ),
                '6b63456b3abeeeccd9b085a76ffba1a3' => array(
                    'id'    => '6b63456b3abeeeccd9b085a76ffba1a3',
                    'title' => 'Kuyichi Jeans Candy',
                    'short' => '',
                    'price' => 89.9,
                ),
                '6b66d82af984e5ad46b9cb27b1ef8aae' => array(
                    'id'    => '6b66d82af984e5ad46b9cb27b1ef8aae',
                    'title' => 'Kuyichi Jeans Sugar',
                    'short' => '',
                    'price' => 89.9,
                ),
                '943ed656e21971fb2f1827facbba9bec' => array(
                    'id'    => '943ed656e21971fb2f1827facbba9bec',
                    'title' => 'Kuyichi Jeans Mick',
                    'short' => '',
                    'price' => 109,
                )
            )
        );

        $this->setRequestParam('searchparam', new oxField('jeans'));

        $this->cushymoco->searchProducts();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertEquals(
            $expected,
            $ajaxResponse['result']
        );
    }
    
    /**
     *
     */
    public function testSearchProductsByArticleNumber()
    {
        $expected = array(
            'count'    => 1,
            'articles' => array(
                1951 => array(
                    'id'    => 1951,
                    'title' => 'Wanduhr BIKINI GIRL',
                    'short' => '',
                    'price' => 14,
                ),
            ),
        );

        $this->setRequestParam('searchparam', new oxField(1951));

        $this->cushymoco->searchProducts();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertEquals(
            $expected,
            $ajaxResponse['result']
        );
    }

    /**
     *
     */
    public function testSearchProductsInCategory()
    {
        $expected = array(
            'count'    => 3,
            'articles' => array(
                '531b537118f5f4d7a427cdb825440922' => array(
                    'id'    => '531b537118f5f4d7a427cdb825440922',
                    'title' => 'Kuyichi Jeans Anna',
                    'short' => '',
                    'price' => 99.9,
                ),
                '6b63456b3abeeeccd9b085a76ffba1a3' => array(
                    'id'    => '6b63456b3abeeeccd9b085a76ffba1a3',
                    'title' => 'Kuyichi Jeans Candy',
                    'short' => '',
                    'price' => 89.9,
                ),
                '6b66d82af984e5ad46b9cb27b1ef8aae' => array(
                    'id'    => '6b66d82af984e5ad46b9cb27b1ef8aae',
                    'title' => 'Kuyichi Jeans Sugar',
                    'short' => '',
                    'price' => 89.9,
                ),
            ),
        );

        $this->setRequestParam('searchparam', new oxField('jeans'));
        $this->setRequestParam('searchcnid', new oxField('94342f1d6f3b6fe9f1520d871f566511'));

        $this->cushymoco->searchProducts();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertEquals(
            $expected,
            $ajaxResponse['result']
        );
    }

    /**
     *
     */
    public function testSearchProductsByVendor()
    {
        $this->markTestIncomplete('How to search for vendor?');

//        $expected = array();
//
//        $this->setRequestParam('searchparam', new oxField('jeans'));
//        $this->setRequestParam('searchcnid', new oxField('kuichi'));
//
//        $this->cushymoco->searchProducts();
//
//        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);
//
//        $this->assertEquals(
//            $expected,
//            $ajaxResponse['result']
//        );
    }

    /**
     *
     */
    public function testGetContent()
    {
        $expected = array(
            array(
                'contentId' => 'oxagb',
                'title'     => 'AGB',
            ),
            array(
                'contentId' => 'oximpressum',
                'title'     => 'Impressum',
            ),
        );

        $this->cushymoco->getContent();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertEquals(
            $expected,
            $ajaxResponse['result']
        );
    }

    /**
     *
     */
    public function testGetContentOfContentId()
    {
        $expected = array(
            'title'   => 'AGB',
            'content' => "<div><strong>AGB</strong></div>\r\n<div><strong>&nbsp;</strong></div>\r\n<div>F�gen Sie hier Ihre allgemeinen Gesch�ftsbedingungen ein:</div>\r\n<div>&nbsp;</div>\r\n<div><span style=\"font-weight: bold\">Strukturvorschlag:</span><br>\r\n<br>\r\n<ol>\r\n<li>Geltungsbereich </li>\r\n<li>Vertragspartner </li>\r\n<li>Angebot und Vertragsschluss </li>\r\n<li>Widerrufsrecht, Widerrufsbelehrung, Widerrufsfolgen </li>\r\n<li>Preise und Versandkosten </li>\r\n<li>Lieferung </li>\r\n<li>Zahlung </li>\r\n<li>Eigentumsvorbehalt </li>\r\n<li>Gew�hrleistung </li>\r\n<li>Weitere Informationen</li></ol></div>",
            'cnid'    => 'oxagb',
        );

        $this->setRequestParam('cnid', new oxField('oxagb'));

        $this->cushymoco->getContent();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertEquals(
            $expected,
            $ajaxResponse['result']
        );
    }

    /**
     *
     */
    public function testGetContentOfContentIdInDifferentLanguage()
    {
        $expected = array(
            'title'   => 'Terms and Conditions',
            'content' => 'Insert your terms and conditions here.',
            'cnid'    => 'oxagb',
        );

        $this->setRequestParam('cnid', new oxField('oxagb'));
        $this->setRequestParam('lid', 1);

        $this->cushymoco->getContent();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertEquals(
            $expected,
            $ajaxResponse['result']
        );
    }

    /**
     *
     */
    public function testAddToBasket()
    {
        $this->setRequestParam('anid', new oxField(1126));

        $this->cushymoco->addToBasket();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertSame(1, $ajaxResponse['result']);
        $this->assertSame(1, $ajaxResponse['cartItemCount']);
    }

    /**
     *
     */
    public function testAddToBasketAddMultipleArticles()
    {
        $this->setRequestParam('anid', new oxField(1131));
        $this->setRequestParam('qty',  new oxField(2));

        $this->cushymoco->addToBasket();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);
        $basketProducts = $this->getOxSession()->getBasket()->getContents();
        $firstProductInBasket = array_shift($basketProducts);

        $this->assertEquals(1, $ajaxResponse['result']);
        $this->assertEquals(1, $ajaxResponse['cartItemCount']);
        $this->assertEquals(2, $firstProductInBasket->getAmount());
    }

    /**
     *
     */
    public function testAddToBasketWhenProductNotBuyable()
    {
        $this->setRequestParam('anid', new oxField(1127));

        $this->cushymoco->addToBasket();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertSame(
            'Artikel ist nicht kaufbar',
            $ajaxResponse['error']
        );
    }

    /**
     *
     */
    public function testDeleteFromBasket()
    {
        $expected = array(
            'success'       => true,
            'totalProducts' => '0,00',
            'shipping'      => false,
            'total'         => '0,00',
            'currency'      => $this->getCurrencySign(),
        );

        $this->addToBasket(1126);

        $this->setRequestParam('anid', new oxField(1126));

        $this->cushymoco->deleteFromBasket();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertEquals(
            $expected,
            $ajaxResponse['result']
        );
        $this->assertEquals(0, $ajaxResponse['cartItemCount']);
    }

    /**
     *
     */
    public function testDeleteFromBasketWhenArticleNotExisting()
    {
        $this->setRequestParam('anid', new oxField('I do not exist!!!11'));

        $this->cushymoco->deleteFromBasket();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertSame(
            'Shopping cart update failed.',
            $ajaxResponse['error']
        );
    }

    /**
     *
     */
    public function testDeleteFromBasketWhenNoArticleIdProvided()
    {
        $this->cushymoco->deleteFromBasket();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertSame(
            'Shopping cart update failed.',
            $ajaxResponse['error']
        );
    }

    /**
     *
     */
    public function testUpdateBasket()
    {
        $expected = array(
            'success'       => true,
            'productPrice'  => '3.366,00 ' . $this->getCurrencySign(),
            'totalProducts' => '3.366,00',
            'shipping'      => false,
            'total'         => '3.366,00',
            'currency'      => $this->getCurrencySign(),
        );

        $this->addToBasket(1126);

        $this->setRequestParam('anid', new oxField(1126));
        $this->setRequestParam('qty',  new oxField(99));

        $this->cushymoco->updateBasket();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);
        $basketProducts = $this->getOxSession()->getBasket()->getContents();
        $firstProductInBasket = array_shift($basketProducts);

        $this->assertEquals(
            $expected,
            $ajaxResponse['result']
        );
        $this->assertEquals(1, $ajaxResponse['cartItemCount']);
        $this->assertEquals(99, $firstProductInBasket->getAmount());
    }

    /**
     *
     */
    public function testUpdateBasketWhenQuantityNotProvided()
    {
        $expected = array(
            'success'       => true,
            'productPrice'  => '34,00 ' . $this->getCurrencySign(),
            'totalProducts' => '34,00',
            'shipping'      => false,
            'total'         => '34,00',
            'currency'      => $this->getCurrencySign(),
        );

        $this->addToBasket(1126, 99);

        $this->setRequestParam('anid', new oxField(1126));

        $this->cushymoco->updateBasket();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);
        $basketProducts = $this->getOxSession()->getBasket()->getContents();
        $firstProductInBasket = array_shift($basketProducts);

        $this->assertEquals(
            $expected,
            $ajaxResponse['result']
        );
        $this->assertEquals(1, $ajaxResponse['cartItemCount']);
        $this->assertEquals(1, $firstProductInBasket->getAmount());
    }

    /**
     *
     */
    public function testUpdateBasketWhenArticleIdNotProvided()
    {
        $this->addToBasket(1126);

        $this->cushymoco->updateBasket();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertSame(
            'article id not provided',
            $ajaxResponse['error']
        );
    }

    /**
     *
     */
    public function testUpdateBasketWhenArticleNotExisting()
    {
        $this->setRequestParam('anid', new oxField('I do not exist!!!11'));

        $this->addToBasket(1126);

        $this->cushymoco->updateBasket();

        $ajaxResponse = $this->getAjaxResponseValue($this->cushymoco);

        $this->assertSame(
            'article could not be loaded',
            $ajaxResponse['error']
        );
    }

}
