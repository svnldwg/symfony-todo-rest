<?php

namespace App\Service;

use App\Entity\ToDo;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExtraAttributesException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
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

    public function deserializeIntoExisting(string $requestData, ToDo $toDo): void
    {
        $this->deserialize($requestData, [AbstractNormalizer::OBJECT_TO_POPULATE => $toDo]);
    }

    public function deserialize(string $requestData, $serializationContext = []): ToDo
    {
        if (!$requestData) {
            throw new BadRequestHttpException('Empty body.');
        }

        try {
            $toDo = $this->serializer->deserialize(
                $requestData,
                ToDo::class,
                JsonEncoder::FORMAT,
                array_merge([
                    AbstractNormalizer::GROUPS                 => 'request',
                    AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => false,
                ], $serializationContext)
            );
        } catch (ExtraAttributesException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        } catch (\Exception $exception) {
            throw new BadRequestHttpException('Invalid body.');
        }

        $errors = $this->validator->validate($toDo);

        if ($errors->count()) {
            $errorMessages = [];
            foreach ($errors as $error) {
                assert($error instanceof ConstraintViolation);
                $errorMessages[] = sprintf('%s: %s', $error->getPropertyPath(), $error->getMessage());
            }

            throw new BadRequestHttpException(json_encode($errorMessages, JSON_THROW_ON_ERROR));
        }

        return $toDo;
    }
}
