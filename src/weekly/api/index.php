<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'None',
    'secure' => false
]);

session_start();
require_once '../../auth/api/auth_check.php';
requireRole('admin');
/**
 * Weekly Course Breakdown API
 * 
 * This is a RESTful API that handles all CRUD operations for weekly course content
 * and discussion comments. It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: weeks
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50), UNIQUE) - Unique identifier (e.g., "week_1")
 *   - title (VARCHAR(200))
 *   - start_date (DATE)
 *   - description (TEXT)
 *   - links (TEXT) - JSON encoded array of links
 *   - created_at (TIMESTAMP)
 *   - updated_at (TIMESTAMP)
 * 
 * Table: comments
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - week_id (VARCHAR(50)) - Foreign key reference to weeks.week_id
 *   - author (VARCHAR(100))
 *   - text (TEXT)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve week(s) or comment(s)
 *   - POST: Create a new week or comment
 *   - PUT: Update an existing week
 *   - DELETE: Delete a week or comment
 * 
 * Response Format: JSON
 */

// ============================================================================
// SETUP AND CONFIGURATION
// ============================================================================

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');


// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
// Example: require_once '../config/Database.php';
// In this project, we assume a shared db.php with getDBConnection().
require_once __DIR__ . '/../../../../db.php';


// TODO: Get the PDO database connection
// Example: $database = new Database();
//          $db = $database->getConnection();
$db = getDBConnection();


// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';


// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$rawInput = file_get_contents('php://input');
$bodyData = [];
if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $bodyData = $decoded;
    }
}


// TODO: Parse query parameters
// Get the 'resource' parameter to determine if request is for weeks or comments
// Example: ?resource=weeks or ?resource=comments
$resource = isset($_GET['resource']) ? strtolower($_GET['resource']) : 'weeks';


// ============================================================================
// WEEKS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all weeks or search for specific weeks
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort: Optional field to sort by (title, start_date)
 *   - order: Optional sort order (asc or desc, default: asc)
 */
function getAllWeeks($db) {
    // TODO: Initialize variables for search, sort, and order from query parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sort   = isset($_GET['sort']) ? trim($_GET['sort']) : 'start_date';
    $order  = isset($_GET['order']) ? strtolower(trim($_GET['order'])) : 'asc';
    
    // TODO: Start building the SQL query
    // Base query: SELECT week_id, title, start_date, description, links, created_at FROM weeks
    $sql = "SELECT week_id, title, start_date, description, links, created_at, updated_at FROM weeks";
    $params = [];

    // TODO: Check if search parameter exists
    // If yes, add WHERE clause using LIKE for title and description
    // Example: WHERE title LIKE ? OR description LIKE ?
    if ($search !== '') {
        $sql .= " WHERE title LIKE :search OR description LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }
    
    // TODO: Check if sort parameter exists
    // Validate sort field to prevent SQL injection (only allow: title, start_date, created_at)
    // If invalid, use default sort field (start_date)
    $allowedSortFields = ['title', 'start_date', 'created_at'];
    if (!isValidSortField($sort, $allowedSortFields)) {
        $sort = 'start_date';
    }
    
    // TODO: Check if order parameter exists
    // Validate order to prevent SQL injection (only allow: asc, desc)
    // If invalid, use default order (asc)
    if ($order !== 'asc' && $order !== 'desc') {
        $order = 'asc';
    }
    
    // TODO: Add ORDER BY clause to the query
    $sql .= " ORDER BY {$sort} {$order}";
    
    // TODO: Prepare the SQL query using PDO
    $stmt = $db->prepare($sql);
    
    // TODO: Bind parameters if using search
    // Use wildcards for LIKE: "%{$searchTerm}%"
    if (isset($params[':search'])) {
        $stmt->bindValue(':search', $params[':search'], PDO::PARAM_STR);
    }
    
    // TODO: Execute the query
    $stmt->execute();
    
    // TODO: Fetch all results as an associative array
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: Process each week's links field
    // Decode the JSON string back to an array using json_decode()
    foreach ($rows as &$row) {
        if (!empty($row['links'])) {
            $decoded = json_decode($row['links'], true);
            $row['links'] = is_array($decoded) ? $decoded : [];
        } else {
            $row['links'] = [];
        }
    }
    
    // TODO: Return JSON response with success status and data
    // Use sendResponse() helper function
    sendResponse([
        'success' => true,
        'data'    => $rows
    ]);
}


/**
 * Function: Get a single week by week_id
 * Method: GET
 * Resource: weeks
 * 
 * Query Parameters:
 *   - week_id: The unique week identifier (e.g., "week_1")
 */
function getWeekById($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (empty($weekId)) {
        sendError('Missing week_id parameter', 400);
    }
    
    // TODO: Prepare SQL query to select week by week_id
    // SELECT week_id, title, start_date, description, links, created_at FROM weeks WHERE week_id = ?
    $stmt = $db->prepare(
        "SELECT week_id, title, start_date, description, links, created_at, updated_at 
         FROM weeks 
         WHERE week_id = ?"
    );
    
    // TODO: Bind the week_id parameter
    $stmt->execute([$weekId]);
    
    // TODO: Execute the query
    // (already executed above)
    
    // TODO: Fetch the result
    $week = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // TODO: Check if week exists
    // If yes, decode the links JSON and return success response with week data
    // If no, return error response with 404 status
    if (!$week) {
        sendError('Week not found', 404);
    }

    if (!empty($week['links'])) {
        $decoded = json_decode($week['links'], true);
        $week['links'] = is_array($decoded) ? $decoded : [];
    } else {
        $week['links'] = [];
    }

    sendResponse([
        'success' => true,
        'data'    => $week
    ]);
}


/**
 * Function: Create a new week
 * Method: POST
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: Unique week identifier (e.g., "week_1")
 *   - title: Week title (e.g., "Week 1: Introduction to HTML")
 *   - start_date: Start date in YYYY-MM-DD format
 *   - description: Week description
 *   - links: Array of resource links (will be JSON encoded)
 */
function createWeek($db, $data) {
    // TODO: Validate required fields
    // Check if week_id, title, start_date, and description are provided
    // If any field is missing, return error response with 400 status
    $required = ['week_id', 'title', 'start_date', 'description'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            sendError("Missing required field: {$field}", 400);
        }
    }
    
    // TODO: Sanitize input data
    // Trim whitespace from title, description, and week_id
    $weekId      = sanitizeInput($data['week_id']);
    $title       = sanitizeInput($data['title']);
    $startDate   = trim($data['start_date']);
    $description = sanitizeInput($data['description']);
    
    // TODO: Validate start_date format
    // Use a regex or DateTime::createFromFormat() to verify YYYY-MM-DD format
    // If invalid, return error response with 400 status
    if (!validateDate($startDate)) {
        sendError('Invalid start_date format. Expected YYYY-MM-DD.', 400);
    }
    
    // TODO: Check if week_id already exists
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
    $stmt = $db->prepare("SELECT id FROM weeks WHERE week_id = ?");
    $stmt->execute([$weekId]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        sendError('week_id already exists', 409);
    }
    
    // TODO: Handle links array
    // If links is provided and is an array, encode it to JSON using json_encode()
    // If links is not provided, use an empty array []
    $linksArray = [];
    if (isset($data['links']) && is_array($data['links'])) {
        $linksArray = $data['links'];
    }
    $linksJson = json_encode($linksArray);
    
    // TODO: Prepare INSERT query
    // INSERT INTO weeks (week_id, title, start_date, description, links) VALUES (?, ?, ?, ?, ?)
    $insert = $db->prepare(
        "INSERT INTO weeks (week_id, title, start_date, description, links) 
         VALUES (?, ?, ?, ?, ?)"
    );
    
    // TODO: Bind parameters
    // (binding done via execute with array)
    
    // TODO: Execute the query
    $ok = $insert->execute([$weekId, $title, $startDate, $description, $linksJson]);
    
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created) and the new week data
    // If no, return error response with 500 status
    if (!$ok) {
        sendError('Failed to create week', 500);
    }

    $newWeek = [
        'week_id'    => $weekId,
        'title'      => $title,
        'start_date' => $startDate,
        'description'=> $description,
        'links'      => $linksArray
    ];

    sendResponse([
        'success' => true,
        'data'    => $newWeek
    ], 201);
}


/**
 * Function: Update an existing week
 * Method: PUT
 * Resource: weeks
 * 
 * Required JSON Body:
 *   - week_id: The week identifier (to identify which week to update)
 *   - title: Updated week title (optional)
 *   - start_date: Updated start date (optional)
 *   - description: Updated description (optional)
 *   - links: Updated array of links (optional)
 */
function updateWeek($db, $data) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (empty($data['week_id'])) {
        sendError('Missing week_id field', 400);
    }
    $weekId = sanitizeInput($data['week_id']);
    
    // TODO: Check if week exists
    // Prepare and execute a SELECT query to find the week
    // If not found, return error response with 404 status
    $stmt = $db->prepare("SELECT * FROM weeks WHERE week_id = ?");
    $stmt->execute([$weekId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        sendError('Week not found', 404);
    }
    
    // TODO: Build UPDATE query dynamically based on provided fields
    // Initialize an array to hold SET clauses
    // Initialize an array to hold values for binding
    $setClauses = [];
    $values     = [];
    
    // TODO: Check which fields are provided and add to SET clauses
    // If title is provided, add "title = ?"
    if (isset($data['title'])) {
        $setClauses[] = "title = ?";
        $values[] = sanitizeInput($data['title']);
    }
    // If start_date is provided, validate format and add "start_date = ?"
    if (isset($data['start_date'])) {
        $startDate = trim($data['start_date']);
        if (!validateDate($startDate)) {
            sendError('Invalid start_date format. Expected YYYY-MM-DD.', 400);
        }
        $setClauses[] = "start_date = ?";
        $values[] = $startDate;
    }
    // If description is provided, add "description = ?"
    if (isset($data['description'])) {
        $setClauses[] = "description = ?";
        $values[] = sanitizeInput($data['description']);
    }
    // If links is provided, encode to JSON and add "links = ?"
    if (isset($data['links'])) {
        $linksArray = is_array($data['links']) ? $data['links'] : [];
        $linksJson  = json_encode($linksArray);
        $setClauses[] = "links = ?";
        $values[] = $linksJson;
    }
    
    // TODO: If no fields to update, return error response with 400 status
    if (empty($setClauses)) {
        sendError('No fields provided to update', 400);
    }
    
    // TODO: Add updated_at timestamp to SET clauses
    // Add "updated_at = CURRENT_TIMESTAMP"
    $setClauses[] = "updated_at = CURRENT_TIMESTAMP";
    
    // TODO: Build the complete UPDATE query
    // UPDATE weeks SET [clauses] WHERE week_id = ?
    $sql = "UPDATE weeks SET " . implode(', ', $setClauses) . " WHERE week_id = ?";
    
    // TODO: Prepare the query
    $updateStmt = $db->prepare($sql);
    
    // TODO: Bind parameters dynamically
    // Bind values array and then bind week_id at the end
    $values[] = $weekId;
    
    // TODO: Execute the query
    $ok = $updateStmt->execute($values);
    
    // TODO: Check if update was successful
    // If yes, return success response with updated week data
    // If no, return error response with 500 status
    if (!$ok) {
        sendError('Failed to update week', 500);
    }

    // Fetch updated row
    $stmt = $db->prepare(
        "SELECT week_id, title, start_date, description, links, created_at, updated_at 
         FROM weeks WHERE week_id = ?"
    );
    $stmt->execute([$weekId]);
    $week = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!empty($week['links'])) {
        $decoded = json_decode($week['links'], true);
        $week['links'] = is_array($decoded) ? $decoded : [];
    } else {
        $week['links'] = [];
    }

    sendResponse([
        'success' => true,
        'data'    => $week
    ]);
}


/**
 * Function: Delete a week
 * Method: DELETE
 * Resource: weeks
 * 
 * Query Parameters or JSON Body:
 *   - week_id: The week identifier
 */
function deleteWeek($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (empty($weekId)) {
        sendError('Missing week_id parameter', 400);
    }
    
    // TODO: Check if week exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $stmt = $db->prepare("SELECT id FROM weeks WHERE week_id = ?");
    $stmt->execute([$weekId]);
    $week = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$week) {
        sendError('Week not found', 404);
    }
    
    // TODO: Delete associated comments first (to maintain referential integrity)
    // Prepare DELETE query for comments table
    // DELETE FROM comments WHERE week_id = ?
    $deleteComments = $db->prepare("DELETE FROM comments WHERE week_id = ?");
    
    // TODO: Execute comment deletion query
    $deleteComments->execute([$weekId]);
    
    // TODO: Prepare DELETE query for week
    // DELETE FROM weeks WHERE week_id = ?
    $deleteWeekStmt = $db->prepare("DELETE FROM weeks WHERE week_id = ?");
    
    // TODO: Bind the week_id parameter
    // (bound via execute)
    
    // TODO: Execute the query
    $ok = $deleteWeekStmt->execute([$weekId]);
    
    // TODO: Check if delete was successful
    // If yes, return success response with message indicating week and comments deleted
    // If no, return error response with 500 status
    if (!$ok || $deleteWeekStmt->rowCount() === 0) {
        sendError('Failed to delete week', 500);
    }

    sendResponse([
        'success' => true,
        'message' => 'Week and associated comments deleted successfully'
    ]);
}


// ============================================================================
// COMMENTS CRUD OPERATIONS
// ============================================================================

/**
 * Function: Get all comments for a specific week
 * Method: GET
 * Resource: comments
 * 
 * Query Parameters:
 *   - week_id: The week identifier to get comments for
 */
function getCommentsByWeek($db, $weekId) {
    // TODO: Validate that week_id is provided
    // If not, return error response with 400 status
    if (empty($weekId)) {
        sendError('Missing week_id parameter', 400);
    }
    
    // TODO: Prepare SQL query to select comments for the week
    // SELECT id, week_id, author, text, created_at FROM comments WHERE week_id = ? ORDER BY created_at ASC
    $stmt = $db->prepare(
        "SELECT id, week_id, author, text, created_at 
         FROM comments 
         WHERE week_id = ? 
         ORDER BY created_at ASC"
    );
    
    // TODO: Bind the week_id parameter
    $stmt->execute([$weekId]);
    
    // TODO: Execute the query
    // (already executed above)
    
    // TODO: Fetch all results as an associative array
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // TODO: Return JSON response with success status and data
    // Even if no comments exist, return an empty array
    sendResponse([
        'success' => true,
        'data'    => $rows
    ]);
}


/**
 * Function: Create a new comment
 * Method: POST
 * Resource: comments
 * 
 * Required JSON Body:
 *   - week_id: The week identifier this comment belongs to
 *   - author: Comment author name
 *   - text: Comment text content
 */
function createComment($db, $data) {
    // TODO: Validate required fields
    // Check if week_id, author, and text are provided
    // If any field is missing, return error response with 400 status
    $required = ['week_id', 'author', 'text'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            sendError("Missing required field: {$field}", 400);
        }
    }
    
    // TODO: Sanitize input data
    // Trim whitespace from all fields
    $weekId = sanitizeInput($data['week_id']);
    $author = sanitizeInput($data['author']);
    $text   = trim($data['text']);
    
    // TODO: Validate that text is not empty after trimming
    // If empty, return error response with 400 status
    if ($text === '') {
        sendError('Comment text cannot be empty', 400);
    }
    
    // TODO: Check if the week exists
    // Prepare and execute a SELECT query on weeks table
    // If week not found, return error response with 404 status
    $stmt = $db->prepare("SELECT id FROM weeks WHERE week_id = ?");
    $stmt->execute([$weekId]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        sendError('Week not found for this comment', 404);
    }
    
    // TODO: Prepare INSERT query
    // INSERT INTO comments (week_id, author, text) VALUES (?, ?, ?)
    $insert = $db->prepare(
        "INSERT INTO comments (week_id, author, text) 
         VALUES (?, ?, ?)"
    );
    
    // TODO: Bind parameters
    // (bound via execute)
    
    // TODO: Execute the query
    $ok = $insert->execute([$weekId, $author, $text]);
    
    // TODO: Check if insert was successful
    // If yes, get the last insert ID and return success response with 201 status
    // Include the new comment data in the response
    // If no, return error response with 500 status
    if (!$ok) {
        sendError('Failed to create comment', 500);
    }

    $newId = $db->lastInsertId();

    $newComment = [
        'id'       => (int) $newId,
        'week_id'  => $weekId,
        'author'   => $author,
        'text'     => $text,
        'created_at' => date('Y-m-d H:i:s') // approximate, DB will have actual
    ];

    sendResponse([
        'success' => true,
        'data'    => $newComment
    ], 201);
}


/**
 * Function: Delete a comment
 * Method: DELETE
 * Resource: comments
 * 
 * Query Parameters or JSON Body:
 *   - id: The comment ID to delete
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that id is provided
    // If not, return error response with 400 status
    if (empty($commentId)) {
        sendError('Missing comment id', 400);
    }
    
    // TODO: Check if comment exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $stmt = $db->prepare("SELECT id FROM comments WHERE id = ?");
    $stmt->execute([$commentId]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        sendError('Comment not found', 404);
    }
    
    // TODO: Prepare DELETE query
    // DELETE FROM comments WHERE id = ?
    $delete = $db->prepare("DELETE FROM comments WHERE id = ?");
    
    // TODO: Bind the id parameter
    // (via execute)
    
    // TODO: Execute the query
    $ok = $delete->execute([$commentId]);
    
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if (!$ok || $delete->rowCount() === 0) {
        sendError('Failed to delete comment', 500);
    }

    sendResponse([
        'success' => true,
        'message' => 'Comment deleted successfully'
    ]);
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Determine the resource type from query parameters
    // Get 'resource' parameter (?resource=weeks or ?resource=comments)
    // If not provided, default to 'weeks'
    $resource = isset($_GET['resource']) ? strtolower($_GET['resource']) : 'weeks';
    
    
    // Route based on resource type and HTTP method
    
    // ========== WEEKS ROUTES ==========
    if ($resource === 'weeks') {
        
        if ($method === 'GET') {
            // TODO: Check if week_id is provided in query parameters
            // If yes, call getWeekById()
            // If no, call getAllWeeks() to get all weeks (with optional search/sort)
            $weekId = $_GET['week_id'] ?? null;
            if ($weekId) {
                getWeekById($db, $weekId);
            } else {
                getAllWeeks($db);
            }
            
        } elseif ($method === 'POST') {
            // TODO: Call createWeek() with the decoded request body
            createWeek($db, $bodyData);
            
        } elseif ($method === 'PUT') {
            // TODO: Call updateWeek() with the decoded request body
            updateWeek($db, $bodyData);
            
        } elseif ($method === 'DELETE') {
            // TODO: Get week_id from query parameter or request body
            // Call deleteWeek()
            $weekId = $_GET['week_id'] ?? ($bodyData['week_id'] ?? null);
            deleteWeek($db, $weekId);
            
        } else {
            // TODO: Return error for unsupported methods
            // Set HTTP status to 405 (Method Not Allowed)
            sendError('Method not allowed for weeks resource', 405);
        }
    }
    
    // ========== COMMENTS ROUTES ==========
    elseif ($resource === 'comments') {
        
        if ($method === 'GET') {
            // TODO: Get week_id from query parameters
            // Call getCommentsByWeek()
            $weekId = $_GET['week_id'] ?? null;
            getCommentsByWeek($db, $weekId);
            
        } elseif ($method === 'POST') {
            // TODO: Call createComment() with the decoded request body
            createComment($db, $bodyData);
            
        } elseif ($method === 'DELETE') {
            // TODO: Get comment id from query parameter or request body
            // Call deleteComment()
            $commentId = $_GET['id'] ?? ($bodyData['id'] ?? null);
            deleteComment($db, $commentId);
            
        } else {
            // TODO: Return error for unsupported methods
            // Set HTTP status to 405 (Method Not Allowed)
            sendError('Method not allowed for comments resource', 405);
        }
    }
    
    // ========== INVALID RESOURCE ==========
    else {
        // TODO: Return error for invalid resource
        // Set HTTP status to 400 (Bad Request)
        // Return JSON error message: "Invalid resource. Use 'weeks' or 'comments'"
        sendError("Invalid resource. Use 'weeks' or 'comments'", 400);
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional, for debugging)
    // error_log($e->getMessage());
    error_log($e->getMessage());
    
    // TODO: Return generic error response with 500 status
    // Do NOT expose database error details to the client
    // Return message: "Database error occurred"
    sendError('Database error occurred', 500);
    
} catch (Exception $e) {
    // TODO: Handle general errors
    // Log the error message (optional)
    error_log($e->getMessage());
    // Return error response with 500 status
    sendError('Server error occurred', 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send (will be JSON encoded)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    // Use http_response_code($statusCode)
    http_response_code($statusCode);
    
    // TODO: Echo JSON encoded data
    // Use json_encode($data)
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    // TODO: Exit to prevent further execution
    exit;
}


/**
 * Helper function to send error response
 * 
 * @param string $message - Error message
 * @param int $statusCode - HTTP status code
 */
function sendError($message, $statusCode = 400) {
    // TODO: Create error response array
    // Structure: ['success' => false, 'error' => $message]
    $error = [
        'success' => false,
        'error'   => $message
    ];
    
    // TODO: Call sendResponse() with the error array and status code
    sendResponse($error, $statusCode);
}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    // TODO: Use DateTime::createFromFormat() to validate
    // Format: 'Y-m-d'
    // Check that the created date matches the input string
    // Return true if valid, false otherwise
    if (empty($date)) {
        return false;
    }
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace
    $data = trim($data);
    
    // TODO: Strip HTML tags using strip_tags()
    $data = strip_tags($data);
    
    // TODO: Convert special characters using htmlspecialchars()
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    // TODO: Return sanitized data
    return $data;
}


/**
 * Helper function to validate allowed sort fields
 * 
 * @param string $field - Field name to validate
 * @param array $allowedFields - Array of allowed field names
 * @return bool - True if valid, false otherwise
 */
function isValidSortField($field, $allowedFields) {
    // TODO: Check if $field exists in $allowedFields array
    // Use in_array()
    // Return true if valid, false otherwise
    return in_array($field, $allowedFields, true);
}

?>
