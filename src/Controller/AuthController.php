<?php

namespace App\Controller;

use App\Service\Authenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    public function __construct(
        private Authenticator $authenticator,
    ) {
    }

    /**
     * @Route("/api/login", name="auth_login", methods={"POST"})
     */
    public function login(): JsonResponse
    {
        $jwt = $this->authenticator->login(); // TODO login with user+pw

        $login = [
            'message' => 'Successful login.',
            'jwt'     => $jwt,
        ];

        return $this->json($login);
    }

    /**
     * @Route("/api/authenticate", name="auth_authenticate", methods={"POST"})
     */
    public function authenticate(Request $request): JsonResponse
    {
        $this->authenticator->authenticate($request);

        return $this->json([
            'message' => 'Access granted',
        ]);
    }
}
