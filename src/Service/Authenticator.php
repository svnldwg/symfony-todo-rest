<?php

namespace App\Service;

use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class Authenticator
{
    private const SECRET_KEY = 'sdf8udsf8sudf98sdf8'; // TODO secure key? env var?
    private const TOKEN_EXPIRES_AFTER_SECONDS = 300;
    private const ENCRYPTION_ALGORITHM = 'HS256';

    public function login(): string
    {
        // @see https://symfony.com/doc/current/security.html#b-the-user-provider
        $issuer_claim = 'svnldwg/symfony-todo-rest';
        $audience_claim = 'THE_AUDIENCE'; // TODO
        $issuedat_claim = time();
        $notbefore_claim = $issuedat_claim; // not before in seconds
        $expire_claim = $issuedat_claim + self::TOKEN_EXPIRES_AFTER_SECONDS; // expire time in seconds
        $token = [
            'iss'  => $issuer_claim,
            'aud'  => $audience_claim,
            'iat'  => $issuedat_claim,
            'nbf'  => $notbefore_claim,
            'exp'  => $expire_claim,
            'data' => [
                'id'        => 1, // TODO return actual data
                'firstname' => 'John',
                'lastname'  => 'Doe',
            ],
        ];

        return JWT::encode($token, self::SECRET_KEY, self::ENCRYPTION_ALGORITHM);
    }

    public function authenticate(Request $request): void
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader) {
            throw new AccessDeniedHttpException('No JWT provided');
        }

        $arr = explode(' ', $authHeader);

        $jwt = $arr[1];
        if (!$jwt) {
            throw new AccessDeniedHttpException('No JWT provided');
        }

        try {
            $decoded = JWT::decode($jwt, self::SECRET_KEY, [self::ENCRYPTION_ALGORITHM]);

            return;
        } catch (\Exception $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        }
    }
}
