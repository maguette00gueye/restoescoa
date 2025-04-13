<?php
require_once('../config/db_connect.php');

if(isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    // Récupérer l'information de la photo avant la suppression
    $query = "SELECT photo FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);
    
    // Supprimer l'utilisateur
    $query = "DELETE FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if(mysqli_stmt_execute($stmt)) {
        // Supprimer la photo si elle existe
        if($user['photo'] && file_exists("../uploads/users/".$user['photo'])) {
            unlink("../uploads/users/".$user['photo']);
        }
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>