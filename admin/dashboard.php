<?php
session_start();
require_once '../config/config.php';

// Tampilkan error PHP untuk debugging (hapus saat production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) != 'admin') {
    header("Location: ../user/login.php");
    exit;
}

// Cek koneksi database
if ($conn->connect_error) {
    die("❌ Koneksi Database Gagal: " . $conn->connect_error);
}

$kkm = get_kkm($conn);

function is_invalid_score($value)
{
    return !is_numeric($value) || $value < 0 || $value > 100;
}

function find_peserta_name($conn, $namaInput)
{
    $stmt = $conn->prepare("SELECT nama_lengkap FROM users WHERE LOWER(nama_lengkap) = LOWER(?) AND LOWER(role) = 'peserta' LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("s", $namaInput);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();
        return $row['nama_lengkap'];
    }
    return null;
}

// CRUD OPERATIONS
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    if ($action == 'update_kkm') {
        $kkmInput = $_POST['kkm'] ?? null;

        if (!is_numeric($kkmInput) || $kkmInput < 0 || $kkmInput > 100) {
            header("Location: dashboard.php?error=kkm_tidak_valid");
            exit;
        }

        if (!set_kkm($conn, $kkmInput)) {
            die("❌ Gagal menyimpan KKM: " . $conn->error);
        }

        header("Location: dashboard.php?status=kkm_updated");
        exit;
    }
    
    if ($action == 'add_user') {
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $role = strtolower(trim($_POST['role'] ?? 'peserta'));
        $wawancaraInput = $_POST['nilai_wawancara'] ?? null;
        $tesTulisInput = $_POST['nilai_tes_tulis'] ?? null;
        $cvProkerInput = $_POST['nilai_cv_proker'] ?? null;

        $wawancara = floatval($wawancaraInput);
        $tes_tulis = floatval($tesTulisInput);
        $cv_proker = floatval($cvProkerInput);

        // Untuk peserta, semua nilai harus disertakan
        if ($role === 'peserta' && (
            $wawancaraInput === null || $wawancaraInput === '' ||
            $tesTulisInput === null || $tesTulisInput === '' ||
            $cvProkerInput === null || $cvProkerInput === ''
        )) {
            header("Location: dashboard.php?error=nilai_harus_diisi");
            exit;
        }

        if ($nama_lengkap === '') {
            header("Location: dashboard.php?error=nama_kosong");
            exit;
        }

        if (!in_array($role, ['admin', 'peserta'], true)) {
            header("Location: dashboard.php?error=role_tidak_valid");
            exit;
        }

        // Nilai peserta dibatasi 0 sampai 100 per komponen
        if ($role === 'peserta' && (
            is_invalid_score($wawancaraInput) ||
            is_invalid_score($tesTulisInput) ||
            is_invalid_score($cvProkerInput)
        )) {
            header("Location: dashboard.php?error=invalid_nilai");
            exit;
        }

        $stmtCekUser = $conn->prepare("SELECT id FROM users WHERE LOWER(nama_lengkap) = LOWER(?) LIMIT 1");
        $stmtCekUser->bind_param("s", $nama_lengkap);
        $stmtCekUser->execute();
        $existingUser = $stmtCekUser->get_result()->fetch_assoc();
        if ($existingUser) {
            header("Location: dashboard.php?error=user_sudah_ada");
            exit;
        }

        $defaultPasswordByRole = [
            'admin' => 'Dewan Ambalan 2025',
            'peserta' => 'Calon Dewan Ambalan 2026',
        ];
        $defaultPassword = $defaultPasswordByRole[$role];

        $conn->begin_transaction();
        try {
            $stmtUser = $conn->prepare("INSERT INTO users (nama_lengkap, password, role) VALUES (?, ?, ?)");
            if (!$stmtUser) {
                throw new Exception($conn->error);
            }
            $stmtUser->bind_param("sss", $nama_lengkap, $defaultPassword, $role);
            if (!$stmtUser->execute()) {
                throw new Exception($stmtUser->error);
            }

            if ($role === 'peserta') {
                $stmtNilai = $conn->prepare("INSERT INTO penilaian (nama_lengkap, nilai_wawancara, nilai_tes_tulis, nilai_cv_proker, tanggal) VALUES (?, ?, ?, ?, NOW())");
                if (!$stmtNilai) {
                    throw new Exception($conn->error);
                }
                $stmtNilai->bind_param("sddd", $nama_lengkap, $wawancara, $tes_tulis, $cv_proker);
                if (!$stmtNilai->execute()) {
                    throw new Exception($stmtNilai->error);
                }
            }

            $conn->commit();
            header("Location: dashboard.php?status=user_added");
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            die("❌ Gagal menambah user baru: " . htmlspecialchars($e->getMessage()));
        }

    } elseif ($action == 'add') {
        header("Location: dashboard.php?error=aksi_tidak_valid");
        exit;

    } elseif ($action == 'edit') {
        if (empty($id)) { header("Location: dashboard.php?error=missing_id"); exit; }
        $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
        $wawancara = floatval($_POST['nilai_wawancara']);
        $tes_tulis = floatval($_POST['nilai_tes_tulis']);
        $cv_proker = floatval($_POST['nilai_cv_proker']);

        if ($nama_lengkap === '') {
            header("Location: dashboard.php?error=nama_kosong");
            exit;
        }

        if (
            is_invalid_score($_POST['nilai_wawancara'] ?? null) ||
            is_invalid_score($_POST['nilai_tes_tulis'] ?? null) ||
            is_invalid_score($_POST['nilai_cv_proker'] ?? null)
        ) {
            header("Location: dashboard.php?error=invalid_nilai");
            exit;
        }

        $namaPesertaValid = find_peserta_name($conn, $nama_lengkap);
        if ($namaPesertaValid === null) {
            header("Location: dashboard.php?error=peserta_tidak_ditemukan");
            exit;
        }
        $nama_lengkap = $namaPesertaValid;
        
        $stmt = $conn->prepare("UPDATE penilaian SET nama_lengkap=?, nilai_wawancara=?, nilai_tes_tulis=?, nilai_cv_proker=? WHERE id=?");
        $stmt->bind_param("sdddi", $nama_lengkap, $wawancara, $tes_tulis, $cv_proker, $id);
        if ($stmt->execute()) {
            header("Location: dashboard.php?status=updated");
        } else {
            die(" Gagal Update: " . $stmt->error . " <a href='dashboard.php'>🔙 Kembali</a>");
        }
        exit;
        
    } elseif ($action == 'delete') {
        if (empty($id)) { 
            header("Location: dashboard.php?error=missing_id"); 
            exit; 
        }
        
        // Gunakan transaksi agar kedua tabel terhapus bersamaan
        $conn->begin_transaction();
        try {
            // 1. Ambil nama_lengkap dari tabel penilaian dulu
            $stmtGet = $conn->prepare("SELECT nama_lengkap FROM penilaian WHERE id = ? LIMIT 1");
            if (!$stmtGet) {
                throw new Exception($conn->error);
            }
            $stmtGet->bind_param("i", $id);
            $stmtGet->execute();
            $resultGet = $stmtGet->get_result();

            if ($resultGet === false) {
                throw new Exception("Gagal membaca data penilaian");
            }

            if ($resultGet->num_rows === 0) {
                throw new Exception("Data penilaian tidak ditemukan");
            }

            $row = $resultGet->fetch_assoc();
            $nama_lengkap = $row['nama_lengkap'];
            
            // 2. Hapus semua penilaian terkait nama_lengkap
            $stmtDeleteNilai = $conn->prepare("DELETE FROM penilaian WHERE LOWER(nama_lengkap) = LOWER(?)");
            if (!$stmtDeleteNilai) {
                throw new Exception($conn->error);
            }
            $stmtDeleteNilai->bind_param("s", $nama_lengkap);
            if (!$stmtDeleteNilai->execute()) {
                throw new Exception($stmtDeleteNilai->error);
            }

            // 3. Hapus user yang bersangkutan (role peserta)
            $stmtDeleteUser = $conn->prepare("DELETE FROM users WHERE LOWER(nama_lengkap) = LOWER(?) AND LOWER(role) = 'peserta'");
            if (!$stmtDeleteUser) {
                throw new Exception($conn->error);
            }
            $stmtDeleteUser->bind_param("s", $nama_lengkap);
            if (!$stmtDeleteUser->execute()) {
                throw new Exception($stmtDeleteUser->error);
            }
            
            // 4. Commit transaksi (simpan semua perubahan)
            $conn->commit();
            header("Location: dashboard.php?status=deleted");
            exit;
            
        } catch (Throwable $e) {
            // Jika ada error, rollback (batalkan semua perubahan)
            $conn->rollback();
            die("❌ Gagal Hapus User: " . htmlspecialchars($e->getMessage()) . " <a href='dashboard.php'>🔙 Kembali</a>");
        }
    }
}

$result = $conn->query("SELECT * FROM penilaian ORDER BY tanggal DESC");

$pesertaList = [];
$pesertaResult = $conn->query("SELECT nama_lengkap FROM users WHERE LOWER(role) = 'peserta' ORDER BY nama_lengkap ASC");
if ($pesertaResult && $pesertaResult->num_rows > 0) {
    while ($peserta = $pesertaResult->fetch_assoc()) {
        $pesertaList[] = $peserta['nama_lengkap'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Dashboard Admin - Sistem Penilaian</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #6B21A8 0%, #7C3AED 50%, #9333EA 100%); min-height: 100vh; color: white; }
.header { background: rgba(0,0,0,0.2); backdrop-filter: blur(10px); padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
.brand-title { display: flex; align-items: center; gap: 10px; font-size: 22px; font-weight: 700; }
.brand-logo { width: 36px; height: 36px; object-fit: contain; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3)); }
.user-info { display: flex; gap: 15px; align-items: center; }
.role-badge { background: rgba(167,139,250,0.2); padding: 5px 12px; border-radius: 8px; }
.btn-logout { background: rgba(255,255,255,0.15); padding: 8px 20px; border-radius: 10px; text-decoration: none; color: white; transition: 0.3s; }
.btn-logout:hover { background: rgba(255,255,255,0.3); }
.container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
.welcome-box { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); padding: 25px; border-radius: 20px; margin-bottom: 30px; }
.settings-box { margin-top: 18px; padding: 18px; border-radius: 16px; background: rgba(0,0,0,0.12); border: 1px solid rgba(255,255,255,0.12); }
.settings-form label { display: block; margin-bottom: 8px; font-size: 14px; opacity: 0.9; }
.settings-grid { display: grid; grid-template-columns: minmax(0, 220px) auto; gap: 14px; align-items: end; }

/* =============================================
   INPUT KKM - HILANGKAN SPINNER/ARROW
   ============================================= */
.settings-form input[type="number"] {
    width: 100%;
    padding: 12px;
    background: rgba(0,0,0,0.2);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 10px;
    color: white;
    font-size: 14px;
    -webkit-appearance: none;
    -moz-appearance: textfield;
    appearance: textfield;
}

/* Hilangkan spinner di Chrome, Safari, Edge, Opera */
.settings-form input[type="number"]::-webkit-inner-spin-button,
.settings-form input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
    display: none;
}

/* Hilangkan spinner di Firefox */
.settings-form input[type="number"] {
    appearance: textfield;
    -moz-appearance: textfield;
}

.settings-form input[type="number"]:focus {
    outline: none;
    border-color: #A78BFA;
}

.settings-actions { display: flex; align-items: stretch; }
.btn-save-kkm { width: 100%; padding: 12px 18px; border: none; border-radius: 10px; background: linear-gradient(135deg, #FB7185, #F43F5E); color: white; font-weight: 700; cursor: pointer; transition: 0.3s; }
.btn-save-kkm:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(244, 63, 94, 0.25); }
.settings-note { margin-top: 12px; font-size: 13px; color: rgba(255,255,255,0.75); line-height: 1.5; }
.alert { padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; }
.alert-success { background: rgba(34,197,94,0.2); border: 1px solid rgba(34,197,94,0.4); color: #86EFAC; }
.alert-error { background: rgba(239,68,68,0.2); border: 1px solid rgba(239,68,68,0.4); color: #FCA5A5; }
.data-table { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 20px; overflow: hidden; }
.table-header { background: linear-gradient(135deg, #7C3AED, #9333EA); padding: 18px 24px; position: relative; text-align: center; font-size: 18px; font-weight: 600; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; }
.btn-add { background: rgba(255,255,255,0.2); border: none; padding: 8px 16px; border-radius: 8px; color: white; cursor: pointer; transition: 0.3s; font-size: 14px; }
.btn-add:hover { background: rgba(255,255,255,0.3); }
.table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
table { width: 100%; border-collapse: collapse; min-width: 850px; }
th, td { padding: 14px 18px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.08); white-space: nowrap; }
th { background: rgba(0,0,0,0.15); font-weight: 600; }
tr:hover td { background: rgba(255,255,255,0.05); }
th:last-child, td:last-child { text-align: center; }
.aksi-wrap { display: flex; justify-content: center; align-items: center; gap: 8px; flex-wrap: nowrap; }
.aksi-wrap form { margin: 0; }

/* =============================================
   ACTION BUTTON - UKURAN SAMA DI SEMUA DEVICE
   ============================================= */
.action-btn {
    width: 40px;
    height: 40px;
    padding: 0;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    color: white;
    transition: transform 0.2s, background 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    line-height: 1;
}
.action-btn:hover { transform: scale(1.08); }
.btn-edit { background: #F59E0B; }
.btn-edit:hover { background: #D97706; }

/* Tombol Hapus: Kotak Merah, Icon Sampah Putih */
.btn-delete {
    background: #EF4444;
    color: #ffffff;
}
.btn-delete:hover { background: #DC2626; }
.btn-delete .icon-trash {
    color: #ffffff;
    font-size: 18px;
    display: inline-block;
}

.status-lulus { background: rgba(34,197,94,0.2); padding: 6px 14px; border-radius: 8px; color: #22C55E; display: inline-block; white-space: nowrap; font-weight: 600; }
.status-tidak-lulus { background: rgba(239,68,68,0.2); padding: 6px 14px; border-radius: 8px; color: #EF4444; display: inline-block; white-space: nowrap; font-weight: 600; }
.modal { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(5px); align-items: center; justify-content: center; z-index: 1000; padding: 16px; }
.modal-content { background: linear-gradient(135deg, #4C1D95, #6B21A8); padding: 30px; border-radius: 20px; max-width: 500px; width: 100%; max-height: 90vh; overflow-y: auto; position: relative; }
.close { position: absolute; top: 15px; right: 20px; font-size: 28px; cursor: pointer; color: white; line-height: 1; }
#modalTitle { margin-bottom: 18px; padding-right: 30px; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-size: 14px; opacity: 0.9; }
.form-group input, .form-group select { width: 100%; padding: 12px; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.2); border-radius: 10px; color: white; font-size: 14px; }
.form-group input:focus, .form-group select:focus { outline: none; border-color: #A78BFA; }
.form-group select option { color: #111827; }

/* =======================================================
   PERBAIKAN SPESIFIK UNTUK INPUT LIST & SELECT
   ======================================================= */

/* 1. HILANGKAN ARROW PADA NAMA LENGKAP (INPUT LIST) */
.form-group input[list] {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}

.form-group input[list]::-webkit-calendar-picker-indicator {
    display: none !important;
    opacity: 0;
    pointer-events: none;
}

/* 2. TAMPILKAN ARROW HANYA PADA ROLE (SELECT) */
.form-group select {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    padding: 12px 40px 12px 12px; /* Padding kanan untuk space arrow */
    background: rgba(0,0,0,0.2);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 10px;
    color: white;
    font-size: 14px;
    /* Custom Arrow Icon */
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='white' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 12px;
}

/* 3. HILANGKAN SPINNER PADA INPUT NUMBER */
.form-group input[type="number"]::-webkit-inner-spin-button,
.form-group input[type="number"]::-webkit-outer-spin-button {
    -webkit-appearance: none;
    margin: 0;
    display: none;
}

.form-group input[type="number"] {
    -moz-appearance: textfield;
    appearance: textfield;
}
/* ======================================================= */

.btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #FB7185, #F43F5E); border: none; border-radius: 10px; color: white; font-weight: bold; cursor: pointer; margin-top: 10px; transition: 0.3s; }
.btn-submit:hover { opacity: 0.9; }
.hidden-group { display: none; }

/* ============================================
   KHUSUS MOBILE / HANDPHONE (max-width: 768px)
   ============================================ */
@media screen and (max-width: 768px) {
    /* Header mobile */
    .header {
        padding: 14px 16px;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 10px;
    }
    
    .brand-title {
        font-size: 18px;
        gap: 8px;
    }
    
    .brand-logo {
        width: 30px;
        height: 30px;
    }
    
    .user-info {
        gap: 10px;
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .role-badge {
        font-size: 12px;
        padding: 4px 10px;
    }
    
    .btn-logout {
        padding: 6px 16px;
        font-size: 13px;
    }
    
    /* Container & Welcome */
    .container {
        margin: 20px auto;
        padding: 0 14px;
    }
    
    .welcome-box {
        padding: 18px 16px;
        border-radius: 16px;
        margin-bottom: 16px;
    }
    
    .welcome-box h2 {
        font-size: 17px;
        text-align: center;
    }
    
    /* Alert */
    .alert {
        padding: 12px 16px;
        margin-bottom: 16px;
        font-size: 13px;
        border-radius: 10px;
    }
    
    /* Table Header - PERBAIKAN ALIGNMENT */
    .data-table {
        border-radius: 16px;
    }
    
    .table-header {
        padding: 14px 16px;
        font-size: 16px;
        justify-content: space-between; /* KIRI-KANAN */
        text-align: left;
        flex-wrap: nowrap; /* Jangan wrap */
    }
    
    .table-header > span {
        white-space: nowrap;
    }
    
    .btn-add {
        padding: 7px 16px;
        font-size: 13px;
        border-radius: 9px;
        flex-shrink: 0; /* Jangan mengecil */
    }
    
    /* Table Mobile Optimized */
    .table-wrapper {
        border-radius: 0 0 16px 16px;
        -webkit-overflow-scrolling: touch;
    }
    
    table {
        min-width: 750px;
        font-size: 13px;
    }
    
    th, td {
        padding: 12px 14px;
        font-size: 13px;
    }
    
    th {
        font-size: 12px;
        padding: 13px 14px;
        font-weight: 600;
    }
    
    /* =============================================
       ACTION BUTTON MOBILE - UKURAN SAMA DENGAN DESKTOP
       ============================================= */
    .action-btn {
        width: 40px;
        height: 40px;
        font-size: 18px;
    }
    
    /* Status badges mobile */
    .status-lulus, .status-tidak-lulus {
        padding: 5px 12px;
        font-size: 12px;
        border-radius: 7px;
    }
    
    /* Modal Mobile */
    .modal {
        padding: 12px;
        align-items: flex-start;
        padding-top: 30px;
    }
    
    .modal-content {
        padding: 24px 18px;
        border-radius: 18px;
        max-height: 88vh;
        margin: 0 auto;
    }
    
    .close {
        top: 12px;
        right: 16px;
        font-size: 26px;
    }
    
    #modalTitle {
        font-size: 17px;
        margin-bottom: 16px;
        padding-right: 26px;
        text-align: center;
    }
    
    .form-group label {
        font-size: 13px;
        margin-bottom: 6px;
    }
    
    .form-group input, .form-group select {
        padding: 12px 14px;
        font-size: 14px;
        border-radius: 9px;
    }
    
    /* Mobile Fix: Nama Lengkap (Hilangkan Arrow) */
    .form-group input[list] {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
    }
    .form-group input[list]::-webkit-calendar-picker-indicator {
        display: none !important;
    }
    
    /* Mobile Fix: Role (Tampilkan Arrow) */
    .form-group select {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        padding: 12px 35px 12px 12px;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='white' viewBox='0 0 16 16'%3E%3Cpath d='M8 11L3 6h10l-5 5z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
        background-size: 12px;
    }
    
    /* Mobile Fix: Hilangkan Spinner Number */
    .form-group input[type="number"]::-webkit-inner-spin-button,
    .form-group input[type="number"]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
        display: none;
    }
    
    .btn-submit {
        padding: 13px;
        font-size: 15px;
        border-radius: 11px;
        margin-top: 8px;
    }

    .settings-box {
        padding: 16px;
    }

    .settings-grid {
        grid-template-columns: 1fr;
    }

    .settings-actions {
        width: 100%;
    }

    .btn-save-kkm {
        width: 100%;
    }
    
    /* Mobile: Hilangkan spinner pada input KKM */
    .settings-form input[type="number"]::-webkit-inner-spin-button,
    .settings-form input[type="number"]::-webkit-outer-spin-button {
        -webkit-appearance: none;
        margin: 0;
        display: none;
    }
    
    .settings-form input[type="number"] {
        appearance: textfield;
        -moz-appearance: textfield;
    }
}

/* HP sangat kecil */
@media screen and (max-width: 360px) {
    table { min-width: 700px; }
    th, td { padding: 10px 12px; font-size: 12px; }
    
    /* Action button tetap sama ukurannya */
    .action-btn {
        width: 38px;
        height: 38px;
        font-size: 16px;
    }
    
    .brand-title { font-size: 16px; }
    #modalTitle { font-size: 16px; }
    .form-group input, .form-group select { font-size: 13px; padding: 11px 13px; }
    .table-header { font-size: 14px; }
    .btn-add { padding: 6px 14px; font-size: 12px; }
}
</style>
</head>
<body>
<div class="header">
    <div class="brand-title">
        <img src="../assets/logo-da.png" alt="Logo Dewan Ambalan" class="brand-logo">
        <span>Dashboard Admin</span>
    </div>
    <div class="user-info">
        <span>👤 <?php echo htmlspecialchars(isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'Admin'); ?></span>
        <span class="role-badge"><?php echo htmlspecialchars(isset($_SESSION['role']) ? $_SESSION['role'] : 'admin'); ?></span>
        <a href="../user/logout.php" class="btn-logout">Keluar</a>
    </div>
</div>
<div class="container">
    <div class="welcome-box">
        <h2>Manajemen User & Penilaian</h2>
        <div class="settings-box">
            <form method="POST" class="settings-form">
                <input type="hidden" name="action" value="update_kkm">
                <div class="settings-grid">
                    <div>
                        <label for="kkm">KKM Saat Ini</label>
                        <input type="number" step="0.01" min="0" max="100" name="kkm" id="kkm" value="<?php echo htmlspecialchars(number_format($kkm, 2, '.', '')); ?>" required>
                    </div>
                    <div class="settings-actions">
                        <button type="submit" class="btn-save-kkm">Simpan KKM</button>
                    </div>
                </div>
                <div class="settings-note">Nilai ini digunakan sebagai KKM untuk menentukan status LULUS / TIDAK LULUS.</div>
            </form>
        </div>
    </div>
    <?php if (isset($_GET['status'])): ?>
        <div class="alert alert-success">
            ✅
            <?php
            $statusCode = $_GET['status'];
            if ($statusCode === 'user_added') {
                echo 'User baru berhasil ditambahkan.';
            } elseif ($statusCode === 'kkm_updated') {
                echo 'KKM berhasil disimpan.';
            } elseif ($statusCode === 'updated') {
                echo 'Data nilai berhasil diupdate.';
            } elseif ($statusCode === 'deleted') {
                echo 'Data nilai berhasil dihapus.';
            } else {
                echo 'Operasi berhasil.';
            }
            ?>
        </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error">
            ❌ Error:
            <?php
            $errorCode = $_GET['error'];
            if ($errorCode === 'missing_id') {
                echo 'ID data tidak ditemukan.';
            } elseif ($errorCode === 'nama_kosong') {
                echo 'Nama lengkap wajib diisi.';
            } elseif ($errorCode === 'kkm_tidak_valid') {
                echo 'KKM harus berupa angka dari 0 sampai 100.';
            } elseif ($errorCode === 'invalid_nilai') {
                echo 'Nilai tidak valid. Nilai peserta harus antara 0 sampai 100.';
            } elseif ($errorCode === 'peserta_tidak_ditemukan') {
                echo 'Nama peserta tidak ditemukan pada tabel users (role peserta).';
            } elseif ($errorCode === 'role_tidak_valid') {
                echo 'Role tidak valid. Gunakan admin atau peserta.';
            } elseif ($errorCode === 'user_sudah_ada') {
                echo 'User dengan nama tersebut sudah terdaftar.';
            } elseif ($errorCode === 'aksi_tidak_valid') {
                echo 'Aksi tambah lama sudah tidak dipakai. Gunakan tambah user baru.';
            } else {
                echo htmlspecialchars($errorCode);
            }
            ?>
        </div>
    <?php endif; ?>
    
    <div class="data-table">
        <div class="table-header">
            <span>📊 Data Penilaian</span>
            <button class="btn-add" id="btnTambah">+ Tambah User</button>
        </div>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr><th>Nomor</th><th>Nama Lengkap</th><th>Wawancara</th><th>Tes Tulis</th><th>CV, Visi, Misi dan Proker</th><th>Jumlah Nilai</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                <?php
                $no = 1;
                if ($result && $result->num_rows > 0):
                    while($row = $result->fetch_assoc()):
                        $total_skor = floatval($row['nilai_wawancara']) + floatval($row['nilai_tes_tulis']) + floatval($row['nilai_cv_proker']);
                        $status = $total_skor >= $kkm ? 'LULUS' : 'TIDAK LULUS';
                        $class = $total_skor >= $kkm ? 'status-lulus' : 'status-tidak-lulus';
                ?>
                <tr data-id="<?php echo $row['id']; ?>" 
                    data-nama_lengkap="<?php echo htmlspecialchars($row['nama_lengkap']); ?>" 
                    data-wawancara="<?php echo $row['nilai_wawancara']; ?>" 
                    data-tes_tulis="<?php echo $row['nilai_tes_tulis']; ?>" 
                    data-cv_proker="<?php echo $row['nilai_cv_proker']; ?>">
                    <td><?php echo $no++; ?></td>
                    <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                    <td><?php echo number_format($row['nilai_wawancara'],2); ?></td>
                    <td><?php echo number_format($row['nilai_tes_tulis'],2); ?></td>
                    <td><?php echo number_format($row['nilai_cv_proker'],2); ?></td>
                    <td style="color:#A78BFA; font-weight:bold"><?php echo number_format($total_skor,2); ?></td>
                    <td><span class="<?php echo $class; ?>"><?php echo $status; ?></span></td>
                    <td>
                        <div class="aksi-wrap">
                            <button class="action-btn btn-edit btn-edit-data" type="button" title="Edit">✏️</button>
                            <form method="POST" onsubmit="return confirm('Yakin hapus data ini?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="action-btn btn-delete" title="Hapus"><span class="icon-trash">🗑️</span></button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="8" style="text-align:center; padding:30px;">Belum ada data penilaian</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL FORM -->
<div id="modalForm" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModalBtn">&times;</span>
        <h3 id="modalTitle">Tambah Data</h3>
        <form method="POST" id="mainForm">
            <input type="hidden" name="action" id="formAction">
            <input type="hidden" name="id" id="formId">
            <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama_lengkap" id="nama_lengkap" list="list-peserta" required></div>
            <datalist id="list-peserta">
                <?php foreach ($pesertaList as $namaPeserta): ?>
                    <option value="<?php echo htmlspecialchars($namaPeserta); ?>"></option>
                <?php endforeach; ?>
            </datalist>
            <div class="form-group only-add-user" id="roleGroup">
                <label>Role</label>
                <select name="role" id="role">
                    <option value="admin">Admin</option>
                    <option value="peserta" selected>Peserta</option>
                </select>
            </div>
            <div class="form-group" id="wawancaraGroup"><label>Nilai Wawancara ( Maksimal Nilai : 100 )</label><input type="number" step="0.01" min="0" max="100" name="nilai_wawancara" id="wawancara"></div>
            <div class="form-group" id="tesGroup"><label>Nilai Tes Tulis ( Maksimal Nilai : 100 )</label><input type="number" step="0.01" min="0" max="100" name="nilai_tes_tulis" id="tes_tulis"></div>
            <div class="form-group" id="cvGroup"><label>Nilai CV, Proker & Visi Misi ( Maksimal Nilai : 100 )</label><input type="number" step="0.01" min="0" max="100" name="nilai_cv_proker" id="cv_proker"></div>
            <button type="submit" class="btn-submit" id="btnSubmit">Simpan Data</button>
        </form>
    </div>
</div>

<script>
var modal = document.getElementById('modalForm');
var actionInput = document.getElementById('formAction');
var idInput = document.getElementById('formId');
var modalTitle = document.getElementById('modalTitle');
var namaLengkapInput = document.getElementById('nama_lengkap');
var roleInput = document.getElementById('role');
var wawancaraInput = document.getElementById('wawancara');
var tesTulisInput = document.getElementById('tes_tulis');
var cvProkerInput = document.getElementById('cv_proker');
var roleGroup = document.getElementById('roleGroup');
var wawancaraGroup = document.getElementById('wawancaraGroup');
var tesGroup = document.getElementById('tesGroup');
var cvGroup = document.getElementById('cvGroup');
var btnSubmit = document.getElementById('btnSubmit');
var btnTambah = document.getElementById('btnTambah');
var closeModalBtn = document.getElementById('closeModalBtn');

function setScoreFieldsRequired(isRequired) {
    wawancaraInput.required = isRequired;
    tesTulisInput.required = isRequired;
    cvProkerInput.required = isRequired;
}

function toggleScoreFieldsForRole() {
    var isPeserta = roleInput.value === 'peserta';
    wawancaraGroup.classList.toggle('hidden-group', !isPeserta);
    tesGroup.classList.toggle('hidden-group', !isPeserta);
    cvGroup.classList.toggle('hidden-group', !isPeserta);
    setScoreFieldsRequired(isPeserta);
    if (!isPeserta) {
        wawancaraInput.value = '';
        tesTulisInput.value = '';
        cvProkerInput.value = '';
    }
}

function openAddModal() {
    actionInput.value = 'add_user';
    idInput.value = '';
    modalTitle.innerText = 'Tambah User Baru';
    namaLengkapInput.value = '';
    roleInput.value = 'peserta';
    wawancaraInput.value = '';
    tesTulisInput.value = '';
    cvProkerInput.value = '';

    roleGroup.style.display = 'block';
    toggleScoreFieldsForRole();

    btnSubmit.innerText = 'Buat User';
    modal.style.display = 'flex';
}

function openEditModal(btn) {
    var row = btn.closest('tr');
    actionInput.value = 'edit';
    idInput.value = row.getAttribute('data-id');
    modalTitle.innerText = 'Edit Data - ' + row.getAttribute('data-nama_lengkap');
    namaLengkapInput.value = row.getAttribute('data-nama_lengkap');
    wawancaraInput.value = row.getAttribute('data-wawancara');
    tesTulisInput.value = row.getAttribute('data-tes_tulis');
    cvProkerInput.value = row.getAttribute('data-cv_proker');

    roleGroup.style.display = 'none';
    wawancaraGroup.classList.remove('hidden-group');
    tesGroup.classList.remove('hidden-group');
    cvGroup.classList.remove('hidden-group');
    setScoreFieldsRequired(true);

    btnSubmit.innerText = 'Update Data';
    modal.style.display = 'flex';
}
function closeModal() { modal.style.display = 'none'; }
btnTambah.addEventListener('click', openAddModal);
roleInput.addEventListener('change', function() {
    if (actionInput.value === 'add_user') {
        toggleScoreFieldsForRole();
    }
});
closeModalBtn.addEventListener('click', closeModal);
window.addEventListener('click', function(e) { if (e.target === modal) closeModal(); });
document.querySelectorAll('.btn-edit-data').forEach(btn => btn.addEventListener('click', function() { openEditModal(this); }));
</script>
</body>
</html>