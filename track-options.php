<?php
header("Content-Type: application/json; charset=utf-8");

$trackOptions = [
    "Magyarország" => [
        "Hungaroring" => "Hungaroring · Mogyoród",
        "Pannonia-Ring" => "Pannonia-Ring · Ostffyasszonyfa",
        "Balaton Park Circuit" => "Balaton Park Circuit · Balatonfőkajár",
        "Kakucs Ring" => "Kakucs Ring · Kakucs",
        "Rabócsiring" => "Rabócsiring · Máriapócs"
    ],
    "Szomszédos országok" => [
        "Slovakia Ring" => "Slovakia Ring · Orechová Potôň",
        "Automotodrom Brno" => "Automotodrom Brno · Csehország",
        "Autodrom Most" => "Autodrom Most · Csehország",
        "Red Bull Ring" => "Red Bull Ring · Spielberg, Ausztria",
        "Salzburgring" => "Salzburgring · Ausztria",
        "Grobnik" => "Automotodrom Grobnik · Horvátország"
    ],
    "További európai pályák" => [
        "Lausitzring" => "Lausitzring · Németország",
        "Hockenheimring" => "Hockenheimring · Németország",
        "Nürburgring" => "Nürburgring · Németország",
        "Spa-Francorchamps" => "Spa-Francorchamps · Belgium",
        "Misano" => "Misano World Circuit · Olaszország"
    ]
];

$requestedTrack = isset($_GET["palya"]) && is_string($_GET["palya"])
    ? trim($_GET["palya"])
    : "";
$selectedTrack = "";

foreach ($trackOptions as $tracksInRegion) {
    if (array_key_exists($requestedTrack, $tracksInRegion)) {
        $selectedTrack = $requestedTrack;
        break;
    }
}

echo json_encode(
    [
        "options" => $trackOptions,
        "selectedTrack" => $selectedTrack
    ],
    JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
);
