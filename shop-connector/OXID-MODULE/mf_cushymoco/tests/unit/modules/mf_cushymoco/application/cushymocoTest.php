<?php

class Unit_Modules_mf_cushymoco_Application_cushymocoTest extends CushymocoTestCase {

    /**
     * Test if init method sets a custom exception handler.
     */
    public function testInitSetsExceptionHandler()
    {
        $oCushy = new cushymoco();
        $oCushy->init();

        $oldExceptionHandler = set_exception_handler(array($this, 'void'));
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
            ->with('{"error":"Can\u0027t find any shop version layer.","result":null,"cartItemCount":null}')
            ->will($this->returnValue('ok, und exit'));
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

        try {
            ob_start();
            $oCushy->exceptionHandler($exception);
            $sObContents = ob_get_contents();
            ob_end_clean();
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }

        $sErrMessage = json_decode($sObContents)->error;


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
        $oCushy = new cushymoco();
        $oCushy->init();

        $oCushy->login();
    }

}
