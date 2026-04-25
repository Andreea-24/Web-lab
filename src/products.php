<?php
require __DIR__ . '/db.php';
$products = $conn->query('SELECT id, slug, name, price, discount, image
                          FROM products ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Flowers</title>
        <link rel="stylesheet" href="../style/products.css">
    </head>
    <body>
         <header>
            <input type="checkbox" name="" id="toggler">
            <label for="toggler" class="fas fa-bars"></label>
         <a href="#" class="logo">flower<span>.</span></a>
           <nav class="navbar ">

                 <a href="/" >Home</a>
                 <a href="/about" >about</a>
                 <a href="#" >products</a>
                 <a href="/recenzii" >recenzii</a>
                 <a href="/contact" >contact</a>
        </nav>

        <div class="icons">
          <a href="#"  class="fas fa-heart" ></a>
          <a href="#"  class="fas fa-shopping-cart" ></a>
          <a href="#"  class="fas fa-user" ></a>
        </div>

    </header>
    <main>
    <section class="products" id="products">
        <h1 class="heading">Produse <span>in stoc</span></h1>
        <div class="box-container">
            <?php foreach ($products as $p): ?>
            <div class="box">
                <div class="image">
                    <img src="../image/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                </div>
                <div class="content">
                    <h3><?= htmlspecialchars($p['name']) ?></h3>
                    <span class="discount">-<?= (int)$p['discount'] ?>%</span>
                    <div class="price"><span><?= number_format((float)$p['price'], 0) ?> lei</span></div>
                    <button class="btn buyBtn" data-product="<?= htmlspecialchars($p['slug']) ?>">Cumpara acum</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    </main>

    <script src="../script.js"></script>
    </body>
</html>
