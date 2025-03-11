<?php
error_reporting(E_ALL);
session_start();

// Memuat kelas ItsMoscow dari function.php
require_once('../function/settings.php'); // Pastikan path-nya benar
$conn = new ItsMoscow();

// Cek autentikasi dengan token
if (empty($_COOKIE['panel']) || !isset($_SESSION['auth_token']) || $_COOKIE['panel'] !== $_SESSION['auth_token']) {
    header("Location: ../nealmtroy");
    exit;
}

// Membaca data JSON dari file config
$json_data = file_get_contents('../function/config.json');
$data = json_decode($json_data, true);
$path = 'https://' . $_SERVER['SERVER_NAME'] . '/nealmtroy/';
$web = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$pages = explode("/", $web);

// Logout
if (isset($_POST['logout'])) {
    // Hapus cookie
    setcookie('panel', '', time() - 3600, "/");
    // Hapus session terkait
    unset($_SESSION['auth_token']);
    session_destroy(); // Hancurkan semua session
    header("Location: ?Logout");
    exit;
}

// Clear all logs
if (isset($_POST['clear_log'])) {
    $conn->CleanAllLogs();
    header("Location: ?refresh");
    exit;
}

class Settings {
    private $config,
            $configFilePath = '../function/config.json';

    public function __construct() {
        $this->config = json_decode(file_get_contents($this->configFilePath), true);
    }

    public function changeSetting($key, $status) {
        $this->config[$key] = $status;
        $this->saveConfig();
        header("Location: ?Success");
        exit;
    }

    private function saveConfig() {
        file_put_contents($this->configFilePath, json_encode($this->config, JSON_PRETTY_PRINT));
    }

    public function getConfig() {
        return $this->config;
    }
}

$setts = new Settings();
$config = $setts->getConfig();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Dashboard | Nealmtroy Shortlink</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="handheldfriendly" content="true" />
    <meta name="MobileOptimized" content="width" />
    <meta name="description" content="Mordenize" />
    <meta name="author" content="" />
    <meta name="keywords" content="Mordenize" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="icon" href="../assets/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <link rel="stylesheet" href="../assets/css/flag-icon.min.css"/>
    <link id="themeColors" rel="stylesheet" href="../assets/css/style-dark.min.css" />
    <link rel="stylesheet" href="../assets/css/dash.css" />
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
                            <button class="border-0 bg-transparent text-primary ms-auto" type="submit" name="logout" aria-label="logout" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="logout">
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
                if (isset($_SESSION['cleanlog'])) {
                    if ($_SESSION['cleanlog'] === 'success') {
                        echo '<div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>Success - </strong>Log successfully deleted</div>';
                    } else {
                        echo '<div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>Failed - </strong>Log failed to delete</div>';
                    }
                    unset($_SESSION['cleanlog']);
                } elseif (isset($_SESSION['panelsettings'])) {
                    if ($_SESSION['panelsettings'] === 'success') {
                        echo '<div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>Success - </strong>Panel successfully updated</div>';
                    } else {
                        echo '<div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>Failed - </strong>Panel failed to update</div>';
                    }
                    unset($_SESSION['panelsettings']);
                }
                ?>
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card border-0 w-100">
                            <div class="card-body pb-0">
                                <h5 class="card-title fw-semibold neon-text">Welcome to Nealmtroy Shortlink</h5>
                                <p class="card-subtitle mb-1">Get an overview of your shortlink performance.</p>
                                <p class="card-subtitle mb-4">Time: <span class="text-danger fw-bold" id="dateTime"></span></p>
                                <h3 class="py-3">Total Visitors: <span class="badge bg-success p-2 rounded"><?= $conn->Visitors(); ?></span></h3>
                                <div class="text-center mt-3 pb-4">
                                    <img src="../assets/images/satan.avif" class="w-100 rounded" alt="Dashboard Image" style="max-height: 200px; object-fit: cover;">
                                </div>
                                <div class="mt-3 pb-4">
                                    <div class="mb-7 pb-1">
                                        <div class="d-flex justify-content-between align-items-center mb-6">
                                            <div>
                                                <h6 class="mb-1 fs-4 fw-semibold"><i class="ti ti-barrier-success"></i> Total Human</h6>
                                                <p class="fs-3 mb-0"><?= $conn->VisitReal(); ?></p>
                                            </div>
                                            <div>
                                                <span class="badge bg-light-success text-success fw-semibold fs-3"><?= $conn->RealPercentage(); ?>%</span>
                                            </div>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" role="progressbar" aria-valuenow="<?= $conn->RealPercentage(); ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?= $conn->RealPercentage(); ?>%;"></div>
                                        </div>
                                    </div>
                                    <!-- Tombol Refresh dipindahkan dari sini -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 d-flex align-items-stretch">
                        <div class="card bg-primary border-0 w-100">
                            <div class="card-body pb-0">
                                <h5 class="fw-semibold mb-1 text-white card-title">Review All Visitors</h5>
                                <h5 class="py-2 text-light">Total Blacklist: <a href="./blacklist"><span class="badge bg-danger p-1 rounded text-light"><?= $conn->BlacklistUser(); ?></span></a></h5>
                            </div>
                            <div class="card mx-2 mb-2 mt-n2">
                                <div class="card-body">
                                    <div class="mb-7 pb-1">
                                        <div class="d-flex justify-content-between align-items-center mb-6">
                                            <div>
                                                <h6 class="mb-1 fs-4 fw-semibold"><i class="ti ti-barrier-block"></i> PARAMETER</h6>
                                                <p class="fs-3 mb-0"><?= $conn->VisitBlocked(); ?></p>
                                            </div>
                                            <div>
                                                <span class="badge bg-light-danger text-danger fw-semibold fs-3"><?= $conn->BlockedPercentage(); ?>%</span>
                                            </div>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-danger" role="progressbar" aria-valuenow="<?= $conn->BlockedPercentage(); ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?= $conn->BlockedPercentage(); ?>%;"></div>
                                        </div>
                                    </div>
                                    <div class="mb-7 pb-1">
                                        <div class="d-flex justify-content-between align-items-center mb-6">
                                            <div>
                                                <h6 class="mb-1 fs-4 fw-semibold"><i class="ti ti-users"></i> HUMAN</h6>
                                                <p class="fs-3 mb-0"><?= $conn->VisitReal(); ?></p>
                                            </div>
                                            <div>
                                                <span class="badge bg-light-success text-success fw-bold fs-3"><?= $conn->RealPercentage(); ?>%</span>
                                            </div>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" role="progressbar" aria-valuenow="<?= $conn->RealPercentage(); ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?= $conn->RealPercentage(); ?>%;"></div>
                                        </div>
                                    </div>
                                    <div class="mb-7 pb-1">
                                        <div class="d-flex justify-content-between align-items-center mb-6">
                                            <div>
                                                <h6 class="mb-1 fs-4 fw-semibold"><i class="ti ti-robot"></i> ROBOT</h6>
                                                <p class="fs-3 mb-0"><?= $conn->VisitRobot(); ?></p>
                                            </div>
                                            <div>
                                                <span class="badge bg-light-danger text-danger fw-bold fs-3"><?= $conn->RobotPercentage(); ?>%</span>
                                            </div>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-danger" role="progressbar" aria-valuenow="<?= $conn->RobotPercentage(); ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?= $conn->RobotPercentage(); ?>%;"></div>
                                        </div>
                                    </div>
                                    <div class="mb-7 pb-1">
                                        <div class="d-flex justify-content-between align-items-center mb-6">
                                            <div>
                                                <h6 class="mb-1 fs-4 fw-semibold"><i class="ti ti-playlist-x"></i> BLACKVISIT</h6>
                                                <p class="fs-3 mb-0"><?= $conn->VisitBlacklist(); ?></p>
                                            </div>
                                            <div>
                                                <span class="badge bg-light-dark text-light fw-bold fs-3"><?= $conn->BlacklistPercentage(); ?>%</span>
                                            </div>
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar bg-dark" role="progressbar" aria-valuenow="<?= $conn->BlacklistPercentage(); ?>" aria-valuemin="0" aria-valuemax="100" style="width: <?= $conn->BlacklistPercentage(); ?>%;"></div>
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
                                        <h5 class="card-title fw-semibold"><i class="ti ti-history"></i> Visitors </h5>
                                    </div>
                                    <div>
                                        <button class="btn btn-primary me-2" onclick="refreshStats()">
                                            <i class="ti ti-refresh"></i> Refresh
                                        </button>
                                        <form method="POST" id="clearLogForm" style="display: inline;">
                                            <button type="submit" class="btn btn-danger" name="clear_log" data-bs-toggle="tooltip" data-bs-placement="top" title="Clear all logs">
                                                <i class="ti ti-trash"></i> Clear All
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="table-responsive" style="overflow-x: auto;">
                                    <table class="table align-middle text-nowrap mb-0" id="logTable">
                                        <thead>
                                            <tr class="text-muted fw-semibold">
                                                <th scope="col" class="ps-0 text-center">#</th>
                                                <th scope="col"><i class="ti ti-address-book"></i> IP</th>
                                                <th scope="col"><i class="ti ti-calendar-due"></i> Date & Time</th>
                                                <th scope="col" class="text-center"><i class="ti ti-flag"></i> Country</th>
                                                <th scope="col" class="text-center"><i class="ti ti-hierarchy-2"></i> Status</th>
                                                <th scope="col" class="text-center"><i class="ti ti-devices"></i> Device</th>
                                                <th scope="col"><i class="ti ti-cpu"></i> User Agent</th>
                                            </tr>
                                        </thead>
                                        <tbody class="border-top">
                                            <?php
                                        // Define the path to the log file
                                        $log_file = '../log/view.log';
                                        
                                        // Check if the log file exists
                                        if (file_exists($log_file)) {
                                            // Read the contents of the log file line by line
                                            $log_entries = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                                        
                                            // Get the last 10 log entries
                                            // Use all log entries
                                            $all_entries = array_reverse($log_entries);
                                            $unique_ips = [];
                                        
                                            // Initialize the counter
                                            $counter = 1;
                                        
                                            // Iterate over each log entry
                                        foreach ($all_entries as $log_entry) {
                                            // Split log entry by pipe (|) to get the parts
                                            $log_parts = explode('|', $log_entry);
                                        
                                            // Extract relevant information
                                            $ip = $log_parts[0] ?? 'N/A';
                                            $user_agent = $log_parts[1] ?? 'N/A';
                                            $date_visit = $log_parts[2] ?? 'N/A';
                                            $devices = $log_parts[3] ?? 'N/A';
                                            $country = $log_parts[4] ?? 'N/A';
                                            $status = $log_parts[5] ?? 'N/A';
                                        
                                            // Determine device info
                                            switch ($devices) {
                                                case 'HUMAN':
                                                    $info = "<span class='badge fw-semibold py-1 w-100 bg-light-success text-success'>" . htmlspecialchars($devices) . "</span>";
                                                    break;
                                                case 'BLACKLIST':
                                                    $info = "<span class='badge fw-semibold py-1 w-100 bg-dark text-light'>" . htmlspecialchars($devices) . "</span>";
                                                    break;
                                                default:
                                                    $info = "<span class='badge fw-semibold py-1 w-100 bg-light-danger text-danger'>" . htmlspecialchars($devices) . "</span>";
                                                    break;
                                            }
                                        
                                            // Determine country flag
                                            $countryFlag = 'N/A';
                                            if (!empty($country)) {
                                                $countryFlag = "<div class='light rounded'><span class='flag-icon flag-icon-" . strtolower(htmlspecialchars($date_visit)) . "'></span></div>";
                                            }
                                        
                                            // Display the log entry in a table row
                                            echo "<tr>
                                                    <td style='text-align:left; border-bottom-left-radius:10px;'>$counter</td>
                                                    <td style='text-align:left;'>$ip</td>
                                                    <td style='text-align:left;'>$user_agent</td>
                                                    <td style='text-align:center;'>$countryFlag</td>
                                                    <td style='text-align:center;'>$info</td>
                                                    <td style='text-align:center;'>$country</td>
                                                    <td>$status</td>
                                                  </tr>";
                                        
                                            $counter++;
                                        }

                                        } else {
                                            // Handle the error if the log file does not exist
                                            echo "<tr><td colspan='7'>Log file not found.</td></tr>";
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

        function refreshStats() {
            location.reload();
        }

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