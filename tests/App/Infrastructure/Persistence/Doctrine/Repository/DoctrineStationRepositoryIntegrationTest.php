<?php

declare(strict_types=1);

namespace tests\App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Exception\Station\StationAlreadyExistsException;
use App\Domain\Model\Station\Station;
use App\Infrastructure\Persistence\Doctrine\Repository\DoctrineStationRepository;
use Ramsey\Uuid\Uuid;
use tests\Support\Builder\LocationBuilder;
use tests\Support\Builder\StationBuilder;
use tests\Support\Builder\StationDetailBuilder;
use tests\Support\Builder\StationDetailTypeBuilder;
use tests\Support\Builder\StationExternalDataBuilder;
use tests\Support\TestCase\DatabaseTestCase;

/**
 * @see DoctrineStationRepository
 */
class DoctrineStationRepositoryIntegrationTest extends DatabaseTestCase
{
    /**
     * @var DoctrineStationRepository
     */
    private $repository;

    /**
     * @test
     */
    public function it_can_add_a_station(): void
    {
        $stationId = Uuid::uuid4();

        $station = (StationBuilder::create())
            ->withStationId($stationId)
            ->withStationDetail((StationDetailBuilder::create())
                ->withName('01 - C/ GRAN VIA CORTS CATALANES 760')
                ->withType(StationDetailTypeBuilder::create()->withTypeBike()->build())
                ->build())
            ->withStationExternalData((StationExternalDataBuilder::create())
                ->withExternalStationId('1')
                ->withNearByExternalStationIds(['24', '369', '387', '426'])
                ->build())
            ->withLocation((LocationBuilder::create())
                ->withAddress('Gran Via Corts Catalanes')
                ->withAddressNumber('760')
                ->withDistrictCode(1)
                ->withLatitude(41.397952)
                ->withLongitude(2.180042)
                ->withZipCode('08013')
                ->build())
            ->withCreatedAt(new \DateTimeImmutable())
            ->withUpdatedAt(new \DateTimeImmutable())
            ->build();

        $this->repository->add($station);

        $this->assertEquals($station, $this->find(Station::class, $stationId));
    }

    /**
     * @test
     */
    public function it_can_add_a_station_without_location_address_number(): void
    {
        $stationId = Uuid::uuid4();

        $station = (StationBuilder::create())
            ->withStationId($stationId)
            ->withStationDetail((StationDetailBuilder::create())
                ->withName('01 - C/ GRAN VIA CORTS CATALANES 760')
                ->withType(StationDetailTypeBuilder::create()->withTypeBike()->build())
                ->build())
            ->withStationExternalData((StationExternalDataBuilder::create())
                ->withExternalStationId('1')
                ->withNearByExternalStationIds(['24', '369', '387', '426'])
                ->build())
            ->withLocation((LocationBuilder::create())
                ->withAddress('Gran Via Corts Catalanes')
                ->withAddressNumber(null)
                ->withDistrictCode(1)
                ->withLatitude(41.397952)
                ->withLongitude(2.180042)
                ->withZipCode('08013')
                ->build())
            ->withCreatedAt(new \DateTimeImmutable())
            ->withUpdatedAt(new \DateTimeImmutable())
            ->build();

        $this->repository->add($station);

        $this->assertEquals($station, $this->find(Station::class, $stationId));
    }

    /**
     * @test
     */
    public function it_can_not_add_a_station_that_already_exists_with_station_id(): void
    {
        $this->expectException(StationAlreadyExistsException::class);
        $this->expectExceptionMessage(
            'A station already exists with station Id "25769c6c-d34d-4bfe-ba98-e0ee856f3e7a".'
        );

        $stationId = Uuid::fromString('25769c6c-d34d-4bfe-ba98-e0ee856f3e7a');

        $this->repository->add((StationBuilder::create())
            ->withStationId($stationId)
            ->build());

        $this->repository->add((StationBuilder::create())
            ->withStationId($stationId)
            ->build());
    }

    /**
     * @test
     */
    public function it_can_not_add_a_station_that_already_exists_with_external_station_id(): void
    {
        $this->expectException(StationAlreadyExistsException::class);
        $this->expectExceptionMessage('A station already exists with external station Id "12"');

        $this->repository->add((StationBuilder::create())
            ->withStationExternalData(
                StationExternalDataBuilder::create()
                    ->withExternalStationId('12')
                ->build()
            )
            ->build());

        $this->repository->add((StationBuilder::create())
            ->withStationExternalData(
                StationExternalDataBuilder::create()
                    ->withExternalStationId('12')
                ->build()
            )
            ->build());
    }

    /** @test */
    public function it_can_find_by_station_id_a_station_that_does_exist(): void
    {
        $stationId = Uuid::uuid4();

        $this->buildPersisted(StationBuilder::create()->withStationId($stationId));

        $this->assertInstanceOf(Station::class, $this->repository->findByStationId($stationId));
    }

    /** @test */
    public function it_can_not_find_by_station_id_a_station_that_does_not_exist(): void
    {
        $this->buildPersisted(StationBuilder::create()->withStationId(Uuid::uuid4()));

        $this->assertNull($this->repository->findByStationId(Uuid::uuid4()));
    }

    /** @test */
    public function it_can_find_by_external_station_id_a_station_that_does_exist(): void
    {
        $externalStationId = (string) rand(1, 1000);

        $this->buildPersisted(StationBuilder::create()->withStationExternalData(
            StationExternalDataBuilder::create()->withExternalStationId($externalStationId)->build()
        ));

        $this->assertInstanceOf(Station::class, $this->repository->findByExternalStationId($externalStationId));
    }

    /** @test */
    public function it_can_not_find_by_external_station_id_a_station_that_does_not_exist(): void
    {
        $this->buildPersisted(StationBuilder::create()->withStationExternalData(
            StationExternalDataBuilder::create()->withExternalStationId('external_id')->build()
        ));

        $this->assertNull($this->repository->findByExternalStationId('invalid_external_id'));
    }

    /** @test */
    public function it_can_find_all_stations(): void
    {
        $station1 = $this->buildPersisted(StationBuilder::create()
            ->withStationExternalData(StationExternalDataBuilder::create()
                ->withExternalStationId('1')->build()));
        $station2 = $this->buildPersisted(StationBuilder::create()
            ->withStationExternalData(StationExternalDataBuilder::create()
                ->withExternalStationId('2')->build()));
        $station3 = $this->buildPersisted(StationBuilder::create()
            ->withStationExternalData(StationExternalDataBuilder::create()
                ->withExternalStationId('3')->build()));

        $this->assertEquals([
            $station1, $station2, $station3,
        ], $this->repository->findAll());
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->repository = $this->getContainer()->get('test.app.model.station_repository');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->repository = null;

        parent::tearDown();
    }
}
