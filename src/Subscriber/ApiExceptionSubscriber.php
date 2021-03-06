<?php

namespace App\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        $response = $this->createResponse($throwable);

        $event->setResponse($response);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    private function createResponse(\Throwable $throwable): Response
    {
        if ($throwable instanceof NotFoundHttpException) {
            return new Response(
                null,
                $throwable->getStatusCode(),
                $throwable->getHeaders(),
            );
        }

        if ($throwable instanceof BadRequestHttpException) {
            return new JsonResponse(
                ['errors' => json_decode($throwable->getMessage(), true) ?? [$throwable->getMessage()], ],
                $throwable->getStatusCode(),
            );
        }

        return new JsonResponse(
            ['message' => $throwable->getMessage()],
            $throwable instanceof HttpExceptionInterface ? $throwable->getStatusCode() : Response::HTTP_INTERNAL_SERVER_ERROR,
        );
    }
}
