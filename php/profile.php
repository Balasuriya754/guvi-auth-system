<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// --------------------------------------------------
// ENV / SETTINGS (Docker)
// --------------------------------------------------
$MONGO_URI  = "mongodb://mongo:27017";
$MONGO_DB   = "guvi_profiles";

$REDIS_HOST = "redis";
$REDIS_PORT = 6379;

// --------------------------------------------------
// AUTHENTICATION USING REDIS TOKEN
// --------------------------------------------------
$headers = getallheaders();

if (!isset($headers["Authorization"])) {
    echo json_encode(["status" => "error", "message" => "Missing token"]);
    exit;
}

$authHeader = $headers["Authorization"];

if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    echo json_encode(["status" => "error", "message" => "Invalid token format"]);
    exit;
}

$token = trim($matches[1]);

// Validate token in Redis
try {
    $redis = new Redis();
    $redis->connect($REDIS_HOST, $REDIS_PORT);

    $sessionData = $redis->get("session:$token");

    if (!$sessionData) {
        echo json_encode(["status" => "error", "message" => "Session expired or invalid"]);
        exit;
    }

    $session = json_decode($sessionData, true);
    $user_id = intval($session["user_id"]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Redis Error: " . $e->getMessage()]);
    exit;
}

// --------------------------------------------------
// DETERMINE ACTION
// --------------------------------------------------
$action = $_GET["action"] ?? "";

$data = json_decode(file_get_contents("php://input"), true);

// --------------------------------------------------
// MONGODB CONNECTION
// --------------------------------------------------
require_once __DIR__ . '/../public/vendor/autoload.php';

try {
    $client = new MongoDB\Client($MONGO_URI);
    $profiles = $client->selectCollection($MONGO_DB, 'profiles');

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "MongoDB Error: " . $e->getMessage()]);
    exit;
}

// --------------------------------------------------
// ACTION: GET PROFILE
// --------------------------------------------------
if ($action === "get") {

    try {
        $profile = $profiles->findOne(["user_id" => $user_id]);

        if (!$profile) {
            echo json_encode(["status" => "error", "message" => "Profile not found"]);
            exit;
        }

        echo json_encode([
            "status" => "success",
            "data" => [
                "fullname" => $profile["fullname"] ?? "",
                "age"      => $profile["age"] ?? "",
                "dob"      => $profile["dob"] ?? "",
                "contact"  => $profile["contact"] ?? "",
                "address"  => $profile["address"] ?? ""
            ]
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Fetch Error: " . $e->getMessage()]);
        exit;
    }
}

// --------------------------------------------------
// ACTION: UPDATE PROFILE
// --------------------------------------------------
if ($action === "update") {

    $updateData = [
        "age"     => $data["age"] ?? null,
        "dob"     => $data["dob"] ?? null,
        "contact" => $data["contact"] ?? null,
        "address" => $data["address"] ?? null,
        "updated_at" => date("Y-m-d H:i:s")
    ];

    try {
        $profiles->updateOne(
            ["user_id" => $user_id],
            ['$set' => $updateData]
        );

        echo json_encode([
            "status" => "success",
            "message" => "Profile updated successfully"
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => "Update Error: " . $e->getMessage()]);
        exit;
    }
}

// --------------------------------------------------
// INVALID ACTION
// --------------------------------------------------
echo json_encode([
    "status" => "error",
    "message" => "Invalid action"
]);
exit;
?>