<?php
class Game
{
    public function run()
    {
        error_reporting(E_ALL);
        $map = array();

        echo "<h2>TCP/IP Connection</h2>\n";

        /* Get the port for the WWW service. */
        $service_port = 7654;

        /* Get the IP address for the target host. */
        $address = gethostbyname('codearena.eu');

        /* Create a TCP/IP socket. */
        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
        } else {
            echo "OK.\n";
        }

        echo "Attempting to connect to '$address' on port '$service_port'...";
        $result = socket_connect($socket, $address, $service_port);
        if ($result === false) {
            echo "socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
        } else {
            echo "OK.\n";
        }

        $in = '{"userid":"9066","hashid":"6c9838f3827d9cb84056b33281e0791e","gamemode":"2"}';
        $out = '';

        echo "Sending HTTP HEAD request...";
        socket_write($socket, $in, strlen($in));
        echo "OK.\n";

        echo "Reading response:\n\n";
        $out = socket_read($socket, 2048, PHP_NORMAL_READ);
        echo $out."\n";
        $responce = json_decode($out, TRUE);
        $directions = Array('NE','E','SE','SW','W','NW');
        if($responce['messageType'] == 'response' && $responce['status']== 'GAME_READY' ) {
            //process step
            $game_ended = FALSE;
            while((!$game_ended && $out = socket_read($socket, 2048, PHP_NORMAL_READ))) {
                if(!strlen($out)) continue;
                echo "Response:".$out."\n";
                if(substr($out, 0, 1) == "<") {
                    echo "Game closed\n";
                    $game_ended = TRUE;
                    continue;
                }
                $responce = json_decode($out, TRUE);
                if($responce['messageType'] == 'response' && $responce['status']== 'GAME_CLOSED' ) {
                    echo "Game closed\n";
                    $game_ended = TRUE;
                    continue;
                }
                if($responce['messageType'] == 'game' && isset($responce['result'])) {
                    echo "Game ended\n";
                    $game_ended = TRUE;
                    continue;
                }
                if($responce['messageType'] == 'game') {
                    echo "Saving map\n";
                    $x = $responce['unit']['x'];
                    $y = $responce['unit']['y'];
                    foreach($responce['unit']['sees'] as $cell) {

                        $u = $cell['background'];
                        $mapcell = array('u' => $u);
                        switch ($cell['direction']) {
                            case "NW":
                                $map[$x][$y-1] = $mapcell;
                                break;
                            case "NE":
                                $map[$x+1][$y-1] = $mapcell;
                                break;
                            case "W":
                                $map[$x-1][$y] = $mapcell;
                                break;
                            case "E":
                                $map[$x+1][$y] = $mapcell;
                                break;
                            case "SW":
                                $map[$x][$y+1] = $mapcell;
                                break;
                            case "SE":
                                $map[$x+1][$y+1] = $mapcell;
                                break;
                        }
                    }

                    echo "Processing step\n";
                    $unitId = $responce['unit']['id'];
                    $roundNum = $responce['roundNum'];
                    $in = '{"unitId":"'.$unitId.'","roundNum":"'.$roundNum.'","direction":"'.$directions[rand(0,5)].'"}';
                    echo "Request:".$in."\n";
                    socket_write($socket, $in, strlen($in));
                }
            }
        }
        else {
            echo "Not started\n";
        }

        echo "Closing socket...";
        socket_close($socket);
        echo "OK.\n\n";
        print_r($map);
    }
}
