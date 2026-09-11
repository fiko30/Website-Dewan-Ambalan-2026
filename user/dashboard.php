<?php
session_start();
require_once '../config/config.php';

// Cek login & role siswa
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) != 'peserta') {
    header("Location: login.php");
    exit;
}

$nama_lengkap = $_SESSION['nama_lengkap'] ?? '';
$query = "SELECT * FROM penilaian WHERE LOWER(nama_lengkap) = LOWER(?) ORDER BY tanggal DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $nama_lengkap);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$kkm = get_kkm($conn);

// PERBAIKAN: Pastikan nilai numerik (NULL dijadikan 0)
if ($data) {
    $data['nilai_wawancara'] = isset($data['nilai_wawancara']) ? (float)$data['nilai_wawancara'] : 0;
    $data['nilai_tes_tulis'] = isset($data['nilai_tes_tulis']) ? (float)$data['nilai_tes_tulis'] : 0;
    $data['nilai_cv_proker'] = isset($data['nilai_cv_proker']) ? (float)$data['nilai_cv_proker'] : 0;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Siswa - Sistem Penilaian</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #6B21A8 0%, #7C3AED 50%, #9333EA 100%);
    background-image:
        linear-gradient(135deg, #6B21A8 0%, #7C3AED 50%, #9333EA 100%),
        linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
    background-size: 100% 100%, 40px 40px, 40px 40px;
    min-height: 100vh;
    color: white;
}
.header {
    background: rgba(0, 0, 0, 0.2);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: white;
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.brand-title { display: flex; align-items: center; gap: 10px; font-size: 22px; font-weight: 600; }
.brand-logo { width: 36px; height: 36px; object-fit: contain; filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3)); }
.user-info { display: flex; align-items: center; gap: 15px; }
.btn-logout {
    background: rgba(255, 255, 255, 0.15); color: white; border: 1px solid rgba(255, 255, 255, 0.3);
    padding: 8px 20px; border-radius: 10px; cursor: pointer; text-decoration: none; transition: all 0.3s; font-size: 14px;
}
.btn-logout:hover { background: rgba(255, 255, 255, 0.25); }
.container { max-width: 800px; margin: 30px auto; padding: 0 20px; }

.hero-section {
    text-align: center; padding: 40px 30px; background: linear-gradient(135deg, #7C3AED 0%, #9333EA 100%);
    border-radius: 24px; border: 1px solid rgba(255, 255, 255, 0.2); margin-bottom: 30px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
}
.hero-section h2 { font-size: 28px; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 1px; }
.hero-section .status-text { font-size: 16px; opacity: 0.9; }
.hero-section .status-pill {
    display: inline-block;
    padding: 6px 16px;
    border-radius: 10px;
    font-weight: 800;
    font-size: 18px;
    line-height: 1;
    vertical-align: middle;
    margin: 0 4px;
}
.hero-section .status-pill.lulus {
    background: rgba(34, 197, 94, 0.22);
    color: #22C55E;
    border: 1px solid rgba(34, 197, 94, 0.45);
}
.hero-section .status-pill.tidak-lulus {
    background: rgba(239, 68, 68, 0.22);
    color: #EF4444;
    border: 1px solid rgba(239, 68, 68, 0.45);
}
.status-lulus { color: #22C55E; font-weight: 700; }
.status-tidak-lulus { color: #EF4444; font-weight: 700; }

.score-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}
.score-item {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    padding: 30px 20px;
    text-align: center;
    transition: all 0.3s;
}
.score-item:hover { transform: translateY(-3px); border-color: rgba(255, 255, 255, 0.3); }
.score-item label { display: block; font-size: 18px; color: #FFFFFF; margin-bottom: 12px; font-weight: 700; }
.score-item .value { font-size: 40px; font-weight: 700; }
.score-item .value.red { color: #EF4444; }
.score-item .value.yellow { color: #FBBF24; }
.score-item .value.green { color: #22C55E; }

.final-result {
    text-align: center;
    padding: 35px 30px;
    border-radius: 20px;
    margin-top: 25px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
}
.final-result .rata-rata {
    font-size: 36px;
    margin-bottom: 12px;
    font-weight: 700;
    letter-spacing: 0.5px;
}
.final-result .minimal-lulus {
    font-size: 14px;
    color: rgba(255, 255, 255, 0.85);
    margin-bottom: 10px;
}
.final-result p {
    font-size: 16px;
    opacity: 0.9;
    line-height: 1.6;
}
.final-result .status-badge {
    display: inline-block; padding: 8px 20px; border-radius: 10px; font-weight: 600; margin-top: 10px;
}
.final-result .status-badge.lulus { background: rgba(34, 197, 94, 0.2); color: #22C55E; border: 1px solid rgba(34, 197, 94, 0.4); }
.final-result .status-badge.tidak-lulus { background: rgba(239, 68, 68, 0.2); color: #EF4444; border: 1px solid rgba(239, 68, 68, 0.4); }

.action-buttons { display: flex; flex-direction: column; gap: 15px; margin-top: 30px; }
.btn-primary {
    background: linear-gradient(135deg, #FB7185 0%, #F43F5E 100%); color: white; border: none;
    padding: 16px 30px; border-radius: 14px; font-size: 16px; font-weight: 600; cursor: pointer;
    transition: all 0.3s; text-align: center; text-decoration: none; display: block;
}
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(244, 63, 94, 0.4); }
.btn-primary.disabled {
    opacity: 0.5;
    pointer-events: none;
    cursor: not-allowed;
}
.btn-secondary {
    background: rgba(255, 255, 255, 0.1); color: white; border: 1px solid rgba(255, 255, 255, 0.3);
    padding: 16px 30px; border-radius: 14px; font-size: 16px; font-weight: 500; cursor: pointer;
    transition: all 0.3s; text-align: center; text-decoration: none; display: block;
}
.btn-secondary:hover { background: rgba(255, 255, 255, 0.2); }

.no-data {
    text-align: center; padding: 60px 20px; color: rgba(255, 255, 255, 0.7);
    background: rgba(255, 255, 255, 0.1); border-radius: 20px; border: 1px solid rgba(255, 255, 255, 0.15);
}

/* ========================================
   PERBAIKAN MOBILE RESPONSIVE (TAMBAHAN)
   ======================================== */
@media (max-width: 768px) {
    .header {
        padding: 12px 15px;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .brand-title {
        font-size: 16px;
        flex: 1 1 100%;
        justify-content: center;
        text-align: center;
        min-width: 0;
    }
    
    .brand-title span {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
    }
    
    .brand-logo {
        width: 28px;
        height: 28px;
        flex-shrink: 0;
    }
    
    .user-info {
        flex: 1 1 100%;
        justify-content: center;
        gap: 8px;
        text-align: center;
    }
    
    .user-info span {
        font-size: 13px;
        word-break: break-word;
        max-width: 100%;
        overflow-wrap: break-word;
        white-space: normal;
    }
    
    .btn-logout {
        padding: 6px 14px;
        font-size: 12px;
        border-radius: 8px;
    }
    
    .hero-section h2 {
        font-size: 20px;
        word-break: break-word;
    }
    
    .hero-section .status-text {
        font-size: clamp(11px, 3.1vw, 14px);
        white-space: nowrap;
        line-height: 1.2;
    }

    .hero-section .status-pill {
        font-size: clamp(12px, 3.5vw, 15px);
        padding: 4px 10px;
        margin: 0 2px;
    }
    
    .score-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .score-item .value {
        font-size: 32px;
    }
    
    .final-result .rata-rata {
        font-size: 28px;
    }
}

/* Untuk layar sangat kecil */
@media (max-width: 480px) {
    .header {
        padding: 10px 12px;
    }
    
    .brand-title {
        font-size: 14px;
    }
    
    .user-info span {
        font-size: 12px;
    }
    
    .hero-section {
        padding: 25px 15px;
    }
    
    .hero-section h2 {
        font-size: 18px;
    }
    
    .score-item {
        padding: 20px 15px;
    }
    
    .score-item .value {
        font-size: 28px;
    }
    
    .final-result .rata-rata {
        font-size: 24px;
    }
    
    .btn-primary, .btn-secondary {
        padding: 12px 20px;
        font-size: 14px;
    }
}
</style>
</head>
<body>
<div class="header">
    <div class="brand-title">
        <img src="../assets/logo-da.png" alt="Logo Dewan Ambalan" class="brand-logo">
        <span>Arunika Estungkara</span>
    </div>
    <div class="user-info">
        <span>👤 <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></span>
        <a href="logout.php" class="btn-logout">Keluar</a>
    </div>
</div>

<div class="container">
<?php if ($data): 
    // Hitung total nilai dari seluruh komponen.
    $total_skor = $data['nilai_wawancara'] + $data['nilai_tes_tulis'] + $data['nilai_cv_proker'];
    $status = $total_skor >= $kkm ? 'LULUS' : 'TIDAK LULUS';
    $wawancaraMid = 25.00;
    $tesTulisMid = 10.00;
    $cvProkerMid = 25.00;

    $wawancaraRounded = round($data['nilai_wawancara'], 2);
    $tesTulisRounded = round($data['nilai_tes_tulis'], 2);
    $cvProkerRounded = round($data['nilai_cv_proker'], 2);

    $wawancaraColor = $wawancaraRounded < $wawancaraMid ? 'red' : ($wawancaraRounded > $wawancaraMid ? 'green' : 'yellow');
    $tesTulisColor = $tesTulisRounded < $tesTulisMid ? 'red' : ($tesTulisRounded > $tesTulisMid ? 'green' : 'yellow');
    $cvProkerColor = $cvProkerRounded < $cvProkerMid ? 'red' : ($cvProkerRounded > $cvProkerMid ? 'green' : 'yellow');
?>
<!-- Hero Section -->
<div class="hero-section">
    <h2>
        <?php if ($total_skor >= $kkm): ?>
            SELAMAT KEPADA
        <?php else: ?>
            MOHON MAAF KEPADA
        <?php endif; ?>
        <br>
        <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>
    </h2>
    <p class="status-text">
        DINYATAKAN <span class="status-pill <?php echo $total_skor >= $kkm ? 'lulus' : 'tidak-lulus'; ?>">
            <?php echo $status; ?>
        </span> SELEKSI DA 26
    </p>
</div>

<!-- Score Cards -->
<div class="score-grid">
    <div class="score-item">
        <label>Wawancara</label>
        <div class="value <?php echo $wawancaraColor; ?>">
            <?php echo number_format($data['nilai_wawancara'], 2); ?>
        </div>
    </div>
    <div class="score-item">
        <label>Tes Tulis</label>
        <div class="value <?php echo $tesTulisColor; ?>">
            <?php echo number_format($data['nilai_tes_tulis'], 2); ?>
        </div>
    </div>
    <div class="score-item">
        <label>CV, Proker & Visi Misi</label>
        <div class="value <?php echo $cvProkerColor; ?>">
            <?php echo number_format($data['nilai_cv_proker'], 2); ?>
        </div>
    </div>
</div>

<!-- Final Result -->
<div class="final-result">
    <div class="rata-rata">Jumlah Nilai: <?php echo number_format($total_skor, 2); ?></div>
    <div class="minimal-lulus">Minimal Jumlah Nilai : <?php echo number_format($kkm, 2); ?></div>
    <?php if ($total_skor >= $kkm): ?>
        <span class="status-badge lulus">LULUS</span>
        <p style="margin-top: 15px;">Selamat, Anda dinyatakan <span class="status-lulus">LULUS</span>. <br> Jadilah Dewan Ambalan Sejati !</p>
    <?php else: ?>
           <span class="status-badge tidak-lulus" style="display:inline-block; padding:6px 12px; margin-left:8px; vertical-align:middle;">TIDAK LULUS</span>
           <p style="margin-top: 15px;">Mohon Maaf <br> Anda dinyatakan <span class="status-tidak-lulus">TIDAK LULUS</span><br><br>Jangan Patah Semangat. <br> Wahai Pejuang Sejati.</p>
    <?php endif; ?>
</div>

<!-- Action Buttons -->
<div class="action-buttons">
    <a href="download_hasil.php" class="btn-primary">📥  Download Hasil Seleksi</a>
</div>

<?php else: ?>
<!-- TAMPILAN JIKA BELUM ADA DATA PENILAIAN -->
<div class="no-data">
    <div style="font-size:64px; margin-bottom:15px;">⏳</div>
    <h3>Penilaian Belum Tersedia</h3>
    <p>Halo <?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?>, data penilaian Anda sedang diproses.</p>
    <p style="margin-top:10px; font-size:14px;">Silakan tunggu hingga admin menginput nilai Anda.</p>
</div>

<div class="action-buttons">
    <a href="#" class="btn-primary disabled" title="Belum ada data untuk didownload">📥 Download Hasil Seleksi</a>
</div>
<?php endif; ?>
</div>

<script>
// Cegah download otomatis saat page load
window.onload = function() {
    const downloadBtn = document.querySelector('.btn-primary');
    if (downloadBtn && downloadBtn.classList.contains('disabled')) {
        downloadBtn.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Data penilaian belum tersedia. Silakan tunggu hingga admin menginput nilai Anda.');
        });
    }
};
</script>
</body>
</html>