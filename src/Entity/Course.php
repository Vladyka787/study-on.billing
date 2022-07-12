<?php

namespace App\Entity;

use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

const RENT = 1;
const FREE = 2;
const BUY = 3;

/**
 * @ORM\Entity(repositoryClass=CourseRepository::class)
 */
class Course
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $CharacterCode;

    /**
     * @ORM\Column(type="smallint")
     */
    private $Type;

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $Price;

    /**
     * @ORM\OneToMany(targetEntity=Transaction::class, mappedBy="Course")
     */
    private $transactions;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCharacterCode(): ?string
    {
        return $this->CharacterCode;
    }

    public function setCharacterCode(string $CharacterCode): self
    {
        $this->CharacterCode = $CharacterCode;

        return $this;
    }

//    public function getType(): ?int
//    {
//        return $this->Type;
//    }

//    public function setType(int $Type): self
//    {
//        $this->Type = $Type;
//
//        return $this;
//    }

    public function getType(): ?string
    {
        if ($this->Type === RENT) {
            return 'rent';
        } elseif ($this->Type === FREE) {
            return 'free';
        } elseif ($this->Type === BUY) {
            return 'buy';
        }

        return null;
    }

    public function setType(string $Type): self
    {
        if ($Type === 'rent') {
            $this->Type = RENT;
        } elseif ($Type === 'free') {
            $this->Type = FREE;
        } elseif ($Type === 'buy') {
            $this->Type = BUY;
        }

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->Price;
    }

    public function setPrice(?float $Price): self
    {
        $this->Price = $Price;

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): self
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions[] = $transaction;
            $transaction->setCourse($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getCourse() === $this) {
                $transaction->setCourse(null);
            }
        }

        return $this;
    }
}
