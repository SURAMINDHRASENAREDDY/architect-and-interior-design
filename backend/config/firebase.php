<?php

declare(strict_types=1);

use Kreait\Firebase\Factory;

require_once __DIR__ . '/env.php';

function getFirebaseAuth()
{
    static $auth = null;

    if ($auth !== null) {
        return $auth;
    }

    $credentialsPath = appEnv('FIREBASE_CREDENTIALS');

    if (
        $credentialsPath === null
        || !is_file($credentialsPath)
        || !is_readable($credentialsPath)
    ) {
        throw new RuntimeException(
            'Firebase service-account credentials are unavailable.'
        );
    }

    $factory = (new Factory())
        ->withServiceAccount($credentialsPath);

    $auth = $factory->createAuth();

    return $auth;
}
