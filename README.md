Nama: Farhan Aminudin
NIM: 2440511007
Kelas: 5C
Prodi: Teknin Informatika

# CryptAccess

> **Protect Campus Confidentiality. Control Document Access**

CryptAccess adalah sistem manajemen dokumen rahasia kampus berbasis **PHP Native 8.3** dan **MySQL**. Aplikasi ini dirancang untuk menjaga kerahasiaan dokumen internal kampus menggunakan enkripsi hibrida (*hybrid encryption*), autentikasi kriptografis berlapis, serta audit aktivitas administrator yang transparan dan aman.

## 🔒 Alur Keamanan Kriptografi

1. **Autentikasi Administrator**: Login menggunakan kombinasi email, passphrase, dan RSA private key format PEM. Proses verifikasi menggunakan mekanisme *challenge-response* berbasis **RSA-PSS SHA-256**.
2. **Enkripsi Dokumen (AES-256-GCM)**: Setiap dokumen atau informasi teks yang dimasukkan oleh staf berwenang dienkripsi menggunakan kunci enkripsi data (*Data Encryption Key* / DEK) acak berukuran 256-bit dengan mode operasi GCM untuk menjamin kerahasiaan sekaligus integritas data.
3. **Pembungkusan Kunci (RSA-OAEP SHA-256)**: DEK yang mengenkripsi dokumen kemudian diamankan kembali (*wrapped*) menggunakan RSA public key administrator.
4. **Penyimpanan Aman**: Tidak ada dokumen ataupun kunci AES yang disimpan sebagai *plaintext* di dalam database; yang tersimpan hanyalah *ciphertext*, *nonce*, *authentication tag*, dan *encrypted DEK*.

```text
Staf Berwenang / Administrator
    │
    ▼
Memasukkan dokumen / informasi internal
    │
    ▼
CryptAccess mengenkripsi isi dengan AES-256-GCM (DEK acak per record)
    │
    ▼
DEK diamankan dengan RSA-OAEP SHA-256 menggunakan RSA public key
    │
    ▼
Ciphertext & Encrypted DEK disimpan ke database
    │
    ▼
Login Administrator menggunakan Passphrase + PEM (RSA-PSS)
    │
    ▼
Dokumen dideskripsi dan integritas diverifikasi dengan sukses

```

## 📋 Contoh Dokumen yang Dikelola

* Notulen rapat pimpinan kampus
* Dokumen keputusan internal
* Rencana anggaran kampus & fakultas
* Laporan evaluasi dosen / pegawai
* Dokumen investigasi pelanggaran akademik
* Surat keputusan yang belum dipublikasikan
* Berkas kerja sama industri & kampus
* Arsip data strategis fakultas

## 🚀 Fitur Utama

* **Landing Page Profesional**: Halaman sambutan dengan informasi ringkas mengenai standar proteksi dokumen kampus.
* **Setup & Provisioning**: Pendaftaran administrator pertama secara aman tanpa akun/key bawaan.
* **Secure Key-Based Login**: Autentikasi ketat menggunakan *passphrase* dan file *RSA private key* (`.pem`).
* **Enkripsi & Dekripsi Teks**: Enkripsi dokumen/informasi instan dengan validasi tag GCM pada *Decryption Center*.
* **Dashboard & Pusat Kendali**: Ringkasan jumlah dokumen terenkripsi, lampiran terlingungi, dan statistik aktivitas administrator.
* **Key Management**: Pemantauan versi RSA key, status aktif/retired, serta sidik jari (*fingerprint*) public key tanpa mengekspos private key.
* **Audit Trail (Activity Logs)**: Pencatatan riwayat aktivitas penting (login, logout, enkripsi, dekripsi, pemulihan key) secara real-time tanpa menyimpan data sensitif.
* **Recovery Key**: Fitur pemulihan/unduh ulang private key PEM yang dilindungi validasi email dan passphrase.

## 🛠️ Teknologi yang Digunakan

| Komponen | Teknologi |
| --- | --- |
| **Backend** | PHP Native 8.3 |
| **Database** | MySQL / MariaDB |
| **Database Access** | PDO Prepared Statements |
| **Enkripsi Data** | OpenSSL AES-256-GCM |
| **Enkripsi Kunci** | RSA-2048, RSA-OAEP SHA-256 |
| **Autentikasi Kunci** | RSA-PSS SHA-256 |
| **Key Derivation Function** | PBKDF2-HMAC-SHA256 (600.000 iterasi) |
| **Frontend** | HTML5, CSS3, Bootstrap 5, Bootstrap Icons |

## 📁 Struktur Direktori Proyek

```text
crypto/
├── app/                 # Core logic: database, auth, CSRF, fungsionalitas kriptografi
├── config/              # Konfigurasi koneksi database & OpenSSL
├── database/            # Skema SQL siap impor (cryptaccess_db.sql)
├── public/              # Endpoint halaman web publik & administrator
│   ├── index.php        # Landing page utama
│   ├── setup.php        # Setup administrator awal
│   ├── login.php        # Autentikasi berbasis key
│   ├── dashboard.php    # Dashboard utama administrator
│   ├── records.php      # Daftar dokumen terenkripsi
│   ├── encrypt.php      # Form input & enkripsi dokumen baru
│   ├── decrypt.php      # Pusat dekripsi dokumen
│   ├── keys.php         # Manajemen versi RSA key
│   ├── logs.php         # Riwayat audit aktivitas sistem
│   └── recover-key.php  # Pemulihan private key PEM
├── storage/encrypted/   # Direktori penyimpanan aman non-publik
└── tests/               # Skrip pengujian modul kriptografi

```

## ⚙️ Panduan Instalasi di Laragon

1. Salin folder proyek Anda ke direktori root web server Laragon:
`C:\laragon\www\crypto`
2. Jalankan **Apache** dan **MySQL** melalui panel kontrol Laragon.
3. Buka **phpMyAdmin**, buat database baru, lalu impor file SQL dari folder `database/cryptaccess_db.sql`.
4. Sesuaikan konfigurasi koneksi database pada file konfigurasi Anda di folder `config/`.
5. Pastikan ekstensi OpenSSL aktif pada PHP 8.3 Anda.
6. Akses aplikasi melalui browser:
```text
http://localhost/crypto/public/

```


7. Buka halaman `setup.php` untuk menginisialisasi administrator pertama dan simpan file *private key PEM* Anda dengan aman.

---
