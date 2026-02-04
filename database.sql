-- HairShake MVP (parrucchieri freelance) - MySQL
-- Importa questo file nel tuo DB.

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  role ENUM('client','hairdresser','admin') NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(50),
  password_hash VARCHAR(255) NOT NULL,
  city VARCHAR(120),
  bio TEXT,
  photo_url VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  hairdresser_id INT NOT NULL,
  title VARCHAR(120) NOT NULL,
  price_chf DECIMAL(7,2) NOT NULL,
  duration_min INT NOT NULL,
  description TEXT,
  active TINYINT(1) DEFAULT 1,
  FOREIGN KEY (hairdresser_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- disponibilità semplice: slot di 30 min, stored as UTC datetime
CREATE TABLE IF NOT EXISTS availability (
  id INT AUTO_INCREMENT PRIMARY KEY,
  hairdresser_id INT NOT NULL,
  start_at DATETIME NOT NULL,
  end_at DATETIME NOT NULL,
  FOREIGN KEY (hairdresser_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (hairdresser_id, start_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  client_id INT NOT NULL,
  hairdresser_id INT NOT NULL,
  service_id INT NOT NULL,
  start_at DATETIME NOT NULL,
  end_at DATETIME NOT NULL,
  address_text VARCHAR(255),
  notes TEXT,
  status ENUM('pending','paid','confirmed','done','cancelled','refunded') NOT NULL DEFAULT 'pending',
  amount_total_chf DECIMAL(7,2) NOT NULL DEFAULT 0,
  platform_fee_chf DECIMAL(7,2) NOT NULL DEFAULT 0,
  stripe_payment_intent_id VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (client_id) REFERENCES users(id),
  FOREIGN KEY (hairdresser_id) REFERENCES users(id),
  FOREIGN KEY (service_id) REFERENCES services(id),
  INDEX (hairdresser_id, start_at),
  INDEX (client_id, start_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL UNIQUE,
  hairdresser_id INT NOT NULL,
  client_id INT NOT NULL,
  rating TINYINT NOT NULL,
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (hairdresser_id) REFERENCES users(id),
  FOREIGN KEY (client_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  sender_id INT NOT NULL,
  recipient_id INT NOT NULL,
  body TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id),
  FOREIGN KEY (recipient_id) REFERENCES users(id),
  INDEX (booking_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin logs minimi (per audit)
CREATE TABLE IF NOT EXISTS audit_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  actor_user_id INT,
  action VARCHAR(120) NOT NULL,
  meta_json JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
