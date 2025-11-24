<?php
/**
 * Assignment Management API
 * RESTful API for assignments and comments
 * Using PDO + MySQL
 */

// ============================================================================
// HEADERS AND CORS CONFIGURATION
// ============================================================================
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================================================
// DATABASE CONNECTION
// ============================================================================
$dsn = "mysql:host=localhost;dbname=your_database;charset=utf8mb4";
$username = "your_username";
$password = "your_password";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
    exit();
}

// ============================================================================
// REQUEST PARSING
// ============================================================================
$method   = $_SERVER['REQUEST_METHOD'];
$input    = json_decode(file_get_contents("php://input"), true);
$resource = $_GET['resource'] ?? null;
$id       = $_GET['id'] ?? null;

// ============================================================================
// ASSIGNMENT CRUD
// ============================================================================
if ($resource === 'assignments') {
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
            } else {
                $search = $_GET['search'] ?? '';
                $sort   = $_GET['sort'] ?? 'created_at';
                $order  = $_GET['order'] ?? 'asc';

                $sql = "SELECT * FROM assignments 
                        WHERE title LIKE :search OR description LIKE :search 
                        ORDER BY $sort $order";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['search' => "%$search%"]);
                echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            }
            break;

        case 'POST':
            $stmt = $pdo->prepare("INSERT INTO assignments (title, description, due_date, files, created_at, updated_at) 
                                   VALUES (:title, :description, :due_date, :files, NOW(), NOW())");
            $stmt->execute([
                'title'       => $input['title'],
                'description' => $input['description'],
                'due_date'    => $input['due_date'],
                'files'       => $input['files'] ?? ''
            ]);
            echo json_encode(["message" => "Assignment created"]);
            break;

        case 'PUT':
            if (!$id) { http_response_code(400); echo json_encode(["error" => "Missing assignment id"]); exit(); }
            $stmt = $pdo->prepare("UPDATE assignments SET title=:title, description=:description, due_date=:due_date, files=:files, updated_at=NOW() WHERE id=:id");
            $stmt->execute([
                'title'       => $input['title'],
                'description' => $input['description'],
                'due_date'    => $input['due_date'],
                'files'       => $input['files'] ?? '',
                'id'          => $id
            ]);
            echo json_encode(["message" => "Assignment updated"]);
            break;

        case 'DELETE':
            if (!$id) { http_response_code(400); echo json_encode(["error" => "Missing assignment id"]); exit(); }
            $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(["message" => "Assignment deleted"]);
            break;
    }
}

// ============================================================================
// COMMENT CRUD
// ============================================================================
if ($resource === 'comments') {
    switch ($method) {
        case 'GET':
            if ($id) {
                $stmt = $pdo->prepare("SELECT * FROM comments WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
            } else {
                $assignment_id = $_GET['assignment_id'] ?? null;
                if ($assignment_id) {
                    $stmt = $pdo->prepare("SELECT * FROM comments WHERE assignment_id = ?");
                    $stmt->execute([$assignment_id]);
                    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                } else {
                    $stmt = $pdo->query("SELECT * FROM comments");
                    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
                }
            }
            break;

        case 'POST':
            $stmt = $pdo->prepare("INSERT INTO comments (assignment_id, author, text, created_at) 
                                   VALUES (:assignment_id, :author, :text, NOW())");
            $stmt->execute([
                'assignment_id' => $input['assignment_id'],
                'author'        => $input['author'],
                'text'          => $input['text']
            ]);
            echo json_encode(["message" => "Comment added"]);
            break;

        case 'DELETE':
            if (!$id) { http_response_code(400); echo json_encode(["error" => "Missing comment id"]); exit(); }
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(["message" => "Comment deleted"]);
            break;
    }
}
function getAllAssignments($db) {
    // Start building the SQL query
    $sql = "SELECT * FROM assignments WHERE 1=1";

    // Check if 'search' query parameter exists
    $params = [];
    if (!empty($_GET['search'])) {
        $sql .= " AND (title LIKE :search OR description LIKE :search)";
        $params[':search'] = "%" . $_GET['search'] . "%";
    }

    // Check if 'sort' and 'order' query parameters exist
    $sort  = $_GET['sort'] ?? 'created_at';
    $order = $_GET['order'] ?? 'asc';
    $allowedSort = ['title', 'due_date', 'created_at'];
    $allowedOrder = ['asc', 'desc'];

    if (!in_array($sort, $allowedSort)) $sort = 'created_at';
    if (!in_array(strtolower($order), $allowedOrder)) $order = 'asc';

    $sql .= " ORDER BY $sort $order";

    // Prepare the SQL statement
    $stmt = $db->prepare($sql);

    // Bind parameters if search is used
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    // Execute the prepared statement
    $stmt->execute();

    // Fetch all results as associative array
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Decode 'files' field from JSON to array
    foreach ($assignments as &$assignment) {
        $assignment['files'] = !empty($assignment['files']) ? json_decode($assignment['files'], true) : [];
    }

    // Return JSON response
    echo json_encode($assignments);
}
function getAssignmentById($db, $assignmentId) {
    // Validate that $assignmentId is provided
    if (empty($assignmentId)) {
        http_response_code(400);
        echo json_encode(["error" => "Assignment ID is required"]);
        return;
    }

    // Prepare SQL query
    $stmt = $db->prepare("SELECT * FROM assignments WHERE id = :id");

    // Bind the :id parameter
    $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);

    // Execute the statement
    $stmt->execute();

    // Fetch result
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if assignment was found
    if (!$assignment) {
        http_response_code(404);
        echo json_encode(["error" => "Assignment not found"]);
        return;
    }

    // Decode 'files' field from JSON
    $assignment['files'] = !empty($assignment['files']) ? json_decode($assignment['files'], true) : [];

    // Return success response
    echo json_encode($assignment);
}
function createAssignment($db, $data) {
    // Validate required fields
    if (empty($data['title']) || empty($data['description']) || empty($data['due_date'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required fields"]);
        return;
    }

    // Encode files array to JSON if provided
    $filesJson = !empty($data['files']) ? json_encode($data['files']) : json_encode([]);

    // Prepare SQL insert
    $stmt = $db->prepare("INSERT INTO assignments (title, description, due_date, files, created_at, updated_at) 
                          VALUES (:title, :description, :due_date, :files, NOW(), NOW())");

    // Bind parameters
    $stmt->bindValue(':title', $data['title']);
    $stmt->bindValue(':description', $data['description']);
    $stmt->bindValue(':due_date', $data['due_date']);
    $stmt->bindValue(':files', $filesJson);

    // Execute
    $stmt->execute();

    // Return created assignment with ID
    $id = $db->lastInsertId();
    echo json_encode([
        "id"          => $id,
        "title"       => $data['title'],
        "description" => $data['description'],
        "due_date"    => $data['due_date'],
        "files"       => json_decode($filesJson, true),
        "created_at"  => date("Y-m-d H:i:s"),
        "updated_at"  => date("Y-m-d H:i:s")
    ]);
}


   



function updateAssignment($db, $data) {
    // Validate that 'id' is provided
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(["error" => "Assignment ID is required"]);
        return;
    }

    $assignmentId = $data['id'];

    // Check if assignment exists
    $checkStmt = $db->prepare("SELECT * FROM assignments WHERE id = :id");
    $checkStmt->execute([':id' => $assignmentId]);
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(["error" => "Assignment not found"]);
        return;
    }

    // Build UPDATE query dynamically
    $fields = [];
    $params = [':id' => $assignmentId];

    if (!empty($data['title'])) {
        $fields[] = "title = :title";
        $params[':title'] = htmlspecialchars(strip_tags($data['title']));
    }
    if (!empty($data['description'])) {
        $fields[] = "description = :description";
        $params[':description'] = htmlspecialchars(strip_tags($data['description']));
    }
    if (!empty($data['due_date'])) {
        $dateRegex = "/^\d{4}-\d{2}-\d{2}$/";
        if (!preg_match($dateRegex, $data['due_date'])) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid date format. Use YYYY-MM-DD"]);
            return;
        }
        $fields[] = "due_date = :due_date";
        $params[':due_date'] = $data['due_date'];
    }
    if (!empty($data['files'])) {
        $fields[] = "files = :files";
        $params[':files'] = json_encode($data['files']);
    }

    if (empty($fields)) {
        http_response_code(400);
        echo json_encode(["error" => "No fields to update"]);
        return;
    }

    $sql = "UPDATE assignments SET " . implode(", ", $fields) . ", updated_at = NOW() WHERE id = :id";
    $stmt = $db->prepare($sql);

    if ($stmt->execute($params)) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(["message" => "Assignment updated"]);
        } else {
            echo json_encode(["message" => "No changes made"]);
        }
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to update assignment"]);
    }
}


function deleteAssignment($db, $assignmentId) {
    // Validate that $assignmentId is provided
    if (empty($assignmentId)) {
        http_response_code(400);
        echo json_encode(["error" => "Assignment ID is required"]);
        return;
    }

    // Check if assignment exists
    $checkStmt = $db->prepare("SELECT * FROM assignments WHERE id = :id");
    $checkStmt->execute([':id' => $assignmentId]);
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(["error" => "Assignment not found"]);
        return;
    }

    // Delete associated comments first (due to foreign key constraint)
    $deleteComments = $db->prepare("DELETE FROM comments WHERE assignment_id = :id");
    $deleteComments->execute([':id' => $assignmentId]);

    // Prepare DELETE query for assignment
    $stmt = $db->prepare("DELETE FROM assignments WHERE id = :id");

    // Bind the :id parameter
    $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);

    // Execute the statement
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(["message" => "Assignment and associated comments deleted"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to delete assignment"]);
        }
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to delete assignment"]);
    }
}
function getCommentsByAssignment($db, $assignmentId) {
    // Validate that $assignmentId is provided
    if (empty($assignmentId)) {
        http_response_code(400);
        echo json_encode(["error" => "Assignment ID is required"]);
        return;
    }

    // Prepare SQL query
    $stmt = $db->prepare("SELECT * FROM comments WHERE assignment_id = :assignment_id");

    // Bind parameter
    $stmt->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);

    // Execute
    $stmt->execute();

    // Fetch results
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return success response
    echo json_encode($comments);
}
function createComment($db, $data) {
    // Validate required fields
    if (empty($data['assignment_id']) || empty($data['author']) || empty($data['text'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required fields"]);
        return;
    }

    // Sanitize input
    $assignmentId = htmlspecialchars(strip_tags($data['assignment_id']));
    $author       = htmlspecialchars(strip_tags($data['author']));
    $text         = trim($data['text']);

    // Validate text not empty
    if (empty($text)) {
        http_response_code(400);
        echo json_encode(["error" => "Comment text cannot be empty"]);
        return;
    }

    // Verify assignment exists
    $checkStmt = $db->prepare("SELECT * FROM assignments WHERE id = :id");
    $checkStmt->execute([':id' => $assignmentId]);
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(["error" => "Assignment not found"]);
        return;
    }

    // Prepare INSERT query
    $stmt = $db->prepare("INSERT INTO comments (assignment_id, author, text, created_at) 
                          VALUES (:assignment_id, :author, :text, NOW())");

    // Bind parameters
    $stmt->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);
    $stmt->bindValue(':author', $author);
    $stmt->bindValue(':text', $text);

    // Execute
    if ($stmt->execute()) {
        $id = $db->lastInsertId();
        echo json_encode([
            "id"            => $id,
            "assignment_id" => $assignmentId,
            "author"        => $author,
            "text"          => $text,
            "created_at"    => date("Y-m-d H:i:s")
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to create comment"]);
    }
}
function deleteComment($db, $commentId) {
    // Validate that $commentId is provided
    if (empty($commentId)) {
        http_response_code(400);
        echo json_encode(["error" => "Comment ID is required"]);
        return;
    }

    // Check if comment exists
    $checkStmt = $db->prepare("SELECT * FROM comments WHERE id = :id");
    $checkStmt->execute([':id' => $commentId]);
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(["error" => "Comment not found"]);
        return;
    }

    // Prepare DELETE query
    $stmt = $db->prepare("DELETE FROM comments WHERE id = :id");

    // Bind parameter
    $stmt->bindValue(':id', $commentId, PDO::PARAM_INT);

    // Execute statement
    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            echo json_encode(["message" => "Comment deleted"]);
        } else {
            http_response_code(500);
            echo json_encode(["error" => "Failed to delete comment"]);
        }
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to delete comment"]);
    }
}
try {
    // Get the HTTP method and resource
    $method   = $_SERVER['REQUEST_METHOD'];
    $resource = $_GET['resource'] ?? null;
    $id       = $_GET['id'] ?? null;
    $data     = json_decode(file_get_contents("php://input"), true);

    if ($method === 'GET') {
        // Handle GET requests
        if ($resource === 'assignments') {
            if (!empty($id)) {
                getAssignmentById($db, $id);
            } else {
                getAllAssignments($db);
            }
        } elseif ($resource === 'comments') {
            $assignmentId = $_GET['assignment_id'] ?? null;
            if (!empty($assignmentId)) {
                getCommentsByAssignment($db, $assignmentId);
            } elseif (!empty($id)) {
                getCommentById($db, $id);
            } else {
                http_response_code(400);
                echo json_encode(["error" => "Missing assignment_id or comment id"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Invalid resource"]);
        }

    } elseif ($method === 'POST') {
        // Handle POST requests
        if ($resource === 'assignments') {
            createAssignment($db, $data);
        } elseif ($resource === 'comments') {
            createComment($db, $data);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Invalid resource"]);
        }

    } elseif ($method === 'PUT') {
        // Handle PUT requests
        if ($resource === 'assignments') {
            updateAssignment($db, $data);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "PUT not supported for this resource"]);
        }

    } elseif ($method === 'DELETE') {
        // Handle DELETE requests
        if ($resource === 'assignments') {
            $assignmentId = $id ?? ($data['id'] ?? null);
            deleteAssignment($db, $assignmentId);
        } elseif ($resource === 'comments') {
            $commentId = $id ?? ($data['id'] ?? null);
            deleteComment($db, $commentId);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Invalid resource"]);
        }

    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not supported"]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Server error: " . $e->getMessage()]);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper function to send JSON response and exit
 * 
 * @param mixed $data - Data to send as JSON (array or string)
 * @param int $statusCode - HTTP status code (default: 200)
 */
function sendResponse($data, $statusCode = 200) {
    // Set HTTP response code
    http_response_code($statusCode);

    // Ensure data is an array or object
    if (!is_array($data) && !is_object($data)) {
        $data = ["message" => $data];
    }

    // Echo JSON encoded data
    echo json_encode($data);

    // Exit to prevent further execution
    exit();
}


/**
 * Helper function to sanitize string input
 * 
 * @param string $data - Input data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // Trim whitespace
    $data = trim($data);

    // Remove HTML and PHP tags
    $data = strip_tags($data);

    // Convert special characters to HTML entities
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');

    // Return sanitized data
    return $data;
}


/**
 * Helper function to validate date format (YYYY-MM-DD)
 * 
 * @param string $date - Date string to validate
 * @return bool - True if valid, false otherwise
 */
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}


/**
 * Helper function to validate allowed values (for sort fields, order, etc.)
 * 
 * @param string $value - Value to validate
 * @param array $allowedValues - Array of allowed values
 * @return bool - True if valid, false otherwise
 */
function validateAllowedValue($value, $allowedValues) {
    return in_array(strtolower($value), array_map('strtolower', $allowedValues));
}
?>
