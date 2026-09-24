<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// En-têtes pour autoriser les requêtes (CORS) et renvoyer du JSON
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

// Paramètres de connexion à la base de données (à adapter si besoin)
$host = 'localhost';
$db_name = 'todo_db'; // Remplace par le nom de ta base de données
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["error" => "Erreur de connexion : " . $e->getMessage()]);
    exit();
}

function normalizeTaskStatus($completed): string {
    return ((int)$completed === 1) ? 'DONE' : 'OPEN';
}

function completedFromStatus($status): int {
    return strtoupper((string)$status) === 'DONE' ? 1 : 0;
}

function getRequestId(array $data = []): ?int {
    $id = $_GET['id'] ?? $data['id'] ?? null;

    if ($id === null || $id === '') {
        return null;
    }

    return (int)$id;
}

// Récupération de la méthode HTTP (GET, POST, etc.)
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'GET':
        // Récupérer toutes les tâches
        $stmt = $pdo->query("SELECT * FROM tasks ORDER BY id DESC");
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $tasks = array_map(function ($task) {
            $task['status'] = normalizeTaskStatus($task['completed'] ?? 0);
            return $task;
        }, $tasks);

        echo json_encode($tasks);
        break;

    case 'POST':
        // Ajouter une nouvelle tâche
        $title = trim((string)($data['title'] ?? ''));
        $status = $data['status'] ?? 'OPEN';

        if ($title !== '') {
            $stmt = $pdo->prepare("INSERT INTO tasks (title, completed) VALUES (:title, :completed)");
            $stmt->execute([
                'title' => $title,
                'completed' => completedFromStatus($status)
            ]);
            echo json_encode(["success" => true, "message" => "Tâche ajoutée avec succès"]);
        } else {
            echo json_encode(["success" => false, "message" => "Le titre est vide"]);
        }
        break;

    case 'PUT':
        // Modifier une tâche (marquer comme complétée ou non)
        $id = getRequestId($data ?? []);

        if ($id !== null) {
            $status = $data['status'] ?? 'OPEN';
            $completed = completedFromStatus($status);

            $stmt = $pdo->prepare("UPDATE tasks SET completed = :completed WHERE id = :id");
            $stmt->execute([
                'completed' => $completed,
                'id' => $id
            ]);
            echo json_encode([
                "success" => true,
                "message" => "Tâche mise à jour",
                "status" => normalizeTaskStatus($completed)
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "ID manquant"]);
        }
        break;

    case 'DELETE':
        // Supprimer une tâche
        $id = getRequestId($data ?? []);
        if ($id !== null) {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id");
            $stmt->execute(['id' => $id]);
            echo json_encode(["success" => true, "message" => "Tâche supprimée"]);
        }
        break;

    default:
        echo json_encode(["error" => "Méthode non autorisée"]);
        break;
}
?>