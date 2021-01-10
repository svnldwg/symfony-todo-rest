<?php

namespace App\Tests\Fixtures;

use App\Entity\Task;
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

        $toDo = new ToDo();
        $toDo->setName('ToDo 2')
            ->addTask((new Task())->setName('Task 1 for ToDo 2')->setDescription('Task description'))
            ->addTask((new Task())->setName('Task 2 for ToDo 2'));
        $manager->persist($toDo);

        $manager->flush();
    }
}
