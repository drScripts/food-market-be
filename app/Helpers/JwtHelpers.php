<?php

namespace App\Helpers;

use DateTimeImmutable;
use Exception;
use Illuminate\Support\Facades\Log;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

class JwtHelpers
{
    protected Configuration $configuration;


    public function __construct(string $signatureKey)
    {
        $key = InMemory::plainText($signatureKey);
        $this->configuration = Configuration::forSymmetricSigner(
            new Sha256(),
            $key,
        );
    }

    public function getToken(string|int $user_id)
    {
        $now   = new DateTimeImmutable();
        return $this->configuration->builder()->issuedBy(
            env("APP_URL")
        )->issuedAt($now)->withClaim('user', $user_id)->getToken($this->configuration->signer(), $this->configuration->signingKey())->toString();
    }

    public function verifyToken(string $token)
    {
        try {
            $parser = $this->configuration->parser()->parse($token);
            $constraint = new SignedWith($this->configuration->signer(), $this->configuration->signingKey());
            $validator = $this->configuration->validator()->validate($parser, $constraint);
            if (!$validator) {
                return false;
            }


            return $parser->claims()->get('user');
        } catch (Exception $err) {
            Log::alert($err->getMessage());
            //throw $th;
            return false;
        }
    }
}
