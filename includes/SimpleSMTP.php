<?php

class SimpleSMTP {
    private $host;
    private $port;
    private $user;
    private $pass;
    private $connection;
    private $debug = false;

    public function __construct($host, $port, $user, $pass) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
    }

    public function send($to, $subject, $body, $fromEmail, $fromName) {
        $this->connect();
        $this->auth();
        $this->sendMail($to, $subject, $body, $fromEmail, $fromName);
        $this->quit();
    }

    private function connect() {
        $socket_context = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $this->connection = stream_socket_client("ssl://{$this->host}:{$this->port}", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $socket_context);

        if (!$this->connection) {
            throw new Exception("Could not connect to SMTP server: $errstr");
        }
        $this->readResponse();
        $this->sendCommand('EHLO ' . $_SERVER['SERVER_NAME']);
    }

    private function auth() {
        $this->sendCommand('AUTH LOGIN');
        $this->sendCommand(base64_encode($this->user));
        $this->sendCommand(base64_encode($this->pass));
    }

    private function sendMail($to, $subject, $body, $fromEmail, $fromName) {
        $this->sendCommand("MAIL FROM: <$fromEmail>");
        $this->sendCommand("RCPT TO: <$to>");
        $this->sendCommand("DATA");

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $headers .= "From: $fromName <$fromEmail>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";

        $message = $headers . "\r\n" . $body . "\r\n.";
        $this->sendCommand($message);
    }

    private function sendCommand($command) {
        fwrite($this->connection, $command . "\r\n");
        return $this->readResponse();
    }

    private function readResponse() {
        $response = "";
        while ($str = fgets($this->connection, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ") {
                break;
            }
        }
        if ($this->debug) {
             // echo "SMTP: " . $response . "<br>";
        }
        return $response;
    }

    private function quit() {
        $this->sendCommand('QUIT');
        fclose($this->connection);
    }
}
?>
