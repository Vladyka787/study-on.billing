<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\ORM\Mapping as ORM;

const DEPOSIT = 1;
const PAYMENT = 2;

/**
 * @ORM\Entity(repositoryClass=TransactionRepository::class)
 */
class Transaction
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="transactions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $Client;

    /**
     * @ORM\ManyToOne(targetEntity=Course::class, inversedBy="transactions")
     */
    private $Course;

    /**
     * @ORM\Column(type="smallint")
     */
    private $TypeOfTransaction;   // Тип операции - начисление/списание

    /**
     * @ORM\Column(type="float", nullable=true)
     */
    private $Value;  //  Значение

    /**
     * @ORM\Column(type="datetimetz", nullable=true)
     */
    private $DateAndTime;   // Дата и время проведения транзакции

    /**
     * @ORM\Column(type="datetimetz", nullable=true)
     */
    private $ValidUntil;   // Срок действия до (дата и время, обязательно для списания по арендуемым курсам)

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?User
    {
        return $this->Client;
    }

    public function setClient(?User $Client): self
    {
        $this->Client = $Client;

        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->Course;
    }

    public function setCourse(?Course $Course): self
    {
        $this->Course = $Course;

        return $this;
    }

//    public function getTypeOfTransaction(): ?int
//    {
//        return $this->TypeOfTransaction;
//    }
//
//    public function setTypeOfTransaction(int $TypeOfTransaction): self
//    {
//        $this->TypeOfTransaction = $TypeOfTransaction;
//
//        return $this;
//    }

    public function getTypeOfTransaction(): ?string
    {
        if ($this->TypeOfTransaction === DEPOSIT) {
            return 'deposit';
        } elseif ($this->TypeOfTransaction === PAYMENT) {
            return 'payment';
        }

        return null;
    }

    public function setTypeOfTransaction(string $TypeOfTransaction): self
    {
        if ($TypeOfTransaction === 'deposit') {
            $this->TypeOfTransaction = DEPOSIT;
        } elseif ($TypeOfTransaction === 'payment') {
            $this->TypeOfTransaction = PAYMENT;
        }

        return $this;
    }

    public function getValue(): ?float
    {
        return $this->Value;
    }

    public function setValue(?float $Value): self
    {
        $this->Value = $Value;

        return $this;
    }

    public function getDateAndTime(): ?\DateTimeInterface
    {
        return $this->DateAndTime;
    }

    public function setDateAndTime(\DateTimeInterface $DateAndTime): self
    {
        $this->DateAndTime = $DateAndTime;

        return $this;
    }

    public function getValidUntil(): ?\DateTimeInterface
    {
        return $this->ValidUntil;
    }

    public function setValidUntil(\DateTimeInterface $ValidUntil): self
    {
        $this->ValidUntil = $ValidUntil;

        return $this;
    }
}
