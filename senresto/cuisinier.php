<?php
// Vérifier la session
session_start();

// config.php - Configuration de la base de données
include "config/database.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'cuisinier') {
    header("Location: login.php");
    exit;
}

// Traiter les actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && isset($_POST['order_id'])) {
        $order_id = $_POST['order_id'];
        
        if ($_POST['action'] == 'start') {
            $sql = "UPDATE orders SET status = 'en_preparation', updated_at = NOW() WHERE id = ?";
        } elseif ($_POST['action'] == 'ready') {
            $sql = "UPDATE orders SET status = 'pret', updated_at = NOW() WHERE id = ?";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Récupérer les commandes non préparées
$sql = "SELECT o.id, o.order_number, o.created_at, o.status, 
        GROUP_CONCAT(CONCAT(oi.quantity, 'x ', m.name) SEPARATOR ', ') as items
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        JOIN menu_items m ON oi.menu_item_id = m.id
        WHERE o.status IN ('nouvelle', 'en_preparation')
        GROUP BY o.id
        ORDER BY 
            CASE 
                WHEN o.status = 'nouvelle' THEN 1
                WHEN o.status = 'en_preparation' THEN 2
                ELSE 3
            END, 
            o.created_at ASC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interface Cuisinier - Restaurant ESCOA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Restaurant ESCOA - Cuisinier</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Commandes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Ingrédients</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Menu à venir</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1>Liste des commandes à préparer</h1>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        Nouvelles commandes
                    </div>
                    <div class="card-body">
                        <?php
                        $hasNew = false;
                        if ($result->num_rows > 0) {
                            $result->data_seek(0);
                            while ($row = $result->fetch_assoc()) {
                                if ($row['status'] == 'nouvelle') {
                                    $hasNew = true;
                                    ?>
                                    <div class="order-card mb-3 p-3 border">
                                        <h5>Commande #<?php echo $row['order_number']; ?></h5>
                                        <p><strong>Heure:</strong> <?php echo date('H:i', strtotime($row['created_at'])); ?></p>
                                        <p><strong>Items:</strong> <?php echo $row['items']; ?></p>
                                        <form method="post" action="chef.php">
                                            <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="action" value="start">
                                            <button type="submit" class="btn btn-success">Commencer la préparation</button>
                                        </form>
                                    </div>
                                    <?php
                                }
                            }
                        }
                        
                        if (!$hasNew) {
                            echo "<p>Aucune nouvelle commande.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        Commandes en préparation
                    </div>
                    <div class="card-body">
                        <?php
                        $hasInProgress = false;
                        if ($result->num_rows > 0) {
                            $result->data_seek(0);
                            while ($row = $result->fetch_assoc()) {
                                if ($row['status'] == 'en_preparation') {
                                    $hasInProgress = true;
                                    ?>
                                    <div class="order-card mb-3 p-3 border">
                                        <h5>Commande #<?php echo $row['order_number']; ?></h5>
                                        <p><strong>Heure:</strong> <?php echo date('H:i', strtotime($row['created_at'])); ?></p>
                                        <p><strong>Items:</strong> <?php echo $row['items']; ?></p>
                                        <form method="post" action="chef.php">
                                            <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="action" value="ready">
                                            <button type="submit" class="btn btn-primary">Marquer comme prêt</button>
                                        </form>
                                    </div>
                                    <?php
                                }
                            }
                        }
                        
                        if (!$hasInProgress) {
                            echo "<p>Aucune commande en préparation.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Ingrédients à faible stock -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        Alerte de stock bas
                    </div>
                    <div class="card-body">
                        <?php
                        $sql_ingredients = "SELECT name, quantity, unit FROM ingredients WHERE quantity < min_quantity";
                        $result_ingredients = $conn->query($sql_ingredients);
                        
                        if ($result_ingredients->num_rows > 0) {
                            echo "<ul class='list-group'>";
                            while ($ingredient = $result_ingredients->fetch_assoc()) {
                                echo "<li class='list-group-item d-flex justify-content-between align-items-center'>";
                                echo $ingredient['name'];
                                echo "<span class='badge bg-danger rounded-pill'>".$ingredient['quantity']." ".$ingredient['unit']."</span>";
                                echo "</li>";
                            }
                            echo "</ul>";
                        } else {
                            echo "<p>Tous les ingrédients sont en stock suffisant.</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Communication avec les serveurs -->
    <div class="container mt-4 mb-5">
        <div class="card">
            <div class="card-header bg-info text-white">
                Messages des serveurs
            </div>
            <div class="card-body">
                <div id="messages">
                    <!-- Les messages seront chargés ici via AJAX -->
                </div>
                <form id="messageForm" class="mt-3">
                    <div class="input-group">
                        <input type="text" id="messageInput" class="form-control" placeholder="Envoyer un message aux serveurs...">
                        <button class="btn btn-primary" type="submit">Envoyer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Rafraîchir la page toutes les 30 secondes
        setTimeout(function() {
            location.reload();
        }, 30000);
        
        // Charger les messages
        function loadMessages() {
            $.ajax({
                url: 'get_messages.php',
                type: 'GET',
                success: function(data) {
                    $('#messages').html(data);
                }
            });
        }
        
        // Envoyer un message
        $('#messageForm').submit(function(e) {
            e.preventDefault();
            var message = $('#messageInput').val();
            
            $.ajax({
                url: 'send_message.php',
                type: 'POST',
                data: {
                    message: message,
                    sender_role: 'cuisinier'
                },
                success: function() {
                    $('#messageInput').val('');
                    loadMessages();
                }
            });
        });
        
        // Charger les messages au chargement de la page
        $(document).ready(function() {
            loadMessages();
            // Rafraîchir les messages toutes les 10 secondes
            setInterval(loadMessages, 10000);
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>