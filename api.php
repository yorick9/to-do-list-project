<?php
// Les erreurs vont dans les logs (docker compose logs app) et non dans la réponse JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

$host = getenv('DB_HOST') ?: 'db';
$db_name = getenv('DB_NAME') ?: 'todo_app';
$username = getenv('DB_USER') ?: 'todo_user';
$password = getenv('DB_PASSWORD') ?: 'todo_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Erreur de connexion : " . $e->getMessage()]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true) ?? [];
$validStatuses = ['OPEN', 'IN_PROGRESS', 'DONE'];

// L'id peut venir de l'URL (?id=3) ou du corps de la requête
$id = $_GET['id'] ?? $data['id'] ?? null;

try {
    switch ($method) {
        case 'GET':
            $stmt = $pdo->query("SELECT * FROM tasks ORDER BY id DESC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'POST':
            $status = $data['status'] ?? 'OPEN';
            if (empty($data['title']) || !in_array($status, $validStatuses, true)) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "Titre vide ou statut invalide"]);
                break;
            }
            $stmt = $pdo->prepare("INSERT INTO tasks (title, status) VALUES (:title, :status)");
            $stmt->execute(['title' => $data['title'], 'status' => $status]);
            echo json_encode(["success" => true, "message" => "Tâche ajoutée avec succès"]);
            break;

        case 'PUT':
            $status = $data['status'] ?? '';
            if (empty($id) || !in_array($status, $validStatuses, true)) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "id ou statut invalide"]);
                break;
            }
            $stmt = $pdo->prepare("UPDATE tasks SET status = :status WHERE id = :id");
            $stmt->execute(['status' => $status, 'id' => $id]);
            echo json_encode(["success" => true, "message" => "Tâche mise à jour"]);
            break;

        case 'DELETE':
            if (empty($id)) {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "id manquant"]);
                break;
            }
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = :id");
            $stmt->execute(['id' => $id]);
            echo json_encode(["success" => true, "message" => "Tâche supprimée"]);
            break;

        default:
            http_response_code(405);
            echo json_encode(["error" => "Méthode non autorisée"]);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}