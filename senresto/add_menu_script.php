<?php
// Connexion à la base de données (ajustez avec vos paramètres de connexion)
$servername = "localhost"; // Remplacez par votre serveur
$username = "root";        // Remplacez par votre utilisateur
$password = "";            // Remplacez par votre mot de passe
$dbname = "restaurant";    // Remplacez par le nom de votre base de données

$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupération des données du formulaire
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    
    // Traitement de l'image
    $image = $_FILES['image']['name'];
    $imageTmp = $_FILES['image']['tmp_name'];
    $imagePath = "src/images/" . basename($image);
    
    // Déplacer l'image téléchargée dans le dossier de stockage
    if (move_uploaded_file($imageTmp, $imagePath)) {
        echo "L'image a été téléchargée avec succès.";
    } else {
        echo "Erreur lors du téléchargement de l'image.";
    }
    
    // Insertion des données dans la base de données
    $sql = "INSERT INTO menu (name, description, price, image) VALUES ('$name', '$description', '$price', '$image')";
    
    if ($conn->query($sql) === TRUE) {
        echo "Le plat a été ajouté avec succès au menu!";
    } else {
        echo "Erreur: " . $sql . "<br>" . $conn->error;
    }

    // Fermeture de la connexion
    $conn->close();
}
?>
