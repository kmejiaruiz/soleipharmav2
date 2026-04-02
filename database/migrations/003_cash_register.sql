-- Migration 003: Cash Register Module
-- Run: mysql -u root pharmacy < 003_cash_register.sql

CREATE TABLE IF NOT EXISTS cash_sessions (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  opened_by       INT NOT NULL,
  closed_by       INT DEFAULT NULL,
  opening_amount  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status          ENUM('open','closed') NOT NULL DEFAULT 'open',
  notes           TEXT,
  opened_at       DATETIME DEFAULT CURRENT_TIMESTAMP,
  closed_at       DATETIME DEFAULT NULL,
  FOREIGN KEY (opened_by) REFERENCES users(id),
  FOREIGN KEY (closed_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS cash_withdrawals (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  session_id      INT NOT NULL,
  withdrawn_by    INT NOT NULL,
  total_amount    DECIMAL(10,2) NOT NULL,
  denominations   JSON NOT NULL,
  reason          VARCHAR(255) DEFAULT NULL,
  created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (session_id) REFERENCES cash_sessions(id),
  FOREIGN KEY (withdrawn_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS cash_closing_counts (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  session_id      INT NOT NULL UNIQUE,
  counted_by      INT NOT NULL,
  counted_amount  DECIMAL(10,2) NOT NULL,
  denominations   JSON NOT NULL,
  expected_amount DECIMAL(10,2) NOT NULL,
  difference      DECIMAL(10,2) NOT NULL,
  created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (session_id)  REFERENCES cash_sessions(id),
  FOREIGN KEY (counted_by) REFERENCES users(id)
);
