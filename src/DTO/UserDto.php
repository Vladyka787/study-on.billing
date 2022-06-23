<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UserDto
{
    /**
     * @Assert\NotBlank(message="Name is mandatory")
     * @Assert\Email(message="Invalid email address")
     */
    public string $username;

    /**
     * @Assert\NotBlank(message="Password is mandatory")
     * @Assert\Length(min=6, minMessage="Password must not be less than 6 characters")
     */
    public string $password;

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }
}