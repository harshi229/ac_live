# Local Database Setup Guide

## Quick Setup Steps

### Option 1: Using phpMyAdmin (Easiest)

1. **Start XAMPP Services**
   - Open XAMPP Control Panel
   - Start Apache and MySQL services

2. **Access phpMyAdmin**
   - Open browser and go to: `http://localhost/phpmyadmin`

3. **Create Database**
   - Click "New" in the left sidebar
   - Database name: `air_conditioning_system_new`
   - Collation: `utf8mb4_general_ci`
   - Click "Create"

4. **Import SQL File**
   - Select the `air_conditioning_system_new` database
   - Click "Import" tab
   - Click "Choose File" and select: `database/migrations/air_conditioning_system_new.sql`
   - Click "Go" to import

### Option 2: Using Command Line

1. **Start MySQL in XAMPP**
2. **Open Command Prompt/Terminal**
3. **Run the following commands:**

```bash
# Navigate to XAMPP MySQL bin directory
cd C:\xampp\mysql\bin

# Connect to MySQL (default: no password)
mysql -u root

# Create database
CREATE DATABASE air_conditioning_system_new CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

# Use the database
USE air_conditioning_system_new;

# Import the SQL file
SOURCE C:/xampp/htdocs/public_html/database/migrations/air_conditioning_system_new.sql;

# Exit MySQL
EXIT;
```

## Database Configuration

The application is now configured to use:
- **Local Development**: 
  - Host: `localhost`
  - Database: `air_conditioning_system_new`
  - Username: `root`
  - Password: `` (empty)

- **Production**: Automatically uses production credentials when deployed

## Verify Setup

After importing, try accessing your local site:
- Homepage: `http://localhost/public_html/`
- Admin Panel: `http://localhost/public_html/admin/`

Default admin credentials (from the SQL file):
- Username: `admin`
- Password: Check your database for the hashed password, or reset it in the database

## Troubleshooting

### "Database connection failed" error
- Make sure MySQL service is running in XAMPP
- Verify database name is exactly: `air_conditioning_system_new`
- Check that username is `root` and password is empty
- If you changed MySQL root password, update `includes/config/database.php`

### "Database doesn't exist" error
- Make sure you created the database first
- Check the database name matches exactly

### "Access denied" error
- Verify your MySQL root password (if you set one)
- Update password in `includes/config/database.php` if needed

