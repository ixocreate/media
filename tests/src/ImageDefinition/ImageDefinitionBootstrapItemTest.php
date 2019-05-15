<?php
/**
 * @link https://github.com/ixocreate
 * @copyright IXOCREATE GmbH
 * @license MIT License
 */

declare(strict_types=1);

namespace Ixocreate\Test\Media\ImageDefinition;

use Ixocreate\Media\ImageDefinition\ImageDefinitionBootstrapItem;
use Ixocreate\Media\ImageDefinition\ImageDefinitionConfigurator;
use PHPUnit\Framework\TestCase;

class ImageDefinitionBootstrapItemTest extends TestCase
{
    /**
     * @var ImageDefinitionBootstrapItem
     */
    private $imageDefinitionBootstrapItem;

    public function setUp()
    {
        $this->imageDefinitionBootstrapItem = new ImageDefinitionBootstrapItem();
    }

    public function testGetConfigurator()
    {
        $this->assertInstanceOf(ImageDefinitionConfigurator::class, $this->imageDefinitionBootstrapItem->getConfigurator());
    }

    public function testGetVariableName()
    {
        $this->assertSame('imageDefinition', $this->imageDefinitionBootstrapItem->getVariableName());
    }

    public function testGetFileName()
    {
        $this->assertSame('image-definition.php', $this->imageDefinitionBootstrapItem->getFileName());
    }
}
