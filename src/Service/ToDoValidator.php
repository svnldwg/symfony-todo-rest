<?php

namespace App\Service;

use App\Entity\ToDo;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ToDoValidator
{
    public function __construct(
        private ValidatorInterface $validator
    ) {
    }

    public function validate(ToDo $toDo): void
    {
        $errors = $this->validator->validate($toDo);

        if ($errors->count() === 0) {
            return;
        }

        $errorMessages = [];
        foreach ($errors as $error) {
            assert($error instanceof ConstraintViolation);
            $errorMessages[] = sprintf('%s: %s', $error->getPropertyPath(), $error->getMessage());
        }

        throw new BadRequestHttpException(json_encode($errorMessages, JSON_THROW_ON_ERROR));
    }
}
