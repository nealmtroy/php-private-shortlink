<?php
error_reporting(E_ALL);
session_start();
require_once('../function/settings.php');
$conn = new ItsMoscow();

// Cek autentikasi dengan token
if (empty($_COOKIE['panel']) || !isset($_SESSION['auth_token']) || $_COOKIE['panel'] !== $_SESSION['auth_token']) {
    header("Location: ../nealmtroy");
    exit;
}

$json_data = file_get_contents('../function/config.json');
$data = json_decode($json_data, true);
$path = 'https://' . $_SERVER['SERVER_NAME'] . '/nealmtroy/';
$web = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$pages = explode("/", $web);

// Process logout
if (isset($_POST['logout'])) {
    setcookie('panel', '', time() - 3600, "/");
    unset($_SESSION['auth_token']);
    session_destroy();
    header("Location: ?Logout");
    exit;
}

function bacaStatsLog($file = '../log/stats.log') {
    $stats = [
        'total' => 0,
        'human' => 0,
        'robot' => 0,
        'by_shortcode' => []
    ];

    if (!file_exists($file)) {
        return $stats;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = explode('|', trim($line));
        
        if (count($parts) === 4) {
            list($shortcode, $datetime, $ip, $status) = $parts;
            
            if (!isset($stats['by_shortcode'][$shortcode])) {
                $stats['by_shortcode'][$shortcode] = [
                    'human' => 0,
                    'robot' => 0,
                    'total_visitors' => 0,
                    'entries' => []
                ];
            }

            $stats['by_shortcode'][$shortcode]['entries'][] = [
                'datetime' => $datetime,
                'ip' => $ip,
                'status' => $status
            ];

            if ($status === 'HUMAN') {
                $stats['by_shortcode'][$shortcode]['human']++;
                $stats['human']++;
            } elseif ($status === 'ROBOT') {
                $stats['by_shortcode'][$shortcode]['robot']++;
                $stats['robot']++;
            }

            $stats['by_shortcode'][$shortcode]['total_visitors']++;
            $stats['total']++;
        }
    }

    return $stats;
}

function clearStatsLog($file = '../log/stats.log') {
    if (file_exists($file)) {
        file_put_contents($file, '');
        return true;
    }
    return false;
}

// Handle clear action
if (isset($_POST['clear_stats'])) {
    if (clearStatsLog()) {
        $_SESSION['notification'] = 'success_clear';
    } else {
        $_SESSION['notification'] = 'error_clear';
    }
    header("Location: statistic");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!--  Title -->
    <title>Statistic | Nealmtroy Shortlink</title>
    <!--  Required Meta Tag -->
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="handheldfriendly" content="true" />
    <meta name="MobileOptimized" content="width" />
    <meta name="description" content="Mordenize" />
    <meta name="author" content="" />
    <meta name="keywords" content="Mordenize" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <!--  Favicon -->
    <link rel="icon" href="../assets/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <link rel="stylesheet" href="../assets/css/flag-icon.min.css"/>
    <!-- Core Css -->
    <link id="themeColors" rel="stylesheet" href="../assets/css/style-dark.min.css" />
    <link rel="stylesheet" href="../assets/css/statistics.css" />
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
                            <button class="border-0 bg-transparent text-primary ms-auto" tabindex="0" type="submit" name="logout" aria-label="logout" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="logout">
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
                    if ($notification === 'success_clear') {
                        echo '<div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>Success - </strong>Statistics successfully cleared</div>';
                    } elseif ($notification === 'error_clear') {
                        echo '<div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>Failed - </strong>Failed to clear statistics</div>';
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
                                        <h5 class="card-title fw-semibold neon-text">Statistic - Nealmtroy Shortlink</h5>
                                        <p class="mb-3">View statistics of shortlink usage</p>
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
                <?php
                $statsData = bacaStatsLog();
                ?>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="stats-box total-visits">
                            <h6>Total Visits</h6>
                            <h4><?php echo $statsData['total']; ?></h4>
                            <div class="progress">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: 100%" aria-valuenow="<?php echo $statsData['total']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $statsData['total'] ?: 1; ?>"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-box human-visits">
                            <h6>Human Visits</h6>
                            <h4><?php echo $statsData['human']; ?></h4>
                            <div class="progress">
                                <div class="progress-bar progress-bar-human" role="progressbar" style="width: <?php echo $statsData['total'] > 0 ? ($statsData['human'] / $statsData['total']) * 100 : 0; ?>%" aria-valuenow="<?php echo $statsData['human']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $statsData['total'] ?: 1; ?>"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stats-box robot-visits">
                            <h6>Robot Visits</h6>
                            <h4><?php echo $statsData['robot']; ?></h4>
                            <div class="progress">
                                <div class="progress-bar progress-bar-robot" role="progressbar" style="width: <?php echo $statsData['total'] > 0 ? ($statsData['robot'] / $statsData['total']) * 100 : 0; ?>%" aria-valuenow="<?php echo $statsData['robot']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $statsData['total'] ?: 1; ?>"></div>
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
                                        <h5 class="card-title fw-semibold text-danger neon-text">
                                            <i class="ti ti-history"></i> Shortlink Statistics
                                        </h5>
                                    </div>
                                    <div>
                                        <button class="btn btn-primary me-2" onclick="refreshStats()" data-bs-toggle="tooltip" data-bs-placement="top" title="Refresh statistics">
                                            <i class="ti ti-refresh"></i> Refresh
                                        </button>
                                        <form method="POST" style="display:inline;">
                                            <button type="submit" name="clear_stats" class="btn btn-danger" data-bs-toggle="tooltip" data-bs-placement="top" title="Clear all statistics">
                                                <i class="ti ti-trash"></i> Clear All
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="table-responsive" style="overflow-x: auto;">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr class="text-muted fw-semibold text-center">
                                                <th style="width: 5%;">#</th>
                                                <th style="width: 20%;">Shortcode</th>
                                                <th style="width: 25%;">Latest Visit</th>
                                                <th style="width: 20%;">Latest IP</th>
                                                <th style="width: 10%;">Human</th>
                                                <th style="width: 10%;">Robot</th>
                                                <th style="width: 10%;">Total </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($statsData['total'] > 0) {
                                                $counter = 1;
                                                foreach ($statsData['by_shortcode'] as $shortcode => $data) {
                                                    $latestEntry = end($data['entries']);
                                                    $humanPercent = $data['total_visitors'] > 0 ? ($data['human'] / $data['total_visitors']) * 100 : 0;
                                                    $robotPercent = $data['total_visitors'] > 0 ? ($data['robot'] / $data['total_visitors']) * 100 : 0;
                                                    echo "<tr>
                                                            <td class='text-center'>{$counter}</td>
                                                            <td class='text-center'>{$shortcode}</td>
                                                            <td class='text-center'>{$latestEntry['datetime']}</td>
                                                            <td class='text-center'>{$latestEntry['ip']}</td>
                                                            <td class='text-center'>
                                                                <span class='badge bg-success'>{$data['human']}</span>
                                                                <div class='progress'>
                                                                    <div class='progress-bar progress-bar-human' role='progressbar' style='width: {$humanPercent}%' aria-valuenow='{$data['human']}' aria-valuemin='0' aria-valuemax='{$data['total_visitors']}'></div>
                                                                </div>
                                                            </td>
                                                            <td class='text-center'>
                                                                <span class='badge bg-danger'>{$data['robot']}</span>
                                                                <div class='progress'>
                                                                    <div class='progress-bar progress-bar-robot' role='progressbar' style='width: {$robotPercent}%' aria-valuenow='{$data['robot']}' aria-valuemin='0' aria-valuemax='{$data['total_visitors']}'></div>
                                                                </div>
                                                            </td>
                                                            <td class='text-center'>
                                                                <span class='badge bg-primary badge-total'><i class='ti ti-users me-1'></i>{$data['total_visitors']}</span>
                                                            </td>
                                                          </tr>";
                                                    $counter++;
                                                }
                                            } else {
                                                echo "<tr><td colspan='7' class='text-center'>No data available in stats.log</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Particles.js -->
    <div id="particles-js"></div>

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
                hour12: true,
                timeZone: "Asia/Jakarta"
            });
            document.getElementById('dateTime').textContent = formattedDateTime;
        }
        updateDateTime();
        setInterval(updateDateTime, 1000);

        function refreshStats() {
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