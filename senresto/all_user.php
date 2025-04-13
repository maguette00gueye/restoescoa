<?php
// Démarrer la session
session_start();

// Inclure la connexion à la base de données
include "config/database.php";


// Vérifier les permissions (seuls les administrateurs peuvent accéder à cette page)
if ($_SESSION['user_role'] != 'super_admin' && $_SESSION['user_role'] != 'admin_resto') {
    // Rediriger vers la page d'accès refusé
    header("Location: access_denied.php");
    exit();
}

// Traitement de la suppression d'un utilisateur
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Supprimer les données liées dans les tables de détails
        $type_tables = [
            'etudiant' => 'etudiants_details',
            'employe' => 'employes_details',
            'livreur' => 'livreurs_details'
        ];
        
        // Récupérer le type d'utilisateur
        $sql_type = "SELECT type_utilisateur FROM utilisateurs WHERE id = :id";
        $stmt_type = $conn->prepare($sql_type);
        $stmt_type->bindParam(':id', $id);
        $stmt_type->execute();
        $user_type = $stmt_type->fetch(PDO::FETCH_ASSOC);
        
        // Supprimer les données dans la table de détails correspondante si elle existe
        if ($user_type && isset($type_tables[$user_type['type_utilisateur']])) {
            $table = $type_tables[$user_type['type_utilisateur']];
            $sql_delete_details = "DELETE FROM $table WHERE utilisateur_id = :id";
            $stmt_delete_details = $conn->prepare($sql_delete_details);
            $stmt_delete_details->bindParam(':id', $id);
            $stmt_delete_details->execute();
        }
        
        // Supprimer l'utilisateur
        $sql_delete = "DELETE FROM utilisateurs WHERE id = :id";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bindParam(':id', $id);
        $stmt_delete->execute();
        
        $success_message = "L'utilisateur a été supprimé avec succès.";
    } catch(PDOException $e) {
        $error_message = "Erreur lors de la suppression: " . $e->getMessage();
    }
}

// Récupération des filtres
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Construction de la requête avec les filtres
$sql = "SELECT u.*, r.nom as role_nom 
        FROM utilisateurs u 
        LEFT JOIN roles r ON u.role_id = r.id 
        WHERE 1=1";

$params = [];

if (!empty($role_filter)) {
    $sql .= " AND r.nom = :role";
    $params[':role'] = $role_filter;
}

if (!empty($type_filter)) {
    $sql .= " AND u.type_utilisateur = :type";
    $params[':type'] = $type_filter;
}

if (!empty($status_filter)) {
    $sql .= " AND u.statut = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $sql .= " AND (u.nom LIKE :search OR u.prenom LIKE :search OR u.email LIKE :search OR u.matricule LIKE :search)";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY u.created_at DESC";

$stmt = $conn->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des rôles pour le filtre
$sql_roles = "SELECT * FROM roles ORDER BY nom";
$stmt_roles = $conn->prepare($sql_roles);
$stmt_roles->execute();
$roles = $stmt_roles->fetchAll(PDO::FETCH_ASSOC);

// Comptage des utilisateurs par type
$stats = [
    'total' => 0,
    'etudiant' => 0,
    'employe' => 0,
    'personnel_professeur' => 0
];

$sql_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN type_utilisateur = 'etudiant' THEN 1 ELSE 0 END) as etudiant,
    SUM(CASE WHEN type_utilisateur = 'employe' THEN 1 ELSE 0 END) as employe,
    SUM(CASE WHEN type_utilisateur IN ('personnel', 'professeur') THEN 1 ELSE 0 END) as personnel_professeur
FROM utilisateurs";

$stmt_stats = $conn->prepare($sql_stats);
$stmt_stats->execute();
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <?php include "head.php"; ?>
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="vendors/styles/datatable-custom.css">
</head>
<body>
    <?php include "chargement.php"; ?>

    <div class="header">
        <div class="header-left">
            <div class="menu-icon dw dw-menu"></div>
            <div class="search-toggle-icon dw dw-search2" data-toggle="header_search"></div>
            <div class="header-search">
                <form action="all_user.php" method="GET">
                    <div class="form-group mb-0">
                        <i class="dw dw-search2 search-icon"></i>
                        <input type="text" class="form-control search-input" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="header-right">
            <div class="user-info-dropdown">
                <div class="dropdown">
                    <a class="dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                        <span class="user-icon">
                            <img src="vendors/images/femme.png" alt="">
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
                        <div class="col-md-6 col-sm-12">
                            <div class="title">
                                <h4>Gestion des Utilisateurs</h4>
                            </div>
                            <nav aria-label="breadcrumb" role="navigation">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Liste des utilisateurs</li>
                                </ol>
                            </nav>
                        </div>
                        <div class="col-md-6 col-sm-12 text-right">
                            <a href="new_user.php" class="btn btn-primary btn-lg"><i class="icon-copy dw dw-add"></i> Ajouter un utilisateur</a>
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

                <!-- Cards statistiques -->
                <div class="row pb-10">
                    <div class="col-xl-3 col-lg-3 col-md-6 mb-20">
                        <div class="card-box height-100-p widget-style3">
                            <div class="d-flex flex-wrap">
                                <div class="widget-data">
                                    <div class="weight-700 font-24 text-dark"><?php echo $stats['total']; ?></div>
                                    <div class="font-14 text-secondary weight-500">Total utilisateurs</div>
                                </div>
                                <div class="widget-icon">
                                    <div class="icon" data-color="#00eccf"><i class="icon-copy dw dw-user-2"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-3 col-md-6 mb-20">
                        <div class="card-box height-100-p widget-style3">
                            <div class="d-flex flex-wrap">
                                <div class="widget-data">
                                    <div class="weight-700 font-24 text-dark"><?php echo $stats['etudiant']; ?></div>
                                    <div class="font-14 text-secondary weight-500">Étudiants</div>
                                </div>
                                <div class="widget-icon">
                                    <div class="icon" data-color="#ff5b5b"><i class="icon-copy dw dw-graduated"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-3 col-md-6 mb-20">
                        <div class="card-box height-100-p widget-style3">
                            <div class="d-flex flex-wrap">
                                <div class="widget-data">
                                    <div class="weight-700 font-24 text-dark"><?php echo $stats['employe']; ?></div>
                                    <div class="font-14 text-secondary weight-500">Employés</div>
                                </div>
                                <div class="widget-icon">
                                    <div class="icon"><i class="icon-copy dw dw-user1"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-3 col-md-6 mb-20">
                        <div class="card-box height-100-p widget-style3">
                            <div class="d-flex flex-wrap">
                                <div class="widget-data">
                                    <div class="weight-700 font-24 text-dark"><?php echo $stats['personnel_professeur']; ?></div>
                                    <div class="font-14 text-secondary weight-500">Personnel & Professeurs</div>
                                </div>
                                <div class="widget-icon">
                                    <div class="icon" data-color="#09cc06"><i class="icon-copy dw dw-user-12"></i></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="pd-20 card-box mb-20">
                    <div class="clearfix mb-10">
                        <div class="pull-left">
                            <h4 class="text-blue h4">Filtres</h4>
                        </div>
                        <div class="pull-right">
                            <a href="all_user.php" class="btn btn-sm btn-outline-secondary"><i class="fa fa-refresh"></i> Réinitialiser</a>
                        </div>
                    </div>
                    <form action="all_user.php" method="GET">
                        <div class="row">
                            <div class="col-md-3 col-sm-6">
                                <div class="form-group">
                                    <label>Rôle</label>
                                    <select class="form-control" name="role" id="role-filter">
                                        <option value="">Tous les rôles</option>
                                        <?php foreach ($roles as $role): ?>
                                            <option value="<?php echo $role['nom']; ?>" <?php echo ($role_filter == $role['nom']) ? 'selected' : ''; ?>>
                                                <?php echo ucfirst($role['nom']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="form-group">
                                    <label>Type d'utilisateur</label>
                                    <select class="form-control" name="type" id="type-filter">
                                        <option value="">Tous les types</option>
                                        <option value="admin" <?php echo ($type_filter == 'admin') ? 'selected' : ''; ?>>Administrateurs</option>
                                        <option value="etudiant" <?php echo ($type_filter == 'etudiant') ? 'selected' : ''; ?>>Étudiants</option>
                                        <option value="personnel" <?php echo ($type_filter == 'personnel') ? 'selected' : ''; ?>>Personnel</option>
                                        <option value="professeur" <?php echo ($type_filter == 'professeur') ? 'selected' : ''; ?>>Professeurs</option>
                                        <option value="employe" <?php echo ($type_filter == 'employe') ? 'selected' : ''; ?>>Employés</option>
                                        <option value="livreur" <?php echo ($type_filter == 'livreur') ? 'selected' : ''; ?>>Livreurs</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <div class="form-group">
                                    <label>Statut</label>
                                    <select class="form-control" name="status" id="status-filter">
                                        <option value="">Tous les statuts</option>
                                        <option value="actif" <?php echo ($status_filter == 'actif') ? 'selected' : ''; ?>>Actifs</option>
                                        <option value="inactif" <?php echo ($status_filter == 'inactif') ? 'selected' : ''; ?>>Inactifs</option>
                                        <option value="suspendu" <?php echo ($status_filter == 'suspendu') ? 'selected' : ''; ?>>Suspendus</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 d-flex align-items-end">
                                <div class="form-group mb-0 w-100">
                                    <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-filter"></i> Filtrer</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Table des utilisateurs -->
                <div class="card-box mb-30">
                    <div class="pd-20">
                        <h4 class="text-blue h4">Liste des utilisateurs <?php echo !empty($search) ? "- Recherche: \"$search\"" : ""; ?></h4>
                    </div>
                    <div class="pb-20">
                        <table class="data-table table stripe hover nowrap">
                            <thead>
                                <tr>
                                    <th class="table-plus">ID</th>
                                    <th>Nom complet</th>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Type</th>
                                    <th>Matricule</th>
                                    <th>Statut</th>
                                    <th>Date création</th>
                                    <th class="datatable-nosort">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($utilisateurs as $user): ?>
                                <tr>
                                    <td class="table-plus"><?php echo $user['id']; ?></td>
                                    <td>
                                        <div class="name-avatar d-flex align-items-center">
                                            <div class="avatar mr-2 flex-shrink-0">
                                                <div class="bg-<?php
                                                    $colors = ['primary', 'success', 'warning', 'danger', 'info', 'secondary'];
                                                    echo $colors[array_rand($colors)];
                                                ?> rounded-circle text-white d-flex align-items-center justify-content-center">
                                                    <?php echo strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)); ?>
                                                </div>
                                            </div>
                                            <div class="txt">
                                                <div class="weight-600"><?php echo $user['nom'] . ' ' . $user['prenom']; ?></div>
                                                <small><?php echo !empty($user['telephone']) ? $user['telephone'] : 'Non renseigné'; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td><?php echo !empty($user['role_nom']) ? ucfirst($user['role_nom']) : 'Non défini'; ?></td>
                                    <td>
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
                                    </td>
                                    <td><?php echo !empty($user['matricule']) ? $user['matricule'] : 'Non défini'; ?></td>
                                    <td>
                                        <?php if($user['statut'] == 'actif'): ?>
                                            <span class="badge badge-success">Actif</span>
                                        <?php elseif($user['statut'] == 'inactif'): ?>
                                            <span class="badge badge-secondary">Inactif</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Suspendu</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="dropdown">
                                            <a class="btn btn-link font-24 p-0 line-height-1 no-arrow dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                                                <i class="dw dw-more"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right dropdown-menu-icon-list">
                                                <a class="dropdown-item" href="view_user.php?id=<?php echo $user['id']; ?>"><i class="dw dw-eye"></i> Voir</a>
                                                <a class="dropdown-item" href="edit_user.php?id=<?php echo $user['id']; ?>"><i class="dw dw-edit2"></i> Modifier</a>
                                                <a class="dropdown-item delete-user" href="javascript:;" data-id="<?php echo $user['id']; ?>" data-name="<?php echo $user['nom'] . ' ' . $user['prenom']; ?>"><i class="dw dw-delete-3"></i> Supprimer</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="footer-wrap pd-20 mb-20 card-box">
                &copy; <?php echo date('Y'); ?> Restaurant ESCOA - Tous droits réservés
            </div>
        </div>
    </div>
    
    <!-- Modal de confirmation de suppression -->
    <div class="modal fade" id="confirmation-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body text-center font-18">
                    <h4 class="padding-top-30 mb-30 weight-500" id="confirmation-message">Êtes-vous sûr de vouloir supprimer cet utilisateur?</h4>
                    <div class="padding-bottom-30 row" style="max-width: 170px; margin: 0 auto;">
                        <div class="col-6">
                            <button type="button" class="btn btn-secondary border-radius-100 btn-block confirmation-btn" data-dismiss="modal"><i class="fa fa-times"></i></button>
                            Non
                        </div>
                        <div class="col-6">
                            <a href="" id="delete-link" class="btn btn-danger border-radius-100 btn-block confirmation-btn"><i class="fa fa-check"></i></a>
                            Oui
                        </div>
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
    <script src="vendors/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="vendors/plugins/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="vendors/plugins/datatables/dataTables.responsive.min.js"></script>
    <script src="vendors/plugins/datatables/responsive.bootstrap4.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialisation de DataTables
            $('.data-table').DataTable({
                scrollCollapse: true,
                autoWidth: false,
                responsive: true,
                columnDefs: [{
                    targets: "datatable-nosort",
                    orderable: false,
                }],
                "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "Tous"]],
                "language": {
                    "info": "_START_-_END_ sur _TOTAL_ entrées",
                    search: "Rechercher:",
                    searchPlaceholder: "Rechercher",
                    paginate: {
                        next: '<i class="ion-chevron-right"></i>',
                        previous: '<i class="ion-chevron-left"></i>'  
                    },
                    "lengthMenu": "Afficher _MENU_ entrées",
                    "zeroRecords": "Aucun résultat trouvé",
                    "emptyTable": "Aucune donnée disponible",
                    "infoEmpty": "Affichage de 0 à 0 sur 0 entrées",
                    "infoFiltered": "(filtrées depuis un total de _MAX_ entrées)"
                },
            });
            
            // Confirmation de suppression
            $('.delete-user').on('click', function() {
                var userId = $(this).data('id');
                var userName = $(this).data('name');
                $('#confirmation-message').text('Êtes-vous sûr de vouloir supprimer l\'utilisateur "' + userName + '" ?');
                $('#delete-link').attr('href', 'all_user.php?delete=' + userId);
                $('#confirmation-modal').modal('show');
            });
            
            // Animation des avatars
            $('.avatar').hover(
                function() {
                    $(this).addClass('shadow-lg').css('transform', 'scale(1.05)');
                },
                function() {
                    $(this).removeClass('shadow-lg').css('transform', 'scale(1)');
                }
            );
        });
    </script>
</body>
</html>