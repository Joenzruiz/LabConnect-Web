<?php
$filePath = 'C:/xampp/htdocs/LabConnect-Web/img/logo_email.jpg';

// Upload to imgbb / freecat / postimages / tmpfiles or Catbox
$ch = curl_init('https://catbox.moe/user/api.php');
$cfile = new CURLFile($filePath, 'image/jpeg', 'logo.jpg');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'reqtype' => 'fileupload',
    'fileToUpload' => $cfile
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
curl_close($ch);

echo "Catbox upload response: " . trim($res) . "\n";
