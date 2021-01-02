<?php

namespace TonySchmitt\GestionEntity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method find($id, $lockMode = null, $lockVersion = null)
 * @method findOneBy(array $criteria, array $orderBy = null)
 * @method findAll()
 * @method findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
abstract class EntityRepository extends ServiceEntityRepository
{
    public function findPagine(
        ?int $limit = null,
        ?string $marker = null,
        ?string $orderBy = null,
        ?string $sortBy = null,
        array $search = []
    ) {
        $query = $this->createQueryBuilder('e');

        if ($search && count($search) > 0) {
            // Search
            $first = true;
            foreach ($search as $field => $value) {
                if ($first) {
                    $query->where('e.'.$field.' LIKE :'.$field);
                    $first = false;
                } else {
                    $query->andWhere('e.'.$field.' LIKE :'.$field);
                }
                $query->setParameter(':'.$field, '%'.$value.'%');
            }
        }

        if ($limit) {
            $query->setMaxResults($limit);
        }

        if ($marker) {
            $query->setFirstResult($marker);
        }

        if ($sortBy && $orderBy) {
            $query->orderBy($sortBy, $orderBy);
        }

        return $query->getQuery()
            ->getResult()
            ;
    }
}
