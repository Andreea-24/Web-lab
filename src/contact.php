<!DOCTYPE html>
<html>
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title>Flowers</title>
        <link rel="stylesheet" href="../style/contact.css">
    </head>
    <body>
        <header>
            <input type="checkbox" name="" id="toggler">
            <label for="toggler" class="fas fa-bars"></label>
         <a href="#" class="logo">flower<span>.</span></a>
           <nav class="navbar ">

                 <a href="/" >Home</a>
                 <a href="/about" >about</a>
                 <a href="/products" >products</a>
                 <a href="/recenzii" >recenzii</a>
                 <a href="#" >contact</a>
        </nav>

        <div class="icons">
          <a href="#"  class="fas fa-heart" ></a>
          <a href="#"  class="fas fa-shopping-cart" ></a>
          <a href="#"  class="fas fa-user" ></a>
        </div>


        </header>
        <section class="contact" id="contact">
            <h1 class="heading"><span>Contacteaza</span>ne</h1>
            <div class="row">

        <form id="orderForm" action="/save-order" method="POST">
            <div class="produs-info">
                Buchet selectat: <strong id="numeProdus">—</strong>
            </div>

            <input type="hidden" id="produs" name="produs">

            <input type="text"  name="nume"    placeholder="nume, prenume" class="box">
            <input type="email" name="email"   placeholder="email"         class="box">
            <input type="tel"   name="telefon" placeholder="telefon"       class="box">
            <textarea           name="message" placeholder="message" cols="30" rows="10" class="box"></textarea>
            <input type="submit" value="trimite mesajul" class="btn">
        </form>
        <div class="image">
            <img src="../image/003e5cfa781a5559ce8513abf5cdd1e3.jpg">
        </div>

        </div>
        </section>
<script src="../script.js"></script>
    </body>
</html>
