<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// --------------------------------------------------
// Database Configuration (Docker)
// --------------------------------------------------
$MYSQL_HOST = "mysql";
$MYSQL_USER = "root";
$MYSQL_PASS = "root";
$MYSQL_DB   = "guvi_user_db";

$REDIS_HOST = "redis";
$REDIS_PORT = 6379;

// --------------------------------------------------
// Get JSON Input
// --------------------------------------------------
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
    exit;
}

$email    = trim($data["email"] ?? "");
$password = trim($data["password"] ?? "");

// --------------------------------------------------
// Validate Input
// --------------------------------------------------
if ($email === "" || $password === "") {
    echo json_encode(["status" => "error", "message" => "Email & password required"]);
    exit;
}

// --------------------------------------------------
// Validate User from MySQL
// --------------------------------------------------
try {
    $pdo = new PDO(
        "mysql:host=$MYSQL_HOST;dbname=$MYSQL_DB;charset=utf8mb4",
        $MYSQL_USER,
        $MYSQL_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(["status" => "error", "message" => "Invalid email"]);
        exit;
    }

    if (!password_verify($password, $user["password"])) {
        echo json_encode(["status" => "error", "message" => "Incorrect password"]);
        exit;
    }

    $userId = $user["id"];

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "MySQL Error: " . $e->getMessage()]);
    exit;
}

// --------------------------------------------------
// Redis Session Token
// --------------------------------------------------
try {
    $redis = new Redis();
    $redis->connect($REDIS_HOST, $REDIS_PORT);

    // Create token
    $token = bin2hex(random_bytes(32));

    // Save session for 24 hours
    $sessionData = json_encode([
        "user_id" => $userId,
        "email" => $email,
        "created" => time()
    ]);

    $redis->setex("session:$token", 86400, $sessionData);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Redis Error: " . $e->getMessage()]);
    exit;
}

// --------------------------------------------------
// Success Response
// --------------------------------------------------
echo json_encode([
    "status" => "success",
    "message" => "Login successful",
    "token"   => $token,
    "user_id" => $userId
]);
exit;
?>