<?php
session_start();

// Cek login — kalau belum login, langsung arahkan ke halaman login
if (!isset($_SESSION['admin'])) {
  header("Location: ../auth/login.php");
  exit;
}

// Handle AJAX request untuk check profile update
if (isset($_GET['check_profile_update']) && isset($_GET['last_check'])) {
    header('Content-Type: application/json');
    
    $response = [
        'updated' => false,
        'foto' => null,
        'timestamp' => $_SESSION['profile_timestamp'] ?? 0
    ];
    
    $last_check = intval($_GET['last_check']);
    
    // Jika timestamp session lebih baru dari last_check, berarti ada update
    if (($_SESSION['profile_timestamp'] ?? 0) > $last_check) {
        $response['updated'] = true;
        $response['foto'] = $_SESSION['foto'] ?? '';
        $response['timestamp'] = $_SESSION['profile_timestamp'];
    }
    
    echo json_encode($response);
    exit;
}

// Handle AJAX request untuk save profile image ke localStorage
if (isset($_GET['save_profile_to_storage']) && isset($_GET['foto'])) {
    header('Content-Type: application/json');
    
    // Dalam implementasi real, Anda akan menyimpan ini ke database
    // Tapi untuk sekarang kita hanya konfirmasi
    $response = [
        'success' => true,
        'message' => 'Profile akan disimpan permanen'
    ];
    
    echo json_encode($response);
    exit;
}

// Siapkan variabel untuk foto profil
$foto_profil = "https://i.pravatar.cc/100"; // Default

// Cek apakah ada foto profil di session
if (isset($_SESSION['foto']) && !empty($_SESSION['foto'])) {
    $foto_path = "../uploads/" . $_SESSION['foto'];
    if (file_exists($foto_path)) {
        $foto_profil = $foto_path;
    }
}

// Inisialisasi timestamp untuk foto profil
if (!isset($_SESSION['profile_timestamp'])) {
    $_SESSION['profile_timestamp'] = time();
}

// Ambil username untuk dijadikan key localStorage
$username = $_SESSION['admin'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | BM Garage</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

    body {
      background: #f8faff;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      padding-bottom: 90px;
    }

    header {
      background: linear-gradient(135deg, #0022a8, #0044ff);
      color: #fff;
      padding: 25px 20px 80px;
      border-bottom-left-radius: 30px;
      border-bottom-right-radius: 30px;
      position: relative;
    }

    header .profile {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 10px;
    }

    header .profile img {
      width: 45px; height: 45px;
      border-radius: 50%;
      border: 2px solid #fff;
      object-fit: cover;
      transition: opacity 0.3s ease;
    }

    header h2 { font-size: 1.2rem; margin-bottom: 3px; }
    header p { font-size: 0.85rem; opacity: 0.9; }

    .search-box {
      background: #fff;
      border-radius: 15px;
      width: 100%;
      box-shadow: 0 6px 25px rgba(0,0,0,0.15);
      display: flex;
      align-items: center;
      padding: 12px 16px;
      border: 1px solid #e8f0ff;
    }

    .search-box input {
      border: none;
      outline: none;
      flex: 1;
      padding: 10px;
      font-size: 0.95rem;
      color: #333;
      background: transparent;
    }

    .content { padding: 60px 20px 100px; flex: 1; }
    .content h3 { font-size: 1rem; margin-bottom: 15px; color: #333; }

    .task-summary {
      background: #fff;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      margin-bottom: 25px;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 15px;
      margin-bottom: 25px;
    }

    .stat-card {
      background: #fff;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      text-align: center;
      border-top: 4px solid #0038ff;
    }

    .stat-card:nth-child(2) { border-top-color: #00c853; }
    .stat-card:nth-child(3) { border-top-color: #ff9800; }
    .stat-card:nth-child(4) { border-top-color: #f44336; }

    .stat-card .stat-number {
      font-size: 1.8rem;
      font-weight: 600;
      margin-bottom: 5px;
    }

    .tasks-section {
      background: #fff;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      margin-bottom: 25px;
    }

    .task-tabs { display: flex; border-bottom: 1px solid #f0f0f0; margin-bottom: 15px; }
    .task-tab {
      flex: 1;
      text-align: center;
      padding: 10px;
      font-size: 0.85rem;
      color: #666;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .task-tab.active {
      color: #0038ff;
      border-bottom: 2px solid #0038ff;
      font-weight: 600;
    }

    .task-list { display: flex; flex-direction: column; gap: 12px; }
    .task-card {
      background: #f8faff;
      border-radius: 8px;
      padding: 12px;
      border-left: 3px solid #0038ff;
    }

    .user-card {
      background: #fff;
      border-radius: 12px;
      padding: 15px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      margin-bottom: 15px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: 0.2s;
    }

    .btn-detail {
      background: #0038ff;
      color: #fff;
      border: none;
      border-radius: 6px;
      padding: 6px 10px;
      font-size: 0.8rem;
      cursor: pointer;
      transition: 0.3s;
    }

    .bottom-nav {
      position: fixed;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      background: #ffffff;
      display: flex;
      justify-content: space-between;
      align-items: center;
      width: 320px;
      padding: 8px;
      border-radius: 50px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
      z-index: 100;
    }

    .bottom-nav a {
      flex: 1;
      text-align: center;
      color: #2455ff;
      text-decoration: none;
      font-weight: 600;
      border-radius: 40px;
      padding: 10px 0;
      font-size: 14px;
      transition: all 0.3s ease;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }

    .bottom-nav a.active { background: #2455ff; color: #fff; }

    /* Loading indicator untuk foto profil */
    .profile-loading {
      opacity: 0.5;
      filter: blur(1px);
    }

    .profile-updated {
      animation: pulse 0.5s ease-in-out;
    }

    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }
  </style>
</head>
<body>

  <header>
    <div class="profile">
      <img src="<?= htmlspecialchars($foto_profil) ?>?t=<?= $_SESSION['profile_timestamp'] ?>" alt="profile" id="profile-picture">
      <div>
        <h2>Hi, <?= htmlspecialchars($_SESSION['admin']); ?></h2>
        <p>Selamat datang kembali 👋</p>
      </div>
    </div>

    <div class="search-box">
      🔍 <input type="text" placeholder="Search project" />
    </div>
  </header>

  <div class="content">
    <div class="task-summary">
      <h3>Anda Memiliki total 100 tugas</h3>
    </div>

    <div class="stats-grid">
      <div class="stat-card"><div class="stat-number">100</div><div class="stat-label">Tugas diberikan</div></div>
      <div class="stat-card"><div class="stat-number">100</div><div class="stat-label">Tugas selesai</div></div>
      <div class="stat-card"><div class="stat-number">100</div><div class="stat-label">Belum Dikerjakan</div></div>
      <div class="stat-card"><div class="stat-number">100</div><div class="stat-label">Tugas dibuat</div></div>
    </div>

    <div class="tasks-section">
      <h3>Tasks</h3>
      <div class="task-tabs">
        <div class="task-tab active">Hari ini</div>
        <div class="task-tab">Mendatang</div>
        <div class="task-tab">Selesai</div>
      </div>
      <div class="task-list">
        <div class="task-card"><h4>Sedang Babylon</h4><p>Proyek pengembangan sistem manajemen</p></div>
        <div class="task-card"><h4>To Do List Management</h4><p>Sistem manajemen tugas harian</p></div>
      </div>
    </div>

    <h3>Anggota Tim</h3>
    <div class="user-card"><div class="user-info"><h4>Aditya Devinza</h4><p>adityadevinza87@gmail.com</p></div><button class="btn-detail">Detail</button></div>
    <div class="user-card"><div class="user-info"><h4>Celeng Pratama</h4><p>celengpratama@gmail.com</p></div><button class="btn-detail">Detail</button></div>
    <div class="user-card"><div class="user-info"><h4>Bima Putra</h4><p>bimaputra84@gmail.com</p></div><button class="btn-detail">Detail</button></div>
  </div>

  <div class="bottom-nav">
    <a href="dashboard.php" class="active"><i class="fas fa-home"></i><span>Home</span></a>
    <a href="tasks.php"><i class="fas fa-tasks"></i><span>Tasks</span></a>
    <a href="users.php"><i class="fas fa-users"></i><span>Users</span></a>
    <a href="profile.php"><i class="fas fa-user"></i><span>Profile</span></a>
  </div>

  <script>
    // Key untuk localStorage berdasarkan username
    const username = '<?= $username ?>';
    const PROFILE_STORAGE_KEY = `bm_garage_profile_${username}`;

    document.querySelectorAll('.task-tab').forEach(tab => {
      tab.addEventListener('click', function() {
        document.querySelectorAll('.task-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
      });
    });

    // Sistem auto-update foto profil dengan localStorage
    let lastProfileCheck = <?= $_SESSION['profile_timestamp'] ?>;
    const profileImg = document.getElementById('profile-picture');

    // Fungsi untuk convert image ke Base64
    function imageToBase64(imgElement) {
      return new Promise((resolve) => {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        
        canvas.width = imgElement.naturalWidth;
        canvas.height = imgElement.naturalHeight;
        
        ctx.drawImage(imgElement, 0, 0);
        
        try {
          const base64 = canvas.toDataURL('image/jpeg', 0.8);
          resolve(base64);
        } catch (error) {
          console.error('Error converting image to Base64:', error);
          resolve(null);
        }
      });
    }

    // Fungsi untuk menyimpan foto profil ke localStorage sebagai Base64
    async function saveProfileToLocalStorage() {
      try {
        // Tunggu sebentar untuk memastikan gambar sudah dimuat
        await new Promise(resolve => setTimeout(resolve, 500));
        
        const base64Image = await imageToBase64(profileImg);
        
        if (base64Image) {
          const profileData = {
            base64: base64Image,
            timestamp: new Date().getTime(),
            username: username
          };
          
          localStorage.setItem(PROFILE_STORAGE_KEY, JSON.stringify(profileData));
          console.log('✅ Foto profil disimpan permanen di localStorage');
        }
      } catch (error) {
        console.error('❌ Gagal menyimpan foto ke localStorage:', error);
      }
    }

    // Fungsi untuk memuat foto profil dari localStorage
    function loadProfileFromLocalStorage() {
      try {
        const savedProfile = localStorage.getItem(PROFILE_STORAGE_KEY);
        if (savedProfile) {
          const profileData = JSON.parse(savedProfile);
          console.log('✅ Foto profil dimuat dari localStorage');
          return profileData;
        }
      } catch (error) {
        console.error('❌ Gagal memuat dari localStorage:', error);
      }
      return null;
    }

    // Fungsi untuk update foto profil
    function updateProfilePicture(newSrc, timestamp, fromLocalStorage = false) {
      // Tampilkan loading indicator
      profileImg.classList.add('profile-loading');
      
      const newTimestamp = new Date().getTime();
      const finalSrc = fromLocalStorage ? newSrc : `${newSrc}?t=${newTimestamp}`;
      
      // Buat image baru untuk preload
      const newImage = new Image();
      newImage.onload = function() {
        // Setelah gambar berhasil dimuat, update src dan hilangkan loading
        profileImg.src = finalSrc;
        profileImg.classList.remove('profile-loading');
        profileImg.classList.add('profile-updated');
        
        // Update timestamp terakhir
        lastProfileCheck = timestamp;
        
        // Simpan ke localStorage jika bukan dari localStorage
        if (!fromLocalStorage) {
          setTimeout(saveProfileToLocalStorage, 1000);
        }
        
        // Hapus animasi setelah selesai
        setTimeout(() => {
          profileImg.classList.remove('profile-updated');
        }, 500);
      };
      
      newImage.onerror = function() {
        // Jika gagal memuat gambar, gunakan default
        profileImg.src = `https://i.pravatar.cc/100?t=${newTimestamp}`;
        profileImg.classList.remove('profile-loading');
      };
      
      newImage.src = finalSrc;
    }

    // Fungsi untuk memeriksa dan memperbarui foto profil
    async function checkProfileUpdate() {
      try {
        const response = await fetch(`dashboard.php?check_profile_update=1&last_check=${lastProfileCheck}`);
        const data = await response.json();
        
        if (data.updated && data.foto) {
          console.log('🔄 Foto profil diperbarui dari server:', data.foto);
          updateProfilePicture(`../uploads/${data.foto}`, data.timestamp);
        }
      } catch (error) {
        console.error('❌ Error checking profile update:', error);
      }
    }

    // Saat halaman dimuat, prioritaskan localStorage
    window.addEventListener('load', function() {
      const savedProfile = loadProfileFromLocalStorage();
      if (savedProfile && savedProfile.base64) {
        console.log('🎯 Menggunakan foto profil dari localStorage');
        updateProfilePicture(savedProfile.base64, savedProfile.timestamp, true);
      } else {
        // Jika tidak ada di localStorage, gunakan dari server
        console.log('🌐 Memuat foto profil dari server');
        const currentProfileSrc = '<?= htmlspecialchars($foto_profil) ?>?t=<?= $_SESSION['profile_timestamp'] ?>';
        updateProfilePicture(currentProfileSrc, <?= $_SESSION['profile_timestamp'] ?>);
      }
      
      // Kemudian cek update dari server
      setTimeout(checkProfileUpdate, 2000);
    });

    // Simpan foto saat ini ke localStorage saat pertama kali load
    window.addEventListener('load', function() {
      setTimeout(saveProfileToLocalStorage, 3000);
    });

    // Periksa pembaruan setiap 10 detik (kurangi frekuensi)
    setInterval(checkProfileUpdate, 10000);

    // Juga periksa saat halaman difokuskan kembali
    document.addEventListener('visibilitychange', function() {
      if (!document.hidden) {
        checkProfileUpdate();
      }
    });

    // Simpan juga sebelum browser/tab ditutup
    window.addEventListener('beforeunload', function() {
      saveProfileToLocalStorage();
    });
  </script>
</body>
</html>