<?php
include __DIR__ . '/../config/config-alerta.php';

// Obtener IP real del visitante
function obtenerIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    return $_SERVER['REMOTE_ADDR'] ?? 'Sin IP';
}

$ip = obtenerIP();
$city = 'Ciudad desconocida';
$country = 'País desconocido';

if ($ip === '127.0.0.1' || $ip === '::1') {
    // Caso especial para pruebas en localhost
    $city = 'Entorno local';
    $country = 'Localhost';
} else {
    // Consultar API para IP públicas
    $geoData = @file_get_contents("https://ipapi.co/{$ip}/json/");
    if ($geoData !== false) {
        $geoJson = json_decode($geoData, true);
        if (!empty($geoJson['city'])) {
            $city = $geoJson['city'];
        }
        if (!empty($geoJson['country_name'])) {
            $country = $geoJson['country_name'];
        }
    }
}

$payload = json_encode([
    "username" => "🚨 VISITANTE DETECTADO 🚨",
    "avatar_url" => "https://media.istockphoto.com/id/911660906/vector/computer-hacker-with-laptop-icon.jpg?s=612x612&w=0&k=20&c=rmx25IUnM2fHP4lXG96PNeZ_YQ1kQUTTWfGU4EE5iqQ=",
    "embeds" => [[
        "title" => "**🚨SE HA DETECTADO NUEVO VISITANTE🚨**",
        "color" => hexdec("3498db"),
        "fields" => [
            [
                "name" => "🌍 IP",
                "value" => "`$ip`",
                "inline" => true
            ],
            [
                "name" => "🏙️ Ciudad",
                "value" => "`$city`",
                "inline" => true
            ],
            [
                "name" => "🇺🇳 País",
                "value" => "`$country`",
                "inline" => true
            ],
            [
                "name" => "🕒 Fecha",
                "value" => "`" . date('Y-m-d H:i:s') . "`",
                "inline" => false
            ]
        ],
        "footer" => [
            "text" => "made by @morph3ush4ck",
            "icon_url" => "https://upload.wikimedia.org/wikipedia/commons/c/ca/Osama_bin_Laden_portrait.jpg"
        ]
    ]]
]);

$ch = curl_init(DISCORD_WEBHOOK_URL);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-type: application/json']);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);
