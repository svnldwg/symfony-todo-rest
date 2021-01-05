<?php

namespace App\Service;

use App\Entity\ToDo;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExtraAttributesException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class ToDoSerializer
{
    private const REQUEST_SERIALIZATION_GROUP = 'request';
    private SerializerInterface $serializer;

    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    public function deserializeRequestIntoExisting(string $jsonRequest, ToDo $toDo): ToDo
    {
        return $this->deserializeRequest($jsonRequest, [AbstractNormalizer::OBJECT_TO_POPULATE => $toDo]);
    }

    public function deserializeRequestIntoNew(string $jsonRequest): ToDo
    {
        return $this->deserializeRequest($jsonRequest);
    }

    public function serializeToJson(ToDo $toDo): string
    {
        return $this->serializer->serialize($toDo, JsonEncoder::FORMAT, [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (ToDo $object) {
                return $object->getId();
            },
        ]);
    }

    public function serializeArrayToJson(array $toDos): string
    {
        return $this->serializer->serialize($toDos, JsonEncoder::FORMAT, [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (ToDo $object) {
                return $object->getId();
            },
        ]);
    }

    /**
     * @param mixed[] $serializationContext
     *
     * @throws BadRequestHttpException
     */
    private function deserializeRequest(string $jsonRequest, array $serializationContext = []): ToDo
    {
        if (!$jsonRequest) {
            throw new BadRequestHttpException('Empty body.');
        }

        try {
            $toDo = $this->serializer->deserialize(
                $jsonRequest,
                ToDo::class,
                JsonEncoder::FORMAT,
                array_merge([
                    AbstractNormalizer::GROUPS                 => self::REQUEST_SERIALIZATION_GROUP,
                    AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => false,
                ], $serializationContext)
            );
        } catch (ExtraAttributesException $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        } catch (\Exception $exception) {
            throw new BadRequestHttpException('Invalid body.');
        }

        return $toDo;
    }
}