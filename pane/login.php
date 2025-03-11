<?php
session_start();

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === "off") {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

if (isset($_COOKIE['panel']) && isset($_SESSION['auth_token']) && $_COOKIE['panel'] === $_SESSION['auth_token']) {
    header("Location: nealmtroy/dashboard");
    exit;
}

// Inisialisasi session
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errorMessage = '';
$maxAttempts = 2;
$redirectUrl = "invalid";

if ($_POST) {
    // Cek CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errorMessage = "Invalid CSRF token";
    } else {
        $json_data = file_get_contents('../function/config.json');
        $data = json_decode($json_data, true);
        $password = filter_var($_POST['password'], FILTER_SANITIZE_STRING);

        if (password_verify($password, $data['password'])) {
            $_SESSION['login_attempts'] = 0;
            $token = bin2hex(random_bytes(16));
            $_SESSION['auth_token'] = $token;
            setcookie('panel', $token, time() + (86400 * 30), "/", "", true, true);
            header("Location: nealmtroy/dashboard");
            exit;
        } else {
            $_SESSION['login_attempts']++;
            if ($_SESSION['login_attempts'] >= $maxAttempts) {
                $_SESSION['login_attempts'] = 0;
                header("Location: $redirectUrl");
                exit;
            } else {
                $errorMessage = "Invalid License";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login to NEALMTROY Panel">
    <meta name="author" content="NEALMTROY">
    <meta name="keywords" content="NEALMTROY, Login">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="icon" href="../assets/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <!-- Core CSS -->
    <link id="themeColors" rel="stylesheet" href="../assets/css/style-dark.min.css" />
    <link rel="stylesheet" href="../assets/css/login.css" />
    <title>Sign-In | Nealmtroy Shortlink</title>
</head>
<body>
    <div id="particles-js"></div>
    <div class="login-container">
        <div class="card">
            <div class="card-body">
                <div class="logo">
                    <h3>NEALMTROY</h3>
                </div>
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger text-center">
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password">
                            <i class="ti ti-lock me-2 fs-4"></i>Password
                        </label>
                    </div>
                    <button type="submit" class="btn btn-navy">
                        <i class="ti ti-login me-2 fs-4"></i>Sign-In
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/custom.js"></script>
    <script src="../assets/js/app.min.js"></script>
    <script src="../assets/js/app.init.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
        particlesJS("particles-js", {
            "particles": {
                "number": { "value": 80, "density": { "enable": true, "value_area": 800 } },
                "color": { "value": "#3182ce" },
                "shape": { "type": "circle", "stroke": { "width": 0, "color": "#000000" } },
                "opacity": { "value": 0.5, "random": false },
                "size": { "value": 3, "random": true },
                "line_linked": { "enable": true, "distance": 150, "color": "#3182ce", "opacity": 0.4, "width": 1 },
                "move": { "enable": true, "speed": 2, "direction": "none", "random": false, "straight": false, "out_mode": "out", "bounce": false }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": { "onhover": { "enable": true, "mode": "repulse" }, "onclick": { "enable": true, "mode": "push" }, "resize": true }
            },
            "retina_detect": true
        });
    </script>
</body>
</html>