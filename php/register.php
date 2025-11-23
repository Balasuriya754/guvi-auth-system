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
// Database Configurations (Docker)
// --------------------------------------------------
$MYSQL_HOST = "mysql";       // Docker service name
$MYSQL_USER = "root";
$MYSQL_PASS = "root";
$MYSQL_DB   = "guvi_user_db";

$MONGO_URI  = "mongodb://mongo:27017";
$MONGO_DB   = "guvi_profiles";

// --------------------------------------------------
// Read JSON Input
// --------------------------------------------------
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
    exit;
}

$fullname = trim($data["fullname"] ?? "");
$email    = trim($data["email"] ?? "");
$password = trim($data["password"] ?? "");

// --------------------------------------------------
// Validate Input
// --------------------------------------------------
if ($fullname === "" || $email === "" || $password === "") {
    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", "message" => "Invalid email format"]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(["status" => "error", "message" => "Password must be at least 6 characters"]);
    exit;
}

// --------------------------------------------------
// MySQL Connection (PDO + Prepared Statements)
// --------------------------------------------------
try {
    $pdo = new PDO(
        "mysql:host=$MYSQL_HOST;dbname=$MYSQL_DB;charset=utf8mb4",
        $MYSQL_USER,
        $MYSQL_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Check if email already exists
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
        echo json_encode(["status" => "error", "message" => "Email already registered"]);
        exit;
    }

    // Insert user securely
    $stmt = $pdo->prepare("INSERT INTO users (fullname, email, password, created_at) VALUES (?, ?, ?, NOW())");
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $stmt->execute([$fullname, $email, $hashedPassword]);

    $userId = $pdo->lastInsertId();

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "MySQL Error: " . $e->getMessage()]);
    exit;
}

// --------------------------------------------------
// Insert into MongoDB (Profile) - Using PHP Library
// --------------------------------------------------
try {
    require_once __DIR__ . '/../public/vendor/autoload.php';
    
    $client = new MongoDB\Client($MONGO_URI);
    $collection = $client->selectCollection($MONGO_DB, 'profiles');
    
    $collection->insertOne([
        "user_id" => intval($userId),
        "fullname" => $fullname,
        "email" => $email,
        "age" => null,
        "dob" => null,
        "contact" => null,
        "address" => null,
        "created_at" => date("Y-m-d H:i:s")
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "MongoDB Error: " . $e->getMessage()]);
    exit;
}

// --------------------------------------------------
// Response
// --------------------------------------------------
echo json_encode([
    "status" => "success",
    "message" => "User registered successfully!",
    "user_id" => $userId
]);
exit;
?>