<?php
// Démarrer la session
session_start();

// Inclure la connexion à la base de données
include "config/database.php";

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// Récupérer l'ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'];

// Traitement de la mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        // Récupérer les données du formulaire
        $nom = trim($_POST['nom']);
        $prenom = trim($_POST['prenom']);
        $email = trim($_POST['email']);
        $telephone = trim($_POST['telephone']);
        $adresse = isset($_POST['adresse']) ? trim($_POST['adresse']) : '';
        
        // Validation des champs
        $errors = [];
        if (empty($nom)) $errors[] = "Le nom est obligatoire";
        if (empty($prenom)) $errors[] = "Le prénom est obligatoire";
        if (empty($email)) $errors[] = "L'email est obligatoire";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "L'email n'est pas valide";
        
        // Vérifier si l'email existe déjà pour un autre utilisateur
        $stmt_check = $conn->prepare("SELECT id FROM utilisateurs WHERE email = :email AND id != :id");
        $stmt_check->bindParam(':email', $email);
        $stmt_check->bindParam(':id', $user_id);
        $stmt_check->execute();
        
        if ($stmt_check->rowCount() > 0) {
            $errors[] = "Cet email est déjà utilisé par un autre compte";
        }
        
        if (empty($errors)) {
            // Mettre à jour les informations de base de l'utilisateur
            $sql = "UPDATE utilisateurs SET nom = :nom, prenom = :prenom, email = :email, telephone = :telephone, adresse = :adresse, updated_at = NOW() WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':prenom', $prenom);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':telephone', $telephone);
            $stmt->bindParam(':adresse', $adresse);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            
            // Mettre à jour les données spécifiques selon le type d'utilisateur
            $user_type = $_SESSION['user_type'];
            if ($user_type == 'etudiant' && isset($_POST['filiere']) && isset($_POST['niveau'])) {
                $filiere = trim($_POST['filiere']);
                $niveau = trim($_POST['niveau']);
                
                $sql_etudiant = "UPDATE etudiants_details SET filiere = :filiere, niveau = :niveau WHERE utilisateur_id = :id";
                $stmt_etudiant = $conn->prepare($sql_etudiant);
                $stmt_etudiant->bindParam(':filiere', $filiere);
                $stmt_etudiant->bindParam(':niveau', $niveau);
                $stmt_etudiant->bindParam(':id', $user_id);
                $stmt_etudiant->execute();
            } elseif ($user_type == 'employe' && isset($_POST['poste']) && isset($_POST['departement'])) {
                $poste = trim($_POST['poste']);
                $departement = trim($_POST['departement']);
                
                $sql_employe = "UPDATE employes_details SET poste = :poste, departement = :departement WHERE utilisateur_id = :id";
                $stmt_employe = $conn->prepare($sql_employe);
                $stmt_employe->bindParam(':poste', $poste);
                $stmt_employe->bindParam(':departement', $departement);
                $stmt_employe->bindParam(':id', $user_id);
                $stmt_employe->execute();
            }
            
            // Mettre à jour les variables de session
            $_SESSION['user_nom'] = $nom;
            $_SESSION['user_prenom'] = $prenom;
            $_SESSION['user_email'] = $email;
            
            $success_message = "Votre profil a été mis à jour avec succès.";
        }
    } catch(PDOException $e) {
        $error_message = "Erreur lors de la mise à jour du profil: " . $e->getMessage();
    }
}

// Traitement du changement de mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    try {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation
        $errors = [];
        if (empty($current_password)) $errors[] = "Le mot de passe actuel est obligatoire";
        if (empty($new_password)) $errors[] = "Le nouveau mot de passe est obligatoire";
        if ($new_password != $confirm_password) $errors[] = "Les nouveaux mots de passe ne correspondent pas";
        if (strlen($new_password) < 8) $errors[] = "Le nouveau mot de passe doit contenir au moins 8 caractères";
        
        if (empty($errors)) {
            // Vérifier le mot de passe actuel
            $stmt_pwd = $conn->prepare("SELECT password FROM utilisateurs WHERE id = :id");
            $stmt_pwd->bindParam(':id', $user_id);
            $stmt_pwd->execute();
            $user_data = $stmt_pwd->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($current_password, $user_data['password'])) {
                // Mettre à jour le mot de passe
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql_pwd = "UPDATE utilisateurs SET password = :password, updated_at = NOW() WHERE id = :id";
                $stmt_update = $conn->prepare($sql_pwd);
                $stmt_update->bindParam(':password', $hashed_password);
                $stmt_update->bindParam(':id', $user_id);
                $stmt_update->execute();
                
                $password_success = "Votre mot de passe a été modifié avec succès.";
            } else {
                $password_error = "Le mot de passe actuel est incorrect.";
            }
        } else {
            $password_error = implode("<br>", $errors);
        }
    } catch(PDOException $e) {
        $password_error = "Erreur lors du changement de mot de passe: " . $e->getMessage();
    }
}

// Récupérer les informations de l'utilisateur
try {
    $sql = "SELECT u.*, r.nom as role_nom 
            FROM utilisateurs u 
            LEFT JOIN roles r ON u.role_id = r.id 
            WHERE u.id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer les informations spécifiques selon le type d'utilisateur
    $details = [];
    $type = $user['type_utilisateur'];
    
    if ($type == 'etudiant') {
        $sql_details = "SELECT * FROM etudiants_details WHERE utilisateur_id = :id";
        $stmt_details = $conn->prepare($sql_details);
        $stmt_details->bindParam(':id', $user_id);
        $stmt_details->execute();
        $details = $stmt_details->fetch(PDO::FETCH_ASSOC);
    } elseif ($type == 'employe') {
        $sql_details = "SELECT * FROM employes_details WHERE utilisateur_id = :id";
        $stmt_details = $conn->prepare($sql_details);
        $stmt_details->bindParam(':id', $user_id);
        $stmt_details->execute();
        $details = $stmt_details->fetch(PDO::FETCH_ASSOC);
    } elseif ($type == 'livreur') {
        $sql_details = "SELECT * FROM livreurs_details WHERE utilisateur_id = :id";
        $stmt_details = $conn->prepare($sql_details);
        $stmt_details->bindParam(':id', $user_id);
        $stmt_details->execute();
        $details = $stmt_details->fetch(PDO::FETCH_ASSOC);
    }
} catch(PDOException $e) {
    $error_message = "Erreur lors de la récupération des données: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <?php include "head.php"; ?>
</head>
<body>
    <?php include "chargement.php"; ?>

    <div class="header">
        <div class="header-left">
            <div class="menu-icon dw dw-menu"></div>
            <div class="search-toggle-icon dw dw-search2" data-toggle="header_search"></div>
        </div>
        
        <div class="header-right">
            <div class="user-info-dropdown">
                <div class="dropdown">
                    <a class="dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                        <span class="user-icon">
                        <img src="vendors/images/<?php echo (isset($user['sexe']) && $user['sexe'] == 'F') ? 'femme.png' : 'femme.png'; ?>" alt="" class="avatar-photo">
                        </span>
                        <span class="user-name">
                            <?php echo isset($_SESSION['user_nom']) ? $_SESSION['user_nom'].' '.$_SESSION['user_prenom'] : 'Utilisateur'; ?>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
                        <a class="dropdown-item" href="profile.php"><i class="dw dw-user1"></i> Profil</a>
                        <a class="dropdown-item" href="logout.php"><i class="dw dw-logout"></i> Déconnexion</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="left-side-bar">
        <div class="brand-logo">
            <a href="index.php">
                <img src="vendors/images/logo_escoa.png" alt="Logo Restaurant ESCOA" class="dark-logo">
                <img src="vendors/images/logo_escoa_white.png" alt="Logo Restaurant ESCOA" class="light-logo">
            </a>
            <div class="close-sidebar" data-toggle="left-sidebar-close">
                <i class="ion-close-round"></i>
            </div>
        </div>
        <div class="menu-block customscroll">
            <?php include "menu.php"; ?>
        </div>
    </div>
    <div class="mobile-menu-overlay"></div>

    <div class="main-container">
        <div class="pd-ltr-20 xs-pd-20-10">
            <div class="min-height-200px">
                <div class="page-header">
                    <div class="row">
                        <div class="col-md-12 col-sm-12">
                            <div class="title">
                                <h4>Profil</h4>
                            </div>
                            <nav aria-label="breadcrumb" role="navigation">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Profil</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Notification de succès/erreur -->
                <?php if(isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong><i class="icon-copy dw dw-check"></i> Succès!</strong> <?php echo $success_message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if(isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong><i class="icon-copy dw dw-warning"></i> Erreur!</strong> <?php echo $error_message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-xl-4 col-lg-4 col-md-4 col-sm-12 mb-30">
                        <div class="pd-20 card-box height-100-p">
                            <div class="profile-photo">
                                <a href="#" class="edit-avatar" title="Changer de photo de profil"><i class="fas fa-pencil-alt"></i></a>
                                <img src="vendors/images/<?php echo (isset($user['sexe']) && $user['sexe'] == 'F') ? 'femme.png' : 'homme.png'; ?>" alt="" class="avatar-photo">
                            </div>
                            <h5 class="text-center h5 mb-0"><?php echo $user['prenom'] . ' ' . $user['nom']; ?></h5>
                            <p class="text-center text-muted font-14"><?php echo ucfirst($user['role_nom']); ?></p>
                            <div class="profile-info">
                                <h5 class="mb-20 h5 text-blue">Informations générales</h5>
                                <ul>
                                    <li>
                                        <span>Type de compte:</span>
                                        <?php
                                            $badge_class = '';
                                            switch($user['type_utilisateur']) {
                                                case 'admin': $badge_class = 'badge-danger'; break;
                                                case 'etudiant': $badge_class = 'badge-primary'; break;
                                                case 'personnel': $badge_class = 'badge-warning'; break;
                                                case 'professeur': $badge_class = 'badge-info'; break;
                                                case 'employe': $badge_class = 'badge-success'; break;
                                                case 'livreur': $badge_class = 'badge-dark'; break;
                                                default: $badge_class = 'badge-secondary';
                                            }
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($user['type_utilisateur']); ?></span>
                                    </li>
                                    <li>
                                        <span>Email:</span>
                                        <?php echo $user['email']; ?>
                                    </li>
                                    <li>
                                        <span>Téléphone:</span>
                                        <?php echo !empty($user['telephone']) ? $user['telephone'] : 'Non renseigné'; ?>
                                    </li>
                                    <li>
                                        <span>Matricule:</span>
                                        <?php echo !empty($user['matricule']) ? $user['matricule'] : 'Non assigné'; ?>
                                    </li>
                                    <li>
                                        <span>Statut:</span>
                                        <?php if($user['statut'] == 'actif'): ?>
                                            <span class="badge badge-success">Actif</span>
                                        <?php elseif($user['statut'] == 'inactif'): ?>
                                            <span class="badge badge-secondary">Inactif</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Suspendu</span>
                                        <?php endif; ?>
                                    </li>
                                    <li>
                                        <span>Date d'inscription:</span>
                                        <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                                    </li>
                                </ul>
                            </div>
                            <?php if ($user['type_utilisateur'] == 'etudiant' && !empty($details)): ?>
                            <div class="profile-info">
                                <h5 class="mb-20 h5 text-blue">Informations étudiant</h5>
                                <ul>
                                    <li>
                                        <span>Filière:</span>
                                        <?php echo !empty($details['filiere']) ? $details['filiere'] : 'Non renseigné'; ?>
                                    </li>
                                    <li>
                                        <span>Niveau:</span>
                                        <?php echo !empty($details['niveau']) ? $details['niveau'] : 'Non renseigné'; ?>
                                    </li>
                                </ul>
                            </div>
                            <?php elseif ($user['type_utilisateur'] == 'employe' && !empty($details)): ?>
                            <div class="profile-info">
                                <h5 class="mb-20 h5 text-blue">Informations employé</h5>
                                <ul>
                                    <li>
                                        <span>Poste:</span>
                                        <?php echo !empty($details['poste']) ? $details['poste'] : 'Non renseigné'; ?>
                                    </li>
                                    <li>
                                        <span>Département:</span>
                                        <?php echo !empty($details['departement']) ? $details['departement'] : 'Non renseigné'; ?>
                                    </li>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-xl-8 col-lg-8 col-md-8 col-sm-12 mb-30">
                        <div class="card-box height-100-p overflow-hidden">
                            <div class="profile-tab">
                                <div class="tab">
                                    <ul class="nav nav-tabs customtab" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" data-toggle="tab" href="#edit_profile" role="tab">Modifier le profil</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#reset_password" role="tab">Changer le mot de passe</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#activities" role="tab">Activités récentes</a>
                                        </li>
                                    </ul>
                                    <div class="tab-content">
                                        <!-- Onglet de modification du profil -->
                                        <div class="tab-pane fade show active" id="edit_profile" role="tabpanel">
                                            <div class="pd-20">
                                                <form method="POST" action="">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Nom</label>
                                                                <input type="text" class="form-control" name="nom" value="<?php echo $user['nom']; ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Prénom</label>
                                                                <input type="text" class="form-control" name="prenom" value="<?php echo $user['prenom']; ?>" required>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Email</label>
                                                                <input type="email" class="form-control" name="email" value="<?php echo $user['email']; ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Téléphone</label>
                                                                <input type="text" class="form-control" name="telephone" value="<?php echo $user['telephone']; ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Adresse</label>
                                                        <textarea class="form-control" name="adresse"><?php echo $user['adresse']; ?></textarea>
                                                    </div>
                                                    
                                                    <?php if ($user['type_utilisateur'] == 'etudiant'): ?>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Filière</label>
                                                                <input type="text" class="form-control" name="filiere" value="<?php echo isset($details['filiere']) ? $details['filiere'] : ''; ?>">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Niveau</label>
                                                                <select class="form-control" name="niveau">
                                                                    <option value="">Sélectionnez un niveau</option>
                                                                    <option value="Licence 1" <?php echo (isset($details['niveau']) && $details['niveau'] == 'Licence 1') ? 'selected' : ''; ?>>Licence 1</option>
                                                                    <option value="Licence 2" <?php echo (isset($details['niveau']) && $details['niveau'] == 'Licence 2') ? 'selected' : ''; ?>>Licence 2</option>
                                                                    <option value="Licence 3" <?php echo (isset($details['niveau']) && $details['niveau'] == 'Licence 3') ? 'selected' : ''; ?>>Licence 3</option>
                                                                    <option value="Master 1" <?php echo (isset($details['niveau']) && $details['niveau'] == 'Master 1') ? 'selected' : ''; ?>>Master 1</option>
                                                                    <option value="Master 2" <?php echo (isset($details['niveau']) && $details['niveau'] == 'Master 2') ? 'selected' : ''; ?>>Master 2</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php elseif ($user['type_utilisateur'] == 'employe'): ?>
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Poste</label>
                                                                <input type="text" class="form-control" name="poste" value="<?php echo isset($details['poste']) ? $details['poste'] : ''; ?>">
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="form-group">
                                                                <label>Département</label>
                                                                <input type="text" class="form-control" name="departement" value="<?php echo isset($details['departement']) ? $details['departement'] : ''; ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <?php endif; ?>
                                                    
                                                    <div class="form-group text-right">
                                                        <input type="hidden" name="update_foto_profile" value="1">
                                                        <button type="submit" class="btn btn-primary">Mettre à jour le profil</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <!-- Onglet de changement de mot de passe -->
                                        <div class="tab-pane fade" id="change_password" role="tabpanel">
                                            <div class="pd-20">
                                                <?php if(isset($password_success)): ?>
                                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                                    <strong><i class="icon-copy dw dw-check"></i> Succès!</strong> <?php echo $password_success; ?>
                                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <?php if(isset($password_error)): ?>
                                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                                    <strong><i class="icon-copy dw dw-warning"></i> Erreur!</strong> <?php echo $password_error; ?>
                                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <?php endif; ?>
                                                
                                                <form method="POST" action="">
                                                    <div class="form-group">
                                                        <label>Mot de passe actuel</label>
                                                        <input type="password" class="form-control" name="current_password" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Nouveau mot de passe</label>
                                                        <input type="password" class="form-control" name="new_password" required>
                                                        <small class="form-text text-muted">Le mot de passe doit contenir au moins 8 caractères.</small>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Confirmer le nouveau mot de passe</label>
                                                        <input type="password" class="form-control" name="confirm_password" required>
                                                    </div>
                                                    <div class="form-group text-right">
                                                        <input type="hidden" name="change_password" value="1">
                                                        <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <!-- Onglet des activités récentes -->
                                        <div class="tab-pane fade" id="activities" role="tabpanel">
                                            <div class="pd-20">
                                                <div class="timeline-steps">
                                                    <div class="timeline-step">
                                                        <div class="timeline-content">
                                                            <div class="inner-circle bg-success">
                                                                <i class="icon-copy dw dw-login"></i>
                                                            </div>
                                                            <p class="h6 mt-3 mb-1">Dernière connexion</p>
                                                            <p class="h6 text-muted mb-0 mb-lg-0">
                                                              <?php echo isset($user['last_login']) ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Jamais'; ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="timeline-step">
                                                        <div class="timeline-content">
                                                            <div class="inner-circle bg-info">
                                                                <i class="icon-copy dw dw-edit2"></i>
                                                            </div>
                                                            <p class="h6 mt-3 mb-1">Dernière mise à jour</p>
                                                            <p class="h6 text-muted mb-0 mb-lg-0">
                                                                <?php echo date('d/m/Y H:i', strtotime($user['updated_at'])); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <div class="timeline-step">
                                                        <div class="timeline-content">
                                                            <div class="inner-circle bg-primary">
                                                                <i class="icon-copy dw dw-user-13"></i>
                                                            </div>
                                                            <p class="h6 mt-3 mb-1">Inscription</p>
                                                            <p class="h6 text-muted mb-0 mb-lg-0">
                                                                <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="alert alert-info mt-4" role="alert">
                                                    <i class="icon-copy dw dw-information"></i>
                                                    L'historique détaillé des activités sera disponible prochainement.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-wrap pd-20 mb-20 card-box">
                &copy; <?php echo date('Y'); ?> Restaurant ESCOA - Tous droits réservés
            </div>
        </div>
    </div>
    
   <!-- Modal de changement de photo de profil -->
<div class="modal fade" id="modal-change-photo" tabindex="-1" role="dialog" aria-labelledby="modalChangePhotoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalChangePhotoLabel">Changer la photo de profil</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Contenu du formulaire de changement de photo -->
                <p>Fonctionnalité en cours de développement.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fermer</button>
                <button type="button" class="btn btn-primary">Enregistrer</button>
            </div>
        </div>
    </div>
</div>

<!-- js -->
<script src="vendors/scripts/core.js"></script>
<script src="vendors/scripts/script.min.js"></script>
<script src="vendors/scripts/process.js"></script>
<script src="vendors/scripts/layout-settings.js"></script>

<script>
    // Script pour ouvrir le modal quand on clique sur edit-avatar
    $(document).ready(function() {
        $('.edit-avatar').on('click', function() {
            $('#modal-change-photo').modal('show');
        });
    });
</script>
</body>
</html>