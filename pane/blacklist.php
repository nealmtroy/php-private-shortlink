<?php
error_reporting(E_ALL);
session_start();
require_once('../function/settings.php');
$Sett = new ItsMoscow();

// Cek autentikasi dengan token
if (empty($_COOKIE['panel']) || !isset($_SESSION['auth_token']) || $_COOKIE['panel'] !== $_SESSION['auth_token']) {
    header("Location: ../nealmtroy");
    exit;
}

$json_data = file_get_contents('../function/config.json');
$data = json_decode($json_data, true);
$path = 'https://' . $_SERVER['SERVER_NAME'] . '/nealmtroy';
$web = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$pages = explode("/", $web);

// Process save blacklist
if (isset($_POST['save'])) {
    $Sett->SaveBlacklist();
    $_SESSION['notification'] = 'success_add';
    header("Location: blacklist");
    exit;
}

// Process clean blacklist
if (isset($_POST['cleanblacklist'])) {
    file_put_contents('../log/blacklist.log', '');
    $_SESSION['notification'] = 'success_clean_all';
    header("Location: blacklist");
    exit;
}

// Process logout
if (isset($_POST['logout'])) {
    setcookie('panel', '', time() - 3600, "/");
    unset($_SESSION['auth_token']);
    session_destroy();
    header("Location: ?Logout");
    exit;
}

// Handle IP deletion if requested
if (isset($_POST['delete_ip'])) {
    $Sett->deleteBlacklist();
    $_SESSION['notification'] = 'success_delete';
}

// Read and clean the IP addresses
$blacklist_filename = "../log/blacklist.log";
if (file_exists($blacklist_filename)) {
    $blacklist_content = file_get_contents($blacklist_filename);
    $lines = preg_split('/[\r\n]+/', trim($blacklist_content));
    $cleaned_ips = array_unique(array_filter(array_map('trim', $lines)));
    sort($cleaned_ips);
} else {
    $cleaned_ips = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Title -->
    <title>Blacklist IP | Nealmtroy Shortlink</title>
    <!-- Required Meta Tags -->
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="handheldfriendly" content="true" />
    <meta name="MobileOptimized" content="width" />
    <meta name="description" content="Mordenize" />
    <meta name="author" content="" />
    <meta name="keywords" content="Mordenize" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!-- Favicon -->
    <link rel="icon" href="../assets/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <!-- Core CSS -->
    <link id="themeColors" rel="stylesheet" href="../assets/css/style-dark.min.css" />
    <link rel="stylesheet" href="../assets/css/blacklist.css" />
</head>
<body>
    <div class="preloader">
        <img src="../assets/images/title.png" alt="loader" class="lds-ripple img-fluid" />
    </div>
    <div class="page-wrapper" id="main-wrapper" data-theme="blue_theme" data-layout="vertical" data-sidebartype="full" data-sidebar-position="fixed" data-header-position="fixed">
        <aside class="left-sidebar">
            <div>
                <div class="brand-logo d-flex align-items-center justify-content-between">
                    <a href="<?= $path; ?>dashboard" class="text-nowrap logo-img">
                        <div class="light-logo container">
                            <h3 class="neon-text text-uppercase">NEALMTROY</h3>
                        </div>
                        <div class="dark-logo container">
                            <h3 class="neon-text text-uppercase">NEALMTROY</h3>
                        </div>
                    </a>
                    <div class="close-btn d-lg-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse">
                        <i class="ti ti-x fs-8"></i>
                    </div>
                </div>
                <nav class="sidebar-nav scroll-sidebar" data-simplebar>
                    <ul id="sidebarnav">
                        <li class="nav-small-cap">
                            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                            <span class="hide-menu">Home</span>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link" href="dashboard" aria-expanded="false">
                                <span><i class="ti ti-home"></i></span>
                                <span class="hide-menu">Home</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link" href="manage" aria-expanded="false">
                                <span><i class="ti ti-link"></i></span>
                                <span class="hide-menu">Links</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link" href="blacklist" aria-expanded="false">
                                <span><i class="ti ti-bookmark"></i></span>
                                <span class="hide-menu">Blacklist IP</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link" href="statistic" aria-expanded="false">
                                <span><i class="ti ti-history"></i></span>
                                <span class="hide-menu">Statistics</span>
                            </a>
                        </li>
                        <li class="nav-small-cap">
                            <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
                            <span class="hide-menu">Settings</span>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link" href="settings" aria-expanded="false">
                                <span><i class="ti ti-settings"></i></span>
                                <span class="hide-menu">Settings</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <div class="fixed-profile p-3 bg-light-secondary rounded sidebar-ad mt-3">
                    <div class="hstack gap-3">
                        <div class="john-img">
                            <img src="../assets/images/users.jpg" class="rounded-circle" width="40" height="40" alt="" />
                        </div>
                        <div class="john-title">
                            <h6 class="mb-0 fs-4 fw-semibold">ADMIN</h6>
                            <span class="fs-2 text-dark">NEALMTROY</span>
                        </div>
                        <form method="post">
                            <button class="border-0 bg-transparent text-primary ms-auto" tabindex="0" type="submit" name="logout" aria-label="logout" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Logout">
                                <i class="ti ti-power fs-6"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </aside>
        <div class="body-wrapper">
            <div class="container-fluid">
                <?php
                $notification = $_SESSION['notification'] ?? null;
                if ($notification) {
                    if ($notification === 'success_add') {
                        echo '<div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>Success - </strong>The blacklist log has been successfully added.</div>';
                    } elseif ($notification === 'success_delete') {
                        echo '<div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>Success - </strong>The blacklist IP has been deleted.</div>';
                    } elseif ($notification === 'success_clean_all') {
                        echo '<div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>Success - </strong>All blacklist logs have been deleted.</div>';
                    }
                    unset($_SESSION['notification']);
                }
                ?>
                <div class="row">
                    <div class="col-lg-12 d-flex align-items-stretch">
                        <div class="card w-100 bg-light-info overflow-hidden shadow-none">
                            <div class="card-body py-3">
                                <div class="row justify-content-between align-items-center">
                                    <div class="col-sm-6">
                                        <h5 class="card-title fw-semibold neon-text">Blacklist IP - Nealmtroy Shortlink</h5>
                                        <p class="mb-3">Control access by managing your blacklist entries.</p>
                                        <div class="btn btn-primary fs-2">Time: <span class="text-danger fw-bold" id="dateTime"></span></div>
                                    </div>
                                    <div class="col-sm-5">
                                        <div class="position-relative mb-n7 text-end">
                                            <img src="../assets/images/welcome-bg2.png" alt="" class="img-fluid rounded" style="max-height: 150px; object-fit: cover;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 d-flex align-items-stretch">
                        <div class="card w-100">
                            <div class="card-body">
                                <div class="d-sm-flex d-block align-items-center justify-content-between mb-7">
                                    <div class="mb-3 mb-sm-0">
                                        <h2 class="card-title fw-semibold neon-text text-danger"><i class="ti ti-home-cog"></i> Blacklist List</h2>
                                    </div>
                                </div>
                                <div class="card">
                                    <form method="POST">
                                        <div class="card-body">
                                            <div class="d-sm-flex d-block align-items-center justify-content-between mb-3">
                                                <div class="mb-3 mb-sm-0">
                                                    <p class="card-subtitle mb-3">
                                                        This page is used to control blacklist IPs 👾
                                                    </p>
                                                </div>
                                                <div>
                                                    <button class="btn btn-primary me-2" onclick="refreshBlacklist()" data-bs-toggle="tooltip" data-bs-placement="top" title="Refresh blacklist list">
                                                        <i class="ti ti-refresh"></i> Refresh
                                                    </button>
                                                    <button type="submit" name="cleanblacklist" class="btn btn-danger" data-bs-toggle="tooltip" data-bs-placement="top" title="Clear all blacklist IPs">
                                                        <i class="ti ti-flame"></i> Clear All
                                                    </button>
                                                </div>
                                            </div>
                                            <table class="table align-middle text-nowrap mb-3">
                                                <thead>
                                                    <tr class="text-muted fw-semibold">
                                                        <th>IP Address</th>
                                                        <th class="action-column">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($cleaned_ips as $ip): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($ip); ?></td>
                                                        <td class="action-column">
                                                            <form method="post" style="display:inline;">
                                                                <input type="hidden" name="delete_ip" value="<?php echo htmlspecialchars($ip); ?>">
                                                                <button type="submit" class="btn btn-danger" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete this IP">
                                                                    <i class="ti ti-flame"></i> Delete
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                    <?php if (empty($cleaned_ips)): ?>
                                                    <tr>
                                                        <td colspan="2" class="text-center">No blacklist IPs found.</td>
                                                    </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                            <div class="col-lg-12">
                                                <div class="form-floating mb-3">
                                                    <input type="text" class="form-control" placeholder="Input Blacklist IP" name="blacklistip">
                                                    <label><i class="ti ti-lock me-2 fs-4"></i>Blacklist IP</label>
                                                </div>
                                            </div>
                                            <div class="d-md-flex align-items-center">
                                                <div class="mt-3 mt-md-0 ms-auto">
                                                    <button type="submit" class="btn btn-primary font-medium rounded-pill px-4" name="save" data-bs-toggle="tooltip" data-bs-placement="top" title="Add new blacklist IP">
                                                        <div class="d-flex align-items-center">
                                                            <i class="ti ti-send me-2 fs-4"></i>
                                                            Add Blacklist
                                                        </div>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <button class="btn btn-primary p-3 rounded-circle d-flex align-items-center justify-content-center customizer-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasExample" aria-controls="offcanvasExample">
        <i class="ti ti-settings fs-7" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Settings"></i>
    </button>
    <div class="offcanvas offcanvas-end customizer" tabindex="-1" id="offcanvasExample" aria-labelledby="offcanvasExampleLabel" data-simplebar="">
        <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
            <h4 class="offcanvas-title fw-semibold" id="offcanvasExampleLabel">Settings</h4>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-4">
            <div class="theme-option pb-4">
                <h6 class="fw-semibold fs-4 mb-1">Theme Option</h6>
                <div class="d-flex align-items-center gap-3 my-3">
                    <a href="javascript:void(0)" onclick="toggleTheme('../assets/css/style.min.css')" class="rounded-2 p-9 customizer-box hover-img d-flex align-items-center gap-2 light-theme text-dark">
                        <i class="ti ti-brightness-up fs-7 text-primary"></i>
                        <span class="text-dark">Light</span>
                    </a>
                    <a href="javascript:void(0)" onclick="toggleTheme('../assets/css/style-dark.min.css')" class="rounded-2 p-9 customizer-box hover-img d-flex align-items-center gap-2 dark-theme text-dark">
                        <i class="ti ti-moon fs-7"></i>
                        <span class="text-dark">Dark</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Particles.js -->
    <div id="particles-js"></div>

    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/custom.js"></script>
    <script src="../assets/js/app.min.js"></script>
    <script src="../assets/js/app.init.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
        function updateDateTime() {
            const now = new Date();
            const formattedDateTime = now.toLocaleString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: 'numeric',
                minute: 'numeric',
                second: 'numeric',
                timeZone: "Asia/Jakarta",
                hour12: true
            });
            document.getElementById('dateTime').textContent = formattedDateTime;
        }
        updateDateTime();
        setInterval(updateDateTime, 1000);

        function refreshBlacklist() {
            location.reload();
        }

        // Particles.js Configuration
        particlesJS("particles-js", {
            "particles": {
                "number": {
                    "value": 80,
                    "density": {
                        "enable": true,
                        "value_area": 800
                    }
                },
                "color": {
                    "value": "#3182ce"
                },
                "shape": {
                    "type": "circle",
                    "stroke": {
                        "width": 0,
                        "color": "#000000"
                    }
                },
                "opacity": {
                    "value": 0.5,
                    "random": false
                },
                "size": {
                    "value": 3,
                    "random": true
                },
                "line_linked": {
                    "enable": true,
                    "distance": 150,
                    "color": "#3182ce",
                    "opacity": 0.4,
                    "width": 1
                },
                "move": {
                    "enable": true,
                    "speed": 2,
                    "direction": "none",
                    "random": false,
                    "straight": false,
                    "out_mode": "out",
                    "bounce": false
                }
            },
            "interactivity": {
                "detect_on": "canvas",
                "events": {
                    "onhover": {
                        "enable": true,
                        "mode": "repulse"
                    },
                    "onclick": {
                        "enable": true,
                        "mode": "push"
                    },
                    "resize": true
                }
            },
            "retina_detect": true
        });
    </script>
</body>
</html>