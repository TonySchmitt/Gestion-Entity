<?php

namespace TonySchmitt\GestionEntity\Generator;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\ORM\Mapping\Entity;
use Ramsey\Uuid\Uuid;

class UuidGenerator extends AbstractIdGenerator
{
    /**
     * Generate an identifier.
     *
     * @param Entity $entity
     *
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function generate(EntityManager $em, $entity): string
    {
        return Uuid::uuid4()->toString();
    }
}
