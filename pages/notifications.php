<?php
session_start();
include_once('../koneksi.php');
if (!isset($_SESSION['username'])) {
    header('Location: ../modules/auth/login.php');
    exit();
}

$username = $_SESSION['username'];
$stmt = $con->prepare("SELECT id FROM user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$uid = $user['id'];

// pastikan tabel notifications ada
$con->query("CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$notifs = $con->query("SELECT * FROM notifications WHERE id_user = " . intval($uid) . " ORDER BY created_at DESC");

// tandai semua sebagai dibaca saat membuka
$con->query("UPDATE notifications SET is_read = 1 WHERE id_user = " . intval($uid));

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Notifikasi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <div class="card p-4 shadow-lg">
    <h4>Notifikasi</h4>
    <?php if ($notifs->num_rows == 0): ?>
      <p class="text-muted">Tidak ada notifikasi.</p>
    <?php else: ?>
      <ul class="list-group">
        <?php while ($n = $notifs->fetch_assoc()): ?>
          <li class="list-group-item">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <?php
                // Cek apakah pesan mengandung link ke PDF (pdf-kembali) atau ke cetak_BA.php
                $msg = $n['message'];
                $url = null;
                // Cari pola /pdf-kembali/... atau cetak_BA.php?file=...
                // Prioritaskan link ke viewer `cetak_BA.php` (bisa berisi path prefix)
                if (preg_match('#([^\s]*cetak_BA\.php\?file=[^\s]+)#i', $msg, $m3)) {
                  $url = $m3[1];
                } elseif (preg_match('#(https?://[^\s]+pdf-kembali/[^\s]+\.pdf)#i', $msg, $m)) {
                  $url = $m[1];
                } elseif (preg_match('#(\/[^\s]+pdf-kembali\/[^\s]+\.pdf)#i', $msg, $m2)) {
                  // relative path
                  $url = $m2[1];
                }

                // Tampilkan pesan tanpa menampilkan raw URL
                // Hapus bagian link dari pesan ketika menampilkan teks
                $displayMsg = preg_replace('#https?://[^\s]+pdf-kembali/[^\s]+\.pdf#i', '', $msg);
                $displayMsg = preg_replace('#pages/cetak_BA.php\?file=[^\s]+#i', '', $displayMsg);
                echo nl2br(htmlspecialchars(trim($displayMsg)));
                ?>
                <?php if ($url): ?>
                  <div class="mt-2">
                    <button type="button" class="btn btn-sm btn-primary view-pdf-btn" data-url="<?= htmlspecialchars($url) ?>">Lihat Berita Acara</button>
                  </div>
                <?php endif; ?>
              </div>
              <small class="text-muted ms-3"><?= $n['created_at'] ?></small>
            </div>
          </li>
        <?php endwhile; ?>
      </ul>
    <?php endif; ?>
    <?php
    $role = $_SESSION['role'] ?? '';
    $backLink = '../index.php';
    if ($role === 'admin_kendaraan') {
      $backLink = '../modules/admin/admin_kendaraan.php';
    } elseif ($role === 'admin_ruangan') {
      $backLink = '../modules/admin/admin_ruangan.php';
    } elseif ($role === 'super_admin') {
      $backLink = '../modules/admin/superadmin.php';
    }
    ?>
    <a href="<?= htmlspecialchars($backLink) ?>" class="btn btn-outline-secondary mt-3">Kembali</a>
  </div>
</div>
</body>
</html>

  <!-- Modal untuk preview PDF -->
  <div class="modal fade" id="pdfModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Preview Berita Acara</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" style="height:80vh;">
          <iframe id="pdfFrame" src="" style="width:100%; height:100%; border:0;" title="Preview PDF"></iframe>
        </div>
      </div>
    </div>
  </div>

  <!-- load bootstrap bundle (modal requires JS) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.view-pdf-btn').forEach(btn => {
      btn.addEventListener('click', function(e){
        e.preventDefault();
        let url = this.getAttribute('data-url');
        if (!url) return;

        // Build absolute URL when needed but DO NOT navigate away
        if (url.charAt(0) === '/') {
          // keep as absolute path from host
          url = window.location.origin + url;
        } else if (!url.match(/^https?:\/\//)) {
          // relative to application root
          url = (window.location.origin + '/' + url).replace(/([^:]\/)\/+/, '$1');
        }

        // set iframe src and show modal; do not open new tab
        var iframe = document.getElementById('pdfFrame');
        iframe.src = url;

        try {
          var modalEl = document.getElementById('pdfModal');
          var modal = new bootstrap.Modal(modalEl);
          modal.show();
        } catch (err) {
          // jika bootstrap tidak tersedia, sebagai fallback buka di tab baru
          window.open(url, '_blank');
        }
      });
    });
  });
  </script>
