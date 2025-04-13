<?php
// Démarrer la session
session_start();

// Inclure la connexion à la base de données
include "config/database.php";

// Variables pour les messages
$error_message = '';
$success_message = '';

// Traitement du formulaire de récupération de mot de passe
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer l'email du formulaire
    $email = trim($_POST['email']);
    
    // Validation de l'email
    if (empty($email)) {
        $error_message = "Veuillez saisir votre adresse email";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format d'email invalide";
    } else {
        try {
            // Vérifier si l'email existe dans la base de données
            $sql = "SELECT id, nom, prenom, email FROM utilisateurs WHERE email = :email AND statut = 'actif'";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Générer un token unique pour la réinitialisation du mot de passe
                $token = bin2hex(random_bytes(32));
                $expire = date('Y-m-d H:i:s', strtotime('+1 hour')); // Le token expire dans 1 heure
                
                // Enregistrer le token dans la base de données
                // Note: vous devez créer une table 'password_resets' pour stocker ces tokens
                try {
                    // Vérifier si la table password_resets existe
                    $sql_check = "SHOW TABLES LIKE 'password_resets'";
                    $stmt_check = $conn->prepare($sql_check);
                    $stmt_check->execute();
                    
                    if ($stmt_check->rowCount() == 0) {
                        // Créer la table password_resets si elle n'existe pas
                        $sql_create = "CREATE TABLE password_resets (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            email VARCHAR(255) NOT NULL,
                            token VARCHAR(100) NOT NULL,
                            expire_at DATETIME NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )";
                        $stmt_create = $conn->prepare($sql_create);
                        $stmt_create->execute();
                    }
                    
                    // Supprimer tout token existant pour cet utilisateur
                    $sql_delete = "DELETE FROM password_resets WHERE email = :email";
                    $stmt_delete = $conn->prepare($sql_delete);
                    $stmt_delete->bindParam(':email', $email);
                    $stmt_delete->execute();
                    
                    // Insérer le nouveau token
                    $sql_insert = "INSERT INTO password_resets (email, token, expire_at) VALUES (:email, :token, :expire)";
                    $stmt_insert = $conn->prepare($sql_insert);
                    $stmt_insert->bindParam(':email', $email);
                    $stmt_insert->bindParam(':token', $token);
                    $stmt_insert->bindParam(':expire', $expire);
                    $stmt_insert->execute();
                    
                    // Construire le lien de réinitialisation
                    $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=" . $token;
                    
                    // En production, vous enverriez un email avec le lien
                    // Pour cet exemple, nous allons simplement afficher le lien
                    $success_message = "Un lien de réinitialisation a été envoyé à votre adresse email. Il expirera dans 1 heure.<br><br>";
                    $success_message .= "<strong>Pour les besoins de la démonstration, voici le lien:</strong><br>";
                    $success_message .= "<a href='$resetLink'>$resetLink</a>";
                    
                    // En production, vous utiliseriez une fonction d'envoi d'email comme celle-ci:
                    /*
                    $to = $email;
                    $subject = "Réinitialisation de mot de passe - Restaurant ESCOA";
                    $message = "Bonjour " . $user['prenom'] . " " . $user['nom'] . ",\n\n";
                    $message .= "Vous avez demandé à réinitialiser votre mot de passe. Veuillez cliquer sur le lien ci-dessous pour définir un nouveau mot de passe :\n\n";
                    $message .= $resetLink . "\n\n";
                    $message .= "Ce lien expirera dans 1 heure.\n\n";
                    $message .= "Si vous n'avez pas demandé cette réinitialisation, veuillez ignorer cet email.\n\n";
                    $message .= "Cordialement,\nL'équipe du Restaurant ESCOA";
                    $headers = "From: noreply@resto-escoa.com";
                    
                    mail($to, $subject, $message, $headers);
                    */
                    
                } catch(PDOException $e) {
                    $error_message = "Erreur lors de la génération du token: " . $e->getMessage();
                }
                
            } else {
                // Pour des raisons de sécurité, ne pas révéler si l'email existe ou non
                $success_message = "Si votre adresse email existe dans notre système, vous recevrez un lien de réinitialisation.";
            }
        } catch(PDOException $e) {
            $error_message = "Erreur de connexion: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <?php include "head.php"; ?>
</head>
<body class="login-page">
    <div class="login-header box-shadow">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div class="brand-logo">
                <a href="login.php">
                    <img src="vendors/images/logo_escoa.png" alt="Logo ESCOA">
                </a>
            </div>
            <div class="login-menu">
                <ul>
                    <li><a href="login.php">Connexion</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="login-wrap d-flex align-items-center flex-wrap justify-content-center">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 col-lg-7">
                    <img src="vendors/images/forgot-password.png" alt="Image mot de passe oublié">
                </div>
                <div class="col-md-6 col-lg-5">
                    <div class="login-box bg-white box-shadow border-radius-10">
                        <div class="login-title">
                            <h2 class="text-center text-primary">Mot de passe oublié</h2>
                        </div>
                        
                        <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong><i class="icon-copy dw dw-warning"></i> Erreur!</strong> <?php echo $error_message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong><i class="icon-copy dw dw-check"></i> Succès!</strong> <?php echo $success_message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <?php else: ?>
                        <p>Entrez votre adresse email pour recevoir un lien de réinitialisation de mot de passe.</p>
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="input-group custom">
                                <input type="email" class="form-control form-control-lg" name="email" placeholder="Adresse email" required>
                                <div class="input-group-append custom">
                                    <span class="input-group-text"><i class="fa fa-envelope-o"></i></span>
                                </div>
                            </div>
                            <div class="row align-items-center">
                                <div class="col-5">
                                    <div class="input-group mb-0">
                                        <a class="btn btn-outline-primary btn-lg btn-block" href="login.php">Retour</a>
                                    </div>
                                </div>
                                <div class="col-7">
                                    <div class="input-group mb-0">
                                        <input class="btn btn-primary btn-lg btn-block" type="submit" value="Réinitialiser">
                                    </div>
                                </div>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- js -->
    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
    <script src="vendors/scripts/process.js"></script>
    <script src="vendors/scripts/layout-settings.js"></script>
</body>
</html>