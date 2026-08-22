<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once dirname(__FILE__) . '/config.php';
require_once dirname(__FILE__) . '/auth.php';

$results = array();

// Check and create qr_dynamic table
$check1 = odbc_exec($conn, "SELECT OBJECT_ID('qr_dynamic', 'U') AS tid");
$row1 = odbc_fetch_array($check1);
$exists1 = isset($row1['tid']) && $row1['tid'] !== null;
odbc_free_result($check1);

if (!$exists1) {
    $sql1 = "
    CREATE TABLE qr_dynamic (
        id INT IDENTITY(1,1) PRIMARY KEY,
        code NVARCHAR(32) NOT NULL UNIQUE,
        target_url NVARCHAR(2048),
        label NVARCHAR(200),
        status SMALLINT DEFAULT 1,
        scan_count INT DEFAULT 0,
        created_at DATETIME DEFAULT GETDATE(),
        updated_at DATETIME DEFAULT GETDATE()
    )";
    $res1 = odbc_exec($conn, $sql1);
    if ($res1) {
        odbc_free_result($res1);
        $results[] = 'qr_dynamic table created';
    } else {
        $results[] = 'qr_dynamic create failed: ' . odbc_error($conn);
    }
} else {
    $results[] = 'qr_dynamic table already exists';

    // Add user_id column if not exists
    $cc0 = odbc_exec($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='qr_dynamic' AND COLUMN_NAME='user_id'");
    $rc0 = odbc_fetch_array($cc0);
    odbc_free_result($cc0);
    if (!$rc0) {
        $ac0 = odbc_exec($conn, "ALTER TABLE qr_dynamic ADD user_id INT NULL");
        if ($ac0) {
            odbc_free_result($ac0);
            $results[] = 'Added user_id column to qr_dynamic';
        } else {
            $results[] = 'user_id add failed: ' . odbc_error($conn);
        }
    } else {
        $results[] = 'user_id column already exists in qr_dynamic';
    }

    // Add expires_at column if not exists
    $cc0b = odbc_exec($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='qr_dynamic' AND COLUMN_NAME='expires_at'");
    $rc0b = odbc_fetch_array($cc0b);
    odbc_free_result($cc0b);
    if (!$rc0b) {
        $ac0b = odbc_exec($conn, "ALTER TABLE qr_dynamic ADD expires_at DATETIME NULL");
        if ($ac0b) { odbc_free_result($ac0b); $results[] = 'Added expires_at column to qr_dynamic'; }
        else { $results[] = 'expires_at add failed: ' . odbc_error($conn); }
    } else {
        $results[] = 'expires_at column already exists in qr_dynamic';
    }

    // Add max_scans column if not exists
    $cc0c = odbc_exec($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='qr_dynamic' AND COLUMN_NAME='max_scans'");
    $rc0c = odbc_fetch_array($cc0c);
    odbc_free_result($cc0c);
    if (!$rc0c) {
        $ac0c = odbc_exec($conn, "ALTER TABLE qr_dynamic ADD max_scans INT NULL");
        if ($ac0c) { odbc_free_result($ac0c); $results[] = 'Added max_scans column to qr_dynamic'; }
        else { $results[] = 'max_scans add failed: ' . odbc_error($conn); }
    } else {
        $results[] = 'max_scans column already exists in qr_dynamic';
    }

    // Add group_name column if not exists
    $cc0d = odbc_exec($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='qr_dynamic' AND COLUMN_NAME='group_name'");
    $rc0d = odbc_fetch_array($cc0d);
    odbc_free_result($cc0d);
    if (!$rc0d) {
        $ac0d = odbc_exec($conn, "ALTER TABLE qr_dynamic ADD group_name NVARCHAR(200) NULL");
        if ($ac0d) { odbc_free_result($ac0d); $results[] = 'Added group_name column to qr_dynamic'; }
        else { $results[] = 'group_name add failed: ' . odbc_error($conn); }
    } else {
        $results[] = 'group_name column already exists in qr_dynamic';
    }

    // Add qr_tag column if not exists
    $cc0e = odbc_exec($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='qr_dynamic' AND COLUMN_NAME='qr_tag'");
    $rc0e = odbc_fetch_array($cc0e);
    odbc_free_result($cc0e);
    if (!$rc0e) {
        $ac0e = odbc_exec($conn, "ALTER TABLE qr_dynamic ADD qr_tag NVARCHAR(200) NULL");
        if ($ac0e) { odbc_free_result($ac0e); $results[] = 'Added qr_tag column to qr_dynamic'; }
        else { $results[] = 'qr_tag add failed: ' . odbc_error($conn); }
    } else {
        $results[] = 'qr_tag column already exists in qr_dynamic';
    }

    // Add qr_tag_font_size column if not exists
    $cc0f = odbc_exec($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='qr_dynamic' AND COLUMN_NAME='qr_tag_font_size'");
    $rc0f = odbc_fetch_array($cc0f);
    odbc_free_result($cc0f);
    if (!$rc0f) {
        $ac0f = odbc_exec($conn, "ALTER TABLE qr_dynamic ADD qr_tag_font_size INT DEFAULT 14");
        if ($ac0f) { odbc_free_result($ac0f); $results[] = 'Added qr_tag_font_size column to qr_dynamic'; }
        else { $results[] = 'qr_tag_font_size add failed: ' . odbc_error($conn); }
    } else {
        $results[] = 'qr_tag_font_size column already exists in qr_dynamic';
    }

    // Add qr_logo column if not exists
    $cc0g = odbc_exec($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='qr_dynamic' AND COLUMN_NAME='qr_logo'");
    $rc0g = odbc_fetch_array($cc0g);
    odbc_free_result($cc0g);
    if (!$rc0g) {
        $ac0g = odbc_exec($conn, "ALTER TABLE qr_dynamic ADD qr_logo NVARCHAR(MAX) NULL");
        if ($ac0g) { odbc_free_result($ac0g); $results[] = 'Added qr_logo column to qr_dynamic'; }
        else { $results[] = 'qr_logo add failed: ' . odbc_error($conn); }
    } else {
        $results[] = 'qr_logo column already exists in qr_dynamic';
    }
}

// Check and create qr_users table
$check2 = odbc_exec($conn, "SELECT OBJECT_ID('qr_users', 'U') AS tid");
$row2 = odbc_fetch_array($check2);
$exists2 = isset($row2['tid']) && $row2['tid'] !== null;
odbc_free_result($check2);

if (!$exists2) {
    $sql2 = "
    CREATE TABLE qr_users (
        id INT IDENTITY(1,1) PRIMARY KEY,
        username NVARCHAR(50) NOT NULL UNIQUE,
        password_hash NVARCHAR(128) NOT NULL,
        role SMALLINT DEFAULT 0,
        email NVARCHAR(200),
        reset_token NVARCHAR(20),
        failed_count INT DEFAULT 0,
        locked_until DATETIME NULL,
        created_at DATETIME DEFAULT GETDATE(),
        updated_at DATETIME DEFAULT GETDATE()
    )";
    $res2 = odbc_exec($conn, $sql2);
    if ($res2) {
        odbc_free_result($res2);
        $results[] = 'qr_users table created';
        // Insert default admin account
        $admin_hash = hashPassword('qrforge2026');
        $admin_sql = "INSERT INTO qr_users (username, password_hash, role, failed_count) VALUES ('admin', '" . $admin_hash . "', 1, 0)";
        $res3 = odbc_exec($conn, $admin_sql);
        if ($res3) {
            odbc_free_result($res3);
            $results[] = 'Default admin account created (admin / qrforge2026)';
        } else {
            $results[] = 'Admin account insert failed: ' . odbc_error($conn);
        }
    } else {
        $results[] = 'qr_users create failed: ' . odbc_error($conn);
    }
} else {
    $results[] = 'qr_users table already exists';

    // Add failed_count column if not exists
    $cc1 = odbc_exec($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='qr_users' AND COLUMN_NAME='failed_count'");
    $rc1 = odbc_fetch_array($cc1);
    odbc_free_result($cc1);
    if (!$rc1) {
        $ac1 = odbc_exec($conn, "ALTER TABLE qr_users ADD failed_count INT DEFAULT 0");
        if ($ac1) {
            odbc_free_result($ac1);
            $results[] = 'Added failed_count column';
        } else {
            $results[] = 'failed_count add failed: ' . odbc_error($conn);
        }
    } else {
        $results[] = 'failed_count column already exists';
    }

    // Add locked_until column if not exists
    $cc2 = odbc_exec($conn, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='qr_users' AND COLUMN_NAME='locked_until'");
    $rc2 = odbc_fetch_array($cc2);
    odbc_free_result($cc2);
    if (!$rc2) {
        $ac2 = odbc_exec($conn, "ALTER TABLE qr_users ADD locked_until DATETIME NULL");
        if ($ac2) {
            odbc_free_result($ac2);
            $results[] = 'Added locked_until column';
        } else {
            $results[] = 'locked_until add failed: ' . odbc_error($conn);
        }
    } else {
        $results[] = 'locked_until column already exists';
    }
}

odbc_close($conn);

echo json_encode(array('success' => true, 'results' => $results));
?>
