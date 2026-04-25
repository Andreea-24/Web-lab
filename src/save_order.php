<?php
require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /contact');
    exit;
}

$nume    = trim($_POST['nume']    ?? '');
$email   = trim($_POST['email']   ?? '');
$telefon = trim($_POST['telefon'] ?? '');
$message = trim($_POST['message'] ?? '');
$produs  = trim($_POST['produs']  ?? '');

$errors = [];
if ($nume === '')                                 $errors[] = 'Numele este obligatoriu.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))   $errors[] = 'Adresa de email nu este validă.';
if (!preg_match('/^\+?[0-9]{9,15}$/', $telefon))  $errors[] = 'Numărul de telefon nu este valid.';
if ($message === '')                              $errors[] = 'Mesajul este obligatoriu.';

if ($errors) {
    http_response_code(400);
    echo '<!DOCTYPE html><meta charset="UTF-8"><h1>Eroare la trimitere</h1><ul>';
    foreach ($errors as $e) echo '<li>' . htmlspecialchars($e) . '</li>';
    echo '</ul><a href="/contact">Înapoi la formular</a>';
    exit;
}

$stmt = $conn->prepare(
    'INSERT INTO orders (nume, email, telefon, message, produs_slug)
     VALUES (:nume, :email, :telefon, :message, :produs)'
);
$stmt->execute([
    ':nume'    => $nume,
    ':email'   => $email,
    ':telefon' => $telefon,
    ':message' => $message,
    ':produs'  => $produs !== '' ? $produs : null,
]);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <title>Comandă plasată</title>
  <link rel="stylesheet" href="../style/contact.css">
</head>
<body style="text-align:center; padding:6rem 2rem; font-family: Verdana, sans-serif;">
  <h1 style="font-size:3rem; color:#333;">Mulțumim, <?= htmlspecialchars($nume) ?>!</h1>
  <p style="font-size:1.6rem; color:#555; margin-top:1rem;">
    Comanda ta a fost înregistrată<?= $produs !== '' ? ' pentru &laquo;' . htmlspecialchars(str_replace('-', ' ', $produs)) . '&raquo;' : '' ?>.
  </p>
  <p style="font-size:1.4rem; color:#777; margin-top:.5rem;">
    Te vom contacta la <strong><?= htmlspecialchars($email) ?></strong>.
  </p>
  <p style="margin-top:2.5rem;">
    <a href="/" style="background:#e84393;color:#fff;padding:.9rem 3rem;border-radius:5rem;text-decoration:none;font-size:1.4rem;">Înapoi acasă</a>
  </p>
</body>
</html>
