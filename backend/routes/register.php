<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/firebase.php';

function registrationResponse(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
}

function handleRegistrationRequest(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        registrationResponse(405, [
            'status' => 'error',
            'message' => 'Method not allowed.'
        ]);
        return;
    }

    try {
        $payload = json_decode(
            file_get_contents('php://input'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    } catch (JsonException $e) {
        registrationResponse(400, [
            'status' => 'error',
            'message' => 'Invalid JSON request.'
        ]);
        return;
    }

    if (!is_array($payload)) {
        registrationResponse(400, [
            'status' => 'error',
            'message' => 'Invalid request data.'
        ]);
        return;
    }

    $authorization = trim((string) (
        $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
        ?? ''
    ));

    // Apache may expose Authorization through request headers
    // instead of the HTTP_AUTHORIZATION server variable.
    if ($authorization === '' && function_exists('getallheaders')) {
        foreach (getallheaders() as $headerName => $headerValue) {
            if (strcasecmp((string) $headerName, 'Authorization') === 0) {
                $authorization = trim((string) $headerValue);
                break;
            }
        }
    }

    if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
        registrationResponse(401, [
            'status' => 'error',
            'message' => 'Authentication token is required.'
        ]);
        return;
    }

    $idToken = trim($matches[1]);

    // Verify the Firebase ID token and obtain the trusted Firebase user.
    try {
        $firebaseAuth = getFirebaseAuth();
        $verifiedToken = $firebaseAuth->verifyIdToken($idToken, true);
        $uid = $verifiedToken->claims()->get('sub');

        if (!is_string($uid) || $uid === '') {
            throw new RuntimeException('Invalid Firebase user ID.');
        }

        $firebaseUser = $firebaseAuth->getUser($uid);
    } catch (Throwable $e) {
        error_log('Firebase registration verification failed: ' . $e->getMessage());

        registrationResponse(401, [
            'status' => 'error',
            'message' => 'Invalid or expired authentication token.'
        ]);
        return;
    }

    $fullName = trim((string) ($payload['full_name'] ?? ''));
    $username = trim((string) ($payload['username'] ?? ''));

    $fullNameLength = function_exists('mb_strlen')
        ? mb_strlen($fullName, 'UTF-8')
        : strlen($fullName);

    if ($fullNameLength < 2 || $fullNameLength > 120) {
        registrationResponse(422, [
            'status' => 'error',
            'message' => 'Full name must contain 2 to 120 characters.'
        ]);
        return;
    }

    if (!preg_match('/^[A-Za-z0-9_]{3,50}$/D', $username)) {
        registrationResponse(422, [
            'status' => 'error',
            'message' => 'Username must be 3 to 50 characters using letters, numbers or underscores.'
        ]);
        return;
    }

    // Obtain the email from Firebase, not from the browser request.
    $email = strtolower(trim((string) ($firebaseUser->email ?? '')));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        registrationResponse(422, [
            'status' => 'error',
            'message' => 'A valid Firebase email address is required.'
        ]);
        return;
    }

    try {
        $pdo = getPDO();
        $pdo->beginTransaction();

        $existing = $pdo->prepare(
            'SELECT id FROM users WHERE firebase_uid = ? FOR UPDATE'
        );
        $existing->execute([$uid]);
        $existingUser = $existing->fetch();

        $emailCheck = $pdo->prepare(
            'SELECT id FROM users WHERE email = ? AND firebase_uid <> ? LIMIT 1'
        );
        $emailCheck->execute([$email, $uid]);

        if ($emailCheck->fetch()) {
            $pdo->rollBack();
            registrationResponse(409, [
                'status' => 'error',
                'message' => 'This email is already associated with another account.'
            ]);
            return;
        }

        $usernameCheck = $pdo->prepare(
            'SELECT id FROM users WHERE username = ? AND firebase_uid <> ? LIMIT 1'
        );
        $usernameCheck->execute([$username, $uid]);

        if ($usernameCheck->fetch()) {
            $pdo->rollBack();
            registrationResponse(409, [
                'status' => 'error',
                'message' => 'This username is already taken.'
            ]);
            return;
        }

        if ($existingUser) {
            $statement = $pdo->prepare(
                'UPDATE users
                 SET full_name = ?, username = ?, email = ?
                 WHERE firebase_uid = ?'
            );

            $statement->execute([
                $fullName,
                $username,
                $email,
                $uid
            ]);

            $userId = (int) $existingUser['id'];
            $created = false;
        } else {
            $statement = $pdo->prepare(
                'INSERT INTO users (firebase_uid, full_name, username, email)
                 VALUES (?, ?, ?, ?)'
            );

            $statement->execute([
                $uid,
                $fullName,
                $username,
                $email
            ]);

            $userId = (int) $pdo->lastInsertId();
            $created = true;
        }

        $pdo->commit();

        registrationResponse($created ? 201 : 200, [
            'status' => 'success',
            'message' => 'User profile saved successfully.',
            'user' => [
                'id' => $userId,
                'full_name' => $fullName,
                'username' => $username,
                'email' => $email
            ]
        ]);

    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Registration database error: ' . $e->getMessage());

        if ($e->getCode() === '23000') {
            registrationResponse(409, [
                'status' => 'error',
                'message' => 'This email or username is already in use.'
            ]);
            return;
        }

        registrationResponse(500, [
            'status' => 'error',
            'message' => 'Unable to save the user profile.'
        ]);

    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log('Registration error: ' . $e->getMessage());

        registrationResponse(500, [
            'status' => 'error',
            'message' => 'An unexpected registration error occurred.'
        ]);
    }
}
