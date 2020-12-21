<?php declare(strict_types=1);

/*
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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\User;

/**
 * @Entity
 * @Table(
 *     uniqueConstraints={
 *          @UniqueConstraint(
 *              columns={
 *                  "owner_id",
 *                  "label"
 *              }
 *          )
 *    }
 * )
 * @HasLifecycleCallbacks
 */
class Selection extends AbstractEntity
{
    /**
     * @var int
     *
     * @Id
     * @Column(
     *      type="integer"
     * )
     * @GeneratedValue
     */
    protected $id;

    /**
     * @var User
     *
     * @ManyToOne(
     *      targetEntity="\Omeka\Entity\User"
     * )
     * @JoinColumn(
     *      nullable=false,
     *      onDelete="CASCADE"
     * )
     */
    protected $owner;

    /**
     * @var string
     *
     * @Column(
     *      nullable=false,
     *      length=190
     * )
     */
    protected $label;

    /**
     * @var string
     *
     * @Column(
     *      type="text",
     *      nullable=true
     * )
     */
    protected $comment;

    /**
     * @var SelectionResource[]
     *
     * @OneToMany(
     *      targetEntity="SelectionResource",
     *      mappedBy="selection",
     *      orphanRemoval=true,
     *      cascade={"persist", "remove", "detach"},
     *      indexBy="id"
     * )
     */
    protected $selectionResources;

    /**
     * @var DateTime
     *
     * @Column(
     *      type="datetime"
     * )
     */
    protected $created;

    public function __construct()
    {
        $this->selectionResources = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setOwner(User $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setComment(?string $comment): self
    {
        $this->comment = $comment;
        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getSelectionResources(): Collection
    {
        return $this->selectionResources;
    }

    public function setCreated(DateTime $dateTime): self
    {
        $this->created = $dateTime;
        return $this;
    }

    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @PrePersist
     */
    public function prePersist(LifecycleEventArgs $eventArgs): self
    {
        $this->created = new DateTime('now');
        return $this;
    }
}
