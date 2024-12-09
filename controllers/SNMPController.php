<?php
require_once __DIR__ . '/../models/Device.php';

class SNMPController {
    private $db;
    private $device;
    private $defaultMetrics;

    public function __construct($db) {
        $this->db = $db;
        $this->device = new Device($db);
        
        // Define default metrics structure
        $this->defaultMetrics = [
            'system' => [
                'description' => 'N/A',
                'os' => 'N/A',
                'hostname' => 'N/A',
                'uptime' => 'N/A',
                'contact' => 'N/A',
                'location' => 'N/A',
                'services' => []
            ],
            'performance' => [
                'cpu' => 0,
                'memory' => 0,
                'disk' => 0,
                'network' => [
                    'in_traffic' => '0 bps',
                    'out_traffic' => '0 bps'
                ]
            ],
            'interfaces' => [],
            'connections' => []
        ];
    }

    public function getAllDevices() {
        try {
            return $this->device->read();
        } catch (Exception $e) {
            error_log("Error in SNMPController::getAllDevices: " . $e->getMessage());
            throw $e;
        }
    }

    public function addDevice($data) {
        try {
            // Validate required fields
            if (empty($data['ip_address']) || empty($data['community_string']) || empty($data['snmp_version'])) {
                return [
                    'success' => false,
                    'message' => 'Missing required fields: IP address, community string, and SNMP version are required'
                ];
            }

            // Validate IP address format
            if (!filter_var($data['ip_address'], FILTER_VALIDATE_IP)) {
                return [
                    'success' => false,
                    'message' => 'Invalid IP address format'
                ];
            }

            // Check if IP already exists
            if ($this->device->check_ip_exists($data['ip_address'])) {
                return [
                    'success' => false,
                    'message' => 'Device with this IP address already exists'
                ];
            }

            // Validate SNMP version
            $valid_versions = ['1', '2c', '3'];
            if (!in_array($data['snmp_version'], $valid_versions)) {
                return [
                    'success' => false,
                    'message' => 'Invalid SNMP version. Must be 1, 2c, or 3'
                ];
            }

            // Test SNMP connection before adding
            $snmp_test = $this->testSNMPConnection(
                $data['ip_address'],
                $data['community_string'],
                $data['snmp_version']
            );

            if (!$snmp_test['success']) {
                return [
                    'success' => false,
                    'message' => 'SNMP connection test failed: ' . $snmp_test['message']
                ];
            }

            // Set device properties
            $this->device->ip_address = $data['ip_address'];
            $this->device->community_string = $data['community_string'];
            $this->device->snmp_version = $data['snmp_version'];
            $this->device->description = $data['description'] ?? '';
            $this->device->status = 'active';

            // Create device
            if ($this->device->create()) {
                return [
                    'success' => true,
                    'message' => 'Device added successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to add device'
                ];
            }
        } catch (Exception $e) {
            error_log("Error in SNMPController::addDevice: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error adding device: ' . $e->getMessage()
            ];
        }
    }

    private function testSNMPConnection($ip, $community, $version) {
        try {
            if (!extension_loaded('snmp')) {
                throw new Exception('SNMP extension is not loaded');
            }

            // Set SNMP parameters
            snmp_set_quick_print(true);
            snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
            snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

            // Test OID - sysDescr
            $test_oid = '.1.3.6.1.2.1.1.1.0';

            // Set timeout and retries
            $timeout = 1000000; // 1 second
            $retries = 2;

            switch ($version) {
                case '1':
                    $session = @snmpget($ip, $community, $test_oid, $timeout, $retries);
                    break;
                case '2c':
                    $session = @snmp2_get($ip, $community, $test_oid, $timeout, $retries);
                    break;
                case '3':
                    // For SNMPv3, additional parameters would be needed
                    return [
                        'success' => false,
                        'message' => 'SNMPv3 not implemented yet'
                    ];
                default:
                    return [
                        'success' => false,
                        'message' => 'Invalid SNMP version'
                    ];
            }

            if ($session === false) {
                $error = error_get_last();
                throw new Exception($error['message'] ?? 'SNMP connection failed');
            }

            return [
                'success' => true,
                'message' => 'SNMP connection successful'
            ];
        } catch (Exception $e) {
            error_log("SNMP connection test failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function monitorDevice($device_id) {
        try {
            // Get device details
            $device = $this->device->read_single($device_id);
            if (!$device) {
                throw new Exception("Device not found");
            }

            // Check SNMP extension
            if (!extension_loaded('snmp')) {
                throw new Exception("SNMP extension is not loaded");
            }

            // Configure SNMP settings
            snmp_set_quick_print(true);
            snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
            snmp_set_oid_output_format(SNMP_OID_OUTPUT_NUMERIC);

            // Initialize response with default structure
            $response = [
                'success' => true,
                'data' => $this->defaultMetrics
            ];

            try {
                $response['data']['system'] = $this->getSystemInfo($device);
            } catch (Exception $e) {
                error_log("Error getting system info: " . $e->getMessage());
            }

            try {
                $response['data']['performance'] = $this->getPerformanceMetrics($device);
            } catch (Exception $e) {
                error_log("Error getting performance metrics: " . $e->getMessage());
            }

            try {
                $response['data']['interfaces'] = $this->getInterfaceInfo($device);
                
                // Calculate total traffic for performance metrics
                $totalInTraffic = 0;
                $totalOutTraffic = 0;
                foreach ($response['data']['interfaces'] as $interface) {
                    $totalInTraffic += $interface['statistics']['in_traffic_rate'] ?? 0;
                    $totalOutTraffic += $interface['statistics']['out_traffic_rate'] ?? 0;
                }

                $response['data']['performance']['network'] = [
                    'in_traffic' => $this->formatTraffic($totalInTraffic),
                    'out_traffic' => $this->formatTraffic($totalOutTraffic)
                ];
            } catch (Exception $e) {
                error_log("Error getting interface info: " . $e->getMessage());
            }

            try {
                $response['data']['connections'] = $this->getNetworkConnections($device);
            } catch (Exception $e) {
                error_log("Error getting network connections: " . $e->getMessage());
            }

            // Update device status
            $this->device->update_status($device_id, 'active');

            return $response;

        } catch (Exception $e) {
            error_log("Error in SNMPController::monitorDevice: " . $e->getMessage());
            $this->device->update_status($device_id, 'error');
            
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => $this->defaultMetrics
            ];
        }
    }

    private function getSystemInfo($device) {
        try {
            $sysDescr = $this->snmpGet($device, '.1.3.6.1.2.1.1.1.0');
            $sysUpTime = $this->snmpGet($device, '.1.3.6.1.2.1.1.3.0');
            $sysContact = $this->snmpGet($device, '.1.3.6.1.2.1.1.4.0');
            $sysName = $this->snmpGet($device, '.1.3.6.1.2.1.1.5.0');
            $sysLocation = $this->snmpGet($device, '.1.3.6.1.2.1.1.6.0');

            // Extract OS information from system description
            $os = 'Unknown';
            if ($sysDescr) {
                if (stripos($sysDescr, 'windows') !== false) {
                    $os = 'Windows';
                } elseif (stripos($sysDescr, 'linux') !== false) {
                    $os = 'Linux';
                } elseif (stripos($sysDescr, 'darwin') !== false) {
                    $os = 'macOS';
                }
            }

            // Format uptime
            $uptime = 'N/A';
            if ($sysUpTime) {
                $ticks = intval($sysUpTime) / 100; // Convert to seconds
                $days = floor($ticks / 86400);
                $hours = floor(($ticks % 86400) / 3600);
                $minutes = floor(($ticks % 3600) / 60);
                $uptime = "{$days}d {$hours}h {$minutes}m";
            }

            return [
                'description' => $sysDescr ?: 'N/A',
                'os' => $os,
                'hostname' => $sysName ?: 'N/A',
                'uptime' => $uptime,
                'contact' => $sysContact ?: 'N/A',
                'location' => $sysLocation ?: 'N/A',
                'services' => []
            ];
        } catch (Exception $e) {
            error_log("Error getting system info: " . $e->getMessage());
            return $this->defaultMetrics['system'];
        }
    }

    private function getPerformanceMetrics($device) {
        try {
            // Get CPU usage
            $cpu = 0;
            $cpuOids = [
                '.1.3.6.1.2.1.25.3.3.1.2.1',  // HOST-RESOURCES-MIB::hrProcessorLoad
                '.1.3.6.1.4.1.2021.11.9.0',   // UCD-SNMP-MIB::ssCpuUser
                '.1.3.6.1.4.1.2021.11.10.0'   // UCD-SNMP-MIB::ssCpuSystem
            ];
            foreach ($cpuOids as $oid) {
                $value = $this->snmpGet($device, $oid);
                if ($value !== false) {
                    $cpu = intval($value);
                    break;
                }
            }

            // Get memory usage
            $memTotal = $this->snmpGet($device, '.1.3.6.1.2.1.25.2.2.0'); // hrMemorySize
            $memUsed = $this->snmpGet($device, '.1.3.6.1.4.1.2021.4.6.0');  // memTotalReal
            $memory = 0;
            if ($memTotal && $memUsed) {
                $memory = round(($memUsed / $memTotal) * 100);
            }

            // Get disk usage
            $disk = 0;
            $storage = $this->snmpWalk($device, '.1.3.6.1.2.1.25.2.3.1.5'); // hrStorageSize
            $used = $this->snmpWalk($device, '.1.3.6.1.2.1.25.2.3.1.6');    // hrStorageUsed
            if ($storage && $used) {
                $totalSize = array_sum(array_map('intval', $storage));
                $totalUsed = array_sum(array_map('intval', $used));
                if ($totalSize > 0) {
                    $disk = round(($totalUsed / $totalSize) * 100);
                }
            }

            return [
                'cpu' => $cpu,
                'memory' => $memory,
                'disk' => $disk,
                'network' => [
                    'in_traffic' => '0 bps',
                    'out_traffic' => '0 bps'
                ]
            ];
        } catch (Exception $e) {
            error_log("Error getting performance metrics: " . $e->getMessage());
            return $this->defaultMetrics['performance'];
        }
    }

    private function getInterfaceInfo($device) {
        try {
            $interfaces = [];
            $ifIndexes = $this->snmpWalk($device, '.1.3.6.1.2.1.2.2.1.1');

            if (!$ifIndexes) {
                throw new Exception("No interfaces found");
            }

            foreach ($ifIndexes as $ifIndex) {
                $interface = [
                    'name' => $this->snmpGet($device, ".1.3.6.1.2.1.2.2.1.2.$ifIndex") ?: 'Unknown',
                    'type' => $this->getInterfaceType($this->snmpGet($device, ".1.3.6.1.2.1.2.2.1.3.$ifIndex")),
                    'status' => $this->getInterfaceStatus($this->snmpGet($device, ".1.3.6.1.2.1.2.2.1.8.$ifIndex")),
                    'admin_status' => $this->getInterfaceStatus($this->snmpGet($device, ".1.3.6.1.2.1.2.2.1.7.$ifIndex")),
                    'speed' => $this->formatSpeed($this->snmpGet($device, ".1.3.6.1.2.1.2.2.1.5.$ifIndex")),
                    'mac_address' => $this->formatMac($this->snmpGet($device, ".1.3.6.1.2.1.2.2.1.6.$ifIndex")),
                    'mtu' => $this->snmpGet($device, ".1.3.6.1.2.1.2.2.1.4.$ifIndex") ?: 'N/A',
                    'statistics' => [
                        'in_octets' => $this->snmpGet($device, ".1.3.6.1.2.1.2.2.1.10.$ifIndex") ?: 0,
                        'out_octets' => $this->snmpGet($device, ".1.3.6.1.2.1.2.2.1.16.$ifIndex") ?: 0,
                        'in_errors' => $this->snmpGet($device, ".1.3.6.1.2.1.2.2.1.14.$ifIndex") ?: 0,
                        'out_errors' => $this->snmpGet($device, ".1.3.6.1.2.1.2.2.1.20.$ifIndex") ?: 0,
                        'in_discards' => $this->snmpGet($device, ".1.3.6.1.2.1.2.2.1.13.$ifIndex") ?: 0,
                        'out_discards' => $this->snmpGet($device, ".1.3.6.1.2.1.2.2.1.19.$ifIndex") ?: 0,
                        'in_traffic' => '0 bps',
                        'out_traffic' => '0 bps',
                        'in_traffic_rate' => 0,
                        'out_traffic_rate' => 0
                    ]
                ];

                // Calculate traffic rates
                if ($interface['statistics']['in_octets'] && $interface['statistics']['out_octets']) {
                    $sessionKey = "interface_{$device['id']}_{$ifIndex}";
                    $prevValues = isset($_SESSION[$sessionKey]) ? $_SESSION[$sessionKey] : null;
                    $currentTime = time();

                    if ($prevValues) {
                        $timeDiff = $currentTime - $prevValues['time'];
                        if ($timeDiff > 0) {
                            $inRate = (($interface['statistics']['in_octets'] - $prevValues['in_octets']) * 8) / $timeDiff;
                            $outRate = (($interface['statistics']['out_octets'] - $prevValues['out_octets']) * 8) / $timeDiff;

                            $interface['statistics']['in_traffic_rate'] = $inRate;
                            $interface['statistics']['out_traffic_rate'] = $outRate;
                            $interface['statistics']['in_traffic'] = $this->formatTraffic($inRate);
                            $interface['statistics']['out_traffic'] = $this->formatTraffic($outRate);
                        }
                    }

                    $_SESSION[$sessionKey] = [
                        'time' => $currentTime,
                        'in_octets' => $interface['statistics']['in_octets'],
                        'out_octets' => $interface['statistics']['out_octets']
                    ];
                }

                $interfaces[] = $interface;
            }

            return $interfaces;
        } catch (Exception $e) {
            error_log("Error getting interface info: " . $e->getMessage());
            return [[
                'name' => 'N/A',
                'type' => 'unknown',
                'status' => 'unknown',
                'admin_status' => 'unknown',
                'speed' => 'N/A',
                'mac_address' => 'N/A',
                'mtu' => 'N/A',
                'statistics' => [
                    'in_octets' => 0,
                    'out_octets' => 0,
                    'in_errors' => 0,
                    'out_errors' => 0,
                    'in_discards' => 0,
                    'out_discards' => 0,
                    'in_traffic' => '0 bps',
                    'out_traffic' => '0 bps',
                    'in_traffic_rate' => 0,
                    'out_traffic_rate' => 0
                ]
            ]];
        }
    }

    private function getNetworkConnections($device) {
        try {
            $connections = [];
            
            // TCP connections
            $tcpStates = $this->snmpWalk($device, '.1.3.6.1.2.1.6.13.1.1');
            $tcpLocalAddrs = $this->snmpWalk($device, '.1.3.6.1.2.1.6.13.1.2');
            $tcpLocalPorts = $this->snmpWalk($device, '.1.3.6.1.2.1.6.13.1.3');
            $tcpRemAddrs = $this->snmpWalk($device, '.1.3.6.1.2.1.6.13.1.4');
            $tcpRemPorts = $this->snmpWalk($device, '.1.3.6.1.2.1.6.13.1.5');

            if ($tcpStates && $tcpLocalAddrs && $tcpLocalPorts && $tcpRemAddrs && $tcpRemPorts) {
                foreach ($tcpStates as $index => $state) {
                    $connections[] = [
                        'protocol' => 'TCP',
                        'localAddress' => $tcpLocalAddrs[$index] ?? '',
                        'localPort' => $tcpLocalPorts[$index] ?? '',
                        'remoteAddress' => $tcpRemAddrs[$index] ?? '',
                        'remotePort' => $tcpRemPorts[$index] ?? '',
                        'state' => $this->getTcpState(intval($state))
                    ];
                }
            }

            if (empty($connections)) {
                throw new Exception("No network connections available");
            }

            return $connections;
        } catch (Exception $e) {
            error_log("Error getting network connections: " . $e->getMessage());
            throw new Exception("Failed to get network connections: " . $e->getMessage());
        }
    }

    private function snmpGet($device, $oid) {
        try {
            $value = false;
            switch ($device['snmp_version']) {
                case '1':
                    $value = @snmpget($device['ip_address'], $device['community_string'], $oid, 1000000, 2);
                    break;
                case '2c':
                    $value = @snmp2_get($device['ip_address'], $device['community_string'], $oid, 1000000, 2);
                    break;
            }
            return $value;
        } catch (Exception $e) {
            error_log("SNMP GET error for OID $oid: " . $e->getMessage());
            return false;
        }
    }

    private function snmpWalk($device, $oid) {
        try {
            $values = false;
            switch ($device['snmp_version']) {
                case '1':
                    $values = @snmpwalk($device['ip_address'], $device['community_string'], $oid, 1000000, 2);
                    break;
                case '2c':
                    $values = @snmp2_walk($device['ip_address'], $device['community_string'], $oid, 1000000, 2);
                    break;
            }
            return $values;
        } catch (Exception $e) {
            error_log("SNMP WALK error for OID $oid: " . $e->getMessage());
            return false;
        }
    }

    private function getTcpState($state) {
        $states = [
            1 => 'CLOSED',
            2 => 'LISTEN',
            3 => 'SYN_SENT',
            4 => 'SYN_RECEIVED',
            5 => 'ESTABLISHED',
            6 => 'FIN_WAIT_1',
            7 => 'FIN_WAIT_2',
            8 => 'CLOSE_WAIT',
            9 => 'CLOSING',
            10 => 'LAST_ACK',
            11 => 'TIME_WAIT',
            12 => 'DELETE_TCB'
        ];
        return $states[$state] ?? 'UNKNOWN';
    }

    public function deleteDevice($id) {
        try {
            if ($this->device->delete($id)) {
                return ['success' => true, 'message' => 'Device deleted successfully'];
            }
            return ['success' => false, 'message' => 'Error deleting device'];
        } catch (Exception $e) {
            error_log("Error deleting device: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    public function updateDevice($id, $data) {
        try {
            // Validate required fields
            if (empty($data['ip_address']) || empty($data['community_string']) || empty($data['snmp_version'])) {
                return [
                    'success' => false,
                    'message' => 'Missing required fields: IP address, community string, and SNMP version are required'
                ];
            }

            // Validate IP address format
            if (!filter_var($data['ip_address'], FILTER_VALIDATE_IP)) {
                return [
                    'success' => false,
                    'message' => 'Invalid IP address format'
                ];
            }

            // Check if IP already exists (excluding current device)
            if ($this->device->check_ip_exists($data['ip_address'], $id)) {
                return [
                    'success' => false,
                    'message' => 'Device with this IP address already exists'
                ];
            }

            // Validate SNMP version
            $valid_versions = ['1', '2c', '3'];
            if (!in_array($data['snmp_version'], $valid_versions)) {
                return [
                    'success' => false,
                    'message' => 'Invalid SNMP version. Must be 1, 2c, or 3'
                ];
            }

            // Test SNMP connection before updating
            $snmp_test = $this->testSNMPConnection(
                $data['ip_address'],
                $data['community_string'],
                $data['snmp_version']
            );

            if (!$snmp_test['success']) {
                return [
                    'success' => false,
                    'message' => 'SNMP connection test failed: ' . $snmp_test['message']
                ];
            }

            // Update device
            $query = "UPDATE devices SET 
                        ip_address = :ip_address,
                        community_string = :community_string,
                        snmp_version = :snmp_version,
                        description = :description,
                        updated_at = CURRENT_TIMESTAMP
                     WHERE id = :id";

            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . print_r($this->db->errorInfo(), true));
            }

            // Bind parameters
            $stmt->bindParam(":ip_address", $data['ip_address']);
            $stmt->bindParam(":community_string", $data['community_string']);
            $stmt->bindParam(":snmp_version", $data['snmp_version']);
            $stmt->bindParam(":description", $data['description']);
            $stmt->bindParam(":id", $id);

            if (!$stmt->execute()) {
                throw new Exception("Error executing statement: " . print_r($stmt->errorInfo(), true));
            }

            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Device not found or no changes made'
                ];
            }

            return [
                'success' => true,
                'message' => 'Device updated successfully'
            ];

        } catch (Exception $e) {
            error_log("Error in SNMPController::updateDevice: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error updating device: ' . $e->getMessage()
            ];
        }
    }

    private function getSnmpVersion($version) {
        switch ($version) {
            case '1':
                return SNMP::VERSION_1;
            case '2c':
                return SNMP::VERSION_2c;
            case '3':
                return SNMP::VERSION_3;
            default:
                return SNMP::VERSION_2c;
        }
    }

    private function getInterfaceStatus($status) {
        if ($status === false) return 'unknown';
        $states = [
            1 => 'up',
            2 => 'down',
            3 => 'testing',
            4 => 'unknown',
            5 => 'dormant',
            6 => 'notPresent',
            7 => 'lowerLayerDown'
        ];
        return $states[intval($status)] ?? 'unknown';
    }

    private function getInterfaceType($type) {
        if ($type === false) return 'unknown';
        $types = [
            1 => 'other',
            2 => 'regular1822',
            3 => 'hdh1822',
            4 => 'ddn-x25',
            5 => 'rfc877-x25',
            6 => 'ethernet-csmacd',
            7 => 'iso88023-csmacd',
            8 => 'iso88024-tokenBus',
            9 => 'iso88025-tokenRing',
            10 => 'iso88026-man',
            11 => 'starLan',
            12 => 'proteon-10Mbit',
            13 => 'proteon-80Mbit',
            14 => 'hyperchannel',
            15 => 'fddi',
            16 => 'lapb',
            17 => 'sdlc',
            18 => 'ds1',
            19 => 'e1',
            20 => 'basicISDN',
            21 => 'primaryISDN',
            22 => 'propPointToPointSerial',
            23 => 'ppp',
            24 => 'softwareLoopback',
            25 => 'eon',
            26 => 'ethernet-3Mbit',
            27 => 'nsip',
            28 => 'slip',
            29 => 'ultra',
            30 => 'ds3',
            31 => 'sip',
            32 => 'frame-relay',
            33 => 'rs232',
            34 => 'para',
            35 => 'arcnet',
            36 => 'arcnetPlus',
            37 => 'atm',
            38 => 'miox25',
            39 => 'sonet',
            40 => 'x25ple',
            41 => 'iso88022llc',
            42 => 'localTalk',
            43 => 'smdsDxi',
            44 => 'frameRelayService',
            45 => 'v35',
            46 => 'hssi',
            47 => 'hippi',
            48 => 'modem',
            49 => 'aal5',
            50 => 'sonetPath',
            51 => 'sonetVT',
            52 => 'smdsIcip',
            53 => 'propVirtual',
            54 => 'propMultiplexor',
            55 => 'ieee80212',
            56 => 'fibreChannel',
            57 => 'hippiInterface',
            58 => 'frameRelayInterconnect',
            59 => 'aflane8023',
            60 => 'aflane8025',
            61 => 'cctEmul',
            62 => 'fastEther',
            63 => 'isdn',
            64 => 'v11',
            65 => 'v36',
            66 => 'g703at64k',
            67 => 'g703at2mb',
            68 => 'qllc',
            69 => 'fastEtherFX',
            70 => 'channel',
            71 => 'ieee80211',
            72 => 'ibm370parChan',
            73 => 'escon',
            74 => 'dlsw',
            75 => 'isdns',
            76 => 'isdnu',
            77 => 'lapd',
            78 => 'ipSwitch',
            79 => 'rsrb',
            80 => 'atmLogical',
            81 => 'ds0',
            82 => 'ds0Bundle',
            83 => 'bsc',
            84 => 'async',
            85 => 'cnr',
            86 => 'iso88025Dtr',
            87 => 'eplrs',
            88 => 'arap',
            89 => 'propCnls',
            90 => 'hostPad',
            91 => 'termPad',
            92 => 'frameRelayMPI',
            93 => 'x213',
            94 => 'adsl',
            95 => 'radsl',
            96 => 'sdsl',
            97 => 'vdsl',
            98 => 'iso88025CRFPInt',
            99 => 'myrinet',
            100 => 'voiceEM',
            101 => 'voiceFXO',
            102 => 'voiceFXS',
            103 => 'voiceEncap',
            104 => 'voiceOverIp',
            105 => 'atmDxi',
            106 => 'atmFuni',
            107 => 'atmIma',
            108 => 'pppMultilinkBundle',
            109 => 'ipOverCdlc',
            110 => 'ipOverClaw',
            111 => 'stackToStack',
            112 => 'virtualIpAddress',
            113 => 'mpc',
            114 => 'ipOverAtm',
            115 => 'iso88025Fiber',
            116 => 'tdlc',
            117 => 'gigabitEthernet',
            118 => 'hdlc',
            119 => 'lapf',
            120 => 'v37',
            121 => 'x25mlp',
            122 => 'x25huntGroup',
            123 => 'transpHdlc',
            124 => 'interleave',
            125 => 'fast',
            126 => 'ip',
            127 => 'docsCableMaclayer',
            128 => 'docsCableDownstream',
            129 => 'docsCableUpstream',
            130 => 'a12MppSwitch',
            131 => 'tunnel',
            132 => 'coffee',
            133 => 'ces',
            134 => 'atmSubInterface',
            135 => 'l2vlan',
            136 => 'l3ipvlan',
            137 => 'l3ipxvlan',
            138 => 'digitalPowerline',
            139 => 'mediaMailOverIp',
            140 => 'dtm',
            141 => 'dcn',
            142 => 'ipForward',
            143 => 'msdsl',
            144 => 'ieee1394',
            145 => 'if-gsn',
            146 => 'dvbRccMacLayer',
            147 => 'dvbRccDownstream',
            148 => 'dvbRccUpstream',
            149 => 'atmVirtual',
            150 => 'mplsTunnel',
            151 => 'srp',
            152 => 'voiceOverAtm',
            153 => 'voiceOverFrameRelay',
            154 => 'idsl',
            155 => 'compositeLink',
            156 => 'ss7SigLink',
            157 => 'propWirelessP2P',
            158 => 'frForward',
            159 => 'rfc1483',
            160 => 'usb',
            161 => 'ieee8023adLag',
            162 => 'bgppolicyaccounting',
            163 => 'frf16MfrBundle',
            164 => 'h323Gatekeeper',
            165 => 'h323Proxy',
            166 => 'mpls',
            167 => 'mfSigLink',
            168 => 'hdsl2',
            169 => 'shdsl',
            170 => 'ds1FDL',
            171 => 'pos',
            172 => 'dvbAsiIn',
            173 => 'dvbAsiOut',
            174 => 'plc',
            175 => 'nfas',
            176 => 'tr008',
            177 => 'gr303RDT',
            178 => 'gr303IDT',
            179 => 'isup',
            180 => 'propDocsWirelessMaclayer',
            181 => 'propDocsWirelessDownstream',
            182 => 'propDocsWirelessUpstream',
            183 => 'hiperlan2',
            184 => 'propBWAp2Mp',
            185 => 'sonetOverheadChannel',
            186 => 'digitalWrapperOverheadChannel',
            187 => 'aal2',
            188 => 'radioMAC',
            189 => 'atmRadio',
            190 => 'imt',
            191 => 'mvl',
            192 => 'reachDSL',
            193 => 'frDlciEndPt',
            194 => 'atmVciEndPt',
            195 => 'opticalChannel',
            196 => 'opticalTransport',
            197 => 'propAtm',
            198 => 'voiceOverCable',
            199 => 'infiniband',
            200 => 'teLink',
            201 => 'q2931',
            202 => 'virtualTg',
            203 => 'sipTg',
            204 => 'sipSig',
            205 => 'docsCableUpstreamChannel',
            206 => 'econet',
            207 => 'pon155',
            208 => 'pon622',
            209 => 'bridge',
            210 => 'linegroup',
            211 => 'voiceEMFGD',
            212 => 'voiceFGDEANA',
            213 => 'voiceDID',
            214 => 'mpegTransport',
            215 => 'sixToFour',
            216 => 'gtp',
            217 => 'pdnEtherLoop1',
            218 => 'pdnEtherLoop2',
            219 => 'opticalChannelGroup',
            220 => 'homepna',
            221 => 'gfp',
            222 => 'ciscoISLvlan',
            223 => 'actelisMetaLOOP',
            224 => 'fcipLink',
            225 => 'rpr',
            226 => 'qam',
            227 => 'lmp',
            228 => 'cblVectaStar',
            229 => 'docsCableMCmtsDownstream',
            230 => 'adsl2',
            231 => 'macSecControlledIF',
            232 => 'macSecUncontrolledIF',
            233 => 'aviciOpticalEther',
            234 => 'atmbond'
        ];
        return $types[intval($type)] ?? 'other';
    }

    private function getInterfaceIpAddress($ip, $community, $ifIndex) {
        // Try to get IP address from IP-MIB
        $ipAddresses = @snmpwalk($ip, $community, 'IP-MIB::ipAdEntIfIndex');
        $ipAddrTable = @snmpwalk($ip, $community, 'IP-MIB::ipAdEntAddr');
        
        if ($ipAddresses !== false && $ipAddrTable !== false) {
            foreach ($ipAddresses as $key => $value) {
                if ((int)$value === (int)$ifIndex && isset($ipAddrTable[$key])) {
                    return (string)$ipAddrTable[$key];
                }
            }
        }
        
        return 'N/A';
    }

    private function formatIpAddress($addr) {
        // Handle hex format
        if (strpos($addr, 'x') !== false) {
            $addr = str_replace(['0x', ' '], '', $addr);
            $addr = trim($addr);
            if (strlen($addr) == 8) {
                $parts = str_split($addr, 2);
                $addr = implode('.', array_map('hexdec', $parts));
            }
        }
        // Handle standard format
        else {
            $addr = str_replace(['STRING: "', '"'], '', $addr);
            $addr = trim($addr);
        }
        return $addr === '0.0.0.0' ? '*' : $addr;
    }

    private function formatTraffic($bps) {
        $units = ['bps', 'Kbps', 'Mbps', 'Gbps', 'Tbps'];
        $unitIndex = 0;
        while ($bps >= 1000 && $unitIndex < count($units) - 1) {
            $bps /= 1000;
            $unitIndex++;
        }
        return round($bps, 2) . ' ' . $units[$unitIndex];
    }

    private function formatSpeed($speed) {
        if ($speed === false || $speed == 0) return 'N/A';
        return $this->formatTraffic($speed);
    }

    private function formatMac($mac) {
        if ($mac === false || empty($mac)) return 'N/A';
        $mac = bin2hex($mac);
        return implode(':', str_split($mac, 2));
    }

    public function getDevice($id) {
        try {
            $query = "SELECT * FROM devices WHERE id = :id";
            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . print_r($this->db->errorInfo(), true));
            }

            $stmt->bindParam(":id", $id);

            if (!$stmt->execute()) {
                throw new Exception("Error executing statement: " . print_r($stmt->errorInfo(), true));
            }

            $device = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$device) {
                return null;
            }

            // Don't send sensitive data like community string
            $device['community_string'] = '';
            
            return $device;
        } catch (Exception $e) {
            error_log("Error in SNMPController::getDevice: " . $e->getMessage());
            return null;
        }
    }

    public function getAlerts() {
        try {
            $query = "SELECT a.*, d.ip_address as device_name 
                     FROM alerts a 
                     LEFT JOIN devices d ON a.device_id = d.id 
                     ORDER BY a.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . print_r($this->db->errorInfo(), true));
            }

            if (!$stmt->execute()) {
                throw new Exception("Error executing statement: " . print_r($stmt->errorInfo(), true));
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error in SNMPController::getAlerts: " . $e->getMessage());
            return [];
        }
    }

    public function addAlert($data) {
        try {
            // Validate required fields
            if (empty($data['device_id']) || empty($data['metric']) || 
                empty($data['condition']) || !isset($data['threshold']) || 
                empty($data['severity'])) {
                return [
                    'success' => false,
                    'message' => 'Missing required fields'
                ];
            }

            // Validate device exists
            $device = $this->getDevice($data['device_id']);
            if (!$device) {
                return [
                    'success' => false,
                    'message' => 'Device not found'
                ];
            }

            // Validate metric
            $valid_metrics = ['cpu_usage', 'memory_usage', 'disk_usage', 'interface_status', 'response_time'];
            if (!in_array($data['metric'], $valid_metrics)) {
                return [
                    'success' => false,
                    'message' => 'Invalid metric'
                ];
            }

            // Validate condition
            $valid_conditions = ['>', '>=', '<', '<=', '==', '!='];
            if (!in_array($data['condition'], $valid_conditions)) {
                return [
                    'success' => false,
                    'message' => 'Invalid condition'
                ];
            }

            // Validate severity
            $valid_severities = ['info', 'warning', 'critical'];
            if (!in_array($data['severity'], $valid_severities)) {
                return [
                    'success' => false,
                    'message' => 'Invalid severity'
                ];
            }

            // Insert alert
            $query = "INSERT INTO alerts (
                        device_id, metric, `condition`, threshold, 
                        severity, status, created_at, updated_at
                    ) VALUES (
                        :device_id, :metric, :condition, :threshold,
                        :severity, 'active', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
                    )";

            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . print_r($this->db->errorInfo(), true));
            }

            // Bind parameters
            $stmt->bindParam(":device_id", $data['device_id']);
            $stmt->bindParam(":metric", $data['metric']);
            $stmt->bindParam(":condition", $data['condition']);
            $stmt->bindParam(":threshold", $data['threshold']);
            $stmt->bindParam(":severity", $data['severity']);

            if (!$stmt->execute()) {
                throw new Exception("Error executing statement: " . print_r($stmt->errorInfo(), true));
            }

            return [
                'success' => true,
                'message' => 'Alert rule added successfully'
            ];

        } catch (Exception $e) {
            error_log("Error in SNMPController::addAlert: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error adding alert rule: ' . $e->getMessage()
            ];
        }
    }

    public function deleteAlert($id) {
        try {
            $query = "DELETE FROM alerts WHERE id = :id";
            $stmt = $this->db->prepare($query);
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . print_r($this->db->errorInfo(), true));
            }

            $stmt->bindParam(":id", $id);

            if (!$stmt->execute()) {
                throw new Exception("Error executing statement: " . print_r($stmt->errorInfo(), true));
            }

            if ($stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Alert rule not found'
                ];
            }

            return [
                'success' => true,
                'message' => 'Alert rule deleted successfully'
            ];

        } catch (Exception $e) {
            error_log("Error in SNMPController::deleteAlert: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error deleting alert rule: ' . $e->getMessage()
            ];
        }
    }
} 