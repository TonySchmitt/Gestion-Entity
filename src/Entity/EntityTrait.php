<?php

namespace TonySchmitt\GestionEntity\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\HasLifecycleCallbacks
 * @Serializer\ExclusionPolicy("all")
 */
trait EntityTrait
{
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(type="string", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class="TonySchmitt\GestionEntity\Generator\UuidGenerator")
     * @Serializer\Expose
     * @Serializer\Groups({"admin"})
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Expose
     * @Serializer\Groups({"dateCreation"})
     */
    private $dateCreation;

    /**
     * @ORM\Column(type="datetime")
     * @Serializer\Expose
     * @Serializer\Groups({"admin"})
     */
    private $dateModification;

    public function setId(?string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTime $dateCreation): self
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getDateModification(): ?\DateTime
    {
        return $this->dateModification;
    }

    public function setDateModification(\DateTime $dateModification): self
    {
        $this->dateModification = $dateModification;

        return $this;
    }

    /** @ORM\PrePersist */
    public function initDateCreation()
    {
        $this->dateCreation = new \DateTime();
        $this->dateModification = new \DateTime();
    }

    /** @ORM\PreUpdate */
    public function miseAjour()
    {
        $this->dateModification = new \DateTime();
    }
}
