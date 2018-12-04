<?php

namespace Pho\Stream;

use Firebase\JWT\BeforeValidException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use Pho\Stream\Exception\AppException;
use Pho\Stream\Exception\AuthorizationFailedException;
use Psr\Http\Message\ServerRequestInterface;

class Authorization
{
    private $request;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    public function signature($value, $secret)
    {
        $digest = hash_hmac('sha1', $value, sha1($secret, true), true);

        return trim(strtr(base64_encode($digest), '+/', '-_'), '=');
    }

    public function decodeToken($token, $secret)
    {
        return JWT::decode($token, $secret, [ 'HS256' ]);
    }

    public function getKey()
    {
        return config('auth.stream_key');
    }

    public function getSecret()
    {
        return config('auth.stream_secret');
    }

    public function authorize($feedSlug, $userId, $resource, $action)
    {
        $queryParams = $this->request->getQueryParams();
        $apiKey = $queryParams['api_key'] ?? null;
        $streamAuthType = $this->request->getHeaderLine('Stream-Auth-Type');
        $authorization = $this->request->getHeaderLine('Authorization');

        if (! $apiKey) {
            throw new AuthorizationFailedException('API-Key not provided');
        }

        if ( strlen($apiKey) != 36 ) {
            throw new AuthorizationFailedException('Invalid API-Key');
        }

        $streamKey = $apiKey;
        $streamSecret = md5(password_hash(strtoupper($streamKey), PASSWORD_BCRYPT, ["salt"=>config('auth.secret_key')]));

        if (! $authorization) {
            throw new AuthorizationFailedException('Invalid Authorization header');
        }

        if (! $streamAuthType) {
            throw new AuthorizationFailedException('Invalid Stream-Auth-Type header');
        }

        if (! in_array($streamAuthType, [ 'jwt', 'simple' ])) {
            throw new AuthorizationFailedException("Unexpected value of header stream-auth-type: {$streamAuthType}");
        }

        if ($streamKey !== $apiKey) {
            throw new AuthorizationFailedException('Invalid API-Key');
        }

        switch ($streamAuthType) {
            case 'jwt':
                try {
                    $decoded = $this->decodeToken($authorization, $streamSecret);
                    $decoded = (array) $decoded;
                    if (! ($decoded['feed_id'] === "{$feedSlug}{$userId}"
                        && in_array($decoded['resource'], [ '*', $resource ])
                        && $decoded['action'] === $action)) {
                        throw new AuthorizationFailedException('Payload mismatch');
                    }
                }
                catch (\Exception $ex) {
                    if (($ex instanceof SignatureInvalidException)
                        || ($ex instanceof BeforeValidException)
                        || ($ex instanceof ExpiredException)
                        || ($ex instanceof \UnexpectedValueException)) {
                        throw new AuthorizationFailedException('Failed to decode JWT token');
                    }
                    else {
                        throw $ex;
                    }
                }
                break;

            case 'simple':

                if (! preg_match('/(^[^ ]+) ([^ ]+$)/', $authorization, $matches)) {
                    throw new AuthorizationFailedException('Invalid authorization');
                }
                $feedId = $matches[1];
                $token = $matches[2];
                if ($feedId !== $feedSlug . $userId) {
                    throw new AuthorizationFailedException('FeedId mismatch');
                }
                $signature = $this->signature($feedSlug . $userId, $streamSecret);
                if ($signature !== $token) {
                    throw new AuthorizationFailedException('Invalid token exception');
                }
                break;
        }
    }
}
