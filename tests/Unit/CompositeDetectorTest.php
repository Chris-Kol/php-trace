<?php

namespace PhpTrace\Tests\Unit;

use Mockery;
use PHPUnit\Framework\TestCase;
use PhpTrace\Detector\CompositeDetector;
use PhpTrace\Detector\DetectorInterface;

class CompositeDetectorTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function test__isEnabled__returns_false_when_no_detectors(): void
    {
        $detector = new CompositeDetector([]);

        $this->assertFalse($detector->isEnabled());
    }

    public function test__isEnabled__returns_true_when_any_detector_enabled(): void
    {
        $detector1 = Mockery::mock(DetectorInterface::class);
        $detector1->shouldReceive('isEnabled')->once()->andReturn(false);

        $detector2 = Mockery::mock(DetectorInterface::class);
        $detector2->shouldReceive('isEnabled')->once()->andReturn(true);

        $detector3 = Mockery::mock(DetectorInterface::class);
        $detector3->shouldReceive('isEnabled')->never();

        $composite = new CompositeDetector([$detector1, $detector2, $detector3]);

        $this->assertTrue($composite->isEnabled());
    }

    public function test__isEnabled__returns_false_when_all_detectors_disabled(): void
    {
        $detector1 = Mockery::mock(DetectorInterface::class);
        $detector1->shouldReceive('isEnabled')->once()->andReturn(false);

        $detector2 = Mockery::mock(DetectorInterface::class);
        $detector2->shouldReceive('isEnabled')->once()->andReturn(false);

        $detector3 = Mockery::mock(DetectorInterface::class);
        $detector3->shouldReceive('isEnabled')->once()->andReturn(false);

        $composite = new CompositeDetector([$detector1, $detector2, $detector3]);

        $this->assertFalse($composite->isEnabled());
    }

    public function test__isEnabled__short_circuits_on_first_enabled(): void
    {
        $detector1 = Mockery::mock(DetectorInterface::class);
        $detector1->shouldReceive('isEnabled')->once()->andReturn(true);

        $detector2 = Mockery::mock(DetectorInterface::class);
        $detector2->shouldReceive('isEnabled')->never();

        $composite = new CompositeDetector([$detector1, $detector2]);

        $this->assertTrue($composite->isEnabled());
    }

    public function test__addDetector__adds_detector_to_composite(): void
    {
        $composite = new CompositeDetector();

        $detector = Mockery::mock(DetectorInterface::class);
        $detector->shouldReceive('isEnabled')->once()->andReturn(true);

        $composite->addDetector($detector);

        $this->assertTrue($composite->isEnabled());
    }

    public function test__getDetectors__returns_all_detectors(): void
    {
        $detector1 = Mockery::mock(DetectorInterface::class);
        $detector2 = Mockery::mock(DetectorInterface::class);

        $composite = new CompositeDetector([$detector1, $detector2]);

        $detectors = $composite->getDetectors();

        $this->assertCount(2, $detectors);
        $this->assertSame($detector1, $detectors[0]);
        $this->assertSame($detector2, $detectors[1]);
    }

    public function test__addDetector__appends_to_existing_detectors(): void
    {
        $detector1 = Mockery::mock(DetectorInterface::class);
        $detector2 = Mockery::mock(DetectorInterface::class);

        $composite = new CompositeDetector([$detector1]);
        $composite->addDetector($detector2);

        $detectors = $composite->getDetectors();

        $this->assertCount(2, $detectors);
        $this->assertSame($detector1, $detectors[0]);
        $this->assertSame($detector2, $detectors[1]);
    }

    public function test__isEnabled__checks_detectors_in_order(): void
    {
        $callOrder = [];

        $detector1 = Mockery::mock(DetectorInterface::class);
        $detector1->shouldReceive('isEnabled')->once()->andReturnUsing(
            function () use (&$callOrder) {
                $callOrder[] = 'detector1';
                return false;
            }
        );

        $detector2 = Mockery::mock(DetectorInterface::class);
        $detector2->shouldReceive('isEnabled')->once()->andReturnUsing(
            function () use (&$callOrder) {
                $callOrder[] = 'detector2';
                return false;
            }
        );

        $composite = new CompositeDetector([$detector1, $detector2]);
        $composite->isEnabled();

        $this->assertEquals(['detector1', 'detector2'], $callOrder);
    }

    public function test__isEnabled__with_single_detector(): void
    {
        $detector = Mockery::mock(DetectorInterface::class);
        $detector->shouldReceive('isEnabled')->once()->andReturn(true);

        $composite = new CompositeDetector([$detector]);

        $this->assertTrue($composite->isEnabled());
    }

    public function test__isEnabled__with_many_detectors(): void
    {
        $detectors = [];
        for ($i = 0; $i < 10; $i++) {
            $detector = Mockery::mock(DetectorInterface::class);
            $detector->shouldReceive('isEnabled')->andReturn(false);
            $detectors[] = $detector;
        }

        // Last one returns true
        $lastDetector = Mockery::mock(DetectorInterface::class);
        $lastDetector->shouldReceive('isEnabled')->once()->andReturn(true);
        $detectors[] = $lastDetector;

        $composite = new CompositeDetector($detectors);

        $this->assertTrue($composite->isEnabled());
    }
}
