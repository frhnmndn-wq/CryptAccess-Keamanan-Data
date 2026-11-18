# CryptAccess

> **Secure Your Information. Control Your Access.**

CryptAccess adalah aplikasi *Sistem Manajemen Dokumen Rahasia Kampus* berbasis **PHP Native 8.3** dan **MySQL**. Aplikasi ini dibuat sebagai proyek UTS Keamanan Data untuk menunjukkan penerapan autentikasi kriptografis, enkripsi hybrid, manajemen informasi terenkripsi, serta audit aktivitas administrator.

## Tujuan Aplikasi

Aplikasi ini melindungi dokumen rahasia kampus agar tidak disimpan sebagai teks biasa (*plaintext*) di database. Setiap informasi teks dienkripsi sebelum disimpan, dan hanya administrator dengan passphrase serta RSA private key yang tepat yang dapat membukanya kembali.

## Fitur Utama

- Landing page CryptAccess dengan desain sistem keamanan profesional.
- Provisioning administrator pertama tanpa akun atau private key bawaan.
- Login berbasis email, passphrase, dan RSA private key format PEM.
- Challenge-response authentication menggunakan RSA-PSS dengan SHA-256.
- Enkripsi informasi teks menggunakan AES-256-GCM.
- Hybrid encryption: setiap record memiliki Data Encryption Key (DEK) acak sendiri yang dibungkus dengan RSA-OAEP SHA-256.
- Decryption Center yang memverifikasi authentication tag AES-GCM sebelum menampilkan plaintext.
- Dashboard dengan statistik langsung dari database.
- Key Management untuk melihat versi dan status RSA key tanpa menampilkan private key.
- Activity logs untuk login, logout, provisioning, enkripsi, dekripsi, dan pemulihan key.
- Pemulihan/download private key PEM yang membutuhkan email dan passphrase administrator.
- CSRF protection, PDO prepared statements, session regeneration, dan pembatasan percobaan login.

## Alur Keamanan

```text
Passphrase
   │ PBKDF2-HMAC-SHA256 + salt acak
   ▼
Key untuk membuka RSA private key yang terenkripsi AES-256-GCM
   │
   ▼
RSA-PSS challenge-response → Session administrator

Plaintext record
   │ AES-256-GCM dengan DEK acak per record
   ▼
Ciphertext + nonce + authentication tag
   │
DEK
   │ RSA-OAEP SHA-256 dengan RSA public key
   ▼
Encrypted DEK
```

Dengan arsitektur tersebut, database menyimpan ciphertext dan DEK yang sudah dibungkus RSA, bukan isi informasi atau kunci AES dalam bentuk terbuka.

## Teknologi

| Bagian | Teknologi |
| --- | --- |
| Backend | PHP Native 8.3 |
| Database | MySQL / MariaDB |
| Database access | PDO prepared statements |
| Enkripsi data | OpenSSL AES-256-GCM |
| Enkripsi key | RSA-2048, RSA-OAEP SHA-256 |
| Autentikasi key | RSA-PSS SHA-256 |
| KDF | PBKDF2-HMAC-SHA256 (600.000 iterasi) |
| Frontend | HTML5, CSS3, Bootstrap 5, Bootstrap Icons |

## Struktur Proyek

```text
crypto/
├── app/                 # Bootstrap, database, auth, CSRF, cryptography
├── config/              # Konfigurasi database dan OpenSSL
├── database/            # SQL siap impor untuk phpMyAdmin
├── public/              # Halaman yang diakses browser
│   ├── setup.php        # Provisioning administrator
│   ├── login.php        # Login berbasis key
│   ├── encrypt.php      # Enkripsi informasi teks
│   ├── decrypt.php      # Dekripsi informasi teks
│   ├── dashboard.php    # Dashboard administrator
│   └── recover-key.php  # Pemulihan private key PEM
├── storage/encrypted/   # Lokasi non-public untuk file terenkripsi
└── tests/               # Pengujian alur kriptografi
```

## Instalasi di Laragon

1. Salin proyek ke `C:\laragon\www\crypto`.
2. Jalankan Apache dan MySQL dari Laragon.
3. Buka phpMyAdmin, lalu import `database/cryptaccess_db.sql`.
4. Salin `config/local.php.example` menjadi `config/local.php`.
5. Sesuaikan koneksi database pada `config/local.php`.
6. Pastikan program `openssl` tersedia pada `PATH` sistem.
7. Buka aplikasi di browser:

   ```text
   http://localhost/crypto/public/
   ```

8. Buka `setup.php` untuk membuat administrator pertama dan unduh file private key PEM.

> Jangan commit `config/local.php`, jangan membagikan file PEM, dan jangan membuka folder `storage/` melalui web server.

## Database

Database `cryptaccess_db` berisi tabel utama berikut:

- `administrators` — data administrator dan fingerprint public key.
- `cryptographic_keys` — public key, private key terenkripsi, salt, nonce, tag, dan versi key.
- `encrypted_records` — ciphertext judul serta isi informasi.
- `encryption_keys` — encrypted DEK dan relasinya dengan record/key RSA.
- `activity_logs` — audit trail aktivitas keamanan.
- `data_categories`, `encrypted_files`, dan `system_settings` — kategori serta fondasi pengembangan berikutnya.

## Pengujian

Jalankan pengujian kriptografi dari folder proyek:

```bash
php tests/crypto_test.php
```

Pengujian memverifikasi alur RSA-OAEP SHA-256 untuk DEK dan RSA-PSS SHA-256 untuk challenge-response.

## Catatan Pengembangan

Versi saat ini memfokuskan implementasi aktif pada informasi teks terenkripsi. Skema database dan storage sudah disiapkan untuk file terenkripsi, tetapi upload/download file berbasis chunk dan key rotation otomatis merupakan pengembangan lanjutan.

## Lisensi

Proyek akademik untuk UTS Keamanan Data.