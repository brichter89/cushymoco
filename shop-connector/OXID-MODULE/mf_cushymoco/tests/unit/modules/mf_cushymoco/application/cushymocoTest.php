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
    public function testLoginWithCorrectCredentials()
    {
        $oCushy = new cushymoco();
        $oCushy->init();

        $this->setRequestParam('lgn_usr', new oxField(oxADMIN_LOGIN));
        $this->setRequestParam('lgn_pwd', new oxField(oxADMIN_PASSWD));

        $oCushy->login();
        $ajaxResponse = $this->getAjaxResponseValue($oCushy);

        $userId = $ajaxResponse['result']['userId'];

        $this->assertEquals(
            'oxdefaultadmin',
            $userId
        );
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

}
