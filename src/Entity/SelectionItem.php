<?php

/*
 * Copyright BibLibre, 2016
 * Copyright Daniel Berthereau, 2020
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace Selection\Entity;

use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\User;

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class SelectionItem extends AbstractEntity
{
    /**
     * @var int
     *
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @var User
     *
     * @ManyToOne(targetEntity="\Omeka\Entity\User")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var \Omeka\Entity\Resource
     *
     * @ManyToOne(targetEntity="\Omeka\Entity\Resource")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $resource;

    /**
     * @var DateTime
     *
     * @Column(type="datetime")
     */
    protected $created;

    public function getId()
    {
        return $this->id;
    }

    /**
     * @param User $user
     * @return \Selection\Entity\SelectionItem
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return \Omeka\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param AbstractEntity $resource
     * @return \Selection\Entity\SelectionItem
     */
    public function setResource(AbstractEntity $resource)
    {
        $this->resource = $resource;
        return $this;
    }

    /**
     * @return \Omeka\Entity\Resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * @param DateTime $dateTime
     * @return \Selection\Entity\SelectionItem
     */
    public function setCreated(DateTime $dateTime)
    {
        $this->created = $dateTime;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @PrePersist
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $this->created = new DateTime('now');
        return $this;
    }
}
