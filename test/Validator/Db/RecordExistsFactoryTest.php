<?php
/**
 * @license   http://opensource.org/licenses/BSD-3-Clause BSD-3-Clause
 * @copyright Copyright (c) 2014-2016 Zend Technologies USA Inc. (http://www.zend.com)
 */

namespace ZFTest\ContentValidation\Validator\Db;

use PHPUnit\Framework\TestCase;
use Zend\Db\Adapter\Adapter;
use Zend\ServiceManager\ServiceManager;
use Zend\Validator\Db\RecordExists;
use Zend\Validator\ValidatorPluginManager;

class RecordExistsFactoryTest extends TestCase
{
    protected $validators;

    protected $adapter;

    protected function setUp()
    {
        parent::setUp();

        $config = include __DIR__ . '/../../../config/module.config.php';

        $this->adapter = $this->prophesize(Adapter::class)->reveal();

        $serviceManager = new ServiceManager();
        $serviceManager->setFactory('CustomAdapter', function () {
            return $this->adapter;
        });

        $this->validators = new ValidatorPluginManager($serviceManager, $config['validators']);
    }

    public function testCreateValidatorWithAdapter()
    {
        $options = [
            'adapter' => 'CustomAdapter',
            'table' => 'my_table',
            'field' => 'my_field',
        ];

        /** @var RecordExists $validator */
        $validator = $this->validators->get('ZF\ContentValidation\Validator\DbRecordExists', $options);

        $this->assertInstanceOf(RecordExists::class, $validator);
        $this->assertEquals($this->adapter, $validator->getAdapter());
        $this->assertEquals('my_table', $validator->getTable());
        $this->assertEquals('my_field', $validator->getField());
    }

    public function testCreateValidatorWithoutAdapter()
    {
        $options = [
            'table' => 'my_table',
            'field' => 'my_field',
        ];

        /** @var RecordExists $validator */
        $validator = $this->validators->get('ZF\ContentValidation\Validator\DbRecordExists', $options);

        $this->assertInstanceOf(RecordExists::class, $validator);
        $this->assertEquals(null, $validator->getAdapter());
        $this->assertEquals('my_table', $validator->getTable());
        $this->assertEquals('my_field', $validator->getField());
    }
}
