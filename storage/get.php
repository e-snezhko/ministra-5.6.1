<?php

use Ministra\Lib\S642b6461e59cef199375bfb377c17a39\Z860a165ed018f157fd40ef2297209b46\s11f4c3e4ac7fcef8584efe64e972b115;
require_once './common.php';
\error_reporting(0);
\ob_start();
$filename = \basename($_GET['filename']);
$ch_id = (int) $_GET['ch_id'];
$start_time = (int) $_GET['start'];
$duration = (int) $_GET['duration'];
$token = $_GET['token'];
$queue = [];
if (empty($filename) || empty($duration) || empty($token)) {
    \header('HTTP/1.0 400 Bad Request');
    exit;
}
$file = \RECORDS_DIR . "archive/{$ch_id}/{$filename}";
if (!\file_exists($file) || !\is_readable($file)) {
    \header('HTTP/1.0 404 Not Found');
    exit;
}
if (!\defined('PORTAL_URL')) {
    \header('HTTP/1.0 500 Internal Server Error');
    \Ministra\Lib\S642b6461e59cef199375bfb377c17a39\Z860a165ed018f157fd40ef2297209b46\s11f4c3e4ac7fcef8584efe64e972b115::q6ee195c1759171b9aef09286fb44db47('TV archive: PORTAL_URL is not defined');
    echo 'PORTAL_URL is not defined';
    exit;
}
$token_resp = \file_get_contents(\PORTAL_URL . '/server/api/chk_tmp_archive_link.php?token=' . $token);
if ($token_resp === \false) {
    \header('HTTP/1.0 500 Internal Server Error');
    \Ministra\Lib\S642b6461e59cef199375bfb377c17a39\Z860a165ed018f157fd40ef2297209b46\s11f4c3e4ac7fcef8584efe64e972b115::q6ee195c1759171b9aef09286fb44db47('TV archive: Portal connection failure');
    echo 'Portal connection failure';
    exit;
}
$token_resp = \json_decode($token_resp, \true);
if ($token_resp === \false || !isset($token_resp['result'])) {
    \header('HTTP/1.0 500 Internal Server Error');
    \Ministra\Lib\S642b6461e59cef199375bfb377c17a39\Z860a165ed018f157fd40ef2297209b46\s11f4c3e4ac7fcef8584efe64e972b115::q6ee195c1759171b9aef09286fb44db47('TV archive: Could not decode portal response');
    echo 'Could not decode portal response';
    exit;
}
if ($token_resp['result'] !== \true) {
    \header('HTTP/1.0 403 Forbidden');
    \Ministra\Lib\S642b6461e59cef199375bfb377c17a39\Z860a165ed018f157fd40ef2297209b46\s11f4c3e4ac7fcef8584efe64e972b115::q6ee195c1759171b9aef09286fb44db47('TV archive: Not valid token ' . $token);
    echo 'Not valid token';
    exit;
}
while ($duration > 0) {
    if (\file_in_current_hour($file)) {
        $chunk_size = (int) \date('i') * 60 + (int) \date('s');
    } else {
        $chunk_size = 3600;
    }
    $filesize = \filesize($file);
    $from_byte = (int) ($start_time * $filesize / $chunk_size);
    if ($duration + $start_time >= $chunk_size) {
        $to_byte = $filesize;
        $duration -= $chunk_size - $start_time;
        $start_time = 0;
    } else {
        $to_byte = (int) (($start_time + $duration) * $filesize / $chunk_size);
        $duration = 0;
    }
    $queue[] = ['filename' => $file, 'from_byte' => $from_byte, 'to_byte' => $to_byte, 'size' => $to_byte - $from_byte];
    $start_time = 0;
    $file = \get_next_file($file);
}
$size = \get_content_length($queue);
\_log("\nTotal size: " . $size);
if (isset($_SERVER['HTTP_RANGE'])) {
    list($size_unit, $range_orig) = \explode('=', $_SERVER['HTTP_RANGE'], 2);
    if ($size_unit == 'bytes') {
        list($range, $extra_ranges) = \explode(',', $range_orig, 2);
        \_log('Range: ' . $range_orig);
    } else {
        $range = '';
    }
} else {
    $range = '';
}
list($seek_start, $seek_end) = \explode('-', $range, 2);
$seek_end = empty($seek_end) ? $size - 1 : \min(\abs((int) $seek_end), $size - 1);
$seek_start = empty($seek_start) || $seek_end < \abs((int) $seek_start) ? 0 : \max(\abs((int) $seek_start), 0);
if (isset($_SERVER['HTTP_RANGE'])) {
    \header('HTTP/1.1 206 Partial Content');
}
\header('Content-Type: video/mpeg');
\header('Content-Length: ' . ($seek_end - $seek_start + 1));
\header('Content-Range: bytes ' . $seek_start . '-' . $seek_end . '/' . $size);
$offset = 0;
\_log('Queue length: ' . \count($queue));
foreach ($queue as $item) {
    \_log('File: ' . $item['filename']);
    \_log('Size: ' . $item['size']);
    \_log('Start: ' . $seek_start);
    \_log('End:  ' . $seek_end);
    \_log('From Byte:  ' . $item['from_byte']);
    \_log('To Byte:  ' . $item['to_byte']);
    \_log('Filesize: ' . \filesize($item['filename']));
    \_log('Offset: ' . $offset);
    if ($offset + $item['from_byte'] + $seek_start <= $offset + \filesize($item['filename'])) {
        $is_first_file = !isset($fp);
        $fp = \fopen($item['filename'], 'rb');
        \fseek($fp, $item['from_byte'] + $seek_start);
        if ($is_first_file && $seek_start == 0) {
            $skipped = 0;
            while (($char = \fgetc($fp)) != 'G') {
                ++$skipped;
            }
            \header('Content-Length: ' . ($seek_end - $seek_start + 1 - $skipped));
            \header('Content-Range: bytes ' . $seek_start . '-' . ($seek_end - $skipped) . '/' . ($size - $skipped));
            \fseek($fp, \ftell($fp) - 1);
        }
        \_log('Seek: ' . ($item['from_byte'] + $seek_start));
    } else {
        $offset += \filesize($item['filename']);
        $seek_start -= \filesize($item['filename']) - $item['from_byte'];
        continue;
    }
    \set_time_limit(0);
    while (!\feof($fp)) {
        $buf_size = 1024 * 8;
        $pos = \ftell($fp);
        if ($pos >= $item['to_byte']) {
            \_log('File close: ' . $item['filename'] . ' on pos: ' . $pos);
            \fclose($fp);
            break;
        }
        if ($pos + $buf_size > $item['to_byte']) {
            $buf_size = $item['to_byte'] - $pos;
        }
        if ($buf_size > 0) {
            echo \fread($fp, $buf_size);
        }
        \flush();
        \ob_flush();
    }
    if (\is_resource($fp)) {
        \_log('Close file resource');
        \fclose($fp);
    }
    $offset += \filesize($item['filename']);
    $seek_start = 0;
}
