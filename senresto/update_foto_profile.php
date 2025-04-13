<?php
// Fonction pour gérer le téléchargement de l'image de profil
function upload_profile_image($user_id) {
    // Définir le répertoire de destination
    $target_dir = "uploads/profile_pictures/";
    
    // Créer le répertoire s'il n'existe pas
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Générer un nom de fichier unique basé sur l'ID utilisateur
    $file_extension = pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION);
    $new_filename = "user_" . $user_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Vérifier si le fichier est une image réelle
    $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
    if($check === false) {
        return ["success" => false, "message" => "Le fichier n'est pas une image."];
    }
    
    // Vérifier la taille du fichier (limite à 5MB)
    if ($_FILES["profile_image"]["size"] > 5000000) {
        return ["success" => false, "message" => "Le fichier est trop volumineux. Maximum 5MB."];
    }
    
    // Autoriser certains formats de fichier
    $allowed_types = ["jpg", "jpeg", "png", "gif"];
    if (!in_array(strtolower($file_extension), $allowed_types)) {
        return ["success" => false, "message" => "Seuls les fichiers JPG, JPEG, PNG et GIF sont autorisés."];
    }
    
    // Essayer de télécharger le fichier
    if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
        // Mettre à jour le chemin de l'image dans la base de données
        global $conn;
        
        try {
            $sql = "UPDATE utilisateurs SET photo_profil = :photo_path, updated_at = NOW() WHERE id = :id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':photo_path', $target_file);
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
            
            return ["success" => true, "message" => "Photo de profil mise à jour avec succès.", "file_path" => $target_file];
        } catch(PDOException $e) {
            return ["success" => false, "message" => "Erreur lors de la mise à jour de la base de données: " . $e->getMessage()];
        }
    } else {
        return ["success" => false, "message" => "Une erreur s'est produite lors du téléchargement de votre fichier."];
    }
}

// Traiter le téléchargement si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_profile_image']) && isset($_FILES["profile_image"])) {
    $upload_result = upload_profile_image($user_id);
    
    if ($upload_result["success"]) {
        $success_message = $upload_result["message"];
        // Mettre à jour la variable de session si nécessaire
        $_SESSION['user_photo'] = $upload_result["file_path"];
    } else {
        $error_message = $upload_result["message"];
    }
}
?>

<!-- Ajouter ceci dans la section modale -->
<div class="modal fade" id="modal-change-photo" tabindex="-1" role="dialog" aria-labelledby="modalChangePhotoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalChangePhotoLabel">Changer la photo de profil</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Sélectionner une nouvelle photo</label>
                        <input type="file" class="form-control-file" name="profile_image" id="profile_image" accept="image/*" required>
                        <small class="form-text text-muted">Formats autorisés: JPG, JPEG, PNG, GIF. Taille maximale: 5MB</small>
                    </div>
                    <div class="image-preview mt-3" id="imagePreview">
                        <img src="#" alt="Aperçu de l'image" class="img-thumbnail" style="max-width: 200px; max-height: 200px; display: none;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annuler</button>
                    <button type="submit" name="upload_profile_image" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modifier la section de l'avatar pour utiliser la nouvelle photo si disponible -->
<div class="profile-photo">
    <a href="javascript:;" class="edit-avatar" title="Changer de photo de profil"><i class="fa fa-pencil"></i></a>
    <?php if (isset($user['photo_profil']) && !empty($user['photo_profil']) && file_exists($user['photo_profil'])): ?>
        <img src="<?php echo $user['photo_profil']; ?>" alt="Photo de profil" class="avatar-photo">
    <?php else: ?>
        <img src="vendors/images/<?php echo (isset($user['sexe']) && $user['sexe'] == 'F') ? 'femme.png' : 'homme.png'; ?>" alt="Avatar par défaut" class="avatar-photo">
    <?php endif; ?>
</div>

<!-- Ajouter ce script JavaScript pour l'aperçu de l'image -->
<script>
$(document).ready(function() {
    // Ouvrir le modal quand on clique sur edit-avatar
    $('.edit-avatar').on('click', function() {
        $('#modal-change-photo').modal('show');
    });
    
    // Prévisualisation de l'image
    $("#profile_image").change(function() {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#imagePreview img').attr('src', e.target.result);
            $('#imagePreview img').css('display', 'block');
        }
        reader.readAsDataURL(this.files[0]);
    });
});
</script>