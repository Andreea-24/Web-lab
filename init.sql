SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    discount INT NOT NULL DEFAULT 0,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nume VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefon VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    produs_slug VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO products (slug, name, price, discount, image) VALUES
('buchet-lalele-albe',          'Buchet lalele albe',          120.00, 10, 'OIP (1).webp'),
('buchet-roziniu-tulpiu',       'Buchet roziniu-tulpiu',       130.00, 15, 'roziniu-tulpiu-puokste.jpg'),
('buchet-trandafiri',           'Buchet trandafiri',           300.00, 10, 'img1333_main_l.jpg'),
('buchet-trandafiri-portocalii','Buchet trandafiri portocalii',300.00, 15, 'suflet-de-floare-buchet-trandafiri-si-minirosa-incantator--5d09104e26ee6.jpg'),
('buchet-trandafiri-roz',       'Buchet trandafiri roz',       800.00,  8, 'amz-buchet-superb-roz.jpg'),
('buchet-lalele-violete',       'Buchet lalele violete',       600.00,  8, 'tulipani_viola_gypsophila.jpg'),
('buchet-trandafiri-rosii-albi','Buchet trandafiri rosii albi',850.00,  8, '3880.jpg'),
('buchet-garoafe',              'Buchet garoafe',              750.00,  8, '1656784395_43182417-600x600.jpeg');
