# Ghid complet — Site Flori cu Docker + PHP + MySQL

Ghid linie-cu-linie pentru toată infrastructura: cum pornește Docker, cum se conectează PHP la MySQL, cum se creează tabelele, cum trec datele din formular în baza de date și cum le scoatem înapoi pe pagina de admin.

---

## 1. Cum pornești totul

```bash
# prima dată (build + start)
docker compose up -d --build

# pentru următoarele porniri (fără rebuild)
docker compose up -d

# oprire
docker compose down

# vezi log-urile
docker compose logs -f web
docker compose logs -f db
```

URL-uri:

| Ce | URL | Detalii |
|---|---|---|
| Site | http://localhost:8000/ | Pagina principală (`index.php`) |
| Admin comenzi | http://localhost:8000/src/admin.php | Listează toate comenzile |
| phpMyAdmin | http://localhost:8080/ | user `root`, parola `rootpass` |
| MySQL direct | localhost:3306 | user `user` / pass `userpass` / db `site_db` |

**Important:** dacă ai pornit înainte container-ul `db` și acum modifici `init.sql`, MySQL **NU** mai rulează scriptul (rulează doar la prima inițializare, când dosarul de date e gol). Ca să forțezi re-inițializarea:

```bash
docker compose down
rm -rf db_data/
docker compose up -d --build
```

`db_data/` e datele MySQL pe disk — nu le pune în git.

---

## 2. Structura proiectului

```
Web-lab/
├── Dockerfile              # rețeta containerului PHP+Apache
├── docker-compose.yml      # orchestrează 3 containere (web, db, phpmyadmin)
├── init.sql                # schema DB + date demo (rulează doar la prima pornire)
├── index.php               # homepage
├── script.js               # JS client (validare formular + redirect)
├── style/                  # CSS
├── image/                  # imagini produse
└── src/
    ├── db.php              # conexiunea PDO la MySQL
    ├── about.php
    ├── products.php        # listează produsele DIN DB
    ├── contact.php         # formular comandă (POST → save_order.php)
    ├── save_order.php      # primește POST, validează, INSERT în orders
    ├── recenzii.php
    └── admin.php           # SELECT toate comenzile
```

---

## 3. Dockerfile — explicat linie cu linie

```dockerfile
FROM php:8.2-apache
```
Pornim de la o imagine oficială care conține deja **PHP 8.2 + Apache** preconfigurat. Nu trebuie să instalăm noi nici Apache, nici PHP — sunt deja setate să meargă împreună.

```dockerfile
RUN docker-php-ext-install pdo pdo_mysql mysqli
```
- `RUN` rulează o comandă în timpul build-ului (nu la rulare).
- `docker-php-ext-install` e un script al imaginii oficiale care **instalează extensii PHP**.
- `pdo` = PHP Data Objects (interfața generică de baze de date)
- `pdo_mysql` = driver-ul PDO pentru MySQL (asta folosim noi în [src/db.php](src/db.php))
- `mysqli` = alt driver pentru MySQL (procedural). Îl includem în caz că vrei să exersezi și varianta procedurală.

```dockerfile
RUN a2enmod rewrite
```
Activează modulul Apache `mod_rewrite`. E util când vrei URL-uri "frumoase" (ex. `/produs/buchet-trandafiri` în loc de `?id=3`). Nu e folosit acum, dar e bine să fie pornit.

```dockerfile
COPY . /var/www/html/
```
- Copiază **tot conținutul** proiectului în `/var/www/html/` (rădăcina serverului Apache).
- Ce e în acest dosar e ce serveste browserul când deschizi `http://localhost:8000/`.
- Atenție: `docker-compose.yml` definește un `volume` care **rescrie** acest dosar cu folder-ul tău local — deci modificările pe care le faci în VSCode apar imediat fără rebuild.

```dockerfile
RUN chown -R www-data:www-data /var/www/html
```
Setează ca utilizatorul `www-data` (cel sub care rulează Apache) să fie proprietarul fișierelor. Fără asta, Apache poate avea probleme de permisiuni să citească/scrie.

---

## 4. docker-compose.yml — explicat linie cu linie

`docker-compose.yml` definește **mai multe containere** care lucrează împreună. Avem 3:

### Serviciul `web` (PHP + Apache)

```yaml
services:
  web:
    build: .
```
- `build: .` zice "construiește containerul folosind `Dockerfile` din directorul curent".

```yaml
    container_name: site_web
```
Numele containerului (cum apare în `docker ps`).

```yaml
    ports:
      - "8000:80"
```
- Mapează portul **80 din container** (unde ascultă Apache) la portul **8000 de pe mașina ta**.
- De aceea accesezi `http://localhost:8000`, nu `:80`.

```yaml
    volumes:
      - ./:/var/www/html
```
Cel mai important rând. Înseamnă: "**dosarul curent de pe Windows** (`./`) e legat la `/var/www/html` din container".
- Modifici `index.php` în VSCode → schimbarea apare imediat la următorul refresh.
- Fără volume ar trebui rebuild la fiecare modificare.

```yaml
    depends_on:
      - db
```
Așteaptă ca serviciul `db` să fi pornit înainte ca `web` să pornească. (Nu așteaptă ca MySQL să fie *gata*, doar ca containerul să existe — pentru "gata" ai nevoie de healthcheck.)

```yaml
    restart: unless-stopped
```
Dacă containerul moare (ex. crash), se repornește automat. Dacă tu îl oprești manual, rămâne oprit.

### Serviciul `db` (MySQL)

```yaml
  db:
    image: mysql:8.0
```
Folosim imaginea oficială MySQL 8.0 (nu mai facem `build`, e gata).

```yaml
    environment:
      MYSQL_ROOT_PASSWORD: rootpass
      MYSQL_DATABASE: site_db
      MYSQL_USER: user
      MYSQL_PASSWORD: userpass
```
Variabile pe care imaginea MySQL le citește la prima pornire:
- creează utilizatorul `root` cu parola `rootpass`
- creează automat baza de date `site_db`
- creează utilizatorul `user` cu parola `userpass` care are acces la `site_db`

Acestea **rulează doar la prima inițializare** (când dosarul de date e gol). Dacă schimbi parola aici după ce ai pornit deja, nu se aplică decât după `rm -rf db_data/`.

```yaml
    ports:
      - "3306:3306"
```
Permite conectarea la MySQL din afara containerului (ex. cu MySQL Workbench pe Windows).

```yaml
    volumes:
      - ./db_data:/var/lib/mysql
      - ./init.sql:/docker-entrypoint-initdb.d/init.sql:ro
```
- **Primul volume:** păstrează datele MySQL pe disk în `./db_data/`. Fără el, dacă oprești containerul, pierzi tot. Cu el, datele supraviețuiesc.
- **Al doilea volume:** orice fișier `.sql` montat în `/docker-entrypoint-initdb.d/` e **rulat automat** la prima inițializare. Așa creăm tabelele fără să intrăm manual în MySQL. `:ro` = read-only.

### Serviciul `phpmyadmin` (interfață web pentru DB)

```yaml
  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    ports:
      - "8080:80"
    environment:
      PMA_HOST: db
      PMA_USER: root
      PMA_PASSWORD: rootpass
```
- `PMA_HOST: db` — se conectează la serviciul `db` (Docker creează DNS intern, "db" rezolvă la IP-ul containerului MySQL).
- Accesezi `http://localhost:8080` și vezi DB-ul în interfață grafică.

---

## 5. Conexiunea PHP → MySQL: [src/db.php](src/db.php)

```php
<?php
$host = 'db';
$user = 'user';
$pass = 'userpass';
$dbname = 'site_db';
```
Variabile cu credențialele.

**De ce `'db'` și nu `'localhost'`?** Pentru că PHP rulează într-un container, iar MySQL în alt container. În rețeaua Docker, containerele se cunosc între ele după **numele serviciului** din `docker-compose.yml`. `db` e numele serviciului MySQL.

```php
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
```
- `new PDO(...)` deschide o conexiune.
- String-ul de tip "DSN" (`Data Source Name`) îi spune PDO: tip `mysql`, host `db`, baza `site_db`, encoding `utf8mb4` (suportă emoji + diacritice).
- `try` prinde erori (dacă DB-ul nu răspunde).

```php
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
```
Spune lui PDO: "dacă apare orice problemă, aruncă o **excepție**" (în loc să eșueze silentios). Bun pentru debugging.

```php
} catch (PDOException $e) {
    die("Eroare: " . $e->getMessage());
}
```
Dacă conectarea eșuează, oprește scriptul și afișează mesajul.

`$conn` rămâne disponibil pentru orice fișier care face `require __DIR__ . '/db.php';`.

---

## 6. Schema DB: [init.sql](init.sql)

```sql
SET NAMES utf8mb4;
```
Setează encoding-ul conexiunii curente la utf-8 (pentru diacritice).

### Tabela `products`

```sql
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    discount INT NOT NULL DEFAULT 0,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

| Coloană | Tip | Sens |
|---|---|---|
| `id` | `INT AUTO_INCREMENT PRIMARY KEY` | identificator unic, generat automat (1, 2, 3, …) |
| `slug` | `VARCHAR(100) NOT NULL UNIQUE` | identificator URL-friendly (`buchet-trandafiri`); `UNIQUE` = nu se poate repeta |
| `name` | `VARCHAR(150) NOT NULL` | numele afișat |
| `price` | `DECIMAL(10,2) NOT NULL` | preț cu 2 zecimale (mai precis decât `FLOAT` pentru bani) |
| `discount` | `INT DEFAULT 0` | procent reducere |
| `image` | `VARCHAR(255)` | numele fișierului din `image/` |
| `created_at` | `TIMESTAMP DEFAULT CURRENT_TIMESTAMP` | data adăugării, completată automat |

`ENGINE=InnoDB` = motorul de stocare modern (suportă tranzacții și foreign keys).

### Tabela `orders`

```sql
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefon VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    produs_slug VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

`TEXT` în loc de `VARCHAR` pentru `message` pentru că poate fi lung (până la 64 KB).

### Datele demo

```sql
INSERT INTO products (slug, name, price, discount, image) VALUES
('buchet-lalele-albe', 'Buchet lalele albe', 120.00, 10, 'OIP (1).webp'),
...
```
Inserăm 8 produse de test. Coloanele `id` și `created_at` se completează singure.

---

## 7. Formularul HTML → server PHP

### Pasul 1 — formularul în [src/contact.php](src/contact.php)

```html
<form id="orderForm" action="save_order.php" method="POST">
    <input type="hidden" id="produs" name="produs">
    <input type="text"  name="nume"    placeholder="nume, prenume" class="box">
    <input type="email" name="email"   placeholder="email"         class="box">
    <input type="tel"   name="telefon" placeholder="telefon"       class="box">
    <textarea           name="message" placeholder="message" ...></textarea>
    <input type="submit" value="trimite mesajul" class="btn">
</form>
```

Lucrurile cheie:
- **`action="save_order.php"`** — unde se trimit datele când apeși submit.
- **`method="POST"`** — metoda HTTP. POST e pentru "scrie ceva pe server" (vs GET care e pentru "citește").
- **`name="..."`** — fiecare input trebuie să aibă un `name`, altfel **nu se trimite la server**. Atributul `id` e doar pentru JS și CSS.
- **`type="hidden"`** pentru `produs` — câmp invizibil. JS îl populează cu produsul ales pe pagina anterioară.

### Pasul 2 — JavaScript: [script.js](script.js)

JS face 2 lucruri:

**A) Pe pagina de produse**, când apeși "Cumpara acum":
```js
btn.addEventListener('click', function () {
    const product = btn.getAttribute('data-product');
    if (product) {
        localStorage.setItem('produsSelectat', product);
        window.location.href = 'contact.php';
    }
});
```
- Salvează slug-ul produsului în `localStorage` (memorie din browser care supraviețuiește la navigare).
- Redirecționează la pagina de contact.

**B) Pe pagina de contact**, la încărcare:
```js
const produs = localStorage.getItem('produsSelectat');
if (produs && produsInput) {
    produsInput.value = produs;
    if (numeProdus) numeProdus.textContent = produs.replace(/-/g, ' ');
}
```
Scoate produsul din `localStorage` și îl pune în input-ul ascuns + îl afișează frumos sus.

**C) Validare client-side la submit:**
```js
form.addEventListener('submit', function (event) {
    if (!nume || !email || !telefon || !message) {
        event.preventDefault();
        alert('Completați toate câmpurile!');
        return;
    }
    ...
});
```
- `event.preventDefault()` oprește submit-ul **doar dacă datele sunt invalide**.
- Dacă totul e ok, NU mai apelezi `preventDefault()` și browserul trimite formularul normal la `save_order.php`.

### Pasul 3 — server-side: [src/save_order.php](src/save_order.php)

```php
require __DIR__ . '/db.php';
```
Includem fișierul cu conexiunea. Acum avem `$conn` disponibil.

```php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.php');
    exit;
}
```
Dacă cineva intră direct cu GET pe `save_order.php`, îl trimitem înapoi la formular.

```php
$nume    = trim($_POST['nume']    ?? '');
```
- `$_POST` e un array cu tot ce a venit din formular (cheia = atributul `name` al input-ului).
- `?? ''` (null coalescing) = "dacă nu există, folosește string gol" — evită erori dacă lipsește un câmp.
- `trim()` taie spațiile de la început/sfârșit.

```php
$errors = [];
if (!filter_var($email, FILTER_VALIDATE_EMAIL))   $errors[] = 'Email invalid.';
if (!preg_match('/^\+?[0-9]{9,15}$/', $telefon))  $errors[] = 'Telefon invalid.';
```
**Validare server-side**. JS-ul poate fi dezactivat sau ocolit, deci validăm și aici.
- `filter_var(..., FILTER_VALIDATE_EMAIL)` — funcție built-in PHP.
- `preg_match` — match cu regex.

```php
$stmt = $conn->prepare(
    'INSERT INTO orders (nume, email, telefon, message, produs_slug)
     VALUES (:nume, :email, :telefon, :message, :produs)'
);
$stmt->execute([
    ':nume'    => $nume,
    ':email'   => $email,
    ...
]);
```

**Asta e cea mai importantă parte:**
- `prepare()` trimite la MySQL **doar query-ul cu placeholdere** (`:nume`, `:email`, …).
- `execute([...])` trimite **datele separat**.
- MySQL nu interpretează datele ca SQL. Asta previne **SQL injection**: dacă userul scrie `'; DROP TABLE orders; --` în câmpul "nume", se salvează exact așa ca text, nu rulează ca query.

**Niciodată nu construi SQL prin concatenare:**
```php
// PERICULOS — NU folosi:
$conn->query("INSERT INTO orders (nume) VALUES ('$nume')");
```

### Pasul 4 — confirmarea

După insert, save_order.php afișează HTML cu "Mulțumim!". Nu redirect, doar pagină de confirmare.

---

## 8. Citirea datelor din DB → afișare

### În [src/admin.php](src/admin.php) (lista comenzi)

```php
$orders = $conn->query('SELECT id, nume, email, telefon, message, produs_slug, created_at
                        FROM orders ORDER BY created_at DESC')
              ->fetchAll(PDO::FETCH_ASSOC);
```

- `query(...)` rulează direct un SELECT (sigur fără placeholdere pentru că nu inserăm date de la utilizator).
- `fetchAll()` returnează **un array cu toate rândurile**.
- `PDO::FETCH_ASSOC` — fiecare rând e un array asociativ cu cheile = numele coloanelor.

Apoi în HTML:
```php
<?php foreach ($orders as $o): ?>
    <tr>
        <td><?= htmlspecialchars($o['nume']) ?></td>
        ...
    </tr>
<?php endforeach; ?>
```

- `<?php foreach (...): ?>` ... `<?php endforeach; ?>` — sintaxă alternativă mai citibilă în template-uri (în loc de `{ }`).
- `<?= ... ?>` e prescurtare pentru `<?php echo ... ?>`.
- **`htmlspecialchars()` e obligatoriu**. Convertește `<`, `>`, `"` în entități HTML, ca să nu poată cineva injecta `<script>` în baza de date și să-l execute pe pagina admin (XSS).

### În [src/products.php](src/products.php) (catalog dinamic)

Identic, doar că query-ul e pe `products`:
```php
$products = $conn->query('SELECT id, slug, name, price, discount, image FROM products ORDER BY id ASC')
                 ->fetchAll(PDO::FETCH_ASSOC);
```

Și în HTML:
```php
<?php foreach ($products as $p): ?>
    <div class="box">
        <img src="../image/<?= htmlspecialchars($p['image']) ?>" alt="">
        <h3><?= htmlspecialchars($p['name']) ?></h3>
        <span class="discount">-<?= (int)$p['discount'] ?>%</span>
        <button class="buyBtn" data-product="<?= htmlspecialchars($p['slug']) ?>">Cumpara</button>
    </div>
<?php endforeach; ?>
```

`(int)$p['discount']` = cast la întreg — extra siguranță, garantezi că nu vine altceva în atribut.

---

## 9. Flow-ul complet end-to-end

```
1. Utilizator deschide  http://localhost:8000/
   → Apache rulează index.php → trimite HTML

2. Click pe "Products" → /src/products.php
   → PHP: SELECT FROM products → afișează 8 buchete

3. Click "Cumpara acum" pe un buchet
   → JS salvează slug-ul în localStorage → redirect la contact.php

4. Pe contact.php:
   → JS citește slug-ul din localStorage → completează input-ul ascuns

5. Userul completează form, click "trimite mesajul"
   → JS validează → form face POST la save_order.php

6. save_order.php:
   → primește $_POST → validează server-side
   → INSERT INTO orders (...)
   → afișează "Mulțumim!"

7. Adminul deschide /src/admin.php
   → SELECT FROM orders ORDER BY created_at DESC
   → tabel cu toate comenzile
```

---

## 10. Sintaxă PHP rapidă (referință)

| Construcție | Ce face |
|---|---|
| `<?php ... ?>` | bloc cod PHP |
| `<?= $x ?>` | scurt pentru `<?php echo $x; ?>` |
| `$variabila` | toate variabilele încep cu `$` |
| `'text'` vs `"text $var"` | string-urile cu `"` interpretează variabilele; cele cu `'` nu |
| `.` | concatenare string-uri (nu `+` ca în JS) |
| `=>` | în array-uri asociative: `['cheie' => 'valoare']` |
| `?->` | null-safe (PHP 8+) |
| `??` | null coalescing — `$a ?? 'default'` |
| `array_name['cheie']` | acces la valoare după cheie |
| `function nume($a, $b) { return ...; }` | declarație funcție |
| `require 'fis.php';` | include obligatoriu (eroare dacă lipsește) |
| `include` | la fel dar warning, nu eroare |

---

## 11. Ce ai în plus de făcut (real production)

Acum proiectul e ok pentru lab/învățat, **dar nu pentru producție**. Lipsesc:

1. **Autentificare la `/admin.php`** — acum poate intra oricine. Adaugă HTTP Basic Auth sau o pagină de login cu sesiune.
2. **HTTPS** — Apache ascultă HTTP. La deploy, pune un reverse proxy (nginx + Let's Encrypt).
3. **CSRF tokens** pe formular — cineva poate face un site care submit-uie automat la `/save_order.php`.
4. **Rate limiting** — un bot poate spamuri formularul.
5. **Logging real** — acum erorile MySQL apar pe pagină (`die($e->getMessage())`). În producție le scrii în log, nu le arăți.
6. **`.gitignore`** — exclude `db_data/`, `.env` etc.
7. **Parolele DB în `.env`**, nu hardcodate în compose.
8. **Email de confirmare** către client — `mail()` sau bibliotecă SMTP.

---

## 12. Probleme frecvente

**„SQLSTATE[HY000] [2002] Connection refused" la prima pornire**
MySQL pornește mai lent decât Apache. `depends_on` așteaptă doar containerul, nu serviciul. Așteaptă 10–15s și refresh.

**„Table doesn't exist" deși am init.sql**
`init.sql` rulează doar la prima inițializare. `rm -rf db_data/` și pornește din nou.

**Imaginile nu se afișează (404)**
Verifică că volumul `./:/var/www/html` e setat corect și că imaginea există în `image/`. Pe products.php calea e `../image/...` (din `/src/` urcăm un nivel).

**„Permission denied" pe Windows la `rm -rf db_data/`**
Windows ține fișierele MySQL deschise. Mai întâi `docker compose down`, apoi șterge.

**Modificările PHP nu se văd**
Dacă ai uitat volumul `./:/var/www/html`, lucrezi în copia de la build. `docker compose down && docker compose up -d`.

---

## 13. Comenzi utile

```bash
# intră în container web (vezi fișierele Apache)
docker exec -it site_web bash

# intră în MySQL CLI
docker exec -it site_db mysql -u user -puserpass site_db

# vezi toate comenzile direct din DB
docker exec -it site_db mysql -u user -puserpass site_db -e "SELECT * FROM orders;"

# șterge toate comenzile (TEST DOAR!)
docker exec -it site_db mysql -u user -puserpass site_db -e "DELETE FROM orders;"

# log-urile DB-ului
docker compose logs db

# stare containere
docker compose ps
```

---

## 14. URL-uri curate cu mod_rewrite (`.htaccess`)

URL-uri "frumoase" gen `/products` în loc de `/src/products.php` se fac prin **modulul Apache `mod_rewrite`**, care rescrie intern URL-ul fără să schimbe ce vede utilizatorul în bara de adresă.

### Setup în Dockerfile (deja făcut)

```dockerfile
# Activează modulul (fără asta, .htaccess nu poate face rewrites)
RUN a2enmod rewrite

# Permite ca .htaccess din folderul proiectului să suprascrie config-ul Apache
RUN sed -i 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf
```

A doua linie e crucială: implicit Apache are `AllowOverride None`, ceea ce înseamnă că **ignoră complet `.htaccess`**. `sed -i 's!cauta!inlocuieste!g' fisier` modifică pe loc fișierul de config schimbând `None` cu `All`.

### Fișierul [.htaccess](.htaccess)

Plasat în rădăcina proiectului (același folder cu `index.php`):

```apache
RewriteEngine On

DirectoryIndex index.php

RewriteRule ^about/?$        src/about.php       [L]
RewriteRule ^products/?$     src/products.php    [L]
RewriteRule ^contact/?$      src/contact.php     [L]
RewriteRule ^recenzii/?$     src/recenzii.php    [L]
RewriteRule ^admin/?$        src/admin.php       [L]
RewriteRule ^save-order/?$   src/save_order.php  [L]
```

**Linie cu linie:**

| Linie | Ce face |
|---|---|
| `RewriteEngine On` | Activează rescrierea **în acest fișier**. Trebuie pus o singură dată la început. |
| `DirectoryIndex index.php` | La cererea `/`, Apache servește implicit `index.php`. |
| `RewriteRule  PATTERN  TARGET  [FLAGS]` | Regula de rescriere. |

**Anatomia unui `RewriteRule ^about/?$ src/about.php [L]`:**
- **`^about/?$`** — pattern regex care se potrivește pe URL:
  - `^` = început, `$` = sfârșit (deci match exact, nu prefix)
  - `about` = literal "about"
  - `/?` = un slash opțional (ca să meargă atât `/about` cât și `/about/`)
- **`src/about.php`** — fișierul real care va fi servit
- **`[L]`** = "Last": dacă regula match-uiește, oprește procesarea altor reguli (ar fi inutile oricum)

### Diferența între *rewrite* și *redirect*

| | Rewrite (intern) | Redirect (extern) |
|---|---|---|
| Ce face | Apache rulează un alt fișier intern | Trimite browser-ului 301/302 + URL nou |
| URL în browser | rămâne `/admin` | se schimbă la `/src/admin.php` |
| Numar request-uri | 1 | 2 (browser face al doilea) |
| În config | `RewriteRule` (fără flag `[R]`) | `RewriteRule ... [R=301,L]` |

Noi folosim **rewrite intern** — ce vrea utilizatorul, URL frumos.

### Atenție la căile relative după rewrite

Când URL-ul în browser e `/products`, dar fișierul real e `src/products.php`, **căile relative din HTML se rezolvă în raport cu URL-ul vizibil**, nu cu locația fișierului.
- `<img src="../image/X.jpg">` din `src/products.php` accesat ca `/products`:
  - URL bază: `http://localhost:8000/products`
  - Cale curentă: `/`
  - `../image/X` → `/image/X` ✓ (pentru că `image/` e în rădăcină)

Funcționează ok pentru noi. **Recomandare** pentru proiecte mai mari: folosește **căi absolute** începând cu `/`:
```html
<img src="/image/buchet.jpg">
<link rel="stylesheet" href="/style/products.css">
```

### Eroarea „Invalid command" în .htaccess

Dacă Apache returnează **HTTP 500** la orice request și log-ul zice:
```
Invalid command 'XXX', perhaps misspelled or defined by a module not included in the server configuration
```

Înseamnă că `.htaccess` are o linie pe care Apache n-o recunoaște (typo, modul nedezactivat). Verifică linie cu linie. **O singură directivă greșită** dă 500 pe TOT site-ul.

---

## 15. Autentificare admin cu HTTP Basic Auth

Vrem ca `/admin` să ceară user/parolă. Cea mai simplă metodă: **HTTP Basic Auth** — browserul afișează un popup nativ de login.

### Cod în [src/admin.php](src/admin.php)

```php
<?php
if (!isset($_SERVER['PHP_AUTH_USER']) ||
    $_SERVER['PHP_AUTH_USER'] !== 'admin' ||
    $_SERVER['PHP_AUTH_PW']   !== 'admin') {
    header('WWW-Authenticate: Basic realm="Zona Admin"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<h1>401 — Autentificare necesara</h1>';
    exit;
}

require __DIR__ . '/db.php';
// ... restul paginii
```

### Linie cu linie

```php
if (!isset($_SERVER['PHP_AUTH_USER']) ||
    $_SERVER['PHP_AUTH_USER'] !== 'admin' ||
    $_SERVER['PHP_AUTH_PW']   !== 'admin') {
```
- `$_SERVER['PHP_AUTH_USER']` și `$_SERVER['PHP_AUTH_PW']` — variabile populate **automat de Apache+PHP** când browserul trimite headerul `Authorization: Basic ...`.
- `!isset(...)` = "dacă nu există" (prima vizită — browserul nu trimite credențiale încă).
- `!==` (strict not equal) — verifică valoare ȘI tip. Mai sigur decât `!=`.

```php
header('WWW-Authenticate: Basic realm="Zona Admin"');
```
Trimite headerul HTTP `WWW-Authenticate`. Asta îi spune browserului: "vreau autentificare Basic, iar resursa pe care o protejezi se numește «Zona Admin»".
- `realm` apare în titlul popup-ului din browser.

```php
header('HTTP/1.0 401 Unauthorized');
```
Setează codul de răspuns HTTP la **401**. Combinat cu headerul anterior, browserul afișează popup-ul de login.

```php
exit;
```
Oprește execuția. Tot ce vine după (inclusiv `require db.php` și SQL-urile) **nu rulează** dacă userul nu e autentificat. Esențial pentru securitate.

### Flow vizualizat

```
Pasul 1 — prima vizită (fără credențiale)
─────────────────────────────────────────
Browser  ──── GET /admin ────►  PHP
                                 ├─ $_SERVER['PHP_AUTH_USER'] absent
                                 └─ răspunde:
                                    Status: 401 Unauthorized
                                    WWW-Authenticate: Basic realm="Zona Admin"
Browser  ◄─── 401 ─────────────  PHP
   │
   └─► AFIȘEAZĂ POPUP "Sign in"

Pasul 2 — userul completează admin/admin → OK
─────────────────────────────────────────────
Browser  ──── GET /admin ────►  PHP
              Authorization:        ├─ Apache decodifică Authorization
              Basic YWRtaW46YWRtaW4=├─ $_SERVER['PHP_AUTH_USER'] = 'admin'
                                    ├─ $_SERVER['PHP_AUTH_PW']   = 'admin'
                                    ├─ if-ul nu mai pică
                                    └─ rulează SELECT FROM orders, randează tabel

Browser  ◄─── 200 + HTML tabel ──  PHP
   │
   └─► CACHE-uiește credențialele pentru sesiunea browserului
       (la următoarele requests trimite automat Authorization)

Pasul 3 — următoarea vizită în aceeași sesiune
──────────────────────────────────────────────
Browser  ──── GET /admin ────►  PHP   (trimite din cache Authorization)
Browser  ◄─── 200 ─────────────  PHP   (autentic, fără popup)
```

### Decodificarea string-ului `Basic YWRtaW46YWRtaW4=`

`Basic` zice "metoda e basic". Restul e **base64 din `admin:admin`**:
```
echo "admin:admin" | base64        →  YWRtaW46YWRtaW4K
echo YWRtaW46YWRtaW4= | base64 -d  →  admin:admin
```
Asta înseamnă că **parolele sunt practic în clar** la fiecare request — base64 NU e criptare, e doar codificare. **Pe HTTPS** e ok, **pe HTTP** oricine ascultă rețeaua poate citi parola.

### Limitări (de știut)

| Problemă | Detalii |
|---|---|
| **Logout** | Nu există standard. Singura metodă: închide tab-ul/browser-ul. |
| **Parolă în cod** | Hardcodat `admin/admin` în PHP. Pentru producție: tabelă `users` cu hash + verify. |
| **Trimite parola la fiecare request** | Browser-ul cache-uiește și retrimite la fiecare URL din `realm`. Dacă cineva interceptează — gata. |
| **Realm îngheață cache-ul** | Toate paginile care răspund cu același `realm` partajează cache-ul de credențiale. Schimbi realm → forțezi re-login. |

### Cum ar arăta auth în producție (pentru curiozitate)

```php
// Verificare cu hash + tabelă users
$user = $conn->prepare('SELECT password_hash FROM users WHERE username = ?');
$user->execute([$_POST['username']]);
$row = $user->fetch();

if ($row && password_verify($_POST['password'], $row['password_hash'])) {
    session_start();
    $_SESSION['user_id'] = $row['id'];
    header('Location: /admin');
} else {
    // greșit
}
```

Plus formular HTML normal cu `<input type="password">`, plus buton de logout care face `session_destroy()`.

---

## 16. Versionare cu git — fișiere de exclus

`db_data/` e folderul în care MySQL își ține datele pe disk. Conține:
- fișiere binare InnoDB (`ibdata1`, `ib_logfile*`)
- fișiere de log
- **`mysql.sock`** — un Unix socket file, pe care git-ul **nu poate să-l indexeze** (`error: open(...): Invalid argument`)

### `.gitignore` (fișier la rădăcină)

```gitignore
db_data/
.vscode/
*.log
```

Cu `db_data/` în `.gitignore`:
- `git add .` ignoră complet folderul → nu mai apar erori cu `mysql.sock`
- Datele MySQL nu ajung în repo (e logic — fiecare colaborator își are propriul DB local)
- La un `git clone` proaspăt, `db_data/` nu există → la primul `docker compose up` MySQL îl creează din nou și rulează `init.sql`

### Dacă deja ai adăugat `db_data/` la index din greșeală

```bash
git rm -r --cached db_data/
git add .gitignore
git commit -m "ignore db_data/"
```

`--cached` = scoate din index (staging area), dar lasă fișierele pe disk. Fără asta ai șterge tot folderul.

### Notele despre `LF will be replaced by CRLF`

Sunt **warnings**, nu erori. Pe Windows, git poate converti automat:
- LF (newline-uri Linux) → CRLF (newline-uri Windows) la checkout
- Invers la commit

Comportamentul e controlat de `core.autocrlf`. Dacă vrei să le faci tăcute:
```bash
git config --global core.autocrlf true     # Windows: convertește LF→CRLF la checkout
# sau
git config --global core.autocrlf input    # nu convertește la checkout, doar la commit
```

Nu blochează commit-ul, deci poți ignora warning-ul.

