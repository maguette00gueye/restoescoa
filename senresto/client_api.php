<?php
$url = "http://localhost/myDashboard/senresto/client_api.php";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    die("Erreur cURL : " . curl_error($ch));
}
curl_close($ch);

$clients = json_decode($response, true);

// Vérifier si le JSON est bien décodé
if (!is_array($clients)) {
    die("Erreur de décodage JSON : " . $response);
}

// Afficher les clients
foreach ($clients as $client) {
    echo "Nom: " . $client['name'] . "<br>";
    echo "Âge: " . $client['age'] . "<br><br>";
}
?>
