<?php
/**
 * SimpleSMTPMailer
 * Class sederhana untuk mengirim email lewat SMTP tanpa library luar (PHPMailer dll).
 * Cara kerja: buka koneksi socket ke server SMTP, lalu "ngobrol" pakai perintah
 * SMTP standar (EHLO, AUTH LOGIN, MAIL FROM, RCPT TO, DATA).
 */
class SimpleSMTPMailer
{
    private $host;
    private $port;
    private $username;
    private $password;
    private $encryption;
    private $fromEmail;
    private $fromName;
    private $socket;
    private $error = '';

    public function __construct($host, $port, $username, $password, $encryption, $fromEmail, $fromName)
    {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->encryption = $encryption;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    public function getError()
    {
        return $this->error;
    }

    public function send($to, $subject, $body)
    {
        $prefix = ($this->encryption === 'ssl') ? 'ssl://' : '';
        $this->socket = @stream_socket_client("{$prefix}{$this->host}:{$this->port}", $errno, $errstr, 15);

        if (!$this->socket) {
            $this->error = "Gagal konek ke server SMTP: $errstr";
            return false;
        }

        $this->readResponse(); // baca banner pembuka server (220)

        if (!$this->command("EHLO localhost", 250)) return $this->fail();

        if ($this->encryption === 'tls') {
            if (!$this->command("STARTTLS", 220)) return $this->fail();
            stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$this->command("EHLO localhost", 250)) return $this->fail();
        }

        if (!$this->command("AUTH LOGIN", 334)) return $this->fail();
        if (!$this->command(base64_encode($this->username), 334)) return $this->fail();
        if (!$this->command(base64_encode($this->password), 235)) {
            return $this->fail('Login SMTP gagal, cek lagi username/App Password.');
        }

        if (!$this->command("MAIL FROM: <{$this->fromEmail}>", 250)) return $this->fail();
        if (!$this->command("RCPT TO: <{$to}>", 250)) return $this->fail();
        if (!$this->command("DATA", 354)) return $this->fail();

        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "To: <{$to}>\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        fwrite($this->socket, $headers . "\r\n" . $body . "\r\n.\r\n");
        $ok = $this->readResponse(250);

        fwrite($this->socket, "QUIT\r\n");
        fclose($this->socket);

        if (!$ok) {
            $this->error = "Server SMTP menolak saat mengirim isi email.";
            return false;
        }
        return true;
    }

    private function command($cmd, $expectedCode)
    {
        fwrite($this->socket, $cmd . "\r\n");
        return $this->readResponse($expectedCode);
    }

    private function readResponse($expectedCode = null)
    {
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            // baris terakhir balasan SMTP ditandai spasi di karakter ke-4 (bukan "-")
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        $code = (int)substr($response, 0, 3);

        if ($expectedCode === null) return true;

        if ($code !== $expectedCode) {
            $this->error = "Respon SMTP tidak sesuai (dapat $code, harap $expectedCode): $response";
            return false;
        }
        return true;
    }

    private function fail($customMessage = null)
    {
        if ($customMessage) $this->error = $customMessage;
        if (is_resource($this->socket)) fclose($this->socket);
        return false;
    }
}
