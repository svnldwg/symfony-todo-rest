<?php

namespace App\Entity;

use App\Repository\ToDoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ToDoRepository::class)
 * @ORM\HasLifecycleCallbacks
 */
class ToDo
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private int $id;

    /** @ORM\Column(type="string") */
    private string $name;

    /** @ORM\Column(type="text", nullable=true) */
    private ?string $description = null;

    /**
     * @var Collection|Task[]
     * @ORM\OneToMany(targetEntity=Task::class, mappedBy="todo", orphanRemoval=true, cascade={"all"})
     */
    private Collection $tasks;

    /** @ORM\Column(type="datetime") */
    private \DateTimeInterface $createdAt;

    /** @ORM\Column(type="datetime") */
    private \DateTimeInterface $updatedAt;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->tasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection|Task[]
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks[] = $task;
            $task->setTodo($this);
        }

        return $this;
    }

    public function removeTask(Task $task): self
    {
        if ($this->tasks->removeElement($task)) {
            // set the owning side to null (unless already changed)
            if ($task->getTodo() === $this) {
                $task->setTodo(null);
            }
        }

        return $this;
    }

    /**
     * @param Collection|Task[] $tasks
     */
    public function setTasks(Collection $tasks): self
    {
        foreach ($this->tasks as $task) {
            $this->removeTask($task);
        }

        foreach ($tasks as $task) {
            $this->addTask($task);
        }

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function setUpdatedAt(): self
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAt(): self
    {
        $this->createdAt = new \DateTimeImmutable();

        return $this;
    }
}
