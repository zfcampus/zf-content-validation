<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2013 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentValidation\InputFilter;

use PHPUnit_Framework_TestCase as TestCase;
use Zend\Filter\FilterPluginManager;
use Zend\ServiceManager\ServiceManager;
use Zend\Validator\ValidatorPluginManager;
use ZF\ContentValidation\InputFilter\InputFilterAbstractServiceFactory;

class InputFilterAbstractFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->services = new ServiceManager();
        $this->factory = new InputFilterAbstractServiceFactory();
    }

    public function testCannotCreateServiceIfNoConfigServicePresent()
    {
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'filter', 'filter'));
    }

    public function testCannotCreateServiceIfConfigServiceDoesNotHaveInputFiltersConfiguration()
    {
        $this->services->setService('Config', []);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'filter', 'filter'));
    }

    public function testCannotCreateServiceIfConfigInputFiltersDoesNotContainMatchingServiceName()
    {
        $this->services->setService('Config', [
            'input_filters' => [],
        ]);
        $this->assertFalse($this->factory->canCreateServiceWithName($this->services, 'filter', 'filter'));
    }

    public function testCanCreateServiceIfConfigInputFiltersContainsMatchingServiceName()
    {
        $this->services->setService('Config', [
            'input_filters' => [
                'filter' => [],
            ],
        ]);
        $this->assertTrue($this->factory->canCreateServiceWithName($this->services, 'filter', 'filter'));
    }

    public function testCreatesInputFilterInstance()
    {
        $this->services->setService('Config', [
            'input_filters' => [
                'filter' => [],
            ],
        ]);
        $filter = $this->factory->createServiceWithName($this->services, 'filter', 'filter');
        $this->assertInstanceOf('Zend\InputFilter\InputFilterInterface', $filter);
    }

    /**
     * @depends testCreatesInputFilterInstance
     */
    public function testUsesConfiguredValidationAndFilterManagerServicesWhenCreatingInputFilter()
    {
        $filters = new FilterPluginManager();
        $filter  = function ($value) {};
        $filters->setService('foo', $filter);

        $validators = new ValidatorPluginManager();
        $validator  = $this->getMock('Zend\Validator\ValidatorInterface');
        $validators->setService('foo', $validator);

        $this->services->setService('FilterManager', $filters);
        $this->services->setService('ValidatorManager', $validators);
        $this->services->setService('Config', [
            'input_filters' => [
                'filter' => [
                    'input' => [
                        'name' => 'input',
                        'required' => true,
                        'filters' => [
                            [ 'name' => 'foo' ],
                        ],
                        'validators' => [
                            [ 'name' => 'foo' ],
                        ],
                    ],
                ],
            ],
        ]);

        $inputFilter = $this->factory->createServiceWithName($this->services, 'filter', 'filter');
        $this->assertTrue($inputFilter->has('input'));

        $input = $inputFilter->get('input');

        $filterChain = $input->getFilterChain();
        $this->assertSame($filters, $filterChain->getPluginManager());
        $this->assertEquals(1, count($filterChain));
        $this->assertSame($filter, $filterChain->plugin('foo'));
        $this->assertEquals(1, count($filterChain));

        $validatorChain = $input->getvalidatorChain();
        $this->assertSame($validators, $validatorChain->getPluginManager());
        $this->assertEquals(1, count($validatorChain));
        $this->assertSame($validator, $validatorChain->plugin('foo'));
        $this->assertEquals(1, count($validatorChain));
    }
}
