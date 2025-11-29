<?php
/**
 * Authentication Handler for Login Form
 * 
 * This PHP script handles user authentication via POST requests from the Fetch API.
 * It validates credentials against a MySQL database using PDO,
 * creates sessions, and returns JSON responses.
 */

// --- Session Management ---
session_start();

// --- Set Response Headers ---
header('Content-Type: application/json');

// TODO: (Optional) Set CORS headers if your frontend and backend are on different domains
// You'll need headers for Access-Control-Allow-Origin, Methods, and Headers
 header('Access-Control-Allow-Origin: http://localhost:3000');
 header('Access-Control-Allow-Credentials: true');
 header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

// Preflight 
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

// Logout logic
if (isset($_GET['action']) && $_GET['action'] === 'logout') {

    $_SESSION = [];
    session_destroy();

    setcookie(session_name(), "", time() - 3600, "/");

    echo json_encode([
        "success" => true,
        "message" => "Logged out successfully"
    ]);
    exit;
}

// --- Check Request Method ---
// TODO: Verify that the request method is POST
// Use the $_SERVER superglobal to check the REQUEST_METHOD
// If the request is not POST, return an error response and exit
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}


// --- Get POST Data ---
// TODO: Retrieve the raw POST data
// The Fetch API sends JSON data in the request body
// Use file_get_contents with 'php://input' to read the raw request body
// TODO: Decode the JSON data into a PHP associative array
// Use json_decode with the second parameter set to true
$data = json_decode(file_get_contents("php://input"), true);

// TODO: Extract the email and password from the decoded data
// Check if both 'email' and 'password' keys exist in the array
// If either is missing, return an error response and exit



if (!isset($data["email"], $data["password"])) {
    echo json_encode(["success" => false, "message" => "Email and password required"]);
    exit;
}




// TODO: Store the email and password in variables
// Trim any whitespace from the email
$email = trim($data['email']);
$password = $data['password'];


// --- Server-Side Validation (Optional but Recommended) ---
// TODO: Validate the email format on the server side
// Use the appropriate filter function for email validation
// If invalid, return an error response and exit
// if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
//     echo json_encode(['success' => false, 'message' => 'Invalid email format']);
//     exit;
// }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email format']);
    exit;
}


// TODO: Validate the password length (minimum 8 characters)
// If invalid, return an error response and exit

if (strlen($password) < 8) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters']);
    exit;
}


// --- Database Connection ---
// TODO: Get the database connection using the provided function
// Assume getDBConnection() returns a PDO instance with error mode set to exception
// The function is defined elsewhere (e.g., in a config file or db.php)

// require 'db.php'; 
require __DIR__ . '/../../../../db.php';



// TODO: Wrap database operations in a try-catch block to handle PDO exceptions
// This ensures you can return a proper JSON error response if something goes wrong

try {
    $pdo = getDBConnection();
    // --- Prepare SQL Query ---
    
    // --- Prepare the Statement ---
    // TODO: Prepare the SQL statement using the PDO prepare method
    // Store the result in a variable
    // Prepared statements protect against SQL injection
$stmt = $pdo->prepare("SELECT id, name, email, password, is_admin FROM users WHERE email = ?");
$stmt->execute([$email]);

    // --- Fetch User Data ---
    // TODO: Fetch the user record from the database
    // Use the fetch method with PDO::FETCH_ASSOC
    // This returns an associative array of the user data, or false if no user found

$user = $stmt->fetch(PDO::FETCH_ASSOC);
    // --- Verify User Exists and Password Matches ---
    // TODO: Check if a user was found
    // The fetch method returns false if no record matches
    if (!$user || !password_verify($password, $user["password"])) {
    echo json_encode(["success" => false, "message" => "Invalid email or password"]);
    exit;
}

    //     // --- Handle Successful Authentication ---
$_SESSION["logged_in"] = true;
$_SESSION["user_id"] = $user["id"];
$_SESSION["user_name"] = $user["name"];
$_SESSION["user_email"] = $user["email"];
$_SESSION["role"] = $user["is_admin"] ? "admin" : "student";

echo json_encode([
    "success" => true,
    "message" => "Login successful",
    "user" => [
        "id" => $user["id"],
        "name" => $user["name"],
        "email" => $user["email"],
        "role" => $_SESSION["role"]
    ]
]);
exit;
    
    }catch (PDOException $e) {


    // TODO: Log the error for debugging
    // Use error_log() to write the error message to the server error log
    
    error_log("Database error: " . $e->getMessage());
    
    
    // TODO: Return a generic error message to the client
    // DON'T expose database details to the user for security reasons
    // Return a JSON response with success false and a generic message
echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.']);

    // TODO: Exit the script

exit;
}
// --- End of Script ---

?>
