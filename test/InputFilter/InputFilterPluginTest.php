<?php

namespace ZFTest\ContentValidation\InputFilter;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Mvc\MvcEvent;
use ZF\ContentValidation\InputFilter\InputFilterPlugin;

class InputFilterPluginTest extends TestCase
{
    public function setUp()
    {
        $this->event = $event = new MvcEvent();

        $controller = $this->getMock('Zend\Mvc\Controller\AbstractController');
        $controller->expects($this->any())
            ->method('getEvent')
            ->will($this->returnValue($event));

        $this->plugin = new InputFilterPlugin();
        $this->plugin->setController($controller);
    }

    public function testMissingInputFilterParamInEventCausesPluginToYieldNull()
    {
        $this->assertNull($this->plugin->__invoke());
    }

    public function testInvalidTypeInEventInputFilterParamCausesPluginToYieldNull()
    {
        $this->event->setParam('ZF\ContentValidation\InputFilter', (object) array('foo' => 'bar'));
        $this->assertNull($this->plugin->__invoke());
    }

    public function testValidInputFilterInEventIsReturnedByPlugin()
    {
        $inputFilter = $this->getMock('Zend\InputFilter\InputFilterInterface');
        $this->event->setParam('ZF\ContentValidation\InputFilter', $inputFilter);
        $this->assertSame($inputFilter, $this->plugin->__invoke());
    }
}
