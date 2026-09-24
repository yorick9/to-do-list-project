<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

$dataFile = 'tasks.json';

// Fonction utilitaire pour lire les tâches du fichier JSON
function readTasks($file) {
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    $tasks = json_decode($content, true);
    return is_array($tasks) ? $tasks : [];
}

// Fonction utilitaire pour sauvegarder les tâches dans le fichier JSON
function writeTasks($file, $tasks) {
    file_put_contents($file, json_encode(values: $tasks, flags: JSON_PRETTY_PRINT));
}

function normalizeStatusValue(?string $status): string {
    $normalized = strtoupper(trim((string)($status ?? 'OPEN')));
    if (in_array($normalized, ['OPEN', 'IN_PROGRESS', 'DONE'], true)) {
        return $normalized;
    }
    return 'OPEN';
}

$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);
$tasks = readTasks($dataFile);

switch ($method) {
    case 'GET':
        // Renvoie toutes les tâches
        echo json_encode($tasks);
        break;

    case 'POST':
        $title = trim((string)($data['title'] ?? ''));
        $status = normalizeStatusValue($data['status'] ?? 'OPEN');

        if ($title !== '') {
            // Création d'un nouvel ID unique basé sur le timestamp ou le max ID
            $newId = count($tasks) > 0 ? max(array_column($tasks, 'id')) + 1 : 1;
            
            $newTask = [
                'id' => $newId,
                'title' => $title,
                'completed' => ($status === 'DONE' ? 1 : 0),
                'status' => $status
            ];

            // On ajoute au début du tableau pour les voir en haut (ORDER BY id DESC équivalent)
            array_unshift($tasks, $newTask);
            writeTasks($dataFile, $tasks);

            echo json_encode(["success" => true, "message" => "Tâche ajoutée avec succès", "status" => $status]);
        } else {
            echo json_encode(["success" => false, "message" => "Le titre est vide"]);
        }
        break;

    case 'PUT':
        $id = $_GET['id'] ?? $data['id'] ?? null;
        if ($id !== null) {
            $status = normalizeStatusValue($data['status'] ?? 'OPEN');
            $updated = false;

            foreach ($tasks as &$task) {
                if ($task['id'] == $id) {
                    $task['status'] = $status;
                    $task['completed'] = ($status === 'DONE' ? 1 : 0);
                    $updated = true;
                    break;
                }
            }
            unset($task);

            if ($updated) {
                writeTasks($dataFile, $tasks);
                echo json_encode(["success" => true, "message" => "Tâche mise à jour", "status" => $status]);
            } else {
                echo json_encode(["success" => false, "message" => "Tâche non trouvée"]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "ID manquant"]);
        }
        break;

    case 'DELETE':
        $id = $_GET['id'] ?? $data['id'] ?? null;
        if ($id !== null) {
            $initialCount = count($tasks);
            $tasks = array_values(array_filter($tasks, function($task) use ($id) {
                return $task['id'] != $id;
            }));

            if (count($tasks) < $initialCount) {
                writeTasks($dataFile, $tasks);
                echo json_encode(["success" => true, "message" => "Tâche supprimée"]);
            } else {
                echo json_encode(["success" => false, "message" => "Tâche non trouvée"]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "ID manquant"]);
        }
        break;

    default:
        echo json_encode(["error" => "Méthode non autorisée"]);
        break;
}
?>