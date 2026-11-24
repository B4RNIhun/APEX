<?php
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["error" => "Csak POST kérés megengedett."], JSON_UNESCAPED_UNICODE);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(["error" => "Érvénytelen adatok."], JSON_UNESCAPED_UNICODE);
    exit;
}

$track = trim((string) ($input["track"] ?? ""));
$availability = trim((string) ($input["availability"] ?? ""));
$package = trim((string) ($input["package"] ?? ""));
$name = trim((string) ($input["name"] ?? ""));
$email = trim((string) ($input["email"] ?? ""));

if ($track === "" || $availability === "" || $package === "" || $name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(["error" => "Kérjük, tölts ki minden mezőt érvényesen."], JSON_UNESCAPED_UNICODE);
    exit;
}

$record = [
    "track" => $track,
    "availability" => $availability,
    "package" => $package,
    "name" => $name,
    "email" => $email,
    "submittedAt" => date("c")
];

$file = __DIR__ . "/../erdeklodesek.json";
$handle = fopen($file, "c+");

if (!$handle) {
    http_response_code(500);
    echo json_encode(["error" => "Az adatfájlt nem sikerült megnyitni."], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!flock($handle, LOCK_EX)) {
    fclose($handle);
    http_response_code(500);
    echo json_encode(["error" => "Az adatfájl jelenleg nem írható."], JSON_UNESCAPED_UNICODE);
    exit;
}

$existing = stream_get_contents($handle);
$inquiries = $existing === false || trim($existing) === "" ? [] : json_decode($existing, true);

if (!is_array($inquiries)) {
    $inquiries = [];
}

$inquiries[] = $record;

rewind($handle);
ftruncate($handle, 0);
$written = fwrite($handle, json_encode($inquiries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
fflush($handle);
flock($handle, LOCK_UN);
fclose($handle);

if ($written === false) {
    http_response_code(500);
    echo json_encode(["error" => "Az adat mentése nem sikerült."], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(["success" => true], JSON_UNESCAPED_UNICODE);
