<?php

namespace TonySchmitt\GestionEntity\Model;

use JMS\Serializer\Annotation as Serializer;

/**
 * @Serializer\ExclusionPolicy("all")
 */
class TableauPagine
{
    /**
     * @var int
     * @Serializer\Expose
     */
    private $total;

    /**
     * @var int|null
     * @Serializer\Expose
     */
    private $limit;

    /**
     * @var array
     * @Serializer\Expose
     */
    private $donnees;

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function getDonnees(): array
    {
        return $this->donnees;
    }

    public function setDonnees(array $donnees): self
    {
        $this->donnees = $donnees;

        return $this;
    }
}
