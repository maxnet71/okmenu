<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Database.php';

// Verifica onboarding attivo
if (!isset($_SESSION['onboarding_user_id'])) {
    header('Location: ' . BASE_URL . '/signup.php');
    exit;
}

$userId = $_SESSION['onboarding_user_id'];
$userEmail = $_SESSION['onboarding_email'] ?? '';

// Verifica che non abbia già password
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if ($user && !empty($user['password'])) {
    header('Location: ' . BASE_URL . '/dashboard/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Imposta Password - OkMenu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 40px 0;
        }
        .strength-bar {
            height: 4px;
            margin-top: 8px;
            border-radius: 2px;
            transition: all 0.3s;
        }
        .strength-weak { background: #dc3545; width: 33%; }
        .strength-medium { background: #ffc107; width: 66%; }
        .strength-strong { background: #28a745; width: 100%; }
        .requirement { font-size: 0.875rem; color: #6c757d; }
        .requirement.met { color: #28a745; }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        <h2 class="mt-3">Completa la Registrazione</h2>
                        <p class="text-muted">Il tuo menu è pronto! Imposta una password per accedere.</p>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-envelope me-2"></i><?php echo htmlspecialchars($userEmail); ?>
                    </div>

                    <div id="alert" style="display:none;"></div>

                    <form id="form">
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="pwd" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggle">
                                    <i class="bi bi-eye" id="icon"></i>
                                </button>
                            </div>
                            <div class="strength-bar" id="bar"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Conferma Password</label>
                            <input type="password" class="form-control" id="pwd2" required>
                        </div>

                        <div class="mb-4">
                            <small class="text-muted d-block mb-1">Requisiti:</small>
                            <div class="requirement" id="r1"><i class="bi bi-circle"></i> Min 8 caratteri</div>
                            <div class="requirement" id="r2"><i class="bi bi-circle"></i> Un numero</div>
                            <div class="requirement" id="r3"><i class="bi bi-circle"></i> Una lettera</div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg" id="btn">
                                <i class="bi bi-check-circle me-2"></i>Accedi alla Dashboard
                            </button>
                        </div>

                        <div id="progress" class="mt-3" style="display:none;">
                            <div class="progress"><div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const pwd = document.getElementById('pwd');
const pwd2 = document.getElementById('pwd2');
const bar = document.getElementById('bar');
const form = document.getElementById('form');
const btn = document.getElementById('btn');
const progress = document.getElementById('progress');
const alert = document.getElementById('alert');

document.getElementById('toggle').onclick = () => {
    const type = pwd.type === 'password' ? 'text' : 'password';
    pwd.type = pwd2.type = type;
    document.getElementById('icon').className = type === 'password' ? 'bi bi-eye' : 'bi bi-eye-slash';
};

pwd.oninput = () => {
    const val = pwd.value;
    const len = val.length >= 8;
    const num = /\d/.test(val);
    const hasLetter = /[a-zA-Z]/.test(val);
    
    check('r1', len);
    check('r2', num);
    check('r3', hasLetter);
    
    let s = 0;
    if (len) s++;
    if (num) s++;
    if (hasLetter) s++;
    if (val.length >= 12) s++;
    
    bar.className = 'strength-bar strength-' + (s <= 2 ? 'weak' : s <= 3 ? 'medium' : 'strong');
};

function check(id, met) {
    const el = document.getElementById(id);
    const i = el.querySelector('i');
    if (met) {
        el.classList.add('met');
        i.className = 'bi bi-check-circle-fill';
    } else {
        el.classList.remove('met');
        i.className = 'bi bi-circle';
    }
}

form.onsubmit = async (e) => {
    e.preventDefault();
    
    const p1 = pwd.value;
    const p2 = pwd2.value;
    
    if (p1.length < 8) return err('Min 8 caratteri');
    if (!/\d/.test(p1)) return err('Serve un numero');
    if (!/[a-zA-Z]/.test(p1)) return err('Serve una lettera');
    if (p1 !== p2) return err('Le password non corrispondono');
    
    btn.disabled = true;
    progress.style.display = 'block';
    alert.style.display = 'none';
    
    try {
        const res = await fetch('<?php echo BASE_URL; ?>/api/set-password.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({password: p1})
        });
        
        const data = await res.json();
        
        if (data.success) {
            window.location.href = '<?php echo BASE_URL; ?>/dashboard/';
        } else {
            throw new Error(data.message);
        }
    } catch (e) {
        err(e.message);
        btn.disabled = false;
        progress.style.display = 'none';
    }
};

function err(msg) {
    alert.className = 'alert alert-danger';
    alert.textContent = msg;
    alert.style.display = 'block';
    alert.scrollIntoView({behavior: 'smooth'});
}
</script>
</body>
</html>