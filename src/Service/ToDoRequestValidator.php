<?php

namespace App\Service;

use App\Entity\ToDo;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ToDoRequestValidator
{
    private ValidatorInterface $validator;
    private SerializerInterface $serializer;

    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->validator = $validator;
        $this->serializer = $serializer;
    }

    public function validate(string $data): ToDo
    {
        if (!$data) {
            throw new BadRequestHttpException('Empty body.');
        }

        try {
            $toDo = $this->serializer->deserialize($data, ToDo::class, 'json');
        } catch (\Exception $e) {
            throw new BadRequestHttpException('Invalid body.');
        }

        $errors = $this->validator->validate($toDo);

        if ($errors->count()) {
            $errorMessages = [];
            foreach ($errors as $error) {
                assert($error instanceof ConstraintViolation);
                $errorMessages[$error->getPropertyPath()] = $error->getMessage();
            }

            throw new BadRequestHttpException(json_encode($errorMessages));
        }

        return $toDo;
    }
}
