<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\MediaBundle\Tests\Form\DataTransformer;

use PHPUnit\Framework\TestCase;
use Sonata\MediaBundle\Form\DataTransformer\ProviderDataTransformer;
use Sonata\MediaBundle\Model\MediaInterface;
use Sonata\MediaBundle\Provider\Pool;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProviderDataTransformerTest extends TestCase
{
    public function testReverseTransformFakeValue()
    {
        $pool = $this->getMockBuilder('Sonata\MediaBundle\Provider\Pool')->disableOriginalConstructor()->getMock();

        $transformer = new ProviderDataTransformer($pool, 'stdClass');
        $this->assertSame('foo', $transformer->reverseTransform('foo'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testReverseTransformUnknownProvider()
    {
        $pool = new Pool('default');

        $media = $this->createMock('Sonata\MediaBundle\Model\MediaInterface');
        $media->expects($this->exactly(3))->method('getProviderName')->will($this->returnValue('unknown'));
        $media->expects($this->any())->method('getId')->will($this->returnValue(1));
        $media->expects($this->any())->method('getBinaryContent')->will($this->returnValue('xcs'));

        $transformer = new ProviderDataTransformer($pool, 'stdClass', [
            'new_on_update' => false,
        ]);
        $transformer->reverseTransform($media);
    }

    public function testReverseTransformValidProvider()
    {
        $provider = $this->createMock('Sonata\MediaBundle\Provider\MediaProviderInterface');
        $provider->expects($this->once())->method('transform');

        $pool = new Pool('default');
        $pool->addProvider('default', $provider);

        $media = $this->createMock('Sonata\MediaBundle\Model\MediaInterface');
        $media->expects($this->exactly(3))->method('getProviderName')->will($this->returnValue('default'));
        $media->expects($this->any())->method('getId')->will($this->returnValue(1));
        $media->expects($this->any())->method('getBinaryContent')->will($this->returnValue('xcs'));

        $transformer = new ProviderDataTransformer($pool, 'stdClass', [
            'new_on_update' => false,
        ]);
        $transformer->reverseTransform($media);
    }

    public function testReverseTransformWithNewMediaAndNoBinaryContent()
    {
        $provider = $this->createMock('Sonata\MediaBundle\Provider\MediaProviderInterface');

        $pool = new Pool('default');
        $pool->addProvider('default', $provider);

        $media = $this->createMock('Sonata\MediaBundle\Model\MediaInterface');
        $media->expects($this->any())->method('getId')->will($this->returnValue(null));
        $media->expects($this->any())->method('getBinaryContent')->will($this->returnValue(null));
        $media->expects($this->any())->method('getProviderName')->will($this->returnValue('default'));
        $media->expects($this->once())->method('setProviderReference')->with(MediaInterface::MISSING_BINARY_REFERENCE);
        $media->expects($this->once())->method('setProviderStatus')->with(MediaInterface::STATUS_PENDING);

        $transformer = new ProviderDataTransformer($pool, 'stdClass', [
            'new_on_update' => false,
            'empty_on_new' => false,
        ]);
        $this->assertSame($media, $transformer->reverseTransform($media));
    }

    public function testReverseTransformWithMediaAndNoBinaryContent()
    {
        $provider = $this->createMock('Sonata\MediaBundle\Provider\MediaProviderInterface');

        $pool = new Pool('default');
        $pool->addProvider('default', $provider);

        $media = $this->createMock('Sonata\MediaBundle\Model\MediaInterface');
        $media->expects($this->any())->method('getId')->will($this->returnValue(1));
        $media->expects($this->any())->method('getBinaryContent')->will($this->returnValue(null));

        $transformer = new ProviderDataTransformer($pool, 'stdClass');
        $this->assertSame($media, $transformer->reverseTransform($media));
    }

    public function testReverseTransformWithMediaAndUploadFileInstance()
    {
        $provider = $this->createMock('Sonata\MediaBundle\Provider\MediaProviderInterface');

        $pool = new Pool('default');
        $pool->addProvider('default', $provider);

        $media = $this->createMock('Sonata\MediaBundle\Model\MediaInterface');
        $media->expects($this->any())->method('getProviderName')->will($this->returnValue('default'));
        $media->expects($this->any())->method('getId')->will($this->returnValue(1));
        $media->expects($this->any())->method('getBinaryContent')->will($this->returnValue(new UploadedFile(__FILE__, 'ProviderDataTransformerTest')));

        $transformer = new ProviderDataTransformer($pool, 'stdClass', [
            'new_on_update' => false,
        ]);
        $transformer->reverseTransform($media);
    }

    public function testReverseTransformWithThrowingProviderNoThrow()
    {
        $provider = $this->createMock('Sonata\MediaBundle\Provider\MediaProviderInterface');
        $provider->expects($this->once())->method('transform')->will($this->throwException(new \Exception()));

        $pool = new Pool('default');
        $pool->addProvider('default', $provider);

        $media = $this->createMock('Sonata\MediaBundle\Model\MediaInterface');
        $media->expects($this->exactly(3))->method('getProviderName')->will($this->returnValue('default'));
        $media->expects($this->any())->method('getId')->will($this->returnValue(1));
        $media->expects($this->any())->method('getBinaryContent')->will($this->returnValue(new UploadedFile(__FILE__, 'ProviderDataTransformerTest')));

        $transformer = new ProviderDataTransformer($pool, 'stdClass', [
            'new_on_update' => false,
        ]);
        $transformer->reverseTransform($media);
    }

    public function testReverseTransformWithThrowingProviderLogsException()
    {
        $provider = $this->createMock('Sonata\MediaBundle\Provider\MediaProviderInterface');
        $provider->expects($this->once())->method('transform')->will($this->throwException(new \Exception()));

        $pool = new Pool('default');
        $pool->addProvider('default', $provider);

        $logger = $this->createMock('Psr\Log\LoggerInterface');
        $logger->expects($this->once())->method('error');

        $media = $this->createMock('Sonata\MediaBundle\Model\MediaInterface');
        $media->expects($this->exactly(3))->method('getProviderName')->will($this->returnValue('default'));
        $media->expects($this->any())->method('getId')->will($this->returnValue(1));
        $media->expects($this->any())->method('getBinaryContent')->will($this->returnValue(new UploadedFile(__FILE__, 'ProviderDataTransformerTest')));

        $transformer = new ProviderDataTransformer($pool, 'stdClass', [
            'new_on_update' => false,
        ]);
        $transformer->setLogger($logger);
        $transformer->reverseTransform($media);
    }
}
