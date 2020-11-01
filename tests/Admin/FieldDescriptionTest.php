<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\DoctrineORMAdminBundle\Tests\Admin;

use PHPUnit\Framework\TestCase;
use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Admin\FieldDescriptionInterface;
use Sonata\AdminBundle\Exception\NoValueException;
use Sonata\DoctrineORMAdminBundle\Admin\FieldDescription;

class FieldDescriptionTest extends TestCase
{
    public function testOptions(): void
    {
        $field = new FieldDescription('name', [
            'template' => 'foo',
            'type' => 'bar',
            'misc' => 'foobar',
        ]);

        // test method shortcut
        $this->assertNull($field->getOption('template'));
        $this->assertNull($field->getOption('type'));

        $this->assertSame('foo', $field->getTemplate());
        $this->assertSame('bar', $field->getType());

        // test the default value option
        $this->assertSame('default', $field->getOption('template', 'default'));

        // test the merge options
        $field->setOption('array', ['key1' => 'val1']);
        $field->mergeOption('array', ['key1' => 'key_1', 'key2' => 'key_2']);

        $this->assertSame(['key1' => 'key_1', 'key2' => 'key_2'], $field->getOption('array'));

        $field->mergeOption('non_existent', ['key1' => 'key_1', 'key2' => 'key_2']);

        $this->assertSame(['key1' => 'key_1', 'key2' => 'key_2'], $field->getOption('array'));

        $field->mergeOptions(['array' => ['key3' => 'key_3']]);

        $this->assertSame(['key1' => 'key_1', 'key2' => 'key_2', 'key3' => 'key_3'], $field->getOption('array'));

        $field->setOption('integer', 1);

        try {
            $field->mergeOption('integer', []);
            $this->fail('no exception raised !!');
        } catch (\RuntimeException $e) {
        }

        $field->mergeOptions(['final' => 'test']);

        $expected = [
            'misc' => 'foobar',
            'placeholder' => 'short_object_description_placeholder',
            'link_parameters' => [],
            'array' => [
                'key1' => 'key_1',
                'key2' => 'key_2',
                'key3' => 'key_3',
            ],
            'non_existent' => [
                'key1' => 'key_1',
                'key2' => 'key_2',
            ],
            'integer' => 1,
            'final' => 'test',
        ];

        $this->assertSame($expected, $field->getOptions());
    }

    public function testAssociationMapping(): void
    {
        $field = new FieldDescription('name');
        $field->setAssociationMapping([
            'type' => 'integer',
            'fieldName' => 'position',
        ]);

        $this->assertSame('integer', $field->getType());
        $this->assertSame('integer', $field->getMappingType());
        $this->assertSame('position', $field->getFieldName());

        // cannot overwrite defined definition
        $field->setAssociationMapping([
            'type' => 'overwrite?',
            'fieldName' => 'overwritten',
        ]);

        $this->assertSame('integer', $field->getType());
        $this->assertSame('integer', $field->getMappingType());
        $this->assertSame('overwritten', $field->getFieldName());

        $field->setMappingType('string');
        $this->assertSame('string', $field->getMappingType());
        $this->assertSame('integer', $field->getType());
    }

    public function testSetName(): void
    {
        $field = new FieldDescription('New field description name');

        $this->assertSame($field->getName(), 'New field description name');
    }

    public function testSetNameSetFieldNameToo(): void
    {
        $field = new FieldDescription('New field description name');

        $this->assertSame($field->getFieldName(), 'New field description name');
    }

    public function testSetNameDoesNotSetFieldNameWhenSetBefore(): void
    {
        $field = new FieldDescription('New field description name');

        $field->setFieldName('field name');
        $this->assertSame($field->getFieldName(), 'field name');

        $field->setName('New field description name');
        $this->assertSame($field->getFieldName(), 'field name');
    }

    public function testGetParent(): void
    {
        $adminMock = $this->createMock(AdminInterface::class);
        $field = new FieldDescription('name');
        $field->setParent($adminMock);

        $this->assertSame($adminMock, $field->getParent());
    }

    public function testGetAdmin(): void
    {
        $adminMock = $this->createMock(AdminInterface::class);
        $field = new FieldDescription('name');
        $field->setAdmin($adminMock);

        $this->assertSame($adminMock, $field->getAdmin());
    }

    public function testGetAssociationAdmin(): void
    {
        $adminMock = $this->createMock(AbstractAdmin::class);
        $adminMock->expects($this->once())
            ->method('setParentFieldDescription')
            ->with($this->isInstanceOf(FieldDescriptionInterface::class));

        $field = new FieldDescription('name');
        $field->setAssociationAdmin($adminMock);

        $this->assertSame($adminMock, $field->getAssociationAdmin());
    }

    public function testHasAssociationAdmin(): void
    {
        $adminMock = $this->createMock(AbstractAdmin::class);
        $adminMock->expects($this->once())
            ->method('setParentFieldDescription')
            ->with($this->isInstanceOf(FieldDescriptionInterface::class));

        $field = new FieldDescription('name');

        $this->assertFalse($field->hasAssociationAdmin());

        $field->setAssociationAdmin($adminMock);

        $this->assertTrue($field->hasAssociationAdmin());
    }

    public function testGetValue(): void
    {
        $mockedObject = $this->getMockBuilder('stdClass')
            ->setMethods(['myMethod'])
            ->getMock();
        $mockedObject->expects($this->once())
            ->method('myMethod')
            ->willReturn('myMethodValue');

        $field = new FieldDescription('name');
        $field->setOption('code', 'myMethod');

        $this->assertSame($field->getValue($mockedObject), 'myMethodValue');
    }

    public function testGetValueWhenCannotRetrieve(): void
    {
        $this->expectException(NoValueException::class);

        $mockedObject = $this->getMockBuilder('stdClass')
            ->setMethods(['myMethod'])
            ->getMock();
        $mockedObject->expects($this->never())
            ->method('myMethod')
            ->willReturn('myMethodValue');

        $field = new FieldDescription('name');

        $this->assertSame($field->getValue($mockedObject), 'myMethodValue');
    }

    public function testGetAssociationMapping(): void
    {
        $assocationMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
        ];

        $field = new FieldDescription('name');
        $field->setAssociationMapping($assocationMapping);

        $this->assertSame($assocationMapping, $field->getAssociationMapping());
    }

    public function testSetFieldMappingSetType(): void
    {
        $fieldMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
        ];

        $field = new FieldDescription('position');
        $field->setFieldMapping($fieldMapping);

        $this->assertSame('integer', $field->getType());
    }

    public function testSetFieldMappingSetMappingType(): void
    {
        $fieldMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
        ];

        $field = new FieldDescription('position');
        $field->setFieldMapping($fieldMapping);

        $this->assertSame('integer', $field->getMappingType());
    }

    public function testGetTargetEntity(): void
    {
        $assocationMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
            'targetEntity' => 'someValue',
        ];

        $field = new FieldDescription('position');

        $this->assertNull($field->getTargetModel());

        $field->setAssociationMapping($assocationMapping);

        $this->assertSame('someValue', $field->getTargetModel());
    }

    public function testIsIdentifierFromFieldMapping(): void
    {
        $fieldMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
            'id' => true,
        ];

        $field = new FieldDescription('position');
        $field->setFieldMapping($fieldMapping);

        $this->assertTrue($field->isIdentifier());
    }

    public function testGetFieldMapping(): void
    {
        $fieldMapping = [
            'type' => 'integer',
            'fieldName' => 'position',
            'id' => 'someId',
        ];

        $field = new FieldDescription('position');
        $field->setFieldMapping($fieldMapping);

        $this->assertSame($fieldMapping, $field->getFieldMapping());
    }

    public function testGetValueForEmbeddedObject(): void
    {
        $mockedEmbeddedObject = $this->getMockBuilder('stdClass')
            ->setMethods(['myMethod'])
            ->getMock();
        $mockedEmbeddedObject->expects($this->once())
                    ->method('myMethod')
                    ->willReturn('myMethodValue');

        $mockedObject = $this->getMockBuilder('stdClass')
            ->setMethods(['getMyEmbeddedObject'])
            ->getMock();
        $mockedObject->expects($this->once())
            ->method('getMyEmbeddedObject')
            ->willReturn($mockedEmbeddedObject);

        $field = new FieldDescription('myMethod');
        $field->setFieldMapping([
            'declaredField' => 'myEmbeddedObject', 'type' => 'string', 'fieldName' => 'myEmbeddedObject.myMethod',
        ]);
        $field->setOption('code', 'myMethod');

        $this->assertSame('myMethodValue', $field->getValue($mockedObject));
    }

    public function testGetValueForMultiLevelEmbeddedObject(): void
    {
        $mockedChildEmbeddedObject = $this->getMockBuilder('stdClass')
            ->setMethods(['myMethod'])
            ->getMock();
        $mockedChildEmbeddedObject->expects($this->once())
            ->method('myMethod')
            ->willReturn('myMethodValue');
        $mockedEmbeddedObject = $this->getMockBuilder('stdClass')
            ->setMethods(['getChild'])
            ->getMock();
        $mockedEmbeddedObject->expects($this->once())
            ->method('getChild')
            ->willReturn($mockedChildEmbeddedObject);
        $mockedObject = $this->getMockBuilder('stdClass')
            ->setMethods(['getMyEmbeddedObject'])
            ->getMock();
        $mockedObject->expects($this->once())
            ->method('getMyEmbeddedObject')
            ->willReturn($mockedEmbeddedObject);
        $field = new FieldDescription('myMethod');
        $field->setFieldMapping([
            'declaredField' => 'myEmbeddedObject.child', 'type' => 'string', 'fieldName' => 'myMethod',
        ]);
        $field->setOption('code', 'myMethod');
        $this->assertSame('myMethodValue', $field->getValue($mockedObject));
    }
}
