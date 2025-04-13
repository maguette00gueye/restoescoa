<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include "head.php"; ?>
    <!-- Select2 CSS -->
    <link rel="stylesheet" type="text/css" href="vendors/styles/select2.min.css">
</head>
<body>
    <?php include "chargement.php"; ?>
    <?php 
    // Connexion à la base de données
    include "config/database.php";
    
    // Vérifier si l'utilisateur est connecté
    include "auth_check.php";
    
    // Récupération des rôles
    $sql_roles = "SELECT * FROM roles ORDER BY nom";
    $stmt_roles = $conn->prepare($sql_roles);
    $stmt_roles->execute();
    $roles = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);
    
    $error_message = '';
    $success_message = '';
    
    // Traitement du formulaire d'ajout
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Récupérer les données du formulaire
        $role_id = $_POST['role_id'];
        $nom = $_POST['nom'];
        $prenom = $_POST['prenom'];
        $email = $_POST['email'];
        $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT); // Cryptage du mot de passe
        $telephone = $_POST['telephone'];
        $type_utilisateur = $_POST['type_utilisateur'];
        $matricule = $_POST['matricule'];
        $date_naissance = $_POST['date_naissance'];
        $adresse = $_POST['adresse'];
        $statut = $_POST['statut'];
        
        try {
            // Vérifier si l'email existe déjà
            $sql_check_email = "SELECT COUNT(*) FROM utilisateurs WHERE email = :email";
            $stmt_check_email = $conn->prepare($sql_check_email);
            $stmt_check_email->bindParam(':email', $email);
            $stmt_check_email->execute();
            $email_exists = $stmt_check_email->fetchColumn();
            
            if ($email_exists) {
                $error_message = "Cet email est déjà utilisé par un autre utilisateur.";
            } else {
                // Vérifier si le matricule existe déjà (s'il est fourni)
                if (!empty($matricule)) {
                    $sql_check_matricule = "SELECT COUNT(*) FROM utilisateurs WHERE matricule = :matricule";
                    $stmt_check_matricule = $conn->prepare($sql_check_matricule);
                    $stmt_check_matricule->bindParam(':matricule', $matricule);
                    $stmt_check_matricule->execute();
                    $matricule_exists = $stmt_check_matricule->fetchColumn();
                    
                    if ($matricule_exists) {
                        $error_message = "Ce matricule est déjà utilisé par un autre utilisateur.";
                    }
                }
                
                // Si aucune erreur, insérer l'utilisateur
                if (empty($error_message)) {
                    $sql_insert = "INSERT INTO utilisateurs (role_id, nom, prenom, email, mot_de_passe, telephone, type_utilisateur, matricule, date_naissance, adresse, statut) 
                                VALUES (:role_id, :nom, :prenom, :email, :mot_de_passe, :telephone, :type_utilisateur, :matricule, :date_naissance, :adresse, :statut)";
                    
                    $stmt_insert = $conn->prepare($sql_insert);
                    $stmt_insert->bindParam(':role_id', $role_id);
                    $stmt_insert->bindParam(':nom', $nom);
                    $stmt_insert->bindParam(':prenom', $prenom);
                    $stmt_insert->bindParam(':email', $email);
                    $stmt_insert->bindParam(':mot_de_passe', $mot_de_passe);
                    $stmt_insert->bindParam(':telephone', $telephone);
                    $stmt_insert->bindParam(':type_utilisateur', $type_utilisateur);
                    $stmt_insert->bindParam(':matricule', $matricule);
                    $stmt_insert->bindParam(':date_naissance', $date_naissance);
                    $stmt_insert->bindParam(':adresse', $adresse);
                    $stmt_insert->bindParam(':statut', $statut);
                    
                    $stmt_insert->execute();
                    $user_id = $conn->lastInsertId();
                    
                    // Ajouter des détails supplémentaires en fonction du type d'utilisateur
                    if ($type_utilisateur === 'etudiant') {
                        $niveau_etude = $_POST['niveau_etude'] ?? null;
                        $filiere = $_POST['filiere'] ?? null;
                        $annee_academique = $_POST['annee_academique'] ?? null;
                        $carte_etudiant = $_POST['carte_etudiant'] ?? null;
                        
                        $sql_etudiant = "INSERT INTO etudiants_details (utilisateur_id, niveau_etude, filiere, annee_academique, carte_etudiant) 
                                        VALUES (:utilisateur_id, :niveau_etude, :filiere, :annee_academique, :carte_etudiant)";
                        
                        $stmt_etudiant = $conn->prepare($sql_etudiant);
                        $stmt_etudiant->bindParam(':utilisateur_id', $user_id);
                        $stmt_etudiant->bindParam(':niveau_etude', $niveau_etude);
                        $stmt_etudiant->bindParam(':filiere', $filiere);
                        $stmt_etudiant->bindParam(':annee_academique', $annee_academique);
                        $stmt_etudiant->bindParam(':carte_etudiant', $carte_etudiant);
                        
                        $stmt_etudiant->execute();
                    } elseif ($type_utilisateur === 'employe') {
                        $poste = $_POST['poste'] ?? null;
                        $departement = $_POST['departement'] ?? null;
                        $date_embauche = $_POST['date_embauche'] ?? null;
                        
                        $sql_employe = "INSERT INTO employes_details (utilisateur_id, poste, departement, date_embauche) 
                                        VALUES (:utilisateur_id, :poste, :departement, :date_embauche)";
                        
                        $stmt_employe = $conn->prepare($sql_employe);
                        $stmt_employe->bindParam(':utilisateur_id', $user_id);
                        $stmt_employe->bindParam(':poste', $poste);
                        $stmt_employe->bindParam(':departement', $departement);
                        $stmt_employe->bindParam(':date_embauche', $date_embauche);
                        
                        $stmt_employe->execute();
                    } elseif ($type_utilisateur === 'livreur') {
                        $zone_livraison = $_POST['zone_livraison'] ?? null;
                        $disponibilite = isset($_POST['disponibilite']) ? 1 : 0;
                        $numero_vehicule = $_POST['numero_vehicule'] ?? null;
                        $type_vehicule = $_POST['type_vehicule'] ?? null;
                        
                        $sql_livreur = "INSERT INTO livreurs_details (utilisateur_id, zone_livraison, disponibilite, numero_vehicule, type_vehicule) 
                                        VALUES (:utilisateur_id, :zone_livraison, :disponibilite, :numero_vehicule, :type_vehicule)";
                        
                        $stmt_livreur = $conn->prepare($sql_livreur);
                        $stmt_livreur->bindParam(':utilisateur_id', $user_id);
                        $stmt_livreur->bindParam(':zone_livraison', $zone_livraison);
                        $stmt_livreur->bindParam(':disponibilite', $disponibilite);
                        $stmt_livreur->bindParam(':numero_vehicule', $numero_vehicule);
                        $stmt_livreur->bindParam(':type_vehicule', $type_vehicule);
                        
                        $stmt_livreur->execute();
                    }
                    
                    $success_message = "L'utilisateur a été ajouté avec succès.";
                    
                    // Rediriger vers la liste des utilisateurs après 2 secondes
                    header("refresh:2;url=all_user.php");
                }
            }
        } catch(PDOException $e) {
            $error_message = "Erreur lors de l'ajout de l'utilisateur: " . $e->getMessage();
        }
    }
    ?>

    <div class="header">
        <div class="header-left">
            <div class="menu-icon dw dw-menu"></div>
            <div class="search-toggle-icon dw dw-search2" data-toggle="header_search"></div>
            <div class="header-search">
                <form>
                    <div class="form-group mb-0">
                        <i class="dw dw-search2 search-icon"></i>
                        <input type="text" class="form-control search-input" placeholder="Rechercher...">
                    </div>
                </form>
            </div>
        </div>
        
        <div class="header-right">
            <div class="user-info-dropdown">
                <div class="dropdown">
                    <a class="dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                        <span class="user-icon">
                            <img src="vendors/images/photo1.jpg" alt="">
                        </span>
                        <span class="user-name">
                            <?php echo isset($_SESSION['user_nom']) ? $_SESSION['user_nom'].' '.$_SESSION['user_prenom'] : 'Administrateur'; ?>
                        </span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
                        <a class="dropdown-item" href="profile.php"><i class="dw dw-user1"></i> Profil</a>
                        <a class="dropdown-item" href="settings.php"><i class="dw dw-settings2"></i> Paramètres</a>
                        <a class="dropdown-item" href="logout.php"><i class="dw dw-logout"></i> Déconnexion</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="left-side-bar">
        <div class="brand-logo">
            <a href="index.php">
                <img src="vendors/images/resto-escoa-logo.png" alt="Logo Restaurant ESCOA" class="dark-logo">
                <img src="vendors/images/resto-escoa-logo-white.png" alt="Logo Restaurant ESCOA" class="light-logo">
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
                        <div class="col-md-6 col-sm-12">
                            <div class="title">
                                <h4>Nouvel Utilisateur</h4>
                            </div>
                            <nav aria-label="breadcrumb" role="navigation">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                                    <li class="breadcrumb-item"><a href="all_user.php">Utilisateurs</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Nouvel Utilisateur</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Erreur!</strong> <?php echo $error_message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Succès!</strong> <?php echo $success_message; ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <!-- Formulaire d'ajout d'utilisateur -->
                <div class="pd-20 card-box mb-30">
                    <div class="clearfix mb-20">
                        <div class="pull-left">
                            <h4 class="text-blue h4">Informations de l'utilisateur</h4>
                            <p class="mb-30">Veuillez remplir tous les champs obligatoires (*)</p>
                        </div>
                    </div>
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Type d'utilisateur <span class="text-danger">*</span></label>
                                    <select class="form-control" name="type_utilisateur" id="type_utilisateur" required>
                                        <option value="">Sélectionner...</option>
                                        <option value="admin">Administrateur</option>
                                        <option value="personnel">Personnel administratif</option>
                                        <option value="professeur">Professeur</option>
                                        <option value="etudiant">Étudiant</option>
                                        <option value="employe">Employé</option>
                                        <option value="livreur">Livreur</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Rôle <span class="text-danger">*</span></label>
                                    <select class="form-control" name="role_id" id="role_id" required>
                                        <option value="">Sélectionner...</option>
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?php echo $role['id']; ?>"><?php echo ucfirst($role['nom']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nom <span class="text-danger">*</span></label>
                                    <input class="form-control" type="text" name="nom" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Prénom <span class="text-danger">*</span></label>
                                    <input class="form-control" type="text" name="prenom" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email <span class="text-danger">*</span></label>
                                    <input class="form-control" type="email" name="email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Mot de passe <span class="text-danger">*</span></label>
                                    <input class="form-control" type="password" name="mot_de_passe" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Téléphone</label>
                                    <input class="form-control" type="tel" name="telephone">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Matricule</label>
                                    <input class="form-control" type="text" name="matricule">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Date de naissance</label>
                                    <input class="form-control date-picker" type="text" name="date_naissance">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Statut <span class="text-danger">*</span></label>
                                    <select class="form-control" name="statut" required>
                                        <option value="actif">Actif</option>
                                        <option value="inactif">Inactif</option>
                                        <option value="suspendu">Suspendu</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Adresse</label>
                            <textarea class="form-control" name="adresse"></textarea>
                        </div>

                        <!-- Informations spécifiques à l'étudiant -->
                        <div id="student_fields" style="display: none;">
                            <hr>
                            <h5 class="text-blue h5">Informations étudiant</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Niveau d'étude</label>
                                        <select class="form-control" name="niveau_etude">
                                            <option value="">Sélectionner...</option>
                                            <option value="Licence 1">Licence 1</option>
                                            <option value="Licence 2">Licence 2</option>
                                            <option value="Licence 3">Licence 3</option>
                                            <option value="Master 1">Master 1</option>
                                            <option value="Master 2">Master 2</option>
                                            <option value="Doctorat">Doctorat</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Filière</label>
                                        <input class="form-control" type="text" name="filiere">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Année académique</label>
                                        <input class="form-control" type="text" name="annee_academique" placeholder="ex: 2024-2025">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Numéro carte étudiant</label>
                                        <input class="form-control" type="text" name="carte_etudiant">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Informations spécifiques à l'employé -->
                        <div id="employee_fields" style="display: none;">
                            <hr>
                            <h5 class="text-blue h5">Informations employé</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Poste</label>
                                        <input class="form-control" type="text" name="poste">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Département</label>
                                        <input class="form-control" type="text" name="departement">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Date d'embauche</label>
                                <input class="form-control date-picker" type="text" name="date_embauche">
                            </div>
                        </div>

                        <!-- Informations spécifiques au livreur -->
                        <div id="delivery_fields" style="display: none;">
                            <hr>
                            <h5 class="text-blue h5">Informations livreur</h5>
                            <div class="form-group">
                                <label>Zone de livraison</label>
                                <textarea class="form-control" name="zone_livraison"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Numéro du véhicule</label>
                                        <input class="form-control" type="text" name="numero_vehicule">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Type de véhicule</label>
                                        <select class="form-control" name="type_vehicule">
                                            <option value="">Sélectionner...</option>
                                            <option value="Moto">Moto</option>
                                            <option value="Vélo">Vélo</option>
                                            <option value="Voiture">Voiture</option>
                                            <option value="Scooter">Scooter</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-checkbox mb-5">
                                    <input type="checkbox" class="custom-control-input" id="disponibilite" name="disponibilite" checked>
                                    <label class="custom-control-label" for="disponibilite">Disponible pour les livraisons</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group text-right">
                            <a href="all_user.php" class="btn btn-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php include "footer.php"; ?>
        </div>
    </div>
    
    <!-- js -->
    <script src="vendors/scripts/core.js"></script>
    <script src="vendors/scripts/script.min.js"></script>
    <script src="vendors/scripts/process.js"></script>
    <script src="vendors/scripts/layout-settings.js"></script>
    <!-- Select2 JS -->
    <script src="vendors/scripts/select2.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialisation de Select2
            $('select').select2();
            
            // Initialisation du datepicker
            $('.date-picker').datepicker({
                language: 'fr',
                autoclose: true,
                todayHighlight: true,
                format: 'yyyy-mm-dd'
            });
            
            // Afficher/masquer les champs supplémentaires en fonction du type d'utilisateur
            $('#type_utilisateur').change(function() {
                var selectedType = $(this).val();
                
                // Masquer tous les champs spécifiques
                $('#student_fields, #employee_fields, #delivery_fields').hide();
                
                // Afficher les champs spécifiques au type sélectionné
                if (selectedType === 'etudiant') {
                    $('#student_fields').show();
                } else if (selectedType === 'employe') {
                    $('#employee_fields').show();
                } else if (selectedType === 'livreur') {
                    $('#delivery_fields').show();
                }
                
                // Mettre à jour les options du rôle en fonction du type d'utilisateur
                updateRoleOptions(selectedType);
            });
            
            // Fonction pour mettre à jour les options du rôle
            function updateRoleOptions(userType) {
                var roleSelect = $('#role_id');
                
                // Masquer toutes les options de rôle d'abord
                roleSelect.find('option').show();
                
                // En fonction du type d'utilisateur, filtrer les rôles pertinents
                switch(userType) {
                    case 'admin':
                        // Montrer seulement les rôles admin
                        roleSelect.find('option').hide();
                        roleSelect.find('option[value="1"], option[value="2"]').show();
                        break;
                    case 'employe':
                        // Montrer les rôles employés (caissier, serveur, cuisinier)
                        roleSelect.find('option').hide();
                        roleSelect.find('option[value="3"], option[value="4"], option[value="5"]').show();
                        break;
                    case 'livreur':
                        // Montrer seulement le rôle livreur
                        roleSelect.find('option').hide();
                        roleSelect.find('option[value="6"]').show();
                        break;
                    case 'personnel':
                        // Montrer seulement le rôle personnel
                        roleSelect.find('option').hide();
                        roleSelect.find('option[value="7"]').show();
                        break;
                    case 'professeur':
                        // Montrer seulement le rôle professeur
                        roleSelect.find('option').hide();
                        roleSelect.find('option[value="8"]').show();
                        break;
                    case 'etudiant':
                        // Montrer seulement le rôle étudiant
                        roleSelect.find('option').hide();
                        roleSelect.find('option[value="9"]').show();
                        break;
                    default:
                        // Montrer toutes les options
                        roleSelect.find('option').show();
                }
                
                // Réinitialiser la sélection
                roleSelect.val('').trigger('change');
            }
        });
    </script>
</body>
</html>