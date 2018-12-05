<?php

/*
 * This file is part of PHP CS Fixer.
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace LinkMonitor\Helper;

class Email
{
    /* Public Variables */
    public $smtp_port; //smtp_port 端口号
    public $time_out;
    public $host_name; //服务器主机名
    public $log_file;
    public $relay_host; //服务器主机地址
    public $debug;
    public $auth; //验证
    public $user; //服务器用户名
    public $pass; //服务器密码

    /* Private Variables */
    public $sock;

    /* Constractor 构造方法*/
    public function __construct($relay_host, $smtp_port, $auth, $user, $pass, $log_file = '')
    {
        $this->debug      = false;
        $this->smtp_port  = $smtp_port;
        $this->relay_host = $relay_host;
        $this->time_out   = 30; //is used in fsockopen()

        $this->auth = $auth; //auth
        $this->user = $user;
        $this->pass = $pass;

        $this->host_name = 'localhost'; //is used in HELO command
        // $this->host_name = "smtp.163.com"; //is used in HELO command
        $this->log_file = $log_file;

        $this->sock = false;
    }

    /* Main Function */
    public function sendmail($to, $from, $subject, $body, $mailtype, $cc = '', $bcc = '', $additional_headers = '')
    {
        $header    = '';
        $mail_from = $this->getAddress($this->stripComment($from));
        $body      = mb_ereg_replace("(^|(\r\n))(\\.)", '\\1.\\3', $body);
        $header .= "MIME-Version:1.0\r\n";
        if ('HTML' == $mailtype) {
            //邮件发送类型
            //$header .= "Content-Type:text/html\r\n";
            $header .= 'Content-type: text/html; charset=utf-8' . "\r\n";
        }
        $header .= 'To: ' . $to . "\r\n";
        if ('' != $cc) {
            $header .= 'Cc: ' . $cc . "\r\n";
        }
        $header .= 'From: ' . $from . "\r\n";
        // $header .= "From: $from<".$from.">\r\n";   //这里只显示邮箱地址，不够人性化
        $header .= 'Subject: ' . $subject . "\r\n";
        $header .= $additional_headers;
        $header .= 'Date: ' . date('r') . "\r\n";
        $header .= 'X-Mailer:By (PHP/' . PHP_VERSION . ")\r\n";
        list($msec, $sec) = explode(' ', microtime());
        $header .= 'Message-ID: <' . date('YmdHis', $sec) . '.' . ($msec * 1000000) . '.' . $mail_from . ">\r\n";
        $TO = explode(',', $this->stripComment($to));

        if ('' != $cc) {
            $TO = array_merge($TO, explode(',', $this->stripComment($cc))); //合并一个或多个数组
        }

        if ('' != $bcc) {
            $TO = array_merge($TO, explode(',', $this->stripComment($bcc)));
        }

        $sent = true;
        foreach ($TO as $rcpt_to) {
            $rcpt_to = $this->getAddress($rcpt_to);
            if (!$this->smtpSockopen($rcpt_to)) {
                $this->logWrite('Error: Cannot send email to ' . $rcpt_to . "\n");
                $sent = false;
                continue;
            }
            if ($this->smtpSend($this->host_name, $mail_from, $rcpt_to, $header, $body)) {
                $this->logWrite('E-mail has been sent to <' . $rcpt_to . ">\n");
            } else {
                $this->logWrite('Error: Cannot send email to <' . $rcpt_to . ">\n");
                $sent = false;
            }
            fclose($this->sock);
            $this->logWrite("Disconnected from remote host\n");
        }
        //echo "<br>";
        //echo $header;
        return $sent;
    }

    /* Private Functions */

    public function smtpSend($helo, $from, $to, $header, $body = '')
    {
        if (!$this->smtpPutcmd('HELO', $helo)) {
            return $this->smtpError('sending HELO command');
        }
        //auth
        if ($this->auth) {
            if (!$this->smtpPutcmd('AUTH LOGIN', base64_encode($this->user))) {
                return $this->smtpError('sending HELO command');
            }

            if (!$this->smtpPutcmd('', base64_encode($this->pass))) {
                return $this->smtpError('sending HELO command');
            }
        }

        if (!$this->smtpPutcmd('MAIL', 'FROM:<' . $from . '>')) {
            return $this->smtpError('sending MAIL FROM command');
        }

        if (!$this->smtpPutcmd('RCPT', 'TO:<' . $to . '>')) {
            return $this->smtpError('sending RCPT TO command');
        }

        if (!$this->smtpPutcmd('DATA')) {
            return $this->smtpError('sending DATA command');
        }

        if (!$this->smtpMessage($header, $body)) {
            return $this->smtpError('sending message');
        }

        if (!$this->smtpEom()) {
            return $this->smtpError('sending <CR><LF>.<CR><LF> [EOM]');
        }

        if (!$this->smtpPutcmd('QUIT')) {
            return $this->smtpError('sending QUIT command');
        }

        return true;
    }

    public function smtpSockopen($address)
    {
        if ('' == $this->relay_host) {
            return $this->smtpSockopenMx($address);
        }

        return $this->smtpSockopenRelay();
    }

    public function smtpSockopenRelay()
    {
        $this->logWrite('Trying to ' . $this->relay_host . ':' . $this->smtp_port . "\n");
        $this->sock = @fsockopen($this->relay_host, $this->smtp_port, $errno, $errstr, $this->time_out);
        if (!($this->sock && $this->smtpOk())) {
            $this->logWrite('Error: Cannot connenct to relay host ' . $this->relay_host . "\n");
            $this->logWrite('Error: ' . $errstr . ' (' . $errno . ")\n");

            return false;
        }
        $this->logWrite('Connected to relay host ' . $this->relay_host . "\n");

        return true;
    }

    public function smtpSockopenMx($address)
    {
        $domain = str_replace('^.+@([^@]+)$', '\\1', $address);
        if (!@getmxrr($domain, $MXHOSTS)) {
            $this->logWrite('Error: Cannot resolve MX "' . $domain . "\"\n");

            return false;
        }
        foreach ($MXHOSTS as $host) {
            $this->logWrite('Trying to ' . $host . ':' . $this->smtp_port . "\n");
            $this->sock = @fsockopen($host, $this->smtp_port, $errno, $errstr, $this->time_out);
            if (!($this->sock && $this->smtpOk())) {
                $this->logWrite('Warning: Cannot connect to mx host ' . $host . "\n");
                $this->logWrite('Error: ' . $errstr . ' (' . $errno . ")\n");
                continue;
            }
            $this->logWrite('Connected to mx host ' . $host . "\n");

            return true;
        }
        $this->logWrite('Error: Cannot connect to any mx hosts (' . implode(', ', $MXHOSTS) . ")\n");

        return false;
    }

    public function smtpMessage($header, $body)
    {
        fwrite($this->sock, $header . "\r\n" . $body);
        $this->smtpDebug('> ' . str_replace("\r\n", "\n" . '> ', $header . "\n> " . $body . "\n> "));

        return true;
    }

    public function smtpEom()
    {
        fwrite($this->sock, "\r\n.\r\n");
        $this->smtpDebug(". [EOM]\n");

        return $this->smtpOk();
    }

    public function smtpOk()
    {
        $response = str_replace("\r\n", '', fgets($this->sock, 512));
        $this->smtpDebug($response . "\n");

        if (!mb_ereg('^[23]', $response)) {
            fwrite($this->sock, "QUIT\r\n");
            fgets($this->sock, 512);
            $this->logWrite('Error: Remote host returned "' . $response . "\"\n");

            return false;
        }

        return true;
    }

    public function smtpPutcmd($cmd, $arg = '')
    {
        if ('' != $arg) {
            if ('' == $cmd) {
                $cmd = $arg;
            } else {
                $cmd = $cmd . ' ' . $arg;
            }
        }

        fwrite($this->sock, $cmd . "\r\n");
        $this->smtpDebug('> ' . $cmd . "\n");

        return $this->smtpOk();
    }

    public function smtpError($string)
    {
        $this->logWrite('Error: Error occurred while ' . $string . ".\n");

        return false;
    }

    public function logWrite($message)
    {
        $this->smtpDebug($message);

        if ('' == $this->log_file) {
            return true;
        }

        $message = date('M d H:i:s ') . get_current_user() . '[' . getmypid() . ']: ' . $message;
        if (!@file_exists($this->log_file) || !($fp = @fopen($this->log_file, 'a'))) {
            $this->smtpDebug('Warning: Cannot open log file "' . $this->log_file . "\"\n");

            return false;
        }
        flock($fp, LOCK_EX);
        fwrite($fp, $message);
        fclose($fp);

        return true;
    }

    public function stripComment($address)
    {
        $comment = '\\([^()]*\\)';
        while (mb_ereg($comment, $address)) {
            $address = mb_ereg_replace($comment, '', $address);
        }

        return $address;
    }

    public function getAddress($address)
    {
        $address = mb_ereg_replace("([ \t\r\n])+", '', $address);
        $address = mb_ereg_replace('^.*<(.+)>.*$', '\\1', $address);

        return $address;
    }

    public function smtpDebug($message)
    {
        if ($this->debug) {
            echo $message . '<br>';
        }
    }

    public function getAttachType($image_tag)
    {
        $filedata = [];

        $img_file_con = fopen($image_tag, 'r');
        unset($image_data);
        while ($tem_buffer = addslashes(fread($img_file_con, filesize($image_tag)))) {
            $image_data .= $tem_buffer;
        }

        fclose($img_file_con);

        $filedata['context']  = $image_data;
        $filedata['filename'] = basename($image_tag);
        $extension            = substr($image_tag, strrpos($image_tag, '.'), strlen($image_tag) - strrpos($image_tag, '.'));
        switch ($extension) {
            case '.gif':
                $filedata['type'] = 'image/gif';
                break;
            case '.gz':
                $filedata['type'] = 'application/x-gzip';
                break;
            case '.htm':
                $filedata['type'] = 'text/html';
                break;
            case '.html':
                $filedata['type'] = 'text/html';
                break;
            case '.jpg':
                $filedata['type'] = 'image/jpeg';
                break;
            case '.tar':
                $filedata['type'] = 'application/x-tar';
                break;
            case '.txt':
                $filedata['type'] = 'text/plain';
                break;
            case '.zip':
                $filedata['type'] = 'application/zip';
                break;
            default:
                $filedata['type'] = 'application/octet-stream';
                break;
        }

        return $filedata;
    }
}
