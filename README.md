# SherLog - PHP Single File Log Analyzer

<table style="border-collapse: collapse; border: none;">
  <tr style="border: none;">
    <td style="border: none; vertical-align: top; padding: 0;">
      <img src="assets/SherLog.png" alt="SherLog Logo" style="width: auto; height: auto; max-width: 100px; max-height: 100px; margin-right: 10px;">
    </td>
    <td style="border: none; vertical-align: top; padding: 0;">
      This project is a single file log analyzer written in PHP. It enables you to read various log formats including PHP slow logs, PHP-FPM error logs, Apache 2.4 access logs, and Apache 2.4 error logs. It is compatible with PHP 8 and uses a mask system that allows you to easily add new log types for interpretation.
    </td>
  </tr>
</table>


## Features

- **Single File Deployment**: Designed to be a single file for easy deployment. Ideal for integration into systems such as `<Files>` in Apache or their equivalents in Nginx, ensuring that the analysis files remain in place even after production updates.
- **Log Formats Supported**: Out-of-the-box support for PHP slow logs, PHP-FPM error logs, Apache 2.4 access logs, and Apache 2.4 error logs.
- **Tail Mode**: Includes an auto-refresh feature, making it perfect for real-time debugging.
- **Search Box**: Quickly find specific entries in your logs.
- **PHP 8 Compatibility**: Fully compatible with PHP 8, ensuring modern PHP features and performance.
- **Maximum Log Entries**: Limits the number of log lines displayed to prevent the script from being overwhelmed (default: 5000 lines).

## Log Format Patterns

The log analyzer uses regular expressions to identify different log formats. You can easily extend support for new log types by adding patterns to the `$logPatterns` array.

```php
$logPatterns = [
    'php-fpm' => '/^\[\d{2}-[A-Za-z]{3}-\d{4} \d{2}:\d{2}:\d{2} [A-Z]{3}\]/',
    'apache-access' => '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3} - - \[\d{2}\/[A-Za-z]{3}\/\d{4}:\d{2}:\d{2}:\d{2} \+\d{4}\]/',
    'apache-error' => '/^\[[A-Za-z]{3} [A-Za-z]{3} \d{2} \d{2}:\d{2}:\d{2}\.\d{6} \d{4}\] \[[a-z]+:[a-z]+\]/',
    'slow-php' => '/^\[\d{2}-[A-Za-z]{3}-\d{4} \d{2}:\d{2}:\d{2}\]/'
];
```

## How to Use

1. **Deployment**: Simply upload the `log-viewer.php` file to your server.
2. **Access**: Navigate to the file in your browser.
3. **Authentication**: Enter the configured password to access the log viewer.
4. **Select Log File**: Use the dropdown to select the log file you wish to view.
5. **Tail Mode**: Activate the auto-refresh mode to continuously update the log view in real-time.
6. **Search**: Use the search box to filter log entries and quickly find relevant information.

## Example Configuration

### Apache Configuration

This tool can be deployed in systems like Apache's `<Files>` directive to maintain log analysis capabilities even after production updates. Here's an example configuration for Apache:

```apache
<Files "log-viewer.php">
    Require all granted
</Files>
```

### Nginx Configuration

In Nginx, you can achieve similar functionality by using the `location` directive to restrict access to the log viewer file:

```nginx
location /log-viewer.php {
    allow 192.168.1.0/24; # Replace with your allowed IP range
    deny all;
    include fastcgi_params;
    fastcgi_pass unix:/var/run/php/php8.0-fpm.sock; # Adjust according to your PHP-FPM socket
    fastcgi_param SCRIPT_FILENAME /path/to/log-viewer.php; # Adjust to your file path
}
```

## Requirements

- PHP 8.0 or higher
- Web server (Apache, Nginx, etc.)

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

