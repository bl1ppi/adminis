<?php
require_once '../vendor/autoload.php';
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

function collectServerStats(array $server): array {
    $ssh = new SSH2($server['ip']);
    $key = file_get_contents('/etc/monitoring/monitor_id_rsa');
    $priv = PublicKeyLoader::load($key);
    if (!$ssh->login($server['user'], $priv)) {
        return ['status' => 'offline'];
    }

    $stats = ['status' => 'online'];

    // CPU: используем top -bn1 для однократного снимка нагрузки
    $cpuOutput = $ssh->exec("top -bn1 | grep 'Cpu(s)'");
    if (preg_match('/(\d+\.\d+)\s*id/', $cpuOutput, $matches)) {
        $cpuUsage = 100 - (float)$matches[1];
        $stats['cpu'] = [['t' => time(), 'v' => $cpuUsage]];
    } else {
        $stats['cpu'] = [['t' => time(), 'v' => null]];
    }

    // RAM: free -m
    $memOutput = $ssh->exec("free -m | grep Mem:");
    $memParts = preg_split('/\s+/', trim($memOutput));
    if (count($memParts) >= 3) {
        $stats['memory'] = [
            'used' => (int)$memParts[2],
            'total' => (int)$memParts[1],
        ];
    }

    // Диски: df -BG с фильтрацией только нужных путей
    $dfOutput = $ssh->exec("df -BG --output=source,size,used,avail,target -x tmpfs -x devtmpfs | tail -n +2");
    $diskStats = [];
    foreach (explode("\n", trim($dfOutput)) as $line) {
        if (!preg_match('#^/dev/sd[a-z]\d*#', $line)) continue;
        $parts = preg_split('/\s+/', $line);
        if (count($parts) === 5) {
            [$dev, $size, $used, $avail, $mount] = $parts;
            $diskStats[] = [
                'device' => $dev,
                'mount' => $mount,
                'used' => (int)filter_var($used, FILTER_SANITIZE_NUMBER_INT),
                'size' => (int)filter_var($size, FILTER_SANITIZE_NUMBER_INT),
            ];
        }
    }
    $stats['disks'] = $diskStats;

    // Службы
    $services = json_decode($server['services'], true) ?? [];
    $serviceStats = [];
    foreach ($services as $svc) {
        $status = trim($ssh->exec("systemctl is-active " . escapeshellarg($svc)));
        $serviceStats[] = ['name' => $svc, 'status' => $status];
    }
    $stats['services'] = $serviceStats;

    return $stats;
}
