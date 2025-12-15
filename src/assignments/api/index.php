<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'None',
    'secure' => false
]);

session_start();

$_SESSION = $_SESSION ?? [];

require_once '../../auth/api/auth_check.php';
requireRole('admin'); 

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
$dsn = "mysql:host=127.0.0.1;dbname=course;charset=utf8mb4";
$username = "admin";
$password = "password123";

try {
    $db = new PDO($dsn, $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $e->getMessage()]);
    exit();
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    if (!is_array($data) && !is_object($data)) {
        $data = ["message" => $data];
    }
    echo json_encode($data);
    exit();
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function validateAllowedValue($value, $allowedValues) {
    return in_array(strtolower($value), array_map('strtolower', $allowedValues));
}

// ============================================================================
// ASSIGNMENT CRUD FUNCTIONS
// ============================================================================

function getAllAssignments($db) {
    $sql = "SELECT * FROM assignments WHERE 1=1";
    $params = [];

    if (!empty($_GET['search'])) {
        $sql .= " AND (title LIKE :search OR description LIKE :search2)";
        $params[':search'] = "%" . $_GET['search'] . "%";
        $params[':search2'] = "%" . $_GET['search'] . "%";
    }

    $sort  = $_GET['sort'] ?? 'created_at';
    $order = $_GET['order'] ?? 'asc';
    $allowedSort = ['title', 'due_date', 'created_at'];
    $allowedOrder = ['asc', 'desc'];

    if (!in_array($sort, $allowedSort)) $sort = 'created_at';
    if (!in_array(strtolower($order), $allowedOrder)) $order = 'asc';

    $sql .= " ORDER BY $sort $order";

    $stmt = $db->prepare($sql);

    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $assignments = $stmt->fetchAll();

    foreach ($assignments as &$assignment) {
        $assignment['files'] = !empty($assignment['files']) ? json_decode($assignment['files'], true) : [];
    }

    echo json_encode($assignments);
}

function getAssignmentById($db, $assignmentId) {
    if (empty($assignmentId)) {
        http_response_code(400);
        echo json_encode(["error" => "Assignment ID is required"]);
        return;
    }

    $stmt = $db->prepare("SELECT * FROM assignments WHERE id = :id");
    $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);
    $stmt->execute();

    $assignment = $stmt->fetch();

    if (!$assignment) {
        http_response_code(404);
        echo json_encode(["error" => "Assignment not found"]);
        return;
    }

    $assignment['files'] = !empty($assignment['files']) ? json_decode($assignment['files'], true) : [];
    echo json_encode($assignment);
}

function createAssignment($db, $data) {
    if (empty($data['title']) || empty($data['description']) || empty($data['due_date'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required fields (title, description, due_date)"]);
        return;
    }

    if (!validateDate($data['due_date'])) {
        http_response_code(400);
        echo json_encode(["error" => "Invalid date format. Use YYYY-MM-DD"]);
        return;
    }

    $filesJson = !empty($data['files']) ? json_encode($data['files']) : json_encode([]);

    $stmt = $db->prepare("INSERT INTO assignments (title, description, due_date, files, created_at, updated_at) 
                          VALUES (:title, :description, :due_date, :files, NOW(), NOW())");

    $stmt->bindValue(':title', sanitizeInput($data['title']));
    $stmt->bindValue(':description', sanitizeInput($data['description']));
    $stmt->bindValue(':due_date', $data['due_date']);
    $stmt->bindValue(':files', $filesJson);

    $stmt->execute();

    $id = $db->lastInsertId();
    http_response_code(201);
    echo json_encode([
        "message"     => "Assignment created",
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
    if (empty($data['id'])) {
        http_response_code(400);
        echo json_encode(["error" => "Assignment ID is required"]);
        return;
    }

    $assignmentId = $data['id'];

    $checkStmt = $db->prepare("SELECT * FROM assignments WHERE id = :id");
    $checkStmt->execute([':id' => $assignmentId]);
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(["error" => "Assignment not found"]);
        return;
    }

    $fields = [];
    $params = [':id' => $assignmentId];

    if (!empty($data['title'])) {
        $fields[] = "title = :title";
        $params[':title'] = sanitizeInput($data['title']);
    }
    if (!empty($data['description'])) {
        $fields[] = "description = :description";
        $params[':description'] = sanitizeInput($data['description']);
    }
    if (!empty($data['due_date'])) {
        if (!validateDate($data['due_date'])) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid date format. Use YYYY-MM-DD"]);
            return;
        }
        $fields[] = "due_date = :due_date";
        $params[':due_date'] = $data['due_date'];
    }
    if (isset($data['files'])) {
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
    if (empty($assignmentId)) {
        http_response_code(400);
        echo json_encode(["error" => "Assignment ID is required"]);
        return;
    }

    $checkStmt = $db->prepare("SELECT * FROM assignments WHERE id = :id");
    $checkStmt->execute([':id' => $assignmentId]);
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(["error" => "Assignment not found"]);
        return;
    }

    $deleteComments = $db->prepare("DELETE FROM comments_assignment WHERE assignment_id = :id");
    $deleteComments->execute([':id' => $assignmentId]);

    $stmt = $db->prepare("DELETE FROM assignments WHERE id = :id");
    $stmt->bindValue(':id', $assignmentId, PDO::PARAM_INT);

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

// ============================================================================
// COMMENT CRUD FUNCTIONS
// ============================================================================

function getCommentsByAssignment($db, $assignmentId) {
    if (empty($assignmentId)) {
        http_response_code(400);
        echo json_encode(["error" => "Assignment ID is required"]);
        return;
    }

    $stmt = $db->prepare("SELECT * FROM comments_assignment WHERE assignment_id = :assignment_id ORDER BY created_at ASC");
    $stmt->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);
    $stmt->execute();

    $comments = $stmt->fetchAll();
    echo json_encode($comments);
}

function getCommentById($db, $commentId) {
    if (empty($commentId)) {
        http_response_code(400);
        echo json_encode(["error" => "Comment ID is required"]);
        return;
    }

    $stmt = $db->prepare("SELECT * FROM comments_assignment WHERE id = :id");
    $stmt->bindValue(':id', $commentId, PDO::PARAM_INT);
    $stmt->execute();

    $comment = $stmt->fetch();

    if (!$comment) {
        http_response_code(404);
        echo json_encode(["error" => "Comment not found"]);
        return;
    }

    echo json_encode($comment);
}

function createComment($db, $data) {
    if (empty($data['assignment_id']) || empty($data['author']) || empty($data['text'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required fields (assignment_id, author, text)"]);
        return;
    }

    $assignmentId = $data['assignment_id'];
    $author = sanitizeInput($data['author']);
    $text = trim($data['text']);

    if (empty($text)) {
        http_response_code(400);
        echo json_encode(["error" => "Comment text cannot be empty"]);
        return;
    }

    $checkStmt = $db->prepare("SELECT * FROM assignments WHERE id = :id");
    $checkStmt->execute([':id' => $assignmentId]);
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(["error" => "Assignment not found"]);
        return;
    }

    $stmt = $db->prepare("INSERT INTO comments_assignment (assignment_id, author, text, created_at) 
                          VALUES (:assignment_id, :author, :text, NOW())");

    $stmt->bindValue(':assignment_id', $assignmentId, PDO::PARAM_INT);
    $stmt->bindValue(':author', $author);
    $stmt->bindValue(':text', sanitizeInput($text));

    if ($stmt->execute()) {
        $id = $db->lastInsertId();
        http_response_code(201);
        echo json_encode([
            "message"       => "Comment added",
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
    if (empty($commentId)) {
        http_response_code(400);
        echo json_encode(["error" => "Comment ID is required"]);
        return;
    }

    $checkStmt = $db->prepare("SELECT * FROM comments_assignment WHERE id = :id");
    $checkStmt->execute([':id' => $commentId]);
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(["error" => "Comment not found"]);
        return;
    }

    $stmt = $db->prepare("DELETE FROM comments_assignment WHERE id = :id");
    $stmt->bindValue(':id', $commentId, PDO::PARAM_INT);

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

// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    $method   = $_SERVER['REQUEST_METHOD'];
    $resource = $_GET['resource'] ?? null;
    $id       = $_GET['id'] ?? null;
    $data     = json_decode(file_get_contents("php://input"), true);

    if ($method === 'GET') {
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
            echo json_encode(["error" => "Invalid resource. Use 'assignments' or 'comments'"]);
        }

    } elseif ($method === 'POST') {
        if ($resource === 'assignments') {
            createAssignment($db, $data);
        } elseif ($resource === 'comments') {
            createComment($db, $data);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Invalid resource. Use 'assignments' or 'comments'"]);
        }

    } elseif ($method === 'PUT') {
        if ($resource === 'assignments') {
            updateAssignment($db, $data);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "PUT is only supported for assignments"]);
        }

    } elseif ($method === 'DELETE') {
        if ($resource === 'assignments') {
            $assignmentId = $id ?? ($data['id'] ?? null);
            deleteAssignment($db, $assignmentId);
        } elseif ($resource === 'comments') {
            $commentId = $id ?? ($data['id'] ?? null);
            deleteComment($db, $commentId);
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Invalid resource. Use 'assignments' or 'comments'"]);
        }

    } else {
        http_response_code(405);
        echo json_encode(["error" => "Method not supported. Use GET, POST, PUT, or DELETE"]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Server error: " . $e->getMessage()]);
}
?>
