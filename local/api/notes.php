<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$connection = \Bitrix\Main\Application::getConnection();

// Функция для форматирования данных заметки
function formatNote($note) {
    if (!$note) return $note;
    
    // Форматируем даты в ISO строки для JavaScript
    if (isset($note['created_at'])) {
        $note['created_at'] = date('c', strtotime($note['created_at']));
    }
    if (isset($note['updated_at'])) {
        $note['updated_at'] = date('c', strtotime($note['updated_at']));
    }
    
    return $note;
}

// Функция ответа
function jsonResponse($status, $data = null, $error = null) {
    http_response_code($status);
    echo json_encode([
        'status' => $status,
        'data' => $data,
        'error' => $error
    ]);
    exit();
}

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $id = (int)$_GET['id'];
                $stmt = $connection->query("SELECT * FROM notes WHERE id = " . $id);
                $note = $stmt->fetch();
                if ($note) {
                    jsonResponse(200, formatNote($note));
                } else {
                    jsonResponse(404, null, 'Note not found');
                }
            } else {
                $stmt = $connection->query("SELECT * FROM notes ORDER BY updated_at DESC");
                $notes = $stmt->fetchAll();
                
                // Форматируем все заметки
                $formattedNotes = array_map('formatNote', $notes);
                jsonResponse(200, $formattedNotes);
            }
            break;

        case 'POST':
            if (empty($input['title'])) {
                jsonResponse(400, null, 'Title required');
            }
            
            $title = $connection->getSqlHelper()->forSql($input['title']);
            $content = $connection->getSqlHelper()->forSql($input['content'] ?? '');
            
            $connection->queryExecute("INSERT INTO notes (title, content) VALUES ('$title', '$content')");
            $id = $connection->getInsertedId();
            $note = $connection->query("SELECT * FROM notes WHERE id = " . $id)->fetch();
            
            jsonResponse(201, formatNote($note));
            break;

        case 'PUT':
            if (!isset($_GET['id'])) {
                jsonResponse(400, null, 'ID required');
            }
            
            $id = (int)$_GET['id'];
            $updates = [];
            
            if (isset($input['title'])) {
                $title = $connection->getSqlHelper()->forSql($input['title']);
                $updates[] = "title = '$title'";
            }
            if (isset($input['content'])) {
                $content = $connection->getSqlHelper()->forSql($input['content']);
                $updates[] = "content = '$content'";
            }
            
            if (empty($updates)) {
                jsonResponse(400, null, 'No data to update');
            }
            
            $connection->queryExecute("UPDATE notes SET " . implode(', ', $updates) . " WHERE id = " . $id);
            $note = $connection->query("SELECT * FROM notes WHERE id = " . $id)->fetch();
            
            jsonResponse(200, formatNote($note));
            break;

        case 'DELETE':
            if (!isset($_GET['id'])) {
                jsonResponse(400, null, 'ID required');
            }
            
            $id = (int)$_GET['id'];
            $connection->queryExecute("DELETE FROM notes WHERE id = " . $id);
            
            jsonResponse(200, ['message' => 'Deleted']);
            break;

        default:
            jsonResponse(405, null, 'Method not allowed');
    }
} catch (Exception $e) {
    jsonResponse(500, null, 'Server error: ' . $e->getMessage());
}