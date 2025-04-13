<?php
$url = 'http://localhost/api.php';
$data = [
    'register' => 'true',
    'name' => 'Test Nom',
    'email' => 'test@email.com',
    'password' => 'testpassword',
    'phone' => '0123456789',
    'address' => 'Adresse test'
];

$options = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);
echo $result;
?>