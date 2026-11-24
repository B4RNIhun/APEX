<?php
header("Content-Type: application/json; charset=utf-8");

function respond(int $status, array $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function encryptAccountField(string $value, string $key): string
{
    $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $ciphertext = sodium_crypto_secretbox($value, $nonce, $key);
    return base64_encode($nonce . $ciphertext);
}

function decryptAccountField(string $value, string $key): string
{
    $decoded = base64_decode($value, true);
    $minimumLength = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES;

    if ($decoded === false || strlen($decoded) < $minimumLength) {
        throw new RuntimeException("A tárolt felhasználói adat sérült.");
    }

    $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
    $plaintext = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);

    if ($plaintext === false) {
        throw new RuntimeException("A felhasználói adat visszafejtése nem sikerült.");
    }

    return $plaintext;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    respond(405, ["error" => "Csak POST kérés engedélyezett."]);
}

if (!extension_loaded("sodium")) {
    respond(500, ["error" => "A PHP sodium bővítményt engedélyezni kell a titkosított tároláshoz."]);
}

try {
    $input = json_decode(file_get_contents("php://input"), true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    respond(400, ["error" => "Érvénytelen JSON-adat."]);
}

if (!is_array($input)) {
    respond(400, ["error" => "Érvénytelen kérés."]);
}

$action = $input["action"] ?? "";
$email = $input["email"] ?? "";
$password = $input["password"] ?? "";
$name = $input["name"] ?? "";

if (!is_string($action) || !in_array($action, ["register", "login"], true)
    || !is_string($email) || !is_string($password)
    || ($action === "register" && !is_string($name))) {
    respond(400, ["error" => "Hiányzó vagy érvénytelen mezők."]);
}

$name = $action === "register" ? $name : "";
$email = strtolower(trim($email));
$name = trim($name);

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254
    || strlen($password) < 8 || strlen($password) > 72
    || ($action === "register" && ($name === "" || strlen($name) > 200))) {
    respond(422, ["error" => "Ellenőrizd az adatokat. A jelszó 8–72 bájt hosszú legyen."]);
}

ini_set("session.use_strict_mode", "1");
session_set_cookie_params([
    "httponly" => true,
    "secure" => isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "samesite" => "Strict",
    "path" => "/"
]);

if (!session_start()) {
    respond(500, ["error" => "A bejelentkezési munkamenetet nem sikerült elindítani."]);
}

$handle = null;
$locked = false;
$responseStatus = 500;
$response = ["error" => "A fiókművelet nem sikerült."];
$accountsChanged = false;
$authenticatedUserId = null;
$authenticatedName = null;

try {
    $parentDirectory = dirname(__DIR__);
    $dataDirectory = $parentDirectory . DIRECTORY_SEPARATOR . "apex-private-data";
    $keyFile = $parentDirectory . DIRECTORY_SEPARATOR . "apex-account.key";

    if (!is_dir($dataDirectory) && !mkdir($dataDirectory, 0700, true) && !is_dir($dataDirectory)) {
        throw new RuntimeException("A privát adattárat nem sikerült létrehozni.");
    }

    if (!is_file($keyFile)) {
        $newKey = random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        $keyHandle = fopen($keyFile, "x+b");

        if ($keyHandle !== false) {
            $keyWritten = fwrite($keyHandle, $newKey);
            fflush($keyHandle);
            fclose($keyHandle);

            if ($keyWritten !== strlen($newKey)) {
                throw new RuntimeException("A titkosítási kulcs mentése nem sikerült.");
            }
        } elseif (!is_file($keyFile)) {
            throw new RuntimeException("A titkosítási kulcsot nem sikerült létrehozni.");
        }
    }

    $masterKey = file_get_contents($keyFile);
    if ($masterKey === false || strlen($masterKey) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
        throw new RuntimeException("A titkosítási kulcs hiányzik vagy érvénytelen.");
    }

    $encryptionKey = hash_hmac("sha256", "apex-user-fields-v1", $masterKey, true);
    $emailIndexKey = hash_hmac("sha256", "apex-email-index-v1", $masterKey, true);
    $emailIndex = hash_hmac("sha256", $email, $emailIndexKey);
    $accountsFile = $dataDirectory . DIRECTORY_SEPARATOR . "users.json";
    $handle = fopen($accountsFile, "c+");

    if ($handle === false) {
        throw new RuntimeException("A felhasználói adattár nem nyitható meg.");
    }

    if (!flock($handle, LOCK_EX)) {
        throw new RuntimeException("A felhasználói adattár zárolása nem sikerült.");
    }
    $locked = true;

    $contents = stream_get_contents($handle);
    if ($contents === false) {
        throw new RuntimeException("A felhasználói adattár nem olvasható.");
    }
    $accounts = trim($contents) === ""
        ? []
        : json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

    if (!is_array($accounts)) {
        throw new RuntimeException("A felhasználói adattár formátuma érvénytelen.");
    }

    if ($action === "register") {
        foreach ($accounts as $account) {
            if (is_array($account) && isset($account["emailIndex"])
                && is_string($account["emailIndex"])
                && hash_equals($account["emailIndex"], $emailIndex)) {
                $responseStatus = 409;
                $response = ["error" => "Ezzel az e-mail-címmel már létezik fiók."];
                break;
            }
        }

        if ($responseStatus === 500) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            if ($passwordHash === false) {
                throw new RuntimeException("A jelszó biztonságos tárolása nem sikerült.");
            }

            $authenticatedUserId = bin2hex(random_bytes(16));
            $authenticatedName = $name;
            $accounts[] = [
                "id" => $authenticatedUserId,
                "emailIndex" => $emailIndex,
                "emailEncrypted" => encryptAccountField($email, $encryptionKey),
                "nameEncrypted" => encryptAccountField($name, $encryptionKey),
                "passwordHash" => $passwordHash,
                "createdAt" => date("c"),
                "lastLoginAt" => date("c")
            ];
            $accountsChanged = true;
            $responseStatus = 201;
            $response = ["success" => true, "message" => "A regisztráció sikerült, be vagy jelentkezve."];
        }
    } else {
        $accountIndex = null;
        foreach ($accounts as $index => $account) {
            if (is_array($account) && isset($account["emailIndex"])
                && is_string($account["emailIndex"])
                && hash_equals($account["emailIndex"], $emailIndex)) {
                $accountIndex = $index;
                break;
            }
        }

        if ($accountIndex === null || !isset($accounts[$accountIndex]["passwordHash"])
            || !is_string($accounts[$accountIndex]["passwordHash"])
            || !password_verify($password, $accounts[$accountIndex]["passwordHash"])) {
            $responseStatus = 401;
            $response = ["error" => "Hibás e-mail-cím vagy jelszó."];
        } else {
            $account = $accounts[$accountIndex];
            $authenticatedUserId = $account["id"];
            $authenticatedName = decryptAccountField($account["nameEncrypted"], $encryptionKey);
            $accounts[$accountIndex]["lastLoginAt"] = date("c");

            if (password_needs_rehash($account["passwordHash"], PASSWORD_DEFAULT)) {
                $accounts[$accountIndex]["passwordHash"] = password_hash($password, PASSWORD_DEFAULT);
            }

            $accountsChanged = true;
            $responseStatus = 200;
            $response = ["success" => true, "message" => "Sikeres bejelentkezés."];
        }
    }

    if ($accountsChanged) {
        $json = json_encode($accounts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        rewind($handle);
        if (!ftruncate($handle, 0)) {
            throw new RuntimeException("A felhasználói adattár ürítése nem sikerült.");
        }
        $written = fwrite($handle, $json);
        if ($written !== strlen($json) || !fflush($handle)) {
            throw new RuntimeException("A felhasználói adatok mentése nem sikerült.");
        }

        if (!session_regenerate_id(true)) {
            throw new RuntimeException("A bejelentkezési munkamenet biztonságosítása nem sikerült.");
        }
        $_SESSION["userId"] = $authenticatedUserId;
        $_SESSION["userName"] = $authenticatedName;
    }
} catch (Throwable $error) {
    error_log("Account endpoint error: " . $error->getMessage());
    $responseStatus = 500;
    $response = ["error" => "A fiókművelet közben szerverhiba történt."];
} finally {
    if (is_resource($handle)) {
        if ($locked) {
            flock($handle, LOCK_UN);
        }
        fclose($handle);
    }
}

respond($responseStatus, $response);
