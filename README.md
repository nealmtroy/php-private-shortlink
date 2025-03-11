# Nealmtroy Shortlink
A private, secure PHP-based admin panel designed for managing a custom shortlink service. This tool allows an admin to create, edit, and monitor short URLs, blacklist IPs, and track visitor statistics, all behind a password-protected interface. It features a sleek dark-themed UI with Bootstrap and Particles.js.

## Description
This is a private shortlink service, meaning it is not intended for public use. Access is restricted to an admin user via a secure login, and shortlinks are only accessible through generated keys managed by the admin. The service includes advanced validation, randomization, and IP checking to ensure security and functionality.

## Features
- **Private Access**: Restricted to a single admin user with secure login.
- **Shortlink Management**: Create, edit, and delete short URLs with random key generation.
- **URL Validation**: Ensures only valid URLs are stored; invalid URLs are rejected.
- **Random Redirect**: Supports random redirection from a pool of URLs (if configured).
- **IP Blacklisting**: Block unwanted IPs with a log-based system.
- **Statistics Tracking**: Monitor human vs. robot visitors per shortcode.
- **Secure Authentication**: Uses Bcrypt-hashed passwords, token-based sessions, and CSRF protection.
- **Responsive Design**: Dark-themed UI with animations and particle effects.

## Requirements
- PHP 7.4 or higher
- Web server (e.g., Apache, Nginx)
- HTTPS enabled (mandatory for secure cookie handling)
- File system write permissions for logs and config files

## Installation
1. Clone the repo:
   ```bash
   git clone https://github.com/nealmtroy/php-private-shortlink.git
   cd php-private-shortlink
2. Set up config.json (edit and add credentials):
   - Edit `config.json` and add your password hash (e.g., {"password": "$2y$12$hashedpasswordhere..."}).
3. Run on a PHP server with HTTPS enabled:
   - Install XAMPP (local) or upload to a PHP hosting.
   - Enable HTTPS (use SSL on hosting or set up locally with XAMPP).
4. Access via https://your-domain/nealmtroy:
   - Open browser and go to `https://localhost/nealmtroy` (local) or `https://your-domain/nealmtroy` (hosting).
   - Log in with the default admin password (see Security section).

## Security
5. Important security notes:
   - Enforces HTTPS for secure access.
   - The default admin panel password is "**Admin123**". After first login, go to the Settings page and change it immediately to a strong, unique password to enhance security.
