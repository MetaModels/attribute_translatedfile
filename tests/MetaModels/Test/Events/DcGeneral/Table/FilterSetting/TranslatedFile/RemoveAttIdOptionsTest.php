<?php

/**
 * This file is part of MetaModels/attribute_translatedfile.
 *
 * (c) 2012-2019 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_translatedfile
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2019 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedfile/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\Test\Events\DcGeneral\Table\FilterSetting\TranslatedFile;

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use ContaoCommunityAlliance\DcGeneral\Data\ModelInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\ContainerInterface;
use ContaoCommunityAlliance\DcGeneral\EnvironmentInterface;
use MetaModels\Events\DcGeneral\Table\FilterSetting\TranslatedFile\RemoveAttIdOptions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * This test the event listener.
 *
 * @covers \MetaModels\Events\DcGeneral\Table\FilterSetting\TranslatedFile\RemoveAttIdOptions
 */
class RemoveAttIdOptionsTest extends TestCase
{
    private function mockEnvironment()
    {
        $dataDefinition = null;

        $environment = $this->getMockForAbstractClass(EnvironmentInterface::class);

        $environment
            ->expects($this->any())
            ->method('getDataDefinition')
            ->will(
                $this->returnCallback(
                    function () use (&$dataDefinition) {
                        return $dataDefinition;
                    }
                )
            );

        $environment
            ->expects($this->any())
            ->method('setDataDefinition')
            ->will(
                $this->returnCallback(
                    function (ContainerInterface $container) use (&$dataDefinition, $environment) {
                        $dataDefinition = $container;

                        return $environment;
                    }
                )
            );

        return $environment;
    }

    private function mockDataDefinition($name = null)
    {
        $dataDefinition = $this->getMockForAbstractClass(ContainerInterface::class);

        $dataDefinition
            ->expects($this->any())
            ->method('getName')
            ->willReturn($name);

        return $dataDefinition;
    }

    private function mockModel()
    {
        return $this->getMockForAbstractClass(ModelInterface::class);
    }

    public function dataProviderTestRemoveOption()
    {

        return [
            [['foo' => 'bar [translatedfile]', 'filesort' => 'foo'], 'foo', 'foo', ['foo' => 'bar [translatedfile]', 'filesort' => 'foo']],
            [['foo' => 'bar [translatedfile]', 'filesort' => 'foo'], 'foo', 'attr_id', ['foo' => 'bar [translatedfile]', 'filesort' => 'foo']],
            [['foo' => 'bar [translatedfile]'], 'tl_metamodel_filtersetting', 'attr_id', ['foo' => 'bar [translatedfile]']],
            [['foo' => 'bar [translatedfile]'], 'tl_metamodel_filtersetting', 'attr_id', ['foo' => 'bar [translatedfile]', 'foo__sort' => 'foo']]
        ];
    }

    /**
     * @dataProvider dataProviderTestRemoveOption
     */
    public function testRemoveOption(array $expected, $providerName, $propertyName, $options)
    {
        $environment = $this->mockEnvironment();
        $environment->setDataDefinition($this->mockDataDefinition($providerName));

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(
            GetPropertyOptionsEvent::NAME,
            [new RemoveAttIdOptions(), 'removeOption']
        );


        $event = new GetPropertyOptionsEvent($environment, $this->mockModel());
        $event->setPropertyName($propertyName);
        $event->setOptions($options);
        $dispatcher->dispatch($event::NAME, $event);

        $this->assertSame($expected, $event->getOptions());
    }
}
