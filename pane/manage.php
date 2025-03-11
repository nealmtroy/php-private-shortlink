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

// Read the JSON data from file
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

// Function to generate a random key
function generateKey($length = 7) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $key = '';
    for ($i = 0; $i < $length; $i++) {
        $key .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $key;
}

// JSON file for storing data
$urlsConfigFile = '../function/urls_config.json';

// Function to read stats log
function readStatsLog($file = '../log/stats.log') {
    $stats = [
        'by_shortcode' => []
    ];

    if (!file_exists($file)) {
        return $stats;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = explode('|', trim($line));
        
        if (count($parts) === 4) { // Format: SHORTCODE|DATETIME|DEVICE|IP|STATUS
            list($shortcode, $datetime, $ip, $status) = $parts;
            
            if (!isset($stats['by_shortcode'][$shortcode])) {
                $stats['by_shortcode'][$shortcode] = [
                    'human' => 0,
                    'robot' => 0,
                    'total_visitors' => 0
                ];
            }

            if ($status === 'HUMAN') {
                $stats['by_shortcode'][$shortcode]['human']++;
            } elseif ($status === 'ROBOT') {
                $stats['by_shortcode'][$shortcode]['robot']++;
            }

            $stats['by_shortcode'][$shortcode]['total_visitors']++;
        }
    }

    return $stats;
}

// Read the contents of the JSON file
$urlsConfig = json_decode(file_get_contents($urlsConfigFile), true) ?? [];
$statsData = readStatsLog();

// Path for redirect after Clear All
$redirectURL = 'https://' . $_SERVER['SERVER_NAME'] . '/nealmtroy/users';

// Process Clear All if the button is pressed
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['clear_urls'])) {
    file_put_contents($urlsConfigFile, json_encode([]));
    $_SESSION['notification'] = 'success_clear_all';
    header("Location: manage");
    exit;
}

// Variables to hold messages
$generatedUrl = '';
$newKey = '';

// Automatically detect domain and protocol
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$domain = $_SERVER['HTTP_HOST'];

// Add a new URL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_url'])) {
    $newUrl = trim($_POST['add_url']);
    $errorMessage = '';

    // Generate an automatic key
    $newKey = generateKey(); 

    // Check if the destination URL already exists
    if (in_array($newUrl, array_values($urlsConfig))) {
        $_SESSION['notification'] = 'error_duplicate';
    } else {
        $urlsConfig[$newKey] = $newUrl;
        file_put_contents($urlsConfigFile, json_encode($urlsConfig, JSON_PRETTY_PRINT));
        $generatedUrl = "$protocol://$domain/go/{$newKey}";
        $_SESSION['notification'] = 'success_add';
        $_SESSION['generated_url'] = $generatedUrl;
    }
    header("Location: manage");
    exit;
}

// Edit the destination URL based on the key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_key'], $_POST['new_url'])) {
    $editKey = $_POST['edit_key'];
    $newUrl = trim($_POST['new_url']);

    if (isset($urlsConfig[$editKey])) {
        $urlsConfig[$editKey] = $newUrl;
        file_put_contents($urlsConfigFile, json_encode($urlsConfig, JSON_PRETTY_PRINT));
        $_SESSION['notification'] = 'success_edit';
    }
    header("Location: manage");
    exit;
}

// Delete a URL based on the key
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_key'])) {
    $deleteKey = $_POST['delete_key'];
    if (isset($urlsConfig[$deleteKey])) {
        unset($urlsConfig[$deleteKey]);
        file_put_contents($urlsConfigFile, json_encode($urlsConfig, JSON_PRETTY_PRINT));
        $_SESSION['notification'] = 'success_delete';
    }
    header("Location: manage");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Title -->
    <title>Links | Nealmtroy Shortlink</title>
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
    <link rel="stylesheet" href="../assets/css/flag-icon.min.css"/>
    <!-- Core CSS -->
    <link id="themeColors" rel="stylesheet" href="../assets/css/style-dark.min.css" />
    <link rel="stylesheet" href="../assets/css/manage.css" />
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
                $generatedUrl = $_SESSION['generated_url'] ?? '';
                if ($notification) {
                    if ($notification === 'success_add') {
                        echo '<div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>The URL was successfully added.</strong>
                                <button class="btn btn-secondary btn-sm px-3 py-2 ms-2" onclick="copyToClipboard(\'' . htmlspecialchars($generatedUrl) . '\')">
                                    <i class="ti ti-clipboard"></i> Copy URL
                                </button>
                              </div>';
                    } elseif ($notification === 'error_duplicate') {
                        echo '<div class="alert alert-danger alert-dismissible bg-danger text-white border-0 fade show" role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>Error - Destination URL already exists. Please use a different URL.</strong></div>';
                    } elseif ($notification === 'success_edit') {
                        echo '<div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>Success - </strong>The URL was successfully updated.</div>';
                    } elseif ($notification === 'success_delete') {
                        echo '<div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>Success - </strong>The URL was successfully deleted.</div>';
                    } elseif ($notification === 'success_clear_all') {
                        echo '<div class="alert alert-success alert-dismissible bg-success text-white border-0 fade show" role="alert">
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                                <strong>Success - </strong>All URLs have been cleared.</div>';
                    }
                    unset($_SESSION['notification']);
                    unset($_SESSION['generated_url']);
                }
                ?>
                <div class="row">
                    <div class="col-lg-12 d-flex align-items-stretch">
                        <div class="card w-100 bg-light-info overflow-hidden shadow-none">
                            <div class="card-body py-3">
                                <div class="row justify-content-between align-items-center">
                                    <div class="col-sm-6">
                                        <h5 class="card-title fw-semibold neon-text">Links - Nealmtroy Shortlink</h5>
                                        <p class="mb-3">Manage your shortened URLs and track their performance.</p>
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
                                        <h5 class="card-title fw-semibold text-danger neon-text">
                                            <i class="ti ti-world"></i> Add New URLs
                                        </h5>
                                    </div>
                                </div>
                                <form method="POST" onsubmit="return validateURL()">
                                    <div class="input-group mb-3">
                                        <div class="form-floating flex-grow-1">
                                            <input type="url" class="form-control" id="add_url" name="add_url" placeholder="Enter URL" required>
                                            <label for="add_url">
                                                <i class="ti ti-link me-2 fs-4"></i>Input URLs
                                            </label>
                                        </div>
                                        <button type="submit" class="btn btn-navy px-3 py-1" data-bs-toggle="tooltip" data-bs-placement="top" title="Add new URL">
                                            <i class="ti ti-send me-2 fs-6"></i>Add URL
                                        </button>
                                    </div>
                                    <div id="urlError" class="text-danger mt-2" style="display: none;">
                                        <i class="ti ti-alert-circle"></i> Invalid URL format!
                                    </div>
                                </form>
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
                                            <i class="ti ti-list"></i> URL List
                                        </h5>
                                    </div>
                                    <div>
                                        <button class="btn btn-primary me-2" onclick="refreshURLs()" data-bs-toggle="tooltip" data-bs-placement="top" title="Refresh URL list">
                                            <i class="ti ti-refresh"></i> Refresh
                                        </button>
                                        <form method="POST" style="display:inline;">
                                            <button type="submit" name="clear_urls" class="btn btn-danger" data-bs-toggle="tooltip" data-bs-placement="top" title="Clear all URLs">
                                                <i class="ti ti-flame"></i> Clear All
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="table-responsive" style="overflow-x: auto;">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr class="text-muted fw-semibold text-center">
                                                <th style="width: 5%;">#</th>
                                                <th style="width: 15%;">Shortcode</th>
                                                <th style="width: 25%;">URL</th>
                                                <th style="width: 10%;">Human</th>
                                                <th style="width: 10%;">Robot</th>
                                                <th style="width: 10%;">Total</th>
                                                <th class="action-column" style="width: 25%;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody id="urlsTableBody">
                                            <?php
                                            $counter = 1;
                                            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                            $domain = $_SERVER['HTTP_HOST'];
                                            
                                            foreach ($urlsConfig as $key => $url) {
                                                $generatedUrl = "$protocol://$domain/go/{$key}";
                                                $human = $statsData['by_shortcode'][$key]['human'] ?? 0;
                                                $robot = $statsData['by_shortcode'][$key]['robot'] ?? 0;
                                                $totalVisitors = $statsData['by_shortcode'][$key]['total_visitors'] ?? 0;
                                                $humanPercent = $totalVisitors > 0 ? ($human / $totalVisitors) * 100 : 0;
                                                $robotPercent = $totalVisitors > 0 ? ($robot / $totalVisitors) * 100 : 0;
                                                echo "<tr>
                                                        <td class='text-center'>{$counter}</td>
                                                        <td class='text-center'>{$key}</td>
                                                        <td style='max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;'>
                                                            <a href='{$url}' target='_blank' class='text-decoration-none text-light'>{$url}</a>
                                                        </td>
                                                        <td class='text-center'>
                                                            <span class='badge bg-success'>{$human}</span>
                                                            <div class='progress'>
                                                                <div class='progress-bar progress-bar-human' role='progressbar' style='width: {$humanPercent}%' aria-valuenow='{$human}' aria-valuemin='0' aria-valuemax='{$totalVisitors}'></div>
                                                            </div>
                                                        </td>
                                                        <td class='text-center'>
                                                            <span class='badge bg-danger'>{$robot}</span>
                                                            <div class='progress'>
                                                                <div class='progress-bar progress-bar-robot' role='progressbar' style='width: {$robotPercent}%' aria-valuenow='{$robot}' aria-valuemin='0' aria-valuemax='{$totalVisitors}'></div>
                                                            </div>
                                                        </td>
                                                        <td class='text-center'>
                                                            <span class='badge bg-primary badge-total'><i class='ti ti-users me-1'></i>{$totalVisitors}</span>
                                                        </td>
                                                        <td class='text-center action-column'>
                                                            <div class='action-buttons d-flex justify-content-end gap-2'>
                                                                <button class='btn btn-warning btn-sm' onclick='openEditModal(\"{$key}\", \"{$url}\")' data-bs-toggle='tooltip' data-bs-placement='top' title='Edit this URL'>
                                                                    <i class='ti ti-edit'></i> Edit
                                                                </button>
                                                                <button class='btn btn-secondary btn-sm' onclick='copyToClipboard(\"{$generatedUrl}\")' data-bs-toggle='tooltip' data-bs-placement='top' title='Copy URL to clipboard'>
                                                                    <i class='ti ti-clipboard'></i> Copy
                                                                </button>
                                                                <form method='POST' style='display:inline-block;'>
                                                                    <input type='hidden' name='delete_key' value='{$key}'>
                                                                    <button type='submit' class='btn btn-danger btn-sm' data-bs-toggle='tooltip' data-bs-placement='top' title='Delete this URL'>
                                                                        <i class='ti ti-trash'></i> Delete
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                      </tr>";
                                                $counter++;
                                            }
                                            if (empty($urlsConfig)) {
                                                echo "<tr><td colspan='7' class='text-center'>No URLs available.</td></tr>";
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

    <!-- Edit URL Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit URL</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST">
                        <input type="hidden" id="edit_key" name="edit_key">
                        <div class="mb-3">
                            <label for="new_url" class="form-label">New URL</label>
                            <input type="url" class="form-control" id="new_url" name="new_url" required>
                        </div>
                        <button type="submit" class="btn btn-navy">Edit</button>
                    </form>
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

        function openEditModal(key, url) {
            document.getElementById('edit_key').value = key;
            document.getElementById('new_url').value = url;
            var editModal = new bootstrap.Modal(document.getElementById('editModal'));
            editModal.show();
        }

        function copyToClipboard(url) {
            const tempInput = document.createElement('input');
            tempInput.value = url;
            document.body.appendChild(tempInput);
            tempInput.select();
            document.execCommand('copy');
            document.body.removeChild(tempInput);
            alert('URL copied to clipboard!');
        }

        function validateURL() {
            var url = document.getElementById('add_url').value;
            var urlPattern = /^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/;
            var errorDiv = document.getElementById('urlError');

            if (!urlPattern.test(url)) {
                errorDiv.style.display = 'block';
                return false;
            } else {
                errorDiv.style.display = 'none';
                return true;
            }
        }

        function refreshURLs() {
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