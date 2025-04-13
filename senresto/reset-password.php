<?php
// Démarrer la session
session_start();

// Inclure la connexion à la base de données
include "config/database.php";

// Variables pour les messages
$error_message = '';
$success_message = '';
$valid_token = false;
$email = '';

// Vérifier si un token est fourni dans l'URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        // Vérifier si le token existe et est valide
        $sql = "SELECT email, expire_at FROM password_resets WHERE token = :token";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $email = $result['email'];
            $expire_at = strtotime($result['expire_at']);
            $now = time();
            
            if ($expire_at > $now) {
                $valid_token = true;
            } else {
                $error_message = "Ce lien de réinitialisation a expiré. Veuillez demander un nouveau lien.";
            }
        } else {
            $error_message = "Token de réinitialisation invalide. Veuillez demander un nouveau lien.";
        }
    } catch(PDOException $e) {
        $error_message = "Erreur de connexion: " . $e->getMessage();
    }
} else {
    $error_message = "Aucun token de réinitialisation fourni.";
}

// Traitement du formulaire de réinitialisation de mot de passe
if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid_token) {
    // Récupérer les données du formulaire
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation des mots de passe
    if (empty($password) || empty($confirm_password)) {
        $error_message = "Tous les champs sont obligatoires";
    } elseif ($password != $confirm_password) {
        $error_message = "Les mots de passe ne correspondent pas";
    } elseif (strlen($password) < 8) {
        $error_message = "Le mot de passe doit contenir au moins 8 caractères";
    } else {
        try {
            // Hasher le nouveau mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Mettre à jour le mot de passe de l'utilisateur
            $sql_update = "UPDATE utilisateurs SET mot_de_passe = :password WHERE email = :email";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bindParam(':password', $hashed_password);
            $stmt_update->bindParam(':email', $email);
            $stmt_update->execute();
            
            // Supprimer le token de réinitialisation
            $sql_delete = "DELETE FROM password_resets WHERE token = :token";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bindParam(':token', $token);
            $stmt_delete->execute();
            
            $success_message = "Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.";
            
            // Rediriger vers la page de connexion après 5 secondes
            header("refresh:5;url=login.php");
        } catch(PDOException $e) {
            $error_message = "Erreur lors de la réinitialisation du mot de passe: " . $e->getMessage();
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
                    <img src="vendors/images/forgot-password.png" alt="Image de réinitialisation de mot de passe">
                </div>
                <div class="col-md-6 col-lg-5">
                    <div class="login-box bg-white box-shadow border-radius-10">
                        <div class="login-title">
                            <h2 class="text-center text-primary">Réinitialiser le mot de passe</h2>
                        </div>
                        
                        <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong><i class="icon-copy dw dw-warning"></i> Erreur!</strong> <?php echo $error_message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="text-center">
                            <a href="forgot-password.php" class="btn btn-outline-primary">Demander un nouveau lien</a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <strong><i class="icon-copy dw dw-check"></i> Succès!</strong> <?php echo $success_message; ?>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="text-center">
                            <p>Vous allez être redirigé vers la page de connexion dans 5 secondes.</p>
                            <a href="login.php" class="btn btn-primary">Se connecter maintenant</a>
                        </div>
                        <?php elseif ($valid_token): ?>
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . "?token=" . $token); ?>">
                            <p>Veuillez entrer votre nouveau mot de passe.</p>
                            <div class="input-group custom">
                                <input type="password" class="form-control form-control-lg" name="password" placeholder="Nouveau mot de passe" required minlength="8">
                                <div class="input-group-append custom">
                                    <span class="input-group-text"><i class="dw dw-padlock1"></i></span>
                                </div>
                            </div>
                            <div class="input-group custom">
                                <input type="password" class="form-control form-control-lg" name="confirm_password" placeholder="Confirmer le mot de passe" required minlength="8">
                                <div class="input-group-append custom">
                                    <span class="input-group-text"><i class="dw dw-padlock1"></i></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-12">
                                    <div class="input-group mb-0">
                                        <input class="btn btn-primary btn-lg btn-block" type="submit" value="Réinitialiser le mot de passe">
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