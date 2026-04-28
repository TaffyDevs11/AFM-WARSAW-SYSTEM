<?php
// Hostinger path: /home/u123456789/public_html/admin/login.php
require_once __DIR__ . '/auth.php';

if (!empty($_SESSION['admin_id'])) { header('Location: overview.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username && $password) {
        if (adminLogin($username, $password)) { header('Location: overview.php'); exit; }
        $error = 'Invalid username or password. Please try again.';
    } else {
        $error = 'Please enter your username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — AFM Warsaw Assembly</title>
  <link rel="icon" type="image/svg+xml" href="../images/logowhite2.png">
  <link rel="stylesheet" href="admin.css">
  <style>
    body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--navy-deep);background-image:radial-gradient(ellipse 60% 60% at 30% 40%,rgba(201,162,39,.05) 0%,transparent 60%),radial-gradient(ellipse 50% 50% at 80% 70%,rgba(204,27,27,.04) 0%,transparent 50%);padding:1.5rem}
    .login-wrap{width:100%;max-width:420px}
    .login-card{background:var(--navy-card);border:1px solid var(--border-gold);border-radius:var(--radius-lg);overflow:hidden;box-shadow:var(--shadow-lg)}
    .login-header{padding:2.5rem 2.5rem 2rem;text-align:center;border-bottom:1px solid var(--border-gold);background:rgba(201,162,39,.04)}
    .login-logo{width:84px;height:84px;object-fit:contain;border-radius:50%;border:3px solid var(--gold);background:var(--white);padding:4px;margin:0 auto 1.2rem;display:block;box-shadow:0 0 32px rgba(201,162,39,.3)}
    .login-title{font-family:'Cinzel',serif;font-size:1.2rem;font-weight:900;color:var(--gold);margin-bottom:.3rem;letter-spacing:.05em}
    .login-sub{font-size:.72rem;color:var(--text-dim);text-transform:uppercase;letter-spacing:.2em}
    .login-body{padding:2rem 2.5rem 2.5rem}
    .login-error{background:var(--red-dim);border:1px solid rgba(204,27,27,.35);border-radius:var(--radius);color:#ff6b6b;font-size:.83rem;padding:.85rem 1rem;margin-bottom:1.2rem;display:flex;align-items:center;gap:8px}
    .field-group{margin-bottom:1.1rem}
    .field-label-lg{display:block;font-size:.7rem;font-weight:700;color:var(--gold);text-transform:uppercase;letter-spacing:.14em;margin-bottom:6px}
    .f-input-lg{width:100%;padding:.85rem 1.1rem;border-radius:var(--radius);border:1px solid var(--border-gold);background:rgba(255,255,255,.04);color:var(--text);font-size:.92rem;outline:none;transition:var(--transition);font-family:'Outfit',sans-serif}
    .f-input-lg::placeholder{color:var(--text-dim)}
    .f-input-lg:focus{border-color:var(--gold);background:rgba(201,162,39,.05);box-shadow:0 0 0 3px rgba(201,162,39,.1)}
    .password-wrap{position:relative}
    .password-wrap .f-input-lg{padding-right:3rem}
    .pw-toggle{position:absolute;right:1rem;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--text-dim);cursor:pointer;font-size:1rem;transition:color .2s}
    .pw-toggle:hover{color:var(--gold)}
    .btn-login{width:100%;padding:.9rem;background:var(--gold);color:var(--navy-deep);border:none;border-radius:100px;font-family:'Cinzel',serif;font-size:.88rem;font-weight:700;letter-spacing:.1em;cursor:pointer;transition:var(--transition);margin-top:.5rem}
    .btn-login:hover{background:var(--gold-light);transform:translateY(-2px);box-shadow:0 6px 20px rgba(201,162,39,.35)}
    .btn-login:disabled{opacity:.6;cursor:not-allowed;transform:none}
    .login-footer{text-align:center;padding:1rem 1.5rem;font-size:.72rem;color:var(--text-dim);border-top:1px solid var(--border)}
    .login-footer a{color:var(--gold)}
    .divider{display:flex;align-items:center;gap:12px;margin:1.5rem 0;color:var(--text-dim);font-size:.72rem}
    .divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--border)}
    .page-loader{position:fixed;inset:0;background:var(--navy-deep);z-index:9999;display:flex;align-items:center;justify-content:center;transition:opacity .5s ease,visibility .5s}
    .page-loader.done{opacity:0;visibility:hidden}
    .lr-wrap{position:relative;width:70px;height:70px}
    .l-ring{position:absolute;inset:0;border-radius:50%;border:2px solid transparent;animation:spin 1.5s linear infinite}
    .l-ring:nth-child(1){border-top-color:var(--gold)}
    .l-ring:nth-child(2){inset:8px;border-bottom-color:var(--red);animation-duration:1.9s;animation-direction:reverse}
    .l-logo{position:absolute;inset:14px;border-radius:50%;object-fit:contain;background:#fff}
    @keyframes spin{to{transform:rotate(360deg)}}
  </style>
</head>
<body>
<div class="page-loader" id="page-loader">
  <div class="lr-wrap"><div class="l-ring"></div><div class="l-ring"></div></div>
</div>

<div class="login-wrap">
  <div class="login-card">
    <div class="login-header">
      <img class="login-logo" src="../images/logowhite2.png" alt="AFM Warsaw Assembly Logo">
      <div class="login-title">Admin Portal</div>
      <div class="login-sub">AFM Warsaw Assembly</div>
    </div>
    <div class="login-body">
      <?php if ($error): ?>
        <div class="login-error"><span>⚠</span><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="POST" id="login-form">
        <div class="field-group">
          <label class="field-label-lg" for="username">Username</label>
          <input class="f-input-lg" type="text" id="username" name="username" placeholder="Enter your username" autocomplete="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
        </div>
        <div class="field-group">
          <label class="field-label-lg" for="password">Password</label>
          <div class="password-wrap">
            <input class="f-input-lg" type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
            <button type="button" class="pw-toggle" id="pw-toggle" aria-label="Toggle visibility">👁</button>
          </div>
        </div>
        <button type="submit" class="btn-login" id="login-btn">Sign In to Dashboard</button>
      </form>
      <div class="divider">Secure Admin Access</div>
      <div style="text-align:center;font-size:.75rem;color:var(--text-dim);">Protected with session-based authentication.<br><span style="color:var(--gold);">✝</span> AFM Warsaw Assembly — Warsaw Christian Centre</div>
    </div>
    <div class="login-footer"><a href="../index.html">← Return to Website</a></div>
  </div>
</div>
<script>
  window.addEventListener('load',()=>setTimeout(()=>document.getElementById('page-loader').classList.add('done'),400));
  document.getElementById('pw-toggle')?.addEventListener('click',function(){const pw=document.getElementById('password');const h=pw.type==='password';pw.type=h?'text':'password';this.textContent=h?'🙈':'👁';});
  document.getElementById('login-form')?.addEventListener('submit',function(){const b=document.getElementById('login-btn');b.disabled=true;b.textContent='Signing in…';});
</script>
</body>
</html>
