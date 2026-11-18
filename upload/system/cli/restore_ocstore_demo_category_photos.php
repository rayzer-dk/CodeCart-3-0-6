<?php
/**
 * Optional, fail-closed repair for the stock 38-category ocStore 3.0.4.1 demo.
 *
 * No customer images are ever replaced. On a non-demo database this tool exits
 * without modifying anything. Not part of automatic storefront migrations.
 *
 * php system/cli/restore_ocstore_demo_category_photos.php --config=/absolute/path/config.php
 * php system/cli/restore_ocstore_demo_category_photos.php --config=/absolute/path/config.php --apply --backup-confirmed
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}

function codecartDemoCategoryPhotos(): array {
    return array(
        17 => array(0, 'imac_2.webp'),
        18 => array(0, 'hp_2.webp'),
        20 => array(0, 'compaq_presario.webp'),
        24 => array(0, 'iphone_1.webp'),
        25 => array(0, 'apple_cinema_30.webp'),
        26 => array(20, 'compaq_presario.webp'),
        27 => array(20, 'imac_1.webp'),
        28 => array(25, 'apple_cinema_30.webp'),
        29 => array(25, 'htc_touch_hd_1.webp'),
        30 => array(25, 'hp_1.webp'),
        31 => array(25, 'canon_eos_5d_1.webp'),
        32 => array(25, 'iphone_3.webp'),
        33 => array(0, 'canon_eos_5d_1.webp'),
        34 => array(0, 'ipod_touch_4.webp'),
        35 => array(28, 'ipod_nano_1.webp'),
        36 => array(28, 'ipod_nano_2.webp'),
        37 => array(34, 'ipod_nano_3.webp'),
        38 => array(34, 'ipod_nano_4.webp'),
        39 => array(34, 'ipod_nano_5.webp'),
        40 => array(34, 'ipod_shuffle_1.webp'),
        41 => array(34, 'ipod_shuffle_2.webp'),
        42 => array(34, 'ipod_shuffle_3.webp'),
        43 => array(34, 'ipod_shuffle_4.webp'),
        44 => array(34, 'ipod_shuffle_5.webp'),
        45 => array(18, 'sony_vaio_1.webp'),
        46 => array(18, 'macbook_1.webp'),
        47 => array(34, 'ipod_touch_1.webp'),
        48 => array(34, 'ipod_touch_2.webp'),
        49 => array(34, 'ipod_touch_3.webp'),
        50 => array(34, 'ipod_touch_4.webp'),
        51 => array(34, 'ipod_touch_5.webp'),
        52 => array(34, 'ipod_touch_6.webp'),
        53 => array(34, 'ipod_touch_7.webp'),
        54 => array(34, 'ipod_classic_1.webp'),
        55 => array(34, 'ipod_classic_2.webp'),
        56 => array(34, 'ipod_classic_3.webp'),
        57 => array(0, 'samsung_tab_1.webp'),
        58 => array(57, 'samsung_tab_2.webp')
    );
}

function codecartDemoPhotoFailure(string $message): void {
    fwrite(STDERR, "Demo category images: " . $message . "\n");
    exit(1);
}

$arguments = array_slice($argv, 1);
$configFile = null;
foreach ($arguments as $argument) {
    if (strpos($argument, '--config=') === 0) {
        $configFile = substr($argument, strlen('--config='));
    }
}
$apply = in_array('--apply', $arguments, true);
$backupConfirmed = in_array('--backup-confirmed', $arguments, true);

if ($configFile === null || !is_file($configFile)) {
    codecartDemoPhotoFailure('Specify --config=/absolute/path/to/installed/site/config.php');
}
if ($apply && !$backupConfirmed) {
    codecartDemoPhotoFailure('Applying changes requires a verified backup and --backup-confirmed.');
}
if (!extension_loaded('mysqli')) {
    codecartDemoPhotoFailure('mysqli is required.');
}

require $configFile;
foreach (array('DB_HOSTNAME', 'DB_USERNAME', 'DB_PASSWORD', 'DB_DATABASE', 'DB_PREFIX', 'DIR_IMAGE') as $constant) {
    if (!defined($constant)) {
        codecartDemoPhotoFailure('Configuration is missing ' . $constant);
    }
}
if (!preg_match('/^[a-z0-9_]*$/iD', DB_PREFIX)) {
    codecartDemoPhotoFailure('Invalid database prefix');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $port = defined('DB_PORT') && (int)DB_PORT > 0 ? (int)DB_PORT : 3306;
    $db = new mysqli(DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE, $port);
    $db->set_charset('utf8mb4');

    $map = codecartDemoCategoryPhotos();
    $root = realpath(DIR_IMAGE);
    if ($root === false) {
        codecartDemoPhotoFailure('Image directory is unavailable.');
    }
    $root = rtrim(str_replace('\\', '/', $root), '/') . '/';
    $table = '`' . DB_PREFIX . 'category`';
    $query = $db->query("SELECT category_id, parent_id, image FROM " . $table . " ORDER BY category_id");
    $actual = array();
    while ($row = $query->fetch_assoc()) {
        $actual[(int)$row['category_id']] = $row;
    }

    // All stock demo IDs and parent relationships must match exactly.
    // A merchant catalogue, an altered tree, or a partial import is rejected.
    if (count($actual) !== count($map) || array_keys($actual) !== array_keys($map)) {
        codecartDemoPhotoFailure('The current category set is not the stock 38-category demo. No changes made.');
    }
    foreach ($map as $id => $spec) {
        if ((int)$actual[$id]['parent_id'] !== $spec[0]) {
            codecartDemoPhotoFailure('The category hierarchy differs from the original demo. No changes made.');
        }
    }

    $updates = array();
    $missingFiles = array();
    foreach ($map as $id => $spec) {
        if (trim((string)$actual[$id]['image']) !== '') {
            continue;
        }
        $relative = 'catalog/demo/' . $spec[1];
        $real = realpath($root . $relative);
        $real = $real === false ? '' : str_replace('\\', '/', $real);
        if ($real === '' || strpos($real, $root) !== 0 || !is_file($real)) {
            $missingFiles[] = $relative;
        } else {
            $updates[$id] = $relative;
        }
    }

    echo "Verified original ocStore demo hierarchy: 38 categories.\n";
    echo "Existing category images preserved: " . (count($map) - count($updates) - count($missingFiles)) . "\n";
    echo "Missing images ready to restore: " . count($updates) . "\n";
    if ($missingFiles) {
        echo "Image files absent: " . implode(', ', $missingFiles) . "\n";
        codecartDemoPhotoFailure('Copy demo images from the CodeCart package before applying any repair.');
    }
    if (!$apply) {
        echo "DRY RUN: no changes made. Add --apply --backup-confirmed after reviewing the result.\n";
        exit(0);
    }

    $db->begin_transaction();
    try {
        $statement = $db->prepare("UPDATE " . $table . " SET image = ? WHERE category_id = ? AND (image IS NULL OR image = '')");
        $count = 0;
        foreach ($updates as $id => $relative) {
            $statement->bind_param('si', $relative, $id);
            $statement->execute();
            $count += $statement->affected_rows;
        }
        $statement->close();
        $db->commit();
    } catch (Throwable $e) {
        $db->rollback();
        throw $e;
    }

    echo "Completed: " . $count . " missing demo category photos restored. Merchant images remain untouched.\n";
} catch (Throwable $e) {
    codecartDemoPhotoFailure($e->getMessage());
}
