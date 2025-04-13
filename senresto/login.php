<?php
// Démarrer la session
session_start();

// Si l'utilisateur est déjà connecté, le rediriger vers la page d'accueil
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Inclure la connexion à la base de données
include "config/database.php";

// Variables pour les messages
$error_message = '';
$success_message = '';

// Traitement du formulaire de connexion
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les données du formulaire
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validation des champs
    if (empty($email) || empty($password)) {
        $error_message = "Tous les champs sont obligatoires";
    } else {
        try {
            // Vérifier si l'email existe dans la base de données
            $sql = "SELECT id, nom, prenom, email, mot_de_passe, role_id, type_utilisateur, statut 
                    FROM utilisateurs 
                    WHERE email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Vérifier si l'utilisateur est actif
                if ($user['statut'] == 'inactif') {
                    $error_message = "Votre compte est inactif. Veuillez contacter l'administrateur.";
                } else if ($user['statut'] == 'suspendu') {
                    $error_message = "Votre compte est suspendu. Veuillez contacter l'administrateur.";
                } else {
                    // Vérifier le mot de passe
                    if (password_verify($password, $user['mot_de_passe'])) {
                        // Récupérer le nom du rôle
                        $sql_role = "SELECT nom FROM roles WHERE id = :role_id";
                        $stmt_role = $conn->prepare($sql_role);
                        $stmt_role->bindParam(':role_id', $user['role_id']);
                        $stmt_role->execute();
                        $role = $stmt_role->fetch(PDO::FETCH_ASSOC);
                        
                        // Stocker les informations de l'utilisateur dans la session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_nom'] = $user['nom'];
                        $_SESSION['user_prenom'] = $user['prenom'];
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $role['nom'];
                        $_SESSION['user_type'] = $user['type_utilisateur'];
                        
                        // Mettre à jour la dernière connexion
                        $sql_update = "UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = :id";
                        $stmt_update = $conn->prepare($sql_update);
                        $stmt_update->bindParam(':id', $user['id']);
                        $stmt_update->execute();
                        
                        // Si "Se souvenir de moi" est coché, créer un cookie
                        if ($remember) {
                            $token = bin2hex(random_bytes(32)); // Générer un token aléatoire
                            
                            // Stocker le token dans un cookie valide pour 30 jours
                            setcookie('remember_token', $token, time() + (86400 * 30), "/");
                            
                            // Stocker le token dans la base de données (vous devrez créer une table pour cela)
                            // Ici, nous le stockons temporairement dans la session
                            $_SESSION['remember_token'] = $token;
                        }
                        
                        // Rediriger selon le rôle de l'utilisateur
                        $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
                        header("Location: $redirect");
                        exit();
                    } else {
                        $error_message = "Email ou mot de passe incorrect";
                    }
                }
            } else {
                $error_message = "Email ou mot de passe incorrect";
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
					<!-- Vous pouvez ajouter des liens ici si nécessaire -->
				</ul>
			</div>
		</div>
	</div>
	<div class="login-wrap d-flex align-items-center flex-wrap justify-content-center">
		<div class="container">
			<div class="row align-items-center">
				<div class="col-md-6 col-lg-7">
					<img src="vendors/images/login-page-img.png" alt="Image de connexion">
				</div>
				<div class="col-md-6 col-lg-5">
					<div class="login-box bg-white box-shadow border-radius-10">
						<div class="login-title">
							<h2 class="text-center text-primary">Connexion au Restaurant ESCOA</h2>
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
						<?php endif; ?>
						
						<form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"] . (isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '')); ?>">
							<div class="input-group custom">
								<input type="email" class="form-control form-control-lg" name="email" placeholder="Adresse email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
								<div class="input-group-append custom">
									<span class="input-group-text"><i class="icon-copy dw dw-user1"></i></span>
								</div>
							</div>
							<div class="input-group custom">
								<input type="password" class="form-control form-control-lg" name="password" placeholder="**********" required>
								<div class="input-group-append custom">
									<span class="input-group-text"><i class="dw dw-padlock1"></i></span>
								</div>
							</div>
							<div class="row pb-30">
								<div class="col-6">
									<div class="custom-control custom-checkbox">
										<input type="checkbox" class="custom-control-input" id="remember" name="remember">
										<label class="custom-control-label" for="remember">Se souvenir de moi</label>
									</div>
								</div>
								<div class="col-6">
									<div class="forgot-password"><a href="forgot-password.php">Mot de passe oublié?</a></div>
								</div>
							</div>
							<div class="row">
								<div class="col-sm-12">
									<div class="input-group mb-0">
										<input class="btn btn-primary btn-lg btn-block" type="submit" value="Se connecter">
									</div>
								</div>
							</div>
						</form>
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