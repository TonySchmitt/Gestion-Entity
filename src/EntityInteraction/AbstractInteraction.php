<?php

namespace TonySchmitt\GestionEntity\EntityInteraction;

use TonySchmitt\GestionEntity\Model\TableauPagine;
use TonySchmitt\GestionEntity\Repository\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class AbstractInteraction
{
    const ENTITY_CLASS = null;
    const ENTITY_LIBELLE_PLURIEL = 'les entités';
    const ENTITY_LIBELLE_SINGULIER = 'l\'entité';

    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->verifierConstantes();
    }

    abstract public function transformEntity(object $entityBdd, object $entityInput): object;

    private function verifierConstantes()
    {
        if (empty(static::ENTITY_CLASS) ||
            'les entités' === static::ENTITY_LIBELLE_PLURIEL ||
            "l'entité" === static::ENTITY_LIBELLE_SINGULIER
        ) {
            throw new HttpException(
                500,
                'Vous n\'avez pas déclaré toutes les constantes obligatoires pour votre classe : '
                .static::class.'.'
            );
        }
    }

    public function addEntity(object $entity): object
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }

    public function supprimerEntities(array $ids)
    {
        $entities = $this->getRepository()->findBy(['id' => $ids]);

        if (count($entities) != count($ids)) {
            throw new NotFoundHttpException('Impossible de trouver toutes les entités à supprimer.');
        }

        foreach ($entities as $entity) {
            $this->entityManager->remove($entity);
        }
    }

    public function getEntityParId(string $id): object
    {
        $entity = $this->getRepository()->findOneBy(['id' => $id]);

        if (!$entity) {
            throw new NotFoundHttpException('L\'objet '.static::ENTITY_CLASS.' n\'a pas été trouvé.');
        }

        return $entity;
    }

    public function modifierEntity(object $entityInput): object
    {
        $entityBdd = $this->getEntityParId($entityInput->getId());

        $dateModificationBdd = $entityBdd->getDateModification()->format('d/m/Y H:i:s');
        $dateModiciationInput = $entityInput->getDateModification()->format('d/m/Y H:i:s');

        if ($dateModiciationInput !== $dateModificationBdd) {
            throw new BadRequestHttpException(
                'L\'objet '.static::ENTITY_CLASS.' a été modifiée par un autre utilisateur à '.
                $dateModificationBdd.'. Veuillez vérifier les changements et réessayer.'
            );
        }

        $this->transformEntity($entityBdd, $entityInput);

        $this->entityManager->flush();

        return $entityBdd;
    }

    public function getEntities(
        ?int $limit = null,
        ?string $marker = null,
        ?string $orderBy = null,
        ?string $sortBy = null,
        array $search = []
    ): TableauPagine {
        $repository = $this->getRepository();
        $entities = $repository->findPagine($limit, $marker, $orderBy, $sortBy, $search);

        $nombreEntities = $repository->count([]);

        $recherche = new TableauPagine();

        return $recherche->setDonnees($entities)
            ->setLimit($limit ?? 0)
            ->setTotal($nombreEntities)
        ;
    }

    private function getRepository(): EntityRepository
    {
        return $this->entityManager->getRepository(static::ENTITY_CLASS);
    }
}
