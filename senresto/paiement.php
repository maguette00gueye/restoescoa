
<?php
session_start();

// Vérifiez si le panier est vide
if (empty($_SESSION['cart'])) {
    die('Le panier est vide.');
}

// Récupérer les informations du panier
$items = $_SESSION['cart'];
$total_amount = 0;
foreach ($items as $item) {
    $total_amount += $item['price'] * $item['quantity'];
}

// Inclure la classe PayTech
include 'PayTech.php';

// Remplacez par vos clés API
$apiKey = '9700a81b5ddda71f2b902764fbe7fd4b0fcad1e63b6614172fd6cb0cf57b7cf2';
$apiSecret = 'dd092fc0feed7d14e6e8b89b3eb839cd976983ca9544887608ae4fed4a38ffbf';

$paytech = new PayTech($apiKey, $apiSecret);
$paytech->setTestMode(true); // Forcer le mode test

$paytech->setQuery([
    'item_name' => 'Commande de restaurant',
    'item_price' => $total_amount,
    'command_name' => 'commande-' . uniqid(),
]);

// Paramétrer la notification de paiement
$paytech->setCurrency('XOF');
$paytech->setRefCommand('commande-' . uniqid());
$paytech->setNotificationUrl([
    'ipn_url' => 'https://votre-site.com/ipn.php',
    'success_url' => 'https://votre-site.com/success.php',
    'cancel_url' => 'https://votre-site.com/cancel.php',
]);

// Demander un paiement
$response = $paytech->send();

if ($response['success'] === 1) {
    header('Location: ' . $response['redirect_url']);
    exit;
} else {
    echo 'Erreur lors de la demande de paiement: ' . implode(', ', $response['errors']);
}
?>




  <!-- mode production -->

<!-- php
session_start();

// Vérifiez si le panier est vide
if (empty($_SESSION['cart'])) {
    die('Le panier est vide.');
}

// Récupérer les informations du panier
$items = $_SESSION['cart'];
$total_amount = 0;
foreach ($items as $item) {
    $total_amount += $item['price'] * $item['quantity'];
}

// Inclure la classe PayTech
include 'PayTech.php';

// Remplacer par vos clés API
$apiKey = 'dc5fb9c2f5c21b6b463287953fa50ef0544bc0d47f234e3380decb07ffff74c0';
$apiSecret = '93bb2f04b317e6cbe08775d359e1fc5a83946ced7cfdad5e4f8ea2d5337f9008';

$paytech = new PayTech($apiKey, $apiSecret);
$paytech->setQuery([
    'item_name' => 'Commande de restaurant',
    'item_price' => $total_amount,
    'command_name' => 'commande-' . uniqid(),
]);

// Paramétrer la notification de paiement
$paytech->setCurrency('XOF');
$paytech->setRefCommand('commande-' . uniqid());
$paytech->setNotificationUrl([
    'ipn_url' => 'https://votre-site.com/ipn.php',
    'success_url' => 'https://votre-site.com/success.php',
    'cancel_url' => 'https://votre-site.com/cancel.php',
]);

// Demander un paiement
$response = $paytech->send();

if ($response['success'] === 1) {
    header('Location: ' . $response['redirect_url']);
    exit;
} else {
    echo 'Erreur lors de la demande de paiement: ' . implode(', ', $response['errors']);
}
?>
 -->