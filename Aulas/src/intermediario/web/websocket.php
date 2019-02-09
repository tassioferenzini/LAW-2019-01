<?php

ini_set('error_reporting', 	E_ERROR);
date_default_timezone_set('America/Sao_Paulo');

$host = 'localhost';
$port = '22223';
$null = NULL;

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socket, 0, $port);
socket_listen($socket);
$clients = array($socket);

while (true) {

    sleep(1);

    $changed = $clients;
    socket_select($changed, $null, $null, 0, 10);

    if (in_array($socket, $changed)) {
        $socket_new = socket_accept($socket);
        $clients[] = $socket_new;
        $header = socket_read($socket_new, 1024);
        perform_handshaking($header, $socket_new, $host, $port);
        socket_getpeername($socket_new, $ip);
        $response = mask(json_encode(array('type'=>'system', 'message'=>$ip.' connected')));
        send_message($response);
        $found_socket = array_search($socket, $changed);
        unset($changed[$found_socket]);
    }

    foreach ($changed as $changed_socket) {

        while(socket_recv($changed_socket, $buf, 1024, 0) >= 1) {
            $received_text = unmask($buf);
            $response_text = mask(Data());
            send_message($response_text);
            break 2;
        }

        $buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);

        if ($buf === false) {
            $found_socket = array_search($changed_socket, $clients);
            socket_getpeername($changed_socket, $ip);
            unset($clients[$found_socket]);
            $response = mask(json_encode(array('type'=>'system', 'message'=>$ip.' disconnected')));
            send_message($response);
        }
    }
}

socket_close($sock);

function send_message($msg) {
    global $clients;
    foreach($clients as $changed_socket) {
        @socket_write($changed_socket,$msg,strlen($msg));
    }
    return true;
}

function unmask($text) {
    $length = ord($text[1]) & 127;
    if($length == 126) {
        $masks = substr($text, 4, 4);
        $data = substr($text, 8);
    }
    elseif($length == 127) {
        $masks = substr($text, 10, 4);
        $data = substr($text, 14);
    }
    else {
        $masks = substr($text, 2, 4);
        $data = substr($text, 6);
    }
    $text = "";
    for ($i = 0; $i < strlen($data); ++$i) {
        $text .= $data[$i] ^ $masks[$i%4];
    }
    return $text;
}

function mask($text) {
    $b1 = 0x80 | (0x1 & 0x0f);
    $length = strlen($text);
    if($length <= 125)
        $header = pack('CC', $b1, $length);
    elseif($length > 125 && $length < 65536)
        $header = pack('CCn', $b1, 126, $length);
    elseif($length >= 65536)
        $header = pack('CCNN', $b1, 127, $length);
    return $header.$text;
}


function perform_handshaking($receved_header,$client_conn, $host, $port) {
    $headers = array();
    $lines = preg_split("/\r\n/", $receved_header);
    foreach($lines as $line)
    {
        $line = chop($line);
        if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
        {
            $headers[$matches[1]] = $matches[2];
        }
    }
    $secKey = $headers['Sec-WebSocket-Key'];
    $secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
    $upgrade  = "HTTP/1.1 101 Web Socket Protocol\r\n" .
        "Upgrade: websocket\r\n" .
        "Connection: Upgrade\r\n" .
        "WebSocket-Origin: $host\r\n" .
        "WebSocket-Location: ws://$host:$port/websocket.php\r\n".
        "Sec-WebSocket-Accept:$secAccept\r\n\r\n";
    socket_write($client_conn,$upgrade,strlen($upgrade));
}

function rand_float($n1=0,$n2=1,$mul=1000000000000000000) {
    if ($n1>$n2) return false;
    return mt_rand($n1*$mul,$n2*$mul)/$mul;
}

/*
//OLD
function rand_color() {
    $letters = '0123456789ABCDEF';
    $color = '#';
    for($i = 0; $i < 6; $i++) {
        $index = rand(0,15);
        $color .= $letters[$index];
    }
    return $color;
}
*/

function rand_color($sensor) {
    switch ($sensor) {
        case "RESP_PER_MIN":
            $color = "#ffe770";
            break;
        case "PULS_PER_MIN + PULS" || "PERCENT":
            $color = "#a0edff";
            break;
        case "CUBIC_CENTI_M_PER_SEC" || "_MMGH" || "MM_HG" || "MM_HG_PER_PERCENT" || "X_L_PER_MM_HG" || "MILLI_L_PER_MM_HG" || "MM_HG_PER_SEC" || "MM_HG_PER_X_L_PER_SEC" || "CUBIC_X_M_PER_SEC" || "PER_CUBIC_MILLI_M" || "PER_CUBIC_CENTI_M" || "CUBIC_MILLI_M" || "CUBIC_CENTI_M" || "CUBIC_X_M":
            $color = "#ff0000";
            break;
        case "MILLI_L_PER_DL" || "MILLI_G_PER_DL" || "MICRO_G_PER_DL" || "X_G_PER_DL" || "KELVIN + FAHR" || "EVOLT" || "X_VOLT" || "MILLI_VOLT" || "MICRO_VOLT" || "VOLT_PER_MILLI_VOLT" || "CM_H2O_PER_MICRO_VOLT" || "X_M_PER_MILLI_VOLT" || "MILLI_M_PER_MILLI_VOLT":
            $color = "#70ff70";
            break;
    }
    return $color;
}

function rand_value_wave() {
    $value=array();
    for($i = 0; $i < 150; $i++) {
        $value[$i]=rand_float(1.1,2.2);
    }
    return $value;
}

function rand_sensor() {
    $type = array("RESP_PER_MIN","PULS_PER_MIN + PULS", "MILLI_L_PER_DL", "MILLI_G_PER_DL", "MICRO_G_PER_DL", "X_G_PER_DL", "CUBIC_X_M", "CUBIC_CENTI_M", "CUBIC_MILLI_M", "PER_CUBIC_CENTI_M", "PER_CUBIC_MILLI_M", "CUBIC_X_M_PER_SEC", "CUBIC_CENTI_M_PER_SEC", "KELVIN + FAHR", "PERCENT", "BEAT", "BEAT_PER_MIN", "BEAT_PER_MIN_PER_X_L", "BEAT_PER_MIN_PER_MILLI_L", "BEAT_PER_MIN_PER_ML_C", "EVOLT", "X_VOLT", "MILLI_VOLT", "MICRO_VOLT", "VOLT_PER_MILLI_VOLT", "CM_H2O_PER_MICRO_VOLT", "X_M_PER_MILLI_VOLT", "MILLI_M_PER_MILLI_VOLT
", "_MMGH", "MM_HG", "MM_HG_PER_PERCENT", "X_L_PER_MM_HG", "MILLI_L_PER_MM_HG", "MM_HG_PER_SEC", "MM_HG_PER_X_L_PER_SEC");

    $sensorData=array();
    for($i = 0; $i < 3; $i++) {
        $sensor=null;
        $sensor->id = mt_rand(1, 3);
        $sensor->type = "'".$type[mt_rand(0, 2)]."'";
        $sensor->value = mt_rand(30, 40);
        $sensor->unitOfMeasurement = "NOM_DIM_DEGC";
        $alarm=(bool)random_int(0, 1);
        $sensor->alarm = $alarm;
        $sensor->minValue = 20;
        $sensor->maxValue = 50;
        $sensor->color = rand_color($sensor->type);
        $sensorData[$i]=$sensor;

        if ($alarm == true) {
            $time1 = time() + random_int(0, 9);
            $time2 = time() + random_int(0, 9);
            if ($time1 == $time2) {
                sendpost($sensor);
                $alarm = null;

            }
        }
    }
    return $sensorData;
}

function Data() {
    $wave=null;
    $wave->Timestamp = date('m/d/y h:i:s A');;
    $wave->Relativetimestamp = strtotime("now");
    $wave->PhysioID = 20480;
    $wave->Physio = "NOM_RESP";
    $wave->Value = rand_value_wave();

    $patient=null;
    $patient->pid = 1;
    $patient->name = "TESTE";

    $DATA = array("sensorData" => rand_sensor(), "waveData" => $wave, "patientData" => $patient);

    $JSON = json_encode($DATA);

    return $JSON;

}

function sendpost($sensor){
    $url = 'http://139.82.24.122:8080/email';

    $fields = array(
        'name' => "$sensor->type",
        'value' => "$sensor->value"
    );

    $data_string = json_encode($fields);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 50);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 50);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_VERBOSE, true);


    $result = curl_exec($ch);

    if (!$result) {
        $body = curl_error($ch);
         echo "CURL Error: = " . $body;
    } else {
        echo $result;
    }

    curl_close ($ch);

}


?>