<?php
$ROLES = [
    0 => "CLIENT",
    1 => "CLIENT_MUTE",
    2 => "ROUTER",
    3 => "ROUTER_CLIENT",
    4 => "REPEATER",
    5 => "TRACKER",
    6 => "SENSOR",
    7 => "TAK",
    8 => "CLIENT_HIDDEN",
    9 => "LOST_AND_FOUND",
    10 => "TAK_TRACKER"
];

$HARDWAREMODEL = [
    0 => "UNSET",
    1 => "TLORA_V2",
    2 => "TLORA_V1",
    3 => "TLORA_V2_1_1P6",
    4 => "TBEAM",
    5 => "HELTEC_V2_0",
    6 => "TBEAM_V0P7",
    7 => "T_ECHO",
    8 => "TLORA_V1_1P3",
    9 => "RAK4631",
    10 => "HELTEC_V2_1",
    11 => "HELTEC_V1",
    12 => "LILYGO_TBEAM_S3_CORE",
    13 => "RAK11200",
    14 => "NANO_G1",
    15 => "TLORA_V2_1_1P8",
    16 => "TLORA_T3_S3",
    17 => "NANO_G1_EXPLORER",
    18 => "NANO_G2_ULTRA",
    19 => "LORA_TYPE",
    20 => "WIPHONE",
    21 => "WIO_WM1110",
    22 => "RAK2560",
    23 => "HELTEC_HRU_3601",
    25 => "STATION_G1",
    26 => "RAK11310",
    27 => "SENSELORA_RP2040",
    28 => "SENSELORA_S3",
    29 => "CANARYONE",
    30 => "RP2040_LORA",
    31 => "STATION_G2",
    32 => "LORA_RELAY_V1",
    33 => "NRF52840DK",
    34 => "PPR",
    35 => "GENIEBLOCKS",
    36 => "NRF52_UNKNOWN",
    37 => "PORTDUINO",
    38 => "ANDROID_SIM",
    39 => "DIY_V1",
    40 => "NRF52840_PCA10059",
    41 => "DR_DEV",
    42 => "M5STACK",
    43 => "HELTEC_V3",
    44 => "HELTEC_WSL_V3",
    45 => "BETAFPV_2400_TX",
    46 => "BETAFPV_900_NANO_TX",
    47 => "RPI_PICO",
    48 => "HELTEC_WIRELESS_TRACKER",
    49 => "HELTEC_WIRELESS_PAPER",
    50 => "T_DECK",
    51 => "T_WATCH_S3",
    52 => "PICOMPUTER_S3",
    53 => "HELTEC_HT62",
    54 => "EBYTE_ESP32_S3",
    55 => "ESP32_S3_PICO",
    56 => "CHATTER_2",
    57 => "HELTEC_WIRELESS_PAPER_V1_0",
    58 => "HELTEC_WIRELESS_TRACKER_V1_0",
    59 => "UNPHONE",
    60 => "TD_LORAC",
    61 => "CDEBYTE_EORA_S3",
    62 => "TWC_MESH_V4",
    63 => "NRF52_PROMICRO_DIY",
    64 => "RADIOMASTER_900_BANDIT_NANO",
    65 => "HELTEC_CAPSULE_SENSOR_V3",
    66 => "HELTEC_VISION_MASTER_T190",
    67 => "HELTEC_VISION_MASTER_E213",
    68 => "HELTEC_VISION_MASTER_E290",
    69 => "HELTEC_MESH_NODE_T114"
];

function neighbours() {
    global $ROLES, $HARDWAREMODEL;

    $mysqli = new mysqli("127.0.0.1", "user", "password", "database");

    $result = $mysqli->query("SELECT node_id, long_name, short_name, neighbours, UNIX_TIMESTAMP(updated_at) AS updated_at, UNIX_TIMESTAMP(neighbours_updated_at) AS neighbours_updated_at, role, hardware_model, battery_level, voltage, air_util_tx, channel_utilization, temperature, relative_humidity, barometric_pressure FROM nodes");
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[$row["node_id"]] = [
            "long_name" => $row["long_name"],
            "short_name" => $row["short_name"],
            "updated_at" => $row["updated_at"] !== null ? (int) $row["updated_at"] : null,
            "neighbours" => json_decode($row["neighbours"], true),
            "neighbours_updated_at" => $row["neighbours_updated_at"] !== null ? (int) $row["neighbours_updated_at"] : null,
            "role" => $ROLES[$row["role"]],
            "hardware_model" => $HARDWAREMODEL[$row["hardware_model"]],
            "battery_level" => $row["battery_level"] !== null ? (int) $row["battery_level"] : null,
            "voltage" => $row["voltage"] !== null ? (float) $row["voltage"] : null,
            "air_util_tx" => $row["air_util_tx"] !== null ? (float) $row["air_util_tx"] : null,
            "channel_utilization" => $row["channel_utilization"] !== null ? (float) $row["channel_utilization"] : null,
            "temperature" => $row["temperature"] !== null ? (float) $row["temperature"] : null,
            "relative_humidity" => $row["relative_humidity"] !== null ? (float) $row["relative_humidity"] : null,
            "barometric_pressure" => $row["barometric_pressure"] !== null ? (float) $row["barometric_pressure"] : null
        ];
    }
    $result->free();
    $mysqli->close();

    return $data;
}

function getgraphdata() {
    $neighboursdata = neighbours();

    $now = time();
    $nodes = [];
    $filterednodes = [];
    $links = [];

    foreach ($neighboursdata as $nodeid => $data) {
        if ($now - $data["updated_at"] > 168 * 3600) {
            continue;
        }

        $nodes[] = [
            "id" => $nodeid,
            "long_name" => $data["long_name"],
            "short_name" => $data["short_name"],
            "updated_at" => $data["updated_at"],
            "neighbours_updated_at" => $data["neighbours_updated_at"],
            "role" => $data["role"],
            "hardware_model" => $data["hardware_model"],
            "battery_level" => $data["battery_level"],
            "voltage" => $data["voltage"],
            "air_util_tx" => $data["air_util_tx"],
            "channel_utilization" => $data["channel_utilization"],
            "temperature" => $data["temperature"],
            "relative_humidity" => $data["relative_humidity"],
            "barometric_pressure" => $data["barometric_pressure"]
        ];
        $filterednodes[$nodeid] = true;
    }

    foreach ($neighboursdata as $nodeid => $data) {
        if (!is_array($data["neighbours"]) || $now - $data["neighbours_updated_at"] > 168 * 3600) {
            continue;
        }

        foreach ($data["neighbours"] as $neighbour) {
            if (!isset($filterednodes[$nodeid]) || !isset($filterednodes[$neighbour["node_id"]])) {
                continue;
            }

            $links[] = [
                "source" => $nodeid,
                "target" => $neighbour["node_id"],
                "snr" => $neighbour["snr"]
            ];
        }
    }

    return json_encode(["nodes" => $nodes, "links" => $links]);
}

header("Content-Type: application/json");
echo getgraphdata();
?>
