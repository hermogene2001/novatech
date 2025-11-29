# Installation Guide for Novatech Investment Platform

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache or Nginx web server
- Composer (optional, for future enhancements)

## Installation Steps

### 1. Clone or Download the Repository

Place the Novatech files in your web server's document root:
- For XAMPP: `C:\xampp\htdocs\nova\`
- For WAMP: `C:\wamp64\www\nova\`
- For Linux: `/var/www/html/nova/`

### 2. Database Setup

#### Option A: Using the provided SQL file
1. Create a new database named `kinginvest` in your MySQL server
2. Import the `kinginvest.sql` file into your newly created database

#### Option B: Using the initialization script
1. Make sure your MySQL server is running
2. Update the database credentials in `config/database.php` if needed
3. Run the initialization script:
   ```bash
   cd /path/to/nova
   php init_db.php
   ```

### 3. Configure Database Connection

Open `config/database.php` and update the following values if needed:
```php
define('DB_HOST', 'localhost');     // Your database host
define('DB_USER', 'root');          // Your database username
define('DB_PASS', '');              // Your database password
define('DB_NAME', 'kinginvest');    // Your database name
```

### 4. Set Permissions

Ensure the following directories are writable by the web server:
- `uploads/products/`
- `uploads/slides/`
- `uploads/profiles/`

On Linux/Mac, you can set permissions with:
```bash
chmod -R 755 uploads/
```

### 5. Access the Application

Navigate to your application in a web browser:
- `http://localhost/nova/` (if installed in a subdirectory)
- `http://novatech.local/` (if configured with virtual host)

### 6. Default Login Credentials

After registering a new user, you can promote a user to admin by running the following SQL query:
```sql
UPDATE users SET role = 'admin' WHERE id = [USER_ID];
```

## Folder Structure

```
nova/
├── admin/              # Admin panel files
├── assets/             # CSS, JavaScript, and other assets
│   ├── css/
│   └── js/
├── client/             # Client dashboard files
├── config/             # Configuration files
├── uploads/            # Upload directories
│   ├── products/
│   ├── slides/
│   └── profiles/
├── index.php           # Homepage
├── login.php           # User login page
├── register.php        # User registration page
├── kinginvest.sql      # Database schema
└── init_db.php         # Database initialization script
```

## Troubleshooting

### Database Connection Issues
- Verify database credentials in `config/database.php`
- Ensure MySQL service is running
- Check if the database `kinginvest` exists

### Permission Issues
- Ensure the web server has write permissions to the `uploads/` directory
- On shared hosting, check with your provider about write permissions

### Missing Pages
- Ensure URL rewriting is enabled if using clean URLs
- Check that all files were uploaded correctly

## Support

For issues with the installation, please check:
1. PHP error logs
2. Browser developer console for JavaScript errors
3. Database connection and permissions

If you continue to experience issues, please open an issue on the project repository.