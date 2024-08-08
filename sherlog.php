<?php
session_start();

$password = 'MyPassw0rd'; // Password to access the script
$logDir = '../../logs/';
$maxLogEntries = 2000; // Limit of log lines displayed

// Function to sanitize the input
function sanitize_input(string $input): string {
    return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
}

// Improved log line detection functions
function isLogLine(string $line, string $pattern): bool {
    return (bool)preg_match($pattern, $line);
}

$logPatterns = [
    'php-fpm' => '/^\[\d{2}-[A-Za-z]{3}-\d{4} \d{2}:\d{2}:\d{2} [A-Z]{3}\]/',
    'apache-access' => '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3} - - \[\d{2}\/[A-Za-z]{3}\/\d{4}:\d{2}:\d{2}:\d{2} \+\d{4}\]/',
    'apache-error' => '/^\[[A-Za-z]{3} [A-Za-z]{3} \d{2} \d{2}:\d{2}:\d{2}\.\d{6} \d{4}\] \[[a-z]+:[a-z]+\]/',
    'slow-php' => '/^\[\d{2}-[A-Za-z]{3}-\d{4} \d{2}:\d{2}:\d{2}\]/'
];

function getLogType(string $file, array $patterns): string {
    $handle = fopen($file, 'r');
    if ($handle) {
        while (($line = fgets($handle)) !== false) {
            foreach ($patterns as $type => $pattern) {
                if (isLogLine($line, $pattern)) {
                    fclose($handle);
                    return $type;
                }
            }
        }
        fclose($handle);
    }
    return 'unknown';
}

// Ensure $logFiles is not empty
$logFiles = glob($logDir . '*.log');
if (empty($logFiles)) {
    die('No log files found.');
}

$selectedLogFile = isset($_GET['logfile']) ? sanitize_input($_GET['logfile']) : basename($logFiles[0]);
$logType = getLogType($logDir . $selectedLogFile, $logPatterns);

$lines = [];
$currentLog = '';

$file = new SplFileObject($logDir . $selectedLogFile, 'r');
while (!$file->eof()) {
    $line = $file->fgets();
    $logPattern = $logPatterns[$logType] ?? null;
    if ($logPattern && isLogLine($line, $logPattern)) {
        if ($currentLog !== '') {
            $lines[] = $currentLog;
            if (count($lines) >= $maxLogEntries) {
                break;
            }
        }
        $currentLog = $line;
    } else {
        $currentLog .= $line;
    }
}

if ($currentLog !== '') {
    $lines[] = $currentLog;
}

$lines = array_slice($lines, -$maxLogEntries);

$logFileName = basename($selectedLogFile);
$totalEntries = count($lines);

if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $response = [
        'entries' => $totalEntries,
        'lines' => ''
    ];
    foreach (array_reverse($lines) as $line) {
        $response['lines'] .= "<div class='log-entry'><pre>" . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . "</pre></div>";
    }
    echo json_encode($response);
    exit;
}

// Initialize $login_error variable
$login_error = '';

// Authentication logic
if (!isset($_SESSION['authenticated']) || !$_SESSION['authenticated']) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['password']) && hash_equals($password, $_POST['password'])) {
            $_SESSION['authenticated'] = true;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $login_error = "Incorrect password.";
        }
    }
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #F9F9F9;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            flex-direction: column;
        }
        .login-container {
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            text-align: center;
        }
        .login-header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
        .login-header a {
            display: flex;
            align-items: center;
            text-decoration: none;
        }
        .login-header img {
            height: 60px; /* Increased by 50% */
            margin-right: 10px;
        }
        .login-header h1 {
            color: #007ACC;
            margin: 0;
        }
        .login-container form {
            display: flex;
            flex-direction: column;
        }
        .login-container input[type="password"],
        .login-container input[type="submit"] {
            padding: 10px;
            margin: 10px 0;
            font-size: 16px;
        }
        .login-container input[type="submit"] {
            background-color: #007ACC;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        .login-container .error {
            color: red;
            margin-bottom: 10px;
        }
        footer {
            margin-top: 20px;
            width: 100%;
            padding: 10px 0;
            background-color: #007ACC;
            color: white;
            text-align: center;
            position: absolute;
            bottom: 0;
        }
        footer a {
            color: white;
            text-decoration: none;
        }
        footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="login-container">
    <div class="login-header">
        <a href="https://github.com/ByteNinja64/SherLog" target="_blank">
            <img src="https://github.com/ByteNinja64/SherLog/blob/main/assets/SherLog.png?raw=true" alt="Logo">
            <h1>SherLog</h1>
        </a>
    </div>
    <form method="POST">
        <input type="password" name="password" placeholder="Password" required>
        <input type="submit" value="Sign in">
    </form>
    <div class="error">{$login_error}</div>
</div>
<footer>
    <a href="https://github.com/ByteNinja64/SherLog" target="_blank">SherLog</a>
</footer>
</body>
</html>
HTML;
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize_input($logFileName) ?> - <?= $totalEntries ?> entries</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #F9F9F9;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            position: relative;
            flex: 1;
        }
        .logo {
            position: absolute;
            top: 20px;
            left: 20px;
            height: 60px; /* Increased by 50% */
        }
        .title {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
            color: #007ACC;
        }
        .log-container {
            border-top: 1px solid #ddd;
        }
        .log-entry {
            padding: 10px 15px;
            margin: 0;
            border-bottom: 1px solid #eee;
            border-left: 5px solid transparent;
            transition: border-color 0.3s, background-color 0.3s;
        }
        .log-entry:nth-child(even) {
            background-color: #f7f7f7;
        }
        .log-entry:nth-child(odd) {
            background-color: #eef6ff;
        }
        .log-entry:hover {
            border-left-color: #007ACC;
            background-color: #f0faff;
        }
        pre {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word;
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px;
        }
        .log-selector {
            margin-bottom: 20px;
            text-align: center;
        }
        .log-selector select, .log-selector input[type="text"] {
            padding: 10px;
            margin: 10px 5px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .log-selector label {
            font-size: 16px;
            margin-left: 10px;
        }
        .log-selector input[type="checkbox"] {
            margin-left: 5px;
        }
        footer {
            margin-top: 20px;
            padding: 10px 0;
            background-color: #007ACC;
            color: white;
            text-align: center;
            border-radius: 0 0 8px 8px;
        }
        footer a {
            color: white;
            text-decoration: none;
        }
        footer a:hover {
            text-decoration: underline;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            let refreshInterval;

            $('#auto-refresh').change(function() {
                if ($(this).is(':checked')) {
                    startAutoRefresh();
                } else {
                    stopAutoRefresh();
                }
            });

            $('#search-box').on('input', function() {
                filterEntries();
            });

            function startAutoRefresh() {
                refreshInterval = setInterval(function() {
                    $.ajax({
                        url: '',
                        type: 'GET',
                        data: { logfile: '<?= sanitize_input($selectedLogFile) ?>', ajax: true },
                        dataType: 'json',
                        success: function(data) {
                            $('.log-container').html(data.lines);
                            $('.title').text('<?= sanitize_input($logFileName) ?> - ' + data.entries + ' entries');
                            filterEntries();
                        }
                    });
                }, 5000);
            }

            function stopAutoRefresh() {
                clearInterval(refreshInterval);
            }

            function filterEntries() {
                let searchTerm = $('#search-box').val().toLowerCase();
                $('.log-entry').each(function() {
                    let entryText = $(this).text().toLowerCase();
                    $(this).toggle(entryText.indexOf(searchTerm) !== -1);
                });
            }
        });
    </script>
</head>
<body>
<div class="container">
    <a href="https://github.com/ByteNinja64/SherLog" target="_blank">
        <img class="logo" src="https://github.com/ByteNinja64/SherLog/blob/main/assets/SherLog.png?raw=true" alt="Logo">
    </a>
    <div class="title"><?= sanitize_input($logFileName) ?> - <?= $totalEntries ?> entries</div>
    <div class="log-selector">
        <form method="GET">
            <select name="logfile" onchange="this.form.submit()">
                <?php foreach ($logFiles as $logFile) : ?>
                    <?php $logFileName = basename($logFile); ?>
                    <option value="<?= sanitize_input($logFileName) ?>" <?= $logFileName === $selectedLogFile ? 'selected' : '' ?>><?= sanitize_input($logFileName) ?></option>
                <?php endforeach; ?>
            </select>
            <label><input type="checkbox" id="auto-refresh"> Activate tail mode</label>
            <input type="text" id="search-box" placeholder="Search...">
        </form>
    </div>
    <div class="log-container">
        <?php foreach (array_reverse($lines) as $line) : ?>
            <div class="log-entry"><pre><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?></pre></div>
        <?php endforeach; ?>
    </div>
    <footer>
        <a href="https://github.com/ByteNinja64/SherLog" target="_blank">SherLog</a>
    </footer>
</div>
</body>
</html>
