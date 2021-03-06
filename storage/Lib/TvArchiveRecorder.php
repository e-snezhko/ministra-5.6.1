<?php

namespace Ministra\Storage\Lib;

use DomainException;
use Exception;
use OutOfRangeException;
class TvArchiveRecorder extends \Ministra\Storage\Lib\Storage
{
    public function start($task)
    {
        $url = $task['cmd'];
        if (!\preg_match('/:\\/\\//', $url)) {
            throw new \Exception('URL wrong format');
        }
        $task['ch_id'] = (int) $task['ch_id'];
        $pid_file = $this->getRecPidFile($task['ch_id']);
        if (\file_exists($pid_file)) {
            if ($this->processExist($pid_file)) {
                return false;
            }
            \unlink($pid_file);
        }
        if (\strpos($url, 'rtp://') !== false || \strpos($url, 'udp://') !== false || \strpos($url, 'http://') !== false && \defined('ASTRA_RECORDER') && ASTRA_RECORDER) {
            $path = $this->getRecordsPath($task);
            if ($path && \is_dir($path)) {
                if (\defined('ASTRA_RECORDER') && ASTRA_RECORDER) {
                    \exec('astra ' . PROJECT_PATH . '/dumpstream.lua' . ' -A ' . $url . ' -d ' . $path . ' -n ' . (int) $task['parts_number'] . ' > /dev/null 2>&1 & echo $!', $out);
                } else {
                    if (!\preg_match('/:\\/\\/([\\d\\.]+):(\\d+)/', $url, $arr)) {
                        throw new \Exception('URL wrong format');
                    }
                    $ip = $arr[1];
                    $port = $arr[2];
                    \exec('nohup ' . getPythonInterpreterName() . ' ' . PROJECT_PATH . '/dumpstream' . ' -a' . $ip . ' -p' . $port . ' -d' . $path . ' -n' . (int) $task['parts_number'] . ' -b' . (\defined('DUMPSTREAM_BUFFERING') ? DUMPSTREAM_BUFFERING : 8) . ' > /dev/null 2>&1 & echo $!', $out);
                }
            } else {
                throw new \Exception('Wrong archive path or permission denied for new folder');
            }
        } else {
            throw new \DomainException('Not supported protocol');
        }
        if ((int) $out[0] == 0) {
            $arr = \explode(' ', $out[0]);
            $pid = (int) $arr[1];
        } else {
            $pid = (int) $out[0];
        }
        if (empty($pid)) {
            throw new \OutOfRangeException('Not possible to get pid');
        }
        if (!\file_put_contents($pid_file, $pid)) {
            \posix_kill($pid, 15);
            throw new \Ministra\Storage\Lib\IOException('PID file is not created');
        }
        $archive = new \Ministra\Storage\Lib\TvArchiveTasks();
        $archive->add($task);
        return true;
    }
    public function startAll($tasks)
    {
        if (!\is_array($tasks)) {
            return false;
        }
        foreach ($tasks as $task) {
            $this->start($task);
        }
        return true;
    }
    public function stop($ch_id)
    {
        $pid_file = $this->getRecPidFile($ch_id);
        if (!\is_file($pid_file)) {
            return true;
        }
        $pid = (int) \file_get_contents($pid_file);
        \unlink($pid_file);
        $archive = new \Ministra\Storage\Lib\TvArchiveTasks();
        $archive->del($ch_id);
        return \posix_kill($pid, 9);
    }
    private function getRecPidFile($ch_id)
    {
        return '/tmp/rec_archive_' . STORAGE_NAME . '_' . $ch_id . '.pid';
    }
    private function getRecordsPath($task)
    {
        $dir = \str_replace('//', '/', RECORDS_DIR . '/archive/' . $task['ch_id'] . '/');
        if (!\is_dir($dir)) {
            \umask(0);
            if (!\mkdir($dir, 0777, true)) {
                return false;
            }
        }
        return $dir;
    }
    private function processExist($pid_file)
    {
        $pid = \trim(\file_get_contents($pid_file));
        return \posix_kill($pid, 0);
    }
}
