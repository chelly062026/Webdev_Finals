<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private $passwordHasher;
    
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }
    
    public function load(ObjectManager $manager): void
    {
        $users = [
            [
                'username' => 'admin',
                'email' => 'richelpaculanang06@gmail.com',
                'roles' => ['ROLE_ADMIN'],
                'password' => 'admin123'
            ],
            [
                'username' => 'user',
                'email' => 'richelpaculanang25@gmail.com',
                'roles' => ['ROLE_USER'],
                'password' => 'user123'
            ],
            [
                'username' => 'staff',
                'email' => 'paculanangrichel24@gmail.com',
                'roles' => ['ROLE_STAFF'],
                'password' => 'staff123'
            ],
        ];

        foreach ($users as $userData) {
            $existingUser = $manager->getRepository(User::class)->findOneBy(['email' => $userData['email']]);
            
            if (!$existingUser) {
                $user = new User();
                $user->setUsername($userData['username']);
                $user->setEmail($userData['email']);
                $user->setRoles($userData['roles']);
                $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
                $user->setPassword($hashedPassword);
                $manager->persist($user);
            }
        }

        $manager->flush();
    }
}