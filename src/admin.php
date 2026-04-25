<?php
// HTTP Basic Auth — user: admin, parola: admin
if (!isset($_SERVER['PHP_AUTH_USER']) ||
    $_SERVER['PHP_AUTH_USER'] !== 'admin' ||
    $_SERVER['PHP_AUTH_PW']   !== 'admin') {
    header('WWW-Authenticate: Basic realm="Zona Admin"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<h1 style="font-family:sans-serif;text-align:center;padding:4rem;">401 &mdash; Autentificare necesara</h1>';
    exit;
}

require __DIR__ . '/db.php';

$orders = $conn->query('SELECT id, nume, email, telefon, message, produs_slug, created_at
                        FROM orders ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <title>Admin · Comenzi</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: Verdana, Geneva, Tahoma, sans-serif; }
    body { padding: 3rem; background: #f6f6f6; color: #333; }
    h1 { margin-bottom: 1.5rem; font-size: 2.2rem; }
    .nav { margin-bottom: 2rem; }
    .nav a { margin-right: 1rem; color: #e84393; text-decoration: none; font-size: 1.1rem; }
    .nav a:hover { text-decoration: underline; }
    table { width: 100%; border-collapse: collapse; background: #fff;
            box-shadow: 0 .2rem .8rem rgba(0,0,0,.08); border-radius: .4rem; overflow: hidden; }
    th, td { padding: .9rem 1rem; border-bottom: 1px solid #eee;
             text-align: left; font-size: 1rem; vertical-align: top; }
    th { background: #e84393; color: #fff; font-weight: 600; }
    tr:hover td { background: #fafafa; }
    .empty { padding: 2rem; text-align: center; color: #999; font-size: 1.1rem;
             background: #fff; border-radius: .4rem; box-shadow: 0 .2rem .8rem rgba(0,0,0,.05); }
    .id { color: #999; font-family: monospace; }
    .msg { max-width: 30rem; }
  </style>
</head>
<body>
  <div class="nav">
    <a href="/">&larr; Acasă</a>
    <a href="/products">Produse</a>
    <a href="/contact">Contact</a>
  </div>
  <h1>Comenzi primite (<?= count($orders) ?>)</h1>

  <?php if (!$orders): ?>
    <div class="empty">Nicio comandă încă. Trimite una de pe pagina <a href="/contact">contact</a>.</div>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Data</th>
          <th>Nume</th>
          <th>Email</th>
          <th>Telefon</th>
          <th>Produs</th>
          <th>Mesaj</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td class="id"><?= (int)$o['id'] ?></td>
            <td><?= htmlspecialchars($o['created_at']) ?></td>
            <td><?= htmlspecialchars($o['nume']) ?></td>
            <td><?= htmlspecialchars($o['email']) ?></td>
            <td><?= htmlspecialchars($o['telefon']) ?></td>
            <td><?= htmlspecialchars($o['produs_slug'] ?? '—') ?></td>
            <td class="msg"><?= nl2br(htmlspecialchars($o['message'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</body>
</html>
