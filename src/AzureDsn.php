<?php

namespace Enqueue\AzureStorage;

use Enqueue\Dsn\Dsn;

class AzureDsn extends Dsn
{
    public static function parseFirst(string $dsn): ?self
    {
        return self::parse($dsn)[0];
    }

    /**
     * @param string $dsn
     *
     * @return Dsn[]
     */
    public static function parse(string $dsn): array
    {
        if (false === strpos($dsn, ':')) {
            throw new \LogicException(sprintf('The DSN is invalid. It does not have scheme separator ":".'));
        }

        list($scheme, $dsnWithoutScheme) = explode(':', $dsn, 2);

        $scheme = strtolower($scheme);

        $schemeParts = explode('+', $scheme);
        $schemeProtocol = $schemeParts[0];

        unset($schemeParts[0]);
        $schemeExtensions = array_values($schemeParts);

        $user = parse_url($dsn, PHP_URL_USER) ?: null;
        if (is_string($user)) {
            $user = rawurldecode($user);
        }

        $password = parse_url($dsn, PHP_URL_PASS) ?: null;
        if (is_string($password)) {
            $password = rawurldecode($password);
        }

        $path = parse_url($dsn, PHP_URL_PATH) ?: null;
        if ($path) {
            $path = rawurldecode($path);
        }

        $query = [];
        $queryString = parse_url($dsn, PHP_URL_QUERY) ?: null;
        if (is_string($queryString)) {
            $query = self::httpParseQuery($queryString, '&', PHP_QUERY_RFC3986);
        }
        $hostsPorts = '';
        if (0 === strpos($dsnWithoutScheme, '//')) {
            $dsnWithoutScheme = substr($dsnWithoutScheme, 2);
            $dsnWithoutUserPassword = explode('@', $dsnWithoutScheme, 2);
            $dsnWithoutUserPassword = 2 === count($dsnWithoutUserPassword) ?
                $dsnWithoutUserPassword[1] :
                $dsnWithoutUserPassword[0]
            ;

            list($hostsPorts) = explode('#', $dsnWithoutUserPassword, 2);
            list($hostsPorts) = explode('?', $hostsPorts, 2);
            list($hostsPorts) = explode('/', $hostsPorts, 2);
        }

        if (empty($hostsPorts)) {
            return [
                new self(
                    $scheme,
                    $schemeProtocol,
                    $schemeExtensions,
                    null,
                    null,
                    null,
                    null,
                    $path,
                    $queryString,
                    $query
                ),
            ];
        }

        $dsns = [];
        $hostParts = explode(',', $hostsPorts);
        foreach ($hostParts as $key => $hostPart) {
            unset($hostParts[$key]);

            $parts = explode(':', $hostPart, 2);
            $host = $parts[0];

            $port = null;
            if (isset($parts[1])) {
                $port = (int) $parts[1];
            }

            $dsns[] = new self(
                $scheme,
                $schemeProtocol,
                $schemeExtensions,
                $user,
                $password,
                $host,
                $port,
                $path,
                $queryString,
                $query
            );
        }

        return $dsns;
    }

}