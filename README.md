<div align="center">

  <img src="assets/Logo%20Dewan%20Ambalan.jpeg" alt="Logo Dewan Ambalan" width="130" style="border-radius: 50%; box-shadow: 0 4px 20px rgba(0,0,0,0.25);" />

  # ⚜️ Website Seleksi Dewan Ambalan 2026
  
  **Sistem Informasi Seleksi, Manajemen Penilaian & Pengumuman Kelulusan Calon Dewan Ambalan**

  [![PHP Version](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
  [![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
  [![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com/)
  [![Railway](https://img.shields.io/badge/Railway-Deployed-0B0D0E?style=for-the-badge&logo=railway&logoColor=white)](https://railway.app/)
  [![License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](LICENSE)

  <p align="center">
    Aplikasi web responsif modern berbasis PHP dan MySQL untuk mendukung transparansi, akurasi, dan efisiensi dalam proses seleksi kepengurusan Dewan Ambalan Pramuka Penegak.
  </p>

</div>

---

## 📋 Daftar Isi
- [Tentang Aplikasi](#-tentang-aplikasi)
- [Fitur Utama](#-fitur-utama)
- [Teknologi](#-teknologi)
- [Struktur Direktori](#-struktur-direktori)
- [Panduan Instalasi Lokal](#-panduan-instalasi-lokal)
- [Menjalankan dengan Docker](#-menjalankan-dengan-docker)
- [Deployment ke Cloud (Railway)](#-deployment-ke-cloud-railway)
- [Variabel Lingkungan (.env)](#-variabel-lingkungan-env)
- [Akun Bawaan (Default Credentials)](#-akun-bawaan-default-credentials)
- [Praktik Keamanan](#-praktik-keamanan)
- [Lisensi](#-lisensi)

---

## 📖 Tentang Aplikasi

**Website Dewan Ambalan 2026** adalah platform terintegrasi yang dirancang untuk memfasilitasi seluruh rangkaian seleksi calon anggota Dewan Ambalan baru. Sistem ini menggantikan pencatatan manual dengan sistem digital yang cepat, transparan, dan dapat diakses dari berbagai perangkat (desktop, tablet, hingga smartphone).

Dengan desain antarmuka bergaya **Glassmorphism modern**, sistem ini memberikan kenyamanan visual sekaligus kemudahan operasional baik bagi tim penilai (Admin) maupun calon anggota (Peserta).

---

## ✨ Fitur Utama

### 🛡️ 1. Panel Administrator
- **Dashboard Statistik Interaktif**: Menampilkan ringkasan total pendaftar, jumlah peserta lulus, jumlah tidak lulus, dan nilai rata-rata.
- **Input & Rekapitulasi Nilai Seleksi**:
  - 📝 **Tes Tulis** (Pemahaman kepramukaan, materi umum, dan kepemimpinan)
  - 🗣️ **Wawancara** (Integritas, komitmen, loyalitas, dan kecakapan)
  - 📄 **CV, Program Kerja & Visi Misi** (Kreativitas, kesiapan gagasan, dan rekam jejak)
- **Pengaturan Ambang Batas KKM Dinamis**: Admin dapat mengubah standar KKM (Kriteria Ketuntasan Minimal) sewaktu-waktu secara langsung melalui dashboard.
- **Kalkulasi Status Kelulusan Otomatis**: Sistem secara otomatis menentukan status `LULUS` atau `TIDAK LULUS` berdasarkan akumulasi skor terhadap KKM.
- **Manajemen Peserta & Akun**: Tambah, perbarui nilai, dan hapus data peserta secara real-time.

### 🎓 2. Portal Peserta (Calon Anggota)
- **Login Personal**: Akses aman dengan akun khusus peserta.
- **Rincian Nilai Transparan**: Mengetahui perolehan nilai dari masing-masing pos seleksi beserta total skor akhir.
- **Status Kelulusan Real-Time**: Peserta langsung dapat mengetahui apakah dinyatakan lulus seleksi.
- **Cetak / Unduh Bukti Hasil Seleksi**: Fitur unduh slip resmi hasil seleksi yang siap dicetak atau disimpan sebagai dokumen arsip.

### ⚡ 3. Performa & Arsitektur
- **Connection Pooling**: Manajemen koneksi database yang efisien untuk beban akses tinggi.
- **Prepared Statements (MySQLi)**: Perlindungan menyeluruh terhadap ancaman SQL Injection.
- **Konfigurasi Fleksibel Berbasis Environment**: Kompatibel dengan `.env` lokal, container Docker, maupun variabel cloud Railway.

---

## 🛠️ Teknologi

- **Backend**: PHP 8.2 (Native OOP & Procedural)
- **Database**: MySQL / MariaDB dengan charset `utf8mb4`
- **Frontend**: HTML5, Modern CSS3 (Glassmorphism & Flexbox/Grid Layout), JavaScript Vanilla
- **Containerization**: Docker
- **Hosting / Cloud Ready**: Railway, VPS, Laragon, XAMPP

---

## 📂 Struktur Direktori

```plaintext
├── admin/
│   └── dashboard.php           # Panel kontrol & manajemen nilai oleh admin
├── assets/
│   ├── Logo Dewan Ambalan.jpeg # Logo identitas ambalan
│   └── css/
│       └── style.css           # Styling utama & styling glassmorphism
├── config/
│   └── config.php              # Koneksi database, connection pool, & helper KKM
├── user/
│   ├── dashboard.php           # Tampilan nilai & status peserta
│   ├── download_hasil.php      # Halaman cetak/unduh slip hasil seleksi
│   ├── login.php               # Halaman masuk untuk admin & peserta
│   └── logout.php              # Pembersihan sesi & keluar
├── .env.example                # Template konfigurasi environment database
├── .gitignore                  # Berkas yang dikecualikan dari repositori Git
├── database.sql                # Skema basis data siap import
├── Dockerfile                  # Konfigurasi container Docker untuk deployment
├── index.php                   # Halaman landing page sambutan
└── README.md                   # Dokumentasi lengkap proyek
```

---

## 💻 Panduan Instalasi Lokal

### Prasyarat:
- Web Server Lokal (**Laragon** disarankan, atau XAMPP)
- PHP versi 8.1 atau lebih baru (dengan ekstensi `mysqli` dan `pdo_mysql` aktif)
- MySQL Server

### Langkah-langkah:

1. **Kloning Repositori**:
   ```bash
   git clone https://github.com/fiko30/Website-Dewan-Ambalan-2026.git
   ```
   Pindahkan folder proyek ke direktori web root server Anda (misal `d:/laragon/www/seleksi_da` atau `C:/xampp/htdocs/seleksi_da`).

2. **Setup Konfigurasi (.env)**:
   Salin file `.env.example` menjadi `.env`:
   ```bash
   cp .env.example .env
   ```
   Buka file `.env` dan sesuaikan koneksi basis data lokal Anda:
   ```env
   DB_HOST=localhost
   DB_USER=root
   DB_PASS=
   DB_NAME=seleksi_da
   DB_PORT=3306
   ```

3. **Impor Database**:
   - Buka **phpMyAdmin** atau tool database favorit Anda (HeidiSQL, DBeaver, dll).
   - Buat database baru bernama `seleksi_da`.
   - Impor berkas `database.sql` yang tersedia di direktori root proyek.

4. **Jalankan Aplikasi**:
   - Jika menggunakan Laragon / XAMPP, buka browser dan akses:
     ```
     http://localhost/seleksi_da/
     ```
   - Atau jalankan built-in PHP server langsung dari terminal:
     ```bash
     php -S localhost:8000
     ```
     Lalu buka `http://localhost:8000`.

---

## 🐳 Menjalankan dengan Docker

Proyek ini telah dilengkapi dengan `Dockerfile` siap pakai.

1. **Build Docker Image**:
   ```bash
   docker build -t website-dewan-ambalan .
   ```

2. **Jalankan Container**:
   ```bash
   docker run -d -p 8080:80 \
     -e DB_HOST=host.docker.internal \
     -e DB_USER=root \
     -e DB_PASS=password_anda \
     -e DB_NAME=seleksi_da \
     -e DB_PORT=3306 \
     --name dewan-ambalan-app website-dewan-ambalan
   ```
   Aplikasi akan dapat diakses pada alamat `http://localhost:8080`.

---

## ☁️ Deployment ke Cloud (Railway)

Aplikasi ini telah dirancang untuk dapat di-deploy secara instan di [Railway](https://railway.app/):

1. Hubungkan repositori GitHub Anda ke Railway Project baru.
2. Tambahkan plugin **MySQL** pada Railway.
3. Atur Environment Variables di dashboard Railway (Service Settings):
   - `DB_HOST`: `${{MySQL.MYSQLHOST}}`
   - `DB_USER`: `${{MySQL.MYSQLUSER}}`
   - `DB_PASS`: `${{MySQL.MYSQLPASSWORD}}`
   - `DB_NAME`: `${{MySQL.MYSQLDATABASE}}`
   - `DB_PORT`: `${{MySQL.MYSQLPORT}}`
   *(Atau Railway secara otomatis mengenali variabel bawaan `MYSQLHOST`, `MYSQLUSER`, dll)*
4. Impor tabel dari file `database.sql` ke database Railway menggunakan database client.
5. Deploy selesai! Railway akan menjalankan web server PHP secara otomatis.

---

## ⚙️ Variabel Lingkungan (.env)

| Variabel | Keterangan | Nilai Default |
|---|---|---|
| `DB_HOST` / `MYSQLHOST` | Host server database | `localhost` |
| `DB_USER` / `MYSQLUSER` | Username database | `root` |
| `DB_PASS` / `MYSQLPASSWORD` | Password database | *(kosong)* |
| `DB_NAME` / `MYSQLDATABASE` | Nama basis data | `seleksi_da` |
| `DB_PORT` / `MYSQLPORT` | Port server database | `3306` |

---

## 🔑 Akun Bawaan (Default Credentials)

Sistem menyertakan akun default pada saat inisialisasi skema `database.sql`:

| Role | Username (Nama Lengkap) | Password Bawaan |
|---|---|---|
| **Administrator** | `Admin DA` | `Dewan Ambalan 2025` |
| **Peserta Baru** *(Dibuat Admin)* | *(Sesuai input nama)* | `Calon Dewan Ambalan 2026` |

> ⚠️ **PENTING**: Segera perbarui password administrator setelah melakukan instalasi pertama demi keamanan data organisasi.

---

## 🔒 Praktik Keamanan

1. **Pemisahan Kredensial**: Tidak ada kredensial, host, atau password database yang disimpan langsung di dalam kode program (`hardcoded`). Seluruh konfigurasi sensitif dipisahkan ke dalam berkas `.env`.
2. **Pencegahan Kebocoran Git**: File `.env` telah didaftarkan dalam `.gitignore` sehingga tidak akan terunggah ke repositori publik.
3. **Pencegahan SQL Injection**: Semua interaksi data dinamis menggunakan MySQLi Prepared Statements dengan parameter binding bertipe ketat.
4. **Proteksi Sesi**: Sesi pengguna diisolasi berdasarkan role (`admin` atau `peserta`) dan divalidasi pada setiap halaman dashboard.

---

## 📄 Lisensi

Didistribusikan di bawah lisensi MIT. Lihat berkas `LICENSE` untuk rincian lebih lanjut.

---

<div align="center">
  <b>Satyaku Kudarmakan, Darmaku Kubaktikan ⚜️</b><br>
  <i>Salam Pramuka!</i>
</div>
