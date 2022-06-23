<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);

        $userOne = new User();
        $userOne->setEmail('userOne@mail.ru');

        $passwordHasherFactory = new PasswordHasherFactory([
            PasswordAuthenticatedUserInterface::class => ['algorithm' => 'auto'],
        ]);
        $hashPassword = new UserPasswordHasher($passwordHasherFactory);
        $hash = $hashPassword->hashPassword($userOne, 'Password');
        $userOne->setPassword($hash);

        $userOne->setRoles(['ROLE_USER']);
        $userOne->setBalance(5005.2);


        $userTwo = new User();
        $userTwo->setEmail('userTwo@mail.ru');

        $hash = $hashPassword->hashPassword($userTwo, 'SuperPassword');
        $userTwo->setPassword($hash);

        $userTwo->setRoles(['ROLE_SUPER_ADMIN']);
        $userTwo->setBalance(22896.3);

        $manager->persist($userOne);
        $manager->persist($userTwo);


        $manager->flush();
    }
}
