<?php
/**
 * Course Resources API
 * 
 * RESTful API to manage course resources and comments using PDO + MySQL.
 */

// ============================================================================
// HEADERS AND INITIALIZATION
// ============================================================================

// JSON + CORS headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include DB config
require_once __DIR__ . '/../config/Database.php';

// Get PDO connection
$database = new Database();
$db       = $database->getConnection();

// HTTP method
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Request body (for POST / PUT / DELETE with JSON)
$rawInput  = file_get_contents('php://input');
$bodyData  = [];
if (!empty($rawInput)) {
    $decoded = json_decode($rawInput, true);
    if (is_array($decoded)) {
        $bodyData = $decoded;
    }
}

// Query params
$action      = $_GET['action']      ?? null;
$idParam     = $_GET['id']          ?? null;
$resourceIdQ = $_GET['resource_id'] ?? null;
$commentIdQ  = $_GET['comment_id']  ?? null;


// ============================================================================
// RESOURCE FUNCTIONS
// ============================================================================

/**
 * Get all resources (GET)
 */
function getAllResources($db)
{
    // Base query
    $sql    = "SELECT id, title, description, link, created_at FROM resources";
    $params = [];

    // Search
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    if ($search !== '') {
        $sql          .= " WHERE title LIKE :search OR description LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }

    // Sort
    $sort  = isset($_GET['sort']) ? strtolower($_GET['sort']) : 'created_at';
    $validSorts = ['title', 'created_at'];
    if (!in_array($sort, $validSorts, true)) {
        $sort = 'created_at';
    }

    // Order
    $order = isset($_GET['order']) ? strtolower($_GET['order']) : 'desc';
    $validOrders = ['asc', 'desc'];
    if (!in_array($order, $validOrders, true)) {
        $order = 'desc';
    }

    $sql .= " ORDER BY {$sort} {$order}";

    $stmt = $db->prepare($sql);

    if (isset($params[':search'])) {
        $stmt->bindValue(':search', $params[':search'], PDO::PARAM_STR);
    }

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse([
        'success' => true,
        'data'    => $rows
    ]);
}

/**
 * Get single resource by ID (GET)
 */
function getResourceById($db, $resourceId)
{
    if (empty($resourceId) || !is_numeric($resourceId)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid or missing resource id.'
        ], 400);
    }

    $sql  = "SELECT id, title, description, link, created_at FROM resources WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(1, (int)$resourceId, PDO::PARAM_INT);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        sendResponse([
            'success' => true,
            'data'    => $row
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Resource not found.'
        ], 404);
    }
}

/**
 * Create a new resource (POST)
 */
function createResource($db, $data)
{
    // Required fields
    $check = validateRequiredFields($data, ['title', 'link']);
    if (!$check['valid']) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields.',
            'missing' => $check['missing']
        ], 400);
    }

    $title       = sanitizeInput($data['title']);
    $description = isset($data['description']) ? sanitizeInput($data['description']) : '';
    $link        = trim($data['link']);

    if (!validateUrl($link)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid URL format for link.'
        ], 400);
    }

    $sql  = "INSERT INTO resources (title, description, link) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);

    $stmt->bindValue(1, $title, PDO::PARAM_STR);
    $stmt->bindValue(2, $description, PDO::PARAM_STR);
    $stmt->bindValue(3, $link, PDO::PARAM_STR);

    if ($stmt->execute()) {
        $newId = $db->lastInsertId();
        sendResponse([
            'success' => true,
            'message' => 'Resource created successfully.',
            'id'      => (int)$newId
        ], 201);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create resource.'
        ], 500);
    }
}

/**
 * Update an existing resource (PUT)
 */
function updateResource($db, $data)
{
    if (!isset($data['id']) || !is_numeric($data['id'])) {
        sendResponse([
            'success' => false,
            'message' => 'Resource id is required for update.'
        ], 400);
    }

    $resourceId = (int)$data['id'];

    // Check exists
    $checkStmt = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $checkStmt->bindValue(1, $resourceId, PDO::PARAM_INT);
    $checkStmt->execute();

    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse([
            'success' => false,
            'message' => 'Resource not found.'
        ], 404);
    }

    $fields = [];
    $values = [];

    if (isset($data['title'])) {
        $fields[] = "title = ?";
        $values[] = sanitizeInput($data['title']);
    }

    if (isset($data['description'])) {
        $fields[] = "description = ?";
        $values[] = sanitizeInput($data['description']);
    }

    if (isset($data['link'])) {
        $link = trim($data['link']);
        if (!validateUrl($link)) {
            sendResponse([
                'success' => false,
                'message' => 'Invalid URL format for link.'
            ], 400);
        }
        $fields[] = "link = ?";
        $values[] = $link;
    }

    if (empty($fields)) {
        sendResponse([
            'success' => false,
            'message' => 'No fields provided to update.'
        ], 400);
    }

    $sql = "UPDATE resources SET " . implode(', ', $fields) . " WHERE id = ?";

    $stmt = $db->prepare($sql);

    // bind dynamic fields
    $i = 1;
    foreach ($values as $val) {
        $stmt->bindValue($i++, $val, PDO::PARAM_STR);
    }
    $stmt->bindValue($i, $resourceId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        sendResponse([
            'success' => true,
            'message' => 'Resource updated successfully.'
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to update resource.'
        ], 500);
    }
}

/**
 * Delete a resource and its comments (DELETE)
 */
function deleteResource($db, $resourceId)
{
    if (empty($resourceId) || !is_numeric($resourceId)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid or missing resource id.'
        ], 400);
    }

    $resourceId = (int)$resourceId;

    // Check exists
    $checkStmt = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $checkStmt->bindValue(1, $resourceId, PDO::PARAM_INT);
    $checkStmt->execute();
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse([
            'success' => false,
            'message' => 'Resource not found.'
        ], 404);
    }

    try {
        $db->beginTransaction();

        // Delete comments
        $delComments = $db->prepare("DELETE FROM comments WHERE resource_id = ?");
        $delComments->bindValue(1, $resourceId, PDO::PARAM_INT);
        $delComments->execute();

        // Delete resource
        $delResource = $db->prepare("DELETE FROM resources WHERE id = ?");
        $delResource->bindValue(1, $resourceId, PDO::PARAM_INT);
        $delResource->execute();

        $db->commit();

        sendResponse([
            'success' => true,
            'message' => 'Resource and related comments deleted successfully.'
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete resource.'
        ], 500);
    }
}


// ============================================================================
// COMMENT FUNCTIONS
// ============================================================================

/**
 * Get comments for a resource (GET, action=comments)
 */
function getCommentsByResourceId($db, $resourceId)
{
    if (empty($resourceId) || !is_numeric($resourceId)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid or missing resource_id.'
        ], 400);
    }

    $resourceId = (int)$resourceId;

    $sql  = "SELECT id, resource_id, author, text, created_at 
             FROM comments 
             WHERE resource_id = ? 
             ORDER BY created_at ASC";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(1, $resourceId, PDO::PARAM_INT);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse([
        'success' => true,
        'data'    => $rows
    ]);
}

/**
 * Create a new comment (POST, action=comment)
 */
function createComment($db, $data)
{
    $check = validateRequiredFields($data, ['resource_id', 'author', 'text']);
    if (!$check['valid']) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields.',
            'missing' => $check['missing']
        ], 400);
    }

    $resourceId = $data['resource_id'];

    if (!is_numeric($resourceId)) {
        sendResponse([
            'success' => false,
            'message' => 'resource_id must be numeric.'
        ], 400);
    }

    $resourceId = (int)$resourceId;

    // Check resource exists
    $checkStmt = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $checkStmt->bindValue(1, $resourceId, PDO::PARAM_INT);
    $checkStmt->execute();
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse([
            'success' => false,
            'message' => 'Resource not found.'
        ], 404);
    }

    $author = sanitizeInput($data['author']);
    $text   = sanitizeInput($data['text']);

    $sql  = "INSERT INTO comments (resource_id, author, text) VALUES (?, ?, ?)";
    $stmt = $db->prepare($sql);

    $stmt->bindValue(1, $resourceId, PDO::PARAM_INT);
    $stmt->bindValue(2, $author, PDO::PARAM_STR);
    $stmt->bindValue(3, $text, PDO::PARAM_STR);

    if ($stmt->execute()) {
        $newId = $db->lastInsertId();
        sendResponse([
            'success' => true,
            'message' => 'Comment created successfully.',
            'id'      => (int)$newId
        ], 201);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to create comment.'
        ], 500);
    }
}

/**
 * Delete a comment (DELETE, action=delete_comment)
 */
function deleteComment($db, $commentId)
{
    if (empty($commentId) || !is_numeric($commentId)) {
        sendResponse([
            'success' => false,
            'message' => 'Invalid or missing comment_id.'
        ], 400);
    }

    $commentId = (int)$commentId;

    // Check exists
    $checkStmt = $db->prepare("SELECT id FROM comments WHERE id = ?");
    $checkStmt->bindValue(1, $commentId, PDO::PARAM_INT);
    $checkStmt->execute();
    if (!$checkStmt->fetch(PDO::FETCH_ASSOC)) {
        sendResponse([
            'success' => false,
            'message' => 'Comment not found.'
        ], 404);
    }

    $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->bindValue(1, $commentId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        sendResponse([
            'success' => true,
            'message' => 'Comment deleted successfully.'
        ]);
    } else {
        sendResponse([
            'success' => false,
            'message' => 'Failed to delete comment.'
        ], 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {

    if ($method === 'GET') {

        if ($action === 'comments') {
            // /api/resources.php?action=comments&resource_id=#
            $resourceId = $resourceIdQ ?? ($bodyData['resource_id'] ?? null);
            getCommentsByResourceId($db, $resourceId);

        } elseif ($idParam !== null) {
            // /api/resources.php?id=#
            getResourceById($db, $idParam);

        } else {
            // /api/resources.php
            getAllResources($db);
        }

    } elseif ($method === 'POST') {

        if ($action === 'comment') {
            // create comment
            createComment($db, $bodyData);
        } else {
            // create resource
            createResource($db, $bodyData);
        }

    } elseif ($method === 'PUT') {

        // update resource
        updateResource($db, $bodyData);

    } elseif ($method === 'DELETE') {

        if ($action === 'delete_comment') {
            $commentId = $commentIdQ ?? ($bodyData['comment_id'] ?? null);
            deleteComment($db, $commentId);
        } else {
            $resourceId = $idParam ?? ($bodyData['id'] ?? null);
            deleteResource($db, $resourceId);
        }

    } else {
        sendResponse([
            'success' => false,
            'message' => 'Method not allowed.'
        ], 405);
    }

} catch (PDOException $e) {
    error_log('DB Error: ' . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'Database error.'
    ], 500);

} catch (Exception $e) {
    error_log('General Error: ' . $e->getMessage());
    sendResponse([
        'success' => false,
        'message' => 'Server error.'
    ], 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Send JSON response
 */
function sendResponse($data, $statusCode = 200)
{
    http_response_code($statusCode);

    if (!is_array($data)) {
        $data = ['data' => $data];
    }

    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Validate URL format
 */
function validateUrl($url)
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Sanitize string input
 */
function sanitizeInput($data)
{
    $data = trim($data);
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate required fields
 */
function validateRequiredFields($data, $requiredFields)
{
    $missing = [];

    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
            $missing[] = $field;
        }
    }

    return [
        'valid'   => count($missing) === 0,
        'missing' => $missing
    ];
}

?>
