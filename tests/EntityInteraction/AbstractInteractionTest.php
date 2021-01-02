<?php

namespace TonySchmitt\GestionEntity\Tests\EntityInteraction;

use TonySchmitt\GestionEntity\EntityInteraction\AbstractInteraction;
use TonySchmitt\GestionEntity\Entity\EntityTrait;
use TonySchmitt\GestionEntity\Repository\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AbstractInteractionTest extends TestCase
{
    /**
     * @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityManager;

    /**
     * @var AbstractInteraction
     */
    private $abstractInteraction;
    /**
     * @var object
     */
    private $fakeEntity;

    public function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->abstractInteraction = new class($this->entityManager) extends AbstractInteraction {
            public const ENTITY_CLASS = 'Baleine';
            public const ENTITY_LIBELLE_PLURIEL = 'les baleines';
            public const ENTITY_LIBELLE_SINGULIER = 'la baleine';

            public function transformEntity(object $entityBdd, object $entityInput): object
            {
                $entityBdd->nom = $entityInput->nom;

                return $entityBdd;
            }
        };

        $this->fakeEntity = new class() {
            use EntityTrait;

            public $nom = 'Mon ancien nom';
        };
    }

    public function testConstantesNonDefinies()
    {
        $this->expectException(HttpException::class);

        new class($this->entityManager) extends AbstractInteraction {
            public function transformEntity(object $entityBdd, object $entityInput): object
            {
                return $entityBdd;
            }
        };
    }

    public function testAddEntitySuccess()
    {
        $object = new class() {
        };

        $this->entityManager->expects(self::at(0))
            ->method('persist')
            ->with($object)
        ;

        $this->entityManager->expects(self::at(1))
            ->method('flush')
        ;

        $actual = $this->abstractInteraction->addEntity($object);

        $this->assertEquals($actual, $object);
    }

    public function testSupprimerEntitiesClassEntitiesNonTrouvees()
    {
        $ids = ['2', '3', '4'];

        $repository = $this->createMock(EntityRepository::class);

        $repository->expects(self::once())
            ->method('find')
            ->with($ids)
            ->willReturn([new class() {
            }, new class() {
            }])
        ;

        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository)
        ;

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Impossible de trouver toutes les entités à supprimer.');

        $this->abstractInteraction->supprimerEntities($ids);
    }

    public function testSupprimerEntitiesSuccess()
    {
        $ids = ['2', '3', '4'];
        $entities = [new class() {
        }, new class() {
        }, new class() {
        }];

        $repository = $this->createMock(EntityRepository::class);

        $repository->expects(self::once())
            ->method('find')
            ->with($ids)
            ->willReturn($entities)
        ;

        $this->entityManager->expects(self::exactly(3))
            ->method('remove')
        ;

        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository)
        ;

        $this->abstractInteraction->supprimerEntities($ids);
    }

    public function testGetEntityParIdNonTrouve()
    {
        $id = '21934';

        $this->mockGetEntityParIdTest($id, null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('L\'objet Baleine n\'a pas été trouvé.');

        $this->abstractInteraction->getEntityParId($id);
    }

    public function testGetEntityParIdSuccess()
    {
        $id = '21934';
        $object = new class() {
        };

        $this->mockGetEntityParIdTest($id, $object);

        $actual = $this->abstractInteraction->getEntityParId($id);

        $this->assertEquals($actual, $object);
    }

    public function testModifierEntityNonTrouve()
    {
        $object = clone $this->fakeEntity;
        $id = '21934';
        $object->setId($id);

        $this->mockGetEntityParIdTest($id, null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('L\'objet Baleine n\'a pas été trouvé.');

        $this->abstractInteraction->modifierEntity($object);
    }

    public function testModifierEntityDejaModifiee()
    {
        $id = '255464';
        $objectBdd = clone $this->fakeEntity;
        $dateModificationBdd = new \DateTime('now');
        $objectBdd->setId($id);
        $objectBdd->setDateModification($dateModificationBdd);

        $objectInput = clone $this->fakeEntity;
        $dateModificationInput = new \DateTime('yesterday');
        $objectInput->setId($id);
        $objectInput->setDateModification($dateModificationInput);

        $this->mockGetEntityParIdTest($id, $objectBdd);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('L\'objet Baleine a été modifiée par un autre utilisateur à '.$dateModificationBdd->format('d/m/Y H:i:s').'. Veuillez vérifier les changements et réessayer.');

        $this->abstractInteraction->modifierEntity($objectInput);
    }

    public function testModifierEntitySuccess()
    {
        $id = '255464';
        $objectBdd = clone $this->fakeEntity;
        $dateModificationBdd = new \DateTime('yesterday');
        $objectBdd->setId($id);
        $objectBdd->setDateModification($dateModificationBdd);

        $objectInput = clone $this->fakeEntity;
        $dateModificationInput = new \DateTime('yesterday');
        $objectInput->nom = 'Mon nouveau nom';
        $objectInput->setId($id);
        $objectInput->setDateModification($dateModificationInput);

        $this->mockGetEntityParIdTest($id, $objectBdd);

        $this->entityManager->expects(self::once())
            ->method('flush')
        ;

        $actual = $this->abstractInteraction->modifierEntity($objectInput);

        $this->assertEquals($actual->nom, $objectInput->nom);
    }

    /**
     * @dataProvider dataGetAllEntities
     */
    public function testGetAllEntitiesSansElement(
        $limit,
        $marker,
        $orderBy,
        $sortBy,
        $search,
        $entities,
        $totalEntities
    ) {
        $this->mockFindPagine($limit, $marker, $orderBy, $sortBy, $search, $entities, $totalEntities);

        $actual = $this->abstractInteraction->getEntities($limit, $marker, $orderBy, $sortBy, $search);

        $this->assertEquals($totalEntities, $actual->getTotal());
        $this->assertEquals($limit ?? 0, $actual->getLimit());
        $this->assertEquals($entities, $actual->getDonnees());
    }

    public function dataGetAllEntities()
    {
        return [
            'Aucun Element' => [
                'limit' => null,
                'marker' => null,
                'orderBy' => null,
                'sortBy' => null,
                'search' => [],
                'entities' => [],
                'totalEntities' => 0,
            ],
            'Un Element' => [
                'limit' => 5,
                'marker' => null,
                'orderBy' => null,
                'sortBy' => null,
                'search' => [],
                'entities' => [2],
                'totalEntities' => 1,
            ],
            'Plusieurs Elements' => [
                'limit' => 5,
                'marker' => 27,
                'orderBy' => 'DESC',
                'sortBy' => 'id',
                'search' => ['nom' => 'Penny', 'ville' => 'Stockholm'],
                'entities' => [2, 4, 4, 3, 2],
                'totalEntities' => 126,
            ],
        ];
    }

    protected function mockGetEntityParIdTest(string $id, ?object $object): void
    {
        $repository = $this->createMock(EntityRepository::class);

        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['id' => $id])
            ->willReturn($object);

        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);
    }

    protected function mockFindPagine(?int $limit, ?string $marker, ?string $orderBy, ?string $sortBy, array $search, array $entities, int $totalEntities): void
    {
        $repository = $this->createMock(EntityRepository::class);

        $repository->expects(self::once())
            ->method('findPagine')
            ->with($limit, $marker, $orderBy, $sortBy, $search)
            ->willReturn($entities);

        $repository->expects(self::once())
            ->method('count')
            ->with([])
            ->willReturn($totalEntities);

        $this->entityManager->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);
    }
}
