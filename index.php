<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma To-Do List Docker</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
        }
        .container {
            width: 100%;
            max-width: 600px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        h1 { text-align: center; color: #333; }
        form { display: flex; gap: 10px; margin-bottom: 20px; }
        input, select, button {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        input[type="text"] { flex: 2; }
        select { flex: 1; }
        button {
            background-color: #28a745;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover { background-color: #218838; }
        ul { list-style: none; padding: 0; }
        li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fafafa;
            padding: 10px 15px;
            margin-bottom: 8px;
            border-radius: 4px;
            border-left: 5px solid #ccc;
        }
        /* Couleurs selon les statuts */
        li.OPEN { border-left-color: #ffc107; }
        li.IN_PROGRESS { border-left-color: #17a2b8; }
        li.DONE { border-left-color: #28a745; text-decoration: line-through; color: #888; }
        
        .task-actions select { padding: 5px; font-size: 14px; margin-right: 5px; }
        .delete-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .delete-btn:hover { background-color: #c82333; }
    </style>
</head>
<body>

<div class="container">
    <h1>Ma To-Do List</h1>

    <!-- Formulaire d'ajout (CREATE) -->
    <form id="taskForm">
        <input type="text" id="taskTitle" placeholder="Nouvelle tâche..." required>
        <select id="taskStatus">
            <option value="OPEN">OPEN</option>
            <option value="IN_PROGRESS">IN PROGRESS</option>
            <option value="DONE">DONE</option>
        </select>
        <button type="submit">Ajouter</button>
    </form>

    <!-- Liste des tâches (READ) -->
    <ul id="taskList">
        <!-- Les tâches s'afficheront ici dynamiquement -->
    </ul>
</div>

<script>
    const apiUrl = 'api.php';

    // 1. READ : Charger les tâches au démarrage
    async function fetchTasks() {
        try {
            const response = await fetch(apiUrl);
            const tasks = await response.json();
            const taskList = document.getElementById('taskList');
            taskList.innerHTML = '';

            (Array.isArray(tasks) ? tasks : []).forEach(task => {
                const status = task.status ?? (task.completed === 1 ? 'DONE' : 'OPEN');
                const li = document.createElement('li');
                li.className = status;

                li.innerHTML = `
                    <span><strong>${task.title}</strong></span>
                    <div class="task-actions">
                        <select onchange="updateStatus(${task.id}, this.value)">
                            <option value="OPEN" ${status === 'OPEN' ? 'selected' : ''}>OPEN</option>
                            <option value="IN_PROGRESS" ${status === 'IN_PROGRESS' ? 'selected' : ''}>IN PROGRESS</option>
                            <option value="DONE" ${status === 'DONE' ? 'selected' : ''}>DONE</option>
                        </select>
                        <button class="delete-btn" onclick="deleteTask(${task.id})">Supprimer</button>
                    </div>
                `;
                taskList.appendChild(li);
            });
        } catch (error) {
            console.error('Erreur lors du chargement des tâches:', error);
        }
    }

    // 2. CREATE : Ajouter une tâche via le formulaire
    document.getElementById('taskForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const title = document.getElementById('taskTitle').value;
        const status = document.getElementById('taskStatus').value;

        await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ title, status })
        });

        document.getElementById('taskTitle').value = '';
        fetchTasks();
    });

    // 3. UPDATE : Modifier le statut d'une tâche
    async function updateStatus(id, newStatus) {
        await fetch(`${apiUrl}?id=${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: newStatus })
        });
        fetchTasks();
    }

    // 4. DELETE : Supprimer une tâche
    async function deleteTask(id) {
        await fetch(`${apiUrl}?id=${id}`, {
            method: 'DELETE'
        });
        fetchTasks();
    }

    // Charger les tâches dès l'ouverture de la page
    fetchTasks();
</script>

</body>
</html>