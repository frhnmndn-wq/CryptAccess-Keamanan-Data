CREATE DATABASE IF NOT EXISTS cryptaccess_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cryptaccess_db;

CREATE TABLE administrators (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, display_name VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE,
 public_key_fingerprint CHAR(64) NOT NULL UNIQUE, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, last_login_at TIMESTAMP NULL
) ENGINE=InnoDB;
CREATE TABLE cryptographic_keys (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, administrator_id BIGINT UNSIGNED NOT NULL, key_version INT UNSIGNED NOT NULL,
 public_key_pem MEDIUMTEXT NOT NULL, encrypted_private_key LONGBLOB NOT NULL, kdf_salt VARBINARY(32) NOT NULL, kdf_iterations INT UNSIGNED NOT NULL,
 private_nonce VARBINARY(12) NOT NULL, private_tag VARBINARY(16) NOT NULL, status ENUM('active','revoked','retired') NOT NULL DEFAULT 'active', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_key_version (administrator_id,key_version), KEY idx_key_status(status), CONSTRAINT fk_key_admin FOREIGN KEY (administrator_id) REFERENCES administrators(id)
) ENGINE=InnoDB;
CREATE TABLE data_categories (id TINYINT UNSIGNED PRIMARY KEY, name VARCHAR(50) NOT NULL UNIQUE);
INSERT INTO data_categories VALUES (1,'Notulen dan Keputusan'),(2,'Dokumen Akademik Internal'),(3,'Dokumen Keuangan dan Kerja Sama');
CREATE TABLE encrypted_records (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, administrator_id BIGINT UNSIGNED NOT NULL, category_id TINYINT UNSIGNED NOT NULL,
 title_ciphertext LONGBLOB NOT NULL, title_nonce VARBINARY(12) NOT NULL, title_tag VARBINARY(16) NOT NULL,
 content_ciphertext LONGBLOB NOT NULL, content_nonce VARBINARY(12) NOT NULL, content_tag VARBINARY(16) NOT NULL,
 algorithm VARCHAR(40) NOT NULL DEFAULT 'AES-256-GCM', status ENUM('encrypted','deleted') NOT NULL DEFAULT 'encrypted', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
 KEY idx_records_status(status), KEY idx_records_category(category_id), CONSTRAINT fk_record_admin FOREIGN KEY(administrator_id) REFERENCES administrators(id), CONSTRAINT fk_record_category FOREIGN KEY(category_id) REFERENCES data_categories(id)
) ENGINE=InnoDB;
CREATE TABLE encrypted_files (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, administrator_id BIGINT UNSIGNED NOT NULL, category_id TINYINT UNSIGNED NOT NULL,
 storage_name CHAR(64) NOT NULL UNIQUE, name_ciphertext LONGBLOB NOT NULL, name_nonce VARBINARY(12) NOT NULL, name_tag VARBINARY(16) NOT NULL,
 mime_ciphertext LONGBLOB NOT NULL, mime_nonce VARBINARY(12) NOT NULL, mime_tag VARBINARY(16) NOT NULL, size_bytes BIGINT UNSIGNED NOT NULL,
 algorithm VARCHAR(40) NOT NULL DEFAULT 'AES-256-GCM-CHUNKED', chunk_size INT UNSIGNED NOT NULL DEFAULT 1048576, status ENUM('encrypted','deleted') NOT NULL DEFAULT 'encrypted', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_files_status(status), CONSTRAINT fk_file_admin FOREIGN KEY(administrator_id) REFERENCES administrators(id), CONSTRAINT fk_file_category FOREIGN KEY(category_id) REFERENCES data_categories(id)
) ENGINE=InnoDB;
CREATE TABLE encryption_keys (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, record_id BIGINT UNSIGNED NULL, file_id BIGINT UNSIGNED NULL, cryptographic_key_id BIGINT UNSIGNED NOT NULL,
 encrypted_dek LONGBLOB NOT NULL, wrap_algorithm VARCHAR(40) NOT NULL DEFAULT 'RSA-OAEP-SHA256', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_record_dek(record_id), UNIQUE KEY uq_file_dek(file_id), CONSTRAINT chk_one_target CHECK ((record_id IS NOT NULL) <> (file_id IS NOT NULL)),
 CONSTRAINT fk_dek_record FOREIGN KEY(record_id) REFERENCES encrypted_records(id), CONSTRAINT fk_dek_file FOREIGN KEY(file_id) REFERENCES encrypted_files(id), CONSTRAINT fk_dek_rsa FOREIGN KEY(cryptographic_key_id) REFERENCES cryptographic_keys(id)
) ENGINE=InnoDB;
CREATE TABLE activity_logs (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, administrator_id BIGINT UNSIGNED NULL, event_type VARCHAR(60) NOT NULL, ip_address VARCHAR(45) NULL, detail VARCHAR(255) NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, KEY idx_log_time(created_at), CONSTRAINT fk_log_admin FOREIGN KEY(administrator_id) REFERENCES administrators(id) ON DELETE SET NULL) ENGINE=InnoDB;
CREATE TABLE system_settings (setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT NOT NULL, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB;
