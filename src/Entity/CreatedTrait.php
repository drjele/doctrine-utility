<?php

declare(strict_types=1);

/*
 * Copyright (c) Adrian Jeledintan
 */

namespace Drjele\DoctrineUtility\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

trait CreatedTrait
{
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?DateTime $created;

    public function getCreated(): ?DateTime
    {
        return $this->created;
    }

    public function setCreated(DateTime $created): self
    {
        $this->created = $created;

        return $this;
    }
}
