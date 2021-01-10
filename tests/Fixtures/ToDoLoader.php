<?php

namespace App\Tests\Fixtures;

use App\Entity\ToDo;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ToDoLoader implements FixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $toDo = new ToDo();
        $toDo->setName('ToDo 1')
            ->setDescription('Description 1');

        $manager->persist($toDo);
        $manager->flush();
    }
}
