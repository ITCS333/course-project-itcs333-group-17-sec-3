<?php
/**
 * Student Management API
 * 
 * This is a RESTful API that handles all CRUD operations for student management.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structure (for reference):
 * Table: students
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - student_id (VARCHAR(50), UNIQUE) - The student's university ID
 *   - name (VARCHAR(100))
 *   - email (VARCHAR(100), UNIQUE)
 *   - password (VARCHAR(255)) - Hashed password
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve student(s)
 *   - POST: Create a new student OR change password
 *   - PUT: Update an existing student
 *   - DELETE: Delete a student
 * 
 * Response Format: JSON
 */

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");


// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit;
}


// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
require_once __DIR__ . '/../../../../db.php';


// TODO: Get the PDO database connection
$db = getDBConnection();


// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']

$method = $_SERVER["REQUEST_METHOD"];

// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()

$raw = file_get_contents("php://input");
$data = json_decode($raw, true) ?? [];

// TODO: Parse query parameters for filtering and searching
$studentId = $_GET['student_id'] ?? null;
$search = $_GET['search'] ?? null;
$sort = $_GET['sort'] ?? null;
$order = $_GET['order'] ?? null;
$action = $_GET['action'] ?? null;



/**
 * Function: Get all students or search for specific students
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by name, student_id, or email
 *   - sort: Optional field to sort by (name, student_id, email)
 *   - order: Optional sort order (asc or desc)
 */
function getStudents($db) {
    // TODO: Check if search parameter exists
    // If yes, prepare SQL query with WHERE clause using LIKE
    // Search should work on name, student_id, and email fields

    $allowedSort = ['name', 'student_id', 'email'];
    $allowedOrder = ['asc', 'desc'];

    $sql = "SELECT id, student_id, name, email, created_at FROM students WHERE 1";

    if ($search) {
        $sql .= " AND (name LIKE :search OR student_id LIKE :search OR email LIKE :search)";
    }

    // TODO: Check if sort and order parameters exist
    // If yes, add ORDER BY clause to the query
    // Validate sort field to prevent SQL injection (only allow: name, student_id, email)
    // Validate order to prevent SQL injection (only allow: asc, desc)
        if ($sort && in_array($sort, $allowedSort)) {
        $order = in_array(strtolower($order), $allowedOrder) ? strtoupper($order) : "ASC";
        $sql .= " ORDER BY $sort $order";
    }


    // TODO: Prepare the SQL query using PDO
    // Note: Do NOT select the password field
    
        $stmt = $db->prepare($sql);

    // TODO: Bind parameters if using search
        if ($search) {
        $stmt->bindValue(':search', '%' . $search . '%');
    }


    // TODO: Execute the query
        $stmt->execute();

    // TODO: Fetch all results as an associative array
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // TODO: Return JSON response with success status and data
        sendResponse(["success" => true, "data" => $students]);

}


/**
 * Function: Get a single student by student_id
 * Method: GET
 * 
 * Query Parameters:
 *   - student_id: The student's university ID
 */
function getStudentById($db, $studentId) {
    // TODO: Prepare SQL query to select student by student_id
        $stmt = $db->prepare("SELECT id, student_id, name, email, created_at FROM students WHERE student_id = ?");

    // TODO: Bind the student_id parameter
    
    // TODO: Execute the query
        $stmt->execute([$studentId]);

    // TODO: Fetch the result
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

    // TODO: Check if student exists
    // If yes, return success response with student data
    // If no, return error response with 404 status
    
    if ($student) {
        sendResponse(["success" => true, "data" => $student]);
    } else {
        sendResponse(["success" => false, "message" => "Student not found"], 404);
    }
}



/**
 * Function: Create a new student
 * Method: POST
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (must be unique)
 *   - name: Student's full name
 *   - email: Student's email (must be unique)
 *   - password: Default password (will be hashed)
 */
function createStudent($db, $data) {
    // TODO: Validate required fields
    // Check if student_id, name, email, and password are provided
    // If any field is missing, return error response with 400 status
        if (!isset($data['student_id'], $data['name'], $data['email'], $data['password'])) {
        sendResponse(["success" => false, "message" => "Missing required fields"], 400);
    }

    // TODO: Sanitize input data
    // Trim whitespace from all fields
    // Validate email format using filter_var()
        $student_id = sanitizeInput($data['student_id']);
    $name = sanitizeInput($data['name']);
    $email = sanitizeInput($data['email']);
    $password = $data['password'];

    // TODO: Check if student_id or email already exists
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
        if (!validateEmail($email)) {
        sendResponse(["success" => false, "message" => "Invalid email format"], 400);
    }
        // Check duplicates
    $stmt = $db->prepare("SELECT id FROM students WHERE email = ? OR student_id = ?");
    $stmt->execute([$email, $student_id]);

    if ($stmt->fetch()) {
        sendResponse(["success" => false, "message" => "Student email or ID already exists"], 409);
    }


    // TODO: Hash the password
    // Use password_hash() with PASSWORD_DEFAULT
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // TODO: Prepare INSERT query
        $stmt = $db->prepare("INSERT INTO students (student_id, name, email, password) VALUES (?, ?, ?, ?)");


    // TODO: Bind parameters
    // Bind student_id, name, email, and hashed password

    // TODO: Execute the query
            $ok = $stmt->execute([$student_id, $name, $email, $hashedPassword]);

    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created)
    // If no, return error response with 500 status
        if ($ok) {
        sendResponse(["success" => true, "message" => "Student created"], 201);
    } else {
        sendResponse(["success" => false, "message" => "Insert failed"], 500);
    }
}



/**
 * Function: Update an existing student
 * Method: PUT
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (to identify which student to update)
 *   - name: Updated student name (optional)
 *   - email: Updated student email (optional)
 */
function updateStudent($db, $data) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
        if (!isset($data['student_id'])) {
        sendResponse(["success" => false, "message" => "student_id required"], 400);
    }
        $student_id = $data['student_id'];

    // TODO: Check if student exists
    // Prepare and execute a SELECT query to find the student
    // If not found, return error response with 404 status
    $check = $db->prepare("SELECT id FROM students WHERE student_id = ?");
    $check->execute([$student_id]);
        if (!$check->fetch()) {
        sendResponse(["success" => false, "message" => "Student not found"], 404);
    }


    // TODO: Build UPDATE query dynamically based on provided fields
    // Only update fields that are provided in the request
        $fields = [];
    $params = [];

        if (isset($data['name'])) {
        $fields[] = "name = ?";
        $params[] = sanitizeInput($data['name']);
    }


    // TODO: If email is being updated, check if new email already exists
    // Prepare and execute a SELECT query
    // Exclude the current student from the check
    // If duplicate found, return error response with 409 status
        if (isset($data['email'])) {
        if (!validateEmail($data['email'])) {
            sendResponse(["success" => false, "message" => "Invalid email"], 400);
        }
                $stmt = $db->prepare("SELECT id FROM students WHERE email = ? AND student_id != ?");
        $stmt->execute([$data['email'], $student_id]);

        if ($stmt->fetch()) {
            sendResponse(["success" => false, "message" => "Email already taken"], 409);
        }

        $fields[] = "email = ?";
        $params[] = sanitizeInput($data['email']);
    }
        if (empty($fields)) {
        sendResponse(["success" => false, "message" => "Nothing to update"], 400);
    }

    $params[] = $student_id;



    // TODO: Bind parameters dynamically
    // Bind only the parameters that are being updated
        $sql = "UPDATE students SET " . implode(", ", $fields) . " WHERE student_id = ?";
    $stmt = $db->prepare($sql);

    // TODO: Execute the query
        $ok = $stmt->execute($params);

    // TODO: Check if update was successful
    // If yes, return success response
    // If no, return error response with 500 status
        if ($ok) {
        sendResponse(["success" => true, "message" => "Student updated"]);
    } else {
        sendResponse(["success" => false, "message" => "Update failed"], 500);
    }
}




/**
 * Function: Delete a student
 * Method: DELETE
 * 
 * Query Parameters or JSON Body:
 *   - student_id: The student's university ID
 */
function deleteStudent($db, $studentId) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
        if (!$studentId) {
        sendResponse(["success" => false, "message" => "student_id required"], 400);
    }


    // TODO: Check if student exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
        $stmt = $db->prepare("SELECT id FROM students WHERE student_id = ?");
    $stmt->execute([$studentId]);
    if (!$stmt->fetch()) {
        sendResponse(["success" => false, "message" => "Student not found"], 404);
    }

    // TODO: Prepare DELETE query
        $delete = $db->prepare("DELETE FROM students WHERE student_id = ?");

    // TODO: Bind the student_id parameter
    
    // TODO: Execute the query
        $ok = $delete->execute([$studentId]);

    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error response with 500 status
        if ($ok) {
        sendResponse(["success" => true, "message" => "Student deleted"]);
    } else {
        sendResponse(["success" => false, "message" => "Delete failed"], 500);
    }
}




/**
 * Function: Change password
 * Method: POST with action=change_password
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (identifies whose password to change)
 *   - current_password: The student's current password
 *   - new_password: The new password to set
 */
function changePassword($db, $data) {
    // TODO: Validate required fields
    // Check if student_id, current_password, and new_password are provided
    // If any field is missing, return error response with 400 status
        if (!isset($data['student_id'], $data['current_password'], $data['new_password'])) {
        sendResponse(["success" => false, "message" => "Missing required fields"], 400);
    }

    // TODO: Validate new password strength
    // Check minimum length (at least 8 characters)
    // If validation fails, return error response with 400 status
        $student_id = $data['student_id'];
    $current = $data['current_password'];
    $new = $data['new_password'];

        if (strlen($new) < 8) {
        sendResponse(["success" => false, "message" => "New password must be at least 8 characters"], 400);
    }

    // TODO: Retrieve current password hash from database
    // Prepare and execute SELECT query to get password
        $stmt = $db->prepare("SELECT password FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // TODO: Verify current password
    // Use password_verify() to check if current_password matches the hash
    // If verification fails, return error response with 401 status (Unauthorized)
        if (!$row) {
        sendResponse(["success" => false, "message" => "Student not found"], 404);
    }

    if (!password_verify($current, $row['password'])) {
        sendResponse(["success" => false, "message" => "Incorrect current password"], 401);
    }

    // TODO: Hash the new password
    // Use password_hash() with PASSWORD_DEFAULT
        $hashed = password_hash($new, PASSWORD_DEFAULT);

    // TODO: Update password in database
    // Prepare UPDATE query
        $update = $db->prepare("UPDATE students SET password = ? WHERE student_id = ?");

    // TODO: Bind parameters and execute
        $ok = $update->execute([$hashed, $student_id]);

    // TODO: Check if update was successful
    // If yes, return success response
    // If no, return error response with 500 status
        if ($ok) {
        sendResponse(["success" => true, "message" => "Password updated"]);
    } else {
        sendResponse(["success" => false, "message" => "Failed to update password"], 500);
    }
}




// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on HTTP method
    
    if ($method === 'GET') {
        // TODO: Check if student_id is provided in query parameters
        // If yes, call getStudentById()
        // If no, call getStudents() to get all students (with optional search/sort)
                if ($studentId) getStudentById($db, $studentId);
        else getStudents($db);

    } elseif ($method === 'POST') {
        // TODO: Check if this is a change password request
        // Look for action=change_password in query parameters
        // If yes, call changePassword()
        // If no, call createStudent()
                if ($action === "change_password") changePassword($db, $data);
        else createStudent($db, $data);

    } elseif ($method === 'PUT') {
        // TODO: Call updateStudent()
                updateStudent($db, $data);

    } elseif ($method === 'DELETE') {
        // TODO: Get student_id from query parameter or request body
        // Call deleteStudent()
                if ($studentId) deleteStudent($db, $studentId);
        else sendResponse(["success" => false, "message" => "student_id required"], 400);

    } else {
        // TODO: Return error for unsupported methods
        // Set HTTP status to 405 (Method Not Allowed)
        // Return JSON error message
         sendResponse(["success" => false, "message" => "Method not allowed"], 405);

    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional)
    // Return generic error response with 500 status

    
} catch (Exception $e) {
    // TODO: Handle general errors
    // Return error response with 500 status
        error_log("API Error: " . $e->getMessage());
    sendResponse(["success" => false, "message" => "Server error"], 500);


}


// ============================================================================
// HELPER FUNCTIONS (Optional but Recommended)
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send
 * @param int $statusCode - HTTP status code
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
        http_response_code($status);

    // TODO: Echo JSON encoded data
        echo json_encode($data);

    // TODO: Exit to prevent further execution
        exit;

}


/**
 * Helper function to validate email format
 * 
 * @param string $email - Email address to validate
 * @return bool - True if valid, false otherwise
 */
function validateEmail($email) {
    // TODO: Use filter_var with FILTER_VALIDATE_EMAIL
    // Return true if valid, false otherwise
        return filter_var($email, FILTER_VALIDATE_EMAIL);

}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace
    // TODO: Strip HTML tags using strip_tags()
    // TODO: Convert special characters using htmlspecialchars()
    // Return sanitized data
        return htmlspecialchars(strip_tags(trim($data)));

}

?>
