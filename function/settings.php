<?php
class ItsMoscow {
    protected $password;

    public function __construct() {
        // Read the JSON data from file
        $config_file = __DIR__ . '/config.json';
        $json_data = file_get_contents($config_file);
        $config = json_decode($json_data, true);

        // Set relevant properties for the Admin Panel
        $this->password = $config['password'] ?? '';
    }

    public function getSettings() {
        return array(
            'password' => $this->password,
        );
    }

    // Function to handle loader/waiting state
    public function startLoader() {
        // Set session to indicate loader is active
        $_SESSION['loader'] = true;
        return true; // Can be returned for confirmation
    }

    public function stopLoader() {
        // Stop loader by unsetting the session
        unset($_SESSION['loader']);
        return true;
    }

    public function checkLoader() {
        // Check if loader is currently active
        return isset($_SESSION['loader']) && $_SESSION['loader'] === true;
    }

    function generateRandomText($length) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomText = '';

        for ($i = 0; $i < $length; $i++) {
            $randomText .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $randomText;
    }
    public function BlacklistUser(){
        // Path to the log file
        $log_file = '../log/blacklist.log';
        $log_lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $total_lines = count($log_lines);
        return $total_lines;
    }

    public function SaveBlacklist() {
        $ip = trim($_POST['blacklistip'] ?? ''); // Retrieve IP and remove spaces
        if (!filter_var($ip, FILTER_VALIDATE_IP)) { // Validate IP
            $_SESSION['cleanblacklist'] = 'failed';
            header("Location: blacklist");
            exit;
        }

        $logblocked = "$ip\n"; // Use \n for consistent new lines
        $blacklist_file = '../log/blacklist.log';
        
        // Check for duplicates before writing
        if (file_exists($blacklist_file)) {
            $existing_ips = file($blacklist_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (in_array($ip, array_map('trim', $existing_ips))) {
                $_SESSION['cleanblacklist'] = 'success'; // Already exists, still success
                header("Location: blacklist");
                exit;
            }
        }
        
        file_put_contents($blacklist_file, $logblocked, FILE_APPEND);
        $_SESSION['cleanblacklist'] = 'success';
        header("Location: blacklist");
        exit;
    }

    
    public function deleteUsers(){
        $blacklist_filename = "../log/usersinfo.log";
        $ip_to_delete = $_POST['delete_ip'];
    
        // Read the blacklist file into an array
        $blacklist_content = file($blacklist_filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
        // Trim each line to remove leading/trailing spaces and filter the array
        $blacklist_content = array_filter(array_map('trim', $blacklist_content));
    
        // Find and remove the line containing the IP to be deleted
        $updated_content = array_filter($blacklist_content, function($line) use ($ip_to_delete) {
            return strpos($line, $ip_to_delete . '|') !== 0;
        });
    
        // Save the updated list back to the file
        file_put_contents($blacklist_filename, implode(PHP_EOL, $updated_content));

        $log_entry = "\r\n";
        file_put_contents('../log/usersinfo.log', $log_entry , FILE_APPEND);
    
        // Redirect to the same page to avoid form resubmission
        $_SESSION['cleanlog'] = 'success';
        header("Location: ?success");
        exit;
    }    

    public function deleteBlacklist(){
        $blacklist_filename = "../log/blacklist.log";
        $ip_to_delete = $_POST['delete_ip'];

        // Read the blacklist file into an array
        $blacklist_content = file($blacklist_filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        // Trim each line to remove leading/trailing spaces and filter the array
        $blacklist_content = array_filter(array_map('trim', $blacklist_content));

        // Remove the IP to be deleted
        $blacklist_content = array_diff($blacklist_content, [$ip_to_delete]);

        // Save the updated list back to the file
        file_put_contents($blacklist_filename, implode(PHP_EOL, $blacklist_content));

        // Redirect to the same page to avoid form resubmission
        $_SESSION['cleanlog'] = 'success';
        header("Location: ./blacklist");
        exit;
    }
    
    public function Visitors() {
        $log_file = '../log/view.log';
        $log_lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $total_lines = count($log_lines);
        return $total_lines;
    }
    
    public function VisitRobot() {
        $log_file = '../log/robot.log';
        $log_lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $total_lines = count($log_lines);
        return $total_lines;
    }

    public function RobotPercentage() {
        $visitors = $this->Visitors();
        if ($visitors == 0) {
            $percentage = 0;
        } else {
            $percentage = ($this->VisitRobot() / $visitors) * 100;
        }
        $showpercent = number_format($percentage, 2);
        return $showpercent;
    }

    public function VisitReal() {
        $log_file = '../log/real.log';
        $log_lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $total_lines = count($log_lines);
        return $total_lines;
    }

    public function VisitBlacklist() {
        $log_file = '../log/blackvisit.log';
        $log_lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $total_lines = count($log_lines);
        return $total_lines;
    }

    public function VisitBlocked() {
        $log_file = '../log/blocked.log';
        $log_lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $total_lines = count($log_lines);
        return $total_lines;
    }

    public function BlockedPercentage() {
        if ($this->VisitBlocked() == 0) {
            $percentage = 0;
        } else {
            $percentage = ($this->VisitBlocked() / $this->Visitors()) * 100;
        }
        $showpercent = number_format($percentage, 2);
        return $showpercent;
    }

    public function BlacklistPercentage() {
        $visitors = $this->Visitors();
        if ($visitors == 0) {
            $percentage = 0;
        } else {
            $percentage = ($this->VisitBlacklist() / $visitors) * 100;
        }
        $showpercent = number_format($percentage, 2);
        return $showpercent;
    }

    public function RealPercentage() {
        if ($this->VisitReal() == 0) {
            $percentage = 0;
        } else {
            $percentage = ($this->VisitReal() / $this->Visitors()) * 100;
        }
        $showpercent = number_format($percentage, 2);
        return $showpercent;
    }

    public function CleanAllLogs() {
        $this->startLoader();
        $log_files = [
            '../log/view.log',
            '../log/real.log',
            '../log/robot.log',
            '../log/blackvisit.log',
            '../log/stats.log',
            '../log/blocked.log',
        ];

        $all_cleared = true;
        foreach ($log_files as $log_file) {
            if (file_put_contents($log_file, '') === false) {
                $all_cleared = false;
            }
        }

        if ($all_cleared) {
            $_SESSION['cleanlog'] = 'success';
        } else {
            $_SESSION['cleanlog'] = 'failed';
        }
        $this->stopLoader();
        header("Location: ?Success");
        exit;
    }
}
?>