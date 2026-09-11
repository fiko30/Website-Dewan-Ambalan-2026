<?php
session_start();
require_once '../config/config.php';

// 1. Keamanan: Cek sesi & role
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) != 'peserta') {
    header("Location: login.php");
    exit;
}

$nama_lengkap = $_SESSION['nama_lengkap'] ?? $_SESSION['nama'] ?? 'Siswa';

// 2. Ambil data penilaian terbaru
$query = "SELECT * FROM penilaian WHERE LOWER(nama_lengkap) = LOWER(?) ORDER BY tanggal DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $nama_lengkap);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("❌ Data penilaian tidak ditemukan. Silakan hubungi administrator.");
}

// 3. PERBAIKAN: Pastikan nilai numerik (NULL dijadikan 0)
$nilai_wawancara = isset($data['nilai_wawancara']) ? (float)$data['nilai_wawancara'] : 0;
$nilai_tes_tulis = isset($data['nilai_tes_tulis']) ? (float)$data['nilai_tes_tulis'] : 0;
$nilai_cv_proker = isset($data['nilai_cv_proker']) ? (float)$data['nilai_cv_proker'] : 0;
$kkm = get_kkm($conn);

// 4. Hitung total nilai & tentukan status
$total_skor = $nilai_wawancara + $nilai_tes_tulis + $nilai_cv_proker;
$status = $total_skor >= $kkm ? 'LULUS' : 'TIDAK LULUS';
$warna_status = $total_skor >= $kkm ? 'green' : 'red';
$tanggal = !empty($data['tanggal']) ? date('d F Y', strtotime($data['tanggal'])) : date('d F Y');

// 5. Generate konten HTML
$konten = "
<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <title>Hasil Seleksi - {$nama_lengkap}</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        h2 { text-align: center; color: #4B0082; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f4f4f4; }
        .status { font-weight: bold; color: {$warna_status}; font-size: 18px; text-align: center; margin-top: 20px; }
        .footer { text-align: center; font-size: 12px; color: #888; margin-top: 30px; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <h2>📄 HASIL SELEKSI DA 26</h2>
    <p><strong>Nama Peserta:</strong> {$nama_lengkap}</p>
    <p><strong>Tanggal Penilaian:</strong> {$tanggal}</p>
    <hr>
    <h3>Rincian Nilai</h3>
    <table>
        <tr><th>Komponen</th><th>Nilai</th></tr>
        <tr><td>Wawancara</td><td>" . number_format($nilai_wawancara, 2) . "</td></tr>
        <tr><td>Tes Tulis</td><td>" . number_format($nilai_tes_tulis, 2) . "</td></tr>
        <tr><td>CV, Program Kerja & Visi Misi</td><td>" . number_format($nilai_cv_proker, 2) . "</td></tr>
        <tr><th>Jumlah Nilai</th><th>" . number_format($total_skor, 2) . "</th></tr>
    </table>
    <div class='status'>STATUS: {$status}</div>
    <div class='footer'>Dokumen ini dihasilkan secara otomatis oleh Sistem Penilaian Arunika Estungkara.<br>File ini sah sebagai bukti hasil seleksi.</div>
</body>
</html>";

// 6. Force Download
$nama_aman = preg_replace('/[^A-Za-z0-9_-]/', '_', $nama_lengkap);
$filename = "Hasil_Seleksi_{$nama_aman}_" . date('Ymd') . ".html";

header('Content-Description: File Transfer');
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($konten));
header('Cache-Control: must-revalidate');

echo $konten;
exit;
?>