<?php

/**
 * This file is part of MetaModels/attribute_translatedfile.
 *
 * (c) 2012-2021 The MetaModels team.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * This project is provided in good faith and hope to be usable by anyone.
 *
 * @package    MetaModels/attribute_translatedfile
 * @author     Sven Baumann <baumann.sv@gmail.com>
 * @copyright  2012-2021 The MetaModels team.
 * @license    https://github.com/MetaModels/attribute_translatedfile/blob/master/LICENSE LGPL-3.0-or-later
 * @filesource
 */

namespace MetaModels\AttributeTranslatedFileBundle\Test\EventListener;

use MetaModels\Attribute\Events\CollectMetaModelAttributeInformationEvent;
use MetaModels\AttributeTranslatedFileBundle\EventListener\Factory\AddAttributeInformation;
use MetaModels\IMetaModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * This test the event listener add attribute information.
 *
 * @covers \MetaModels\AttributeTranslatedFileBundle\EventListener\Factory\AddAttributeInformation
 */
class AddAttributeInformationTest extends TestCase
{
    public function dataProviderAddInformation()
    {
        return [
            [
                [],
                []
            ],

            [
                [
                    'file' => ['type' => 'nofile']
                ],
                [
                    'file' => ['type' => 'nofile']
                ]
            ],

            [
                [
                    'file' => ['type' => 'nofile', 'file_multiple' => '']
                ],
                [
                    'file' => ['type' => 'nofile', 'file_multiple' => '']
                ]
            ],

            [
                [
                    'file' => ['type' => 'nofile', 'file_multiple' => '1']
                ],
                [
                    'file' => ['type' => 'nofile', 'file_multiple' => '1']
                ]
            ],

            [
                [
                    'file' => ['type' => 'translatedfile']
                ],
                [
                    'file' => ['type' => 'translatedfile']
                ]
            ],

            [
                [
                    'file' => ['type' => 'translatedfile', 'file_multiple' => '']
                ],
                [
                    'file' => ['type' => 'translatedfile', 'file_multiple' => '']
                ]
            ],

            [
                [
                    'file'       => ['colname' => 'file', 'type' => 'translatedfile', 'file_multiple' => '1'],
                    'file__sort' => ['colname' => 'file__sort', 'type' => 'translatedfilesort']
                ],
                [
                    'file' => ['colname' => 'file', 'type' => 'translatedfile', 'file_multiple' => '1']
                ]
            ],

            [
                [
                    'beforeNoFile' => ['type' => 'nofile'],
                    'file'         => ['colname' => 'file', 'type' => 'translatedfile', 'file_multiple' => '1'],
                    'file__sort'   => ['colname' => 'file__sort', 'type' => 'translatedfilesort']
                ],
                [
                    'beforeNoFile' => ['type' => 'nofile'],
                    'file'         => ['colname' => 'file', 'type' => 'translatedfile', 'file_multiple' => '1']
                ]
            ],

            [
                [
                    'file'         => ['colname' => 'file', 'type' => 'translatedfile', 'file_multiple' => '1'],
                    'file__sort'   => ['colname' => 'file__sort', 'type' => 'translatedfilesort'],
                    'afterNoFile'  => ['type' => 'nofile']
                ],
                [
                    'file'         => ['colname' => 'file', 'type' => 'translatedfile', 'file_multiple' => '1'],
                    'afterNoFile'  => ['type' => 'nofile']
                ]
            ],

            [
                [
                    'beforeNoFile' => ['type' => 'nofile'],
                    'file'         => ['colname' => 'file', 'type' => 'translatedfile', 'file_multiple' => '1'],
                    'file__sort'   => ['colname' => 'file__sort', 'type' => 'translatedfilesort'],
                    'afterNoFile'  => ['type' => 'nofile']
                ],
                [
                    'beforeNoFile' => ['type' => 'nofile'],
                    'file'         => ['colname' => 'file', 'type' => 'translatedfile', 'file_multiple' => '1'],
                    'afterNoFile'  => ['type' => 'nofile']
                ]
            ],

            [
                [
                    'beforeNoFile' => ['type' => 'nofile'],
                    'file'         => ['colname' => 'file', 'type' => 'translatedfile', 'file_multiple' => '1'],
                    'file__sort'   => ['colname' => 'file__sort', 'type' => 'translatedfilesort'],
                    'file2'        => ['colname' => 'file2', 'type' => 'translatedfile', 'file_multiple' => '1'],
                    'file2__sort'  => ['colname' => 'file2__sort', 'type' => 'translatedfilesort'],
                    'afterNoFile'  => ['type' => 'nofile']
                ],
                [
                    'beforeNoFile' => ['type' => 'nofile'],
                    'file'         => ['colname' => 'file', 'type' => 'translatedfile', 'file_multiple' => '1'],
                    'file2'        => ['colname' => 'file2', 'type' => 'translatedfile', 'file_multiple' => '1'],
                    'afterNoFile'  => ['type' => 'nofile']
                ]
            ],

            [
                [
                    'beforeNoFile' => ['type' => 'nofile'],
                    'file'         => ['colname' => 'file', 'type' => 'translatedfile', 'file_multiple' => '1'],
                    'file__sort'   => ['colname' => 'file__sort', 'type' => 'translatedfilesort'],
                    'middleNoFile' => ['type' => 'nofile'],
                    'file2'        => ['colname' => 'file2', 'type' => 'translatedfile', 'file_multiple' => '1'],
                    'file2__sort'  => ['colname' => 'file2__sort', 'type' => 'translatedfilesort'],
                    'afterNoFile'  => ['type' => 'nofile']
                ],
                [
                    'beforeNoFile' => ['type' => 'nofile'],
                    'file'         => ['colname' => 'file', 'type' => 'translatedfile', 'file_multiple' => '1'],
                    'middleNoFile' => ['type' => 'nofile'],
                    'file2'        => ['colname' => 'file2', 'type' => 'translatedfile', 'file_multiple' => '1'],
                    'afterNoFile'  => ['type' => 'nofile']
                ]
            ]
        ];
    }

    /**
     * @dataProvider dataProviderAddInformation
     */
    public function testAddInformation($expected, $information)
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(
            CollectMetaModelAttributeInformationEvent::NAME,
            [new AddAttributeInformation(), 'addInformation']
        );

        $metaModel = $this->getMockForAbstractClass(IMetaModel::class);
        $event     = new CollectMetaModelAttributeInformationEvent($metaModel);
        $event->setAttributeInformation($information);
        $dispatcher->dispatch($event::NAME, $event);

        self::assertSame($expected, $event->getAttributeInformation());
    }
}
