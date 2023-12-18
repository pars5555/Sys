<?php

namespace system\util {

    class Util {

        private static $ch = null;
        private static $process_timings = [];
        private static $last_curl_handler_before_request = "";
        private static $curl_last_error;

        public static function getCurlLastError() {
            return self::$curl_last_error;
        }

        public static function getDirectoryFiles($dir, $extensions = '*', $relative = false, $recursive = true) {
            if (!is_dir($dir)) {
                return [];
            }
            $dir = str_replace('\\', '/', $dir);
            $files = @scandir($dir);
            if (!is_array($extensions)) {
                $extensions = [$extensions];
            }
            $extensions = array_map('strtolower', $extensions);
            $results = [];
            if (empty($files)) {
                return $results;
            }
            $ignoreDirectories = [];
            $ignoreFiles = [];
            if (in_array('ignore.sys', $files)) {
                $ignoreJson = json_decode(file_get_contents($dir . DIRECTORY_SEPARATOR . 'ignore.sys'));
                if (!empty($ignoreJson)) {
                    $ignoreDirectories = isset($ignoreJson->directories) ? $ignoreJson->directories : [];
                    $ignoreFiles = isset($ignoreJson->files) ? $ignoreJson->files : [];
                }
            }
            foreach ($files as $file) {
                $path = realpath($dir . DIRECTORY_SEPARATOR . $file);
                if (is_file($path) && !in_array($file, $ignoreFiles)) {
                    if ($extensions[0] === '*' || in_array(strtolower(self::getFileExtension($path)), $extensions)) {
                        $results[] = $path;
                    }
                } else if ($recursive && $file != "." && $file != ".." && !in_array($file, $ignoreDirectories)) {
                    $results = array_merge($results, self::getDirectoryFiles($path, $extensions));
                }
            }
            foreach ($results as &$value) {
                $value = rtrim(str_replace('\\', '/', $value), '\\/');
            }
            if ($relative) {
                $dirRealPath = trim(realpath($dir));
                $dirRealPathClean = trim(str_replace('\\', '/', $dirRealPath), '\\/');

                foreach ($results as &$value) {
                    $value = trim(str_replace($dirRealPathClean, "", $value), '\\/');
                    $value = trim(str_replace($dir, "", $value), '\\/');
                }
            }
            return $results;
        }
        
        public static function isAscii($str) {
            return mb_detect_encoding($str, 'ASCII', true) === 'ASCII';
        }
        

        public static function form_safe_json($value) {
            # list from www.json.org: (\b backspace, \f formfeed)    
            $escapers = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c");
            $replacements = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b");
            $result = str_replace($escapers, $replacements, $value);
            return $result;
        }

        public static function decode_safe_json($value) {
            # list from www.json.org: (\b backspace, \f formfeed)    
            $replacements = array("\\", "/", "\"", "\n", "\r", "\t", "\x08", "\x0c");
            $escapers = array("\\\\", "\\/", "\\\"", "\\n", "\\r", "\\t", "\\f", "\\b");
            $result = str_replace($escapers, $replacements, $value);
            return $result;
        }

        public static function profileStartTiming() {
            $processUuid = uniqid('pt', true);
            self::$process_timings[$processUuid] = microtime(true);
            return $processUuid;
        }

        public static function hex2rgb($color) {
            if (empty($color)) {
                return false;
            }
            if ($color[0] == '#') {
                $color = substr($color, 1);
            }
            if (strlen($color) == 6) {
                $hex = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
            } elseif (strlen($color) == 3) {
                $hex = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
            } else {
                return false;
            }
            return array_map('hexdec', $hex);
        }

        public static function wrapObjectsIntoSysObjectWrapper($objs) {
            $ret = [];
            foreach ($objs as $obj) {
                $ret[] = new \system\entities\SysEntity($obj);
            }
            return $ret;
        }

        public static function profileGetTiming($processUuid, $precision = 4) {
            if (isset(self::$process_timings[$processUuid])) {
                $seconds = round(microtime(true) - self::$process_timings[$processUuid], $precision);
                self::$process_timings[$processUuid] = microtime(true);
                return $seconds;
            }
            return false;
        }

        public static function getProxyIpAddress($proxyIpPort = '', $proxyUser = '', $proxyPass = '', $proxyType = '') {
            //http://www.geoplugin.net/json.gp?ip=69.69.69.69

            $protocolIpPort = preg_split('/\s+/', trim($proxyIpPort), -1, PREG_SPLIT_NO_EMPTY);
            $ipport = $protocolIpPort[count($protocolIpPort) - 1];
            list($ipAddress, $headers, $httpCode) = \system\util\Util::curlMethod('GET', 'https://api.ipify.org/', '', [], $ipport, $proxyUser, $proxyPass, 0, 10, $proxyType);
            return $ipAddress;
        }

        public static function generateQrPngBase64($text) {
            $renderer = new \BaconQrCode\Renderer\ImageRenderer(
                    new \BaconQrCode\Renderer\RendererStyle\RendererStyle(400, 2), new \BaconQrCode\Renderer\Image\ImagickImageBackEnd()
            );
            $writer = new \BaconQrCode\Writer($renderer);
            return base64_encode($writer->writeString($text));
        }

        public static function getTimeDiffSeconds($t1, $t2) {
            if ($t1 < $t2) {
                $start = strtotime($t1);
                $end = strtotime($t2);
                return $end - $start;
            } else {
                $start = strtotime($t1);
                $end = strtotime('24:00:00');
                return strtotime('24:00:00') - strtotime($t1) + (strtotime($t2) - strtotime('00:00:00'));
            }
        }

        public static function getDateDiffInSeconds($t1, $t2) {
            $t1 = strtotime($t1);
            $t2 = strtotime($t2);
            return abs($t1 - $t2);
        }

        public static function witWavToText($wavBlob, $accessToken, &$body, &$headers) {
            list($body, $headers, $httpCode) = \system\util\Util::curlMethod("POST", 'https://api.wit.ai/speech', $wavBlob, [
                        'Authorization' => "Bearer $accessToken",
                        'Content-Type' => "audio/wav"
                            ]
            );
            return $body;
        }

        public static function witMp3ToText($wavBlob, $accessToken, &$body, &$headers) {
            list($body, $headers, $httpCode) = \system\util\Util::curlMethod("POST", 'https://api.wit.ai/speech', $wavBlob, [
                        'Authorization' => "Bearer $accessToken",
                        'Content-Type' => "audio/mpeg3"
                            ]
            );
            return $body;
        }

        public static function getFixedLengthRandomNumber($length = 4) {
            return sprintf('%0' . $length . 'd', random_int(0, pow(10, $length) - 1));
        }

        public static function swapVariableValues(&$x, &$y) {
            $tmp = $x;
            $x = $y;
            $y = $tmp;
        }

        public static function getRamTotal() {
            $result = 0;
            if (PHP_OS == 'WINNT') {
                $lines = null;
                $matches = null;
                exec('wmic ComputerSystem get TotalPhysicalMemory /Value', $lines);
                if (preg_match('/^TotalPhysicalMemory\=(\d+)$/', $lines[2], $matches)) {
                    $result = $matches[1] / 1024 / 1024 / 1024;
                }
            } else {
                $fh = fopen('/proc/meminfo', 'r');
                while ($line = fgets($fh)) {
                    $pieces = array();
                    if (preg_match('/^MemTotal:\s+(\d+)\skB$/', $line, $pieces)) {
                        $result = $pieces[1];
                        // KB to Bytes
                        $result = $result * 1024;
                        break;
                    }
                }
                fclose($fh);
            }
            // KB RAM Total
            return round($result, 1);
        }

        public static function getRamFree() {
            $result = 0;
            if (PHP_OS == 'WINNT') {
                $lines = null;
                $matches = null;
                exec('wmic OS get FreePhysicalMemory /Value', $lines);
                if (preg_match('/^FreePhysicalMemory\=(\d+)$/', $lines[2], $matches)) {
                    $result = $matches[1] / 1024 / 1024;
                }
            } else {
                $fh = fopen('/proc/meminfo', 'r');
                while ($line = fgets($fh)) {
                    $pieces = array();
                    if (preg_match('/^MemFree:\s+(\d+)\skB$/', $line, $pieces)) {
                        // KB to Bytes
                        $result = $pieces[1] * 1024;
                        break;
                    }
                }
                fclose($fh);
            }
            // KB RAM Total
            return round($result, 1);
        }

        public static function getDiskFreeSpace($path = '/') {
            $result = array();
            $result['size'] = 0;
            $result['free'] = 0;
            $result['used'] = 0;

            if (PHP_OS == 'WINNT') {
                $lines = null;
                exec('wmic logicaldisk get FreeSpace^,Name^,Size /Value', $lines);
                foreach ($lines as $index => $line) {
                    if ($line != "Name=$path") {
                        continue;
                    }
                    $result['free'] = explode('=', $lines[$index - 1])[1] / 1024 / 1024 / 1024;
                    $result['size'] = explode('=', $lines[$index + 1])[1] / 1024 / 1024 / 1024;
                    $result['used'] = $result['size'] - $result['free'] / 1024 / 1024 / 1024;
                    break;
                }
            } else {
                $lines = null;
                exec(sprintf('df /P %s', $path), $lines);
                foreach ($lines as $index => $line) {
                    if ($index != 1) {
                        continue;
                    }
                    $values = preg_split('/\s{1,}/', $line);
                    $result['size'] = $values[1] / 1024 / 1024;
                    $result['free'] = $values[3] / 1024 / 1024;
                    $result['used'] = $values[2] / 1024 / 1024;
                    break;
                }
            }
            return $result['free'];
        }

        public static function getCpuLoadPercentage() {
            $result = -1;
            $lines = null;
            if (PHP_OS == 'WINNT') {
                $matches = null;
                exec('wmic.exe CPU get loadpercentage /Value', $lines);
                if (preg_match('/^LoadPercentage\=(\d+)$/', $lines[2], $matches)) {
                    $result = $matches[1];
                }
            } else {
                // https://github.com/Leo-G/DevopsWiki/wiki/How-Linux-CPU-Usage-Time-and-Percentage-is-calculated
                //$tests = array();
                //$tests[] = 'cpu  3194489 5224 881924 305421192 603380 76 52143 106209 0 0';
                //$tests[] = 'cpu  3194490 5224 881925 305422568 603380 76 52143 106209 0 0';

                $checks = array();
                foreach (array(0, 1) as $i) {
                    $cmd = '/proc/stat';
                    #$cmd = 'grep \'cpu \' /proc/stat <(sleep 1 && grep \'cpu \' /proc/stat) | awk -v RS="" \'{print ($13-$2+$15-$4)*100/($13-$2+$15-$4+$16-$5) "%"}\'';
                    #exec($cmd, $lines);
                    $lines = array();
                    $fh = fopen($cmd, 'r');
                    while ($line = fgets($fh)) {
                        $lines[] = $line;
                    }
                    fclose($fh);
                    //$lines = array($tests[$i]);

                    foreach ($lines as $line) {
                        $ma = array();
                        if (!preg_match('/^cpu  (\d+) (\d+) (\d+) (\d+) (\d+) (\d+) (\d+) (\d+) (\d+) (\d+)$/', $line, $ma)) {
                            continue;
                        }
                        /**
                         * The meanings of the columns are as follows, from left to right:
                          1st column : user = normal processes executing in user mode
                          2nd column : nice = niced processes executing in user mode
                          3rd column : system = processes executing in kernel mode
                          4th column : idle = twiddling thumbs
                          5th column : iowait = waiting for I/O to complete
                          6th column : irq = servicing interrupts
                          7th column : softirq = servicing softirqs
                          8th column:
                          9th column:
                          Calculation:
                          sum up all the columns in the 1st line "cpu" :
                          ( user + nice + system + idle + iowait + irq + softirq )
                          this will yield 100% of CPU time
                          calculate the average percentage of total 'idle' out of 100% of CPU time :
                          ( user + nice + system + idle + iowait + irq + softirq ) = 100%
                          ( idle ) = X %
                          TOTAL USER = %user + %nice
                          TOTAL CPU = %user + %nice + %system
                          TOTAL IDLE = %iowait + %steal + %idle
                         */
                        $total = $ma[1] + $ma[2] + $ma[3] + $ma[4] + $ma[5] + $ma[6] + $ma[7] + $ma[8] + $ma[9];
                        //$totalCpu = $ma[1] + $ma[2] + $ma[3];
                        //$result = (100 / $total) * $totalCpu;
                        $ma['total'] = $total;
                        $checks[] = $ma;
                        break;
                    }

                    if ($i == 0) {
                        // Wait before checking again.
                        sleep(1);
                    }
                }

                // Idle - prev idle
                $diffIdle = $checks[1][4] - $checks[0][4];

                // Total - prev total
                $diffTotal = $checks[1]['total'] - $checks[0]['total'];

                // Usage in %
                $diffUsage = (1000 * ($diffTotal - $diffIdle) / $diffTotal + 5) / 10;
                $result = $diffUsage;
            }
            return (float) $result;
        }

        public static function dateNowMinutesBefore($minutes) {
            $timeoutAtDatetime = new \DateTime();
            $timeoutAtDatetime->modify("-$minutes minute");
            return $timeoutAtDatetime->format('Y-m-d H:i:s');
        }

        public static function dateNowMinutesAfter($minutes) {
            $timeoutAtDatetime = new \DateTime();
            $timeoutAtDatetime->modify("+$minutes minute");
            return $timeoutAtDatetime->format('Y-m-d H:i:s');
        }

        public static function dateNowSecondsBefore($seconds) {
            $timeoutAtDatetime = new \DateTime();
            $timeoutAtDatetime->modify("-$seconds second");
            return $timeoutAtDatetime->format('Y-m-d H:i:s');
        }

        public static function dateNowSecondsAfter($seconds) {
            $timeoutAtDatetime = new \DateTime();
            $timeoutAtDatetime->modify("+$seconds second");
            return $timeoutAtDatetime->format('Y-m-d H:i:s');
        }

        public static function mysqlDateTimeNow() {
            return date('Y-m-d H:i:s');
        }

        public static function preparePostFields($array) {
            $params = array();

            foreach ($array as $key => $value) {
                $params[] = $key . '=' . urlencode($value);
            }

            return implode('&', $params);
        }

        public static function str_contains($str, array $arr) {
            if (!is_array($arr)) {
                $arr = [$arr];
            }
            foreach ($arr as $a) {
                if (stripos($str, $a) !== false)
                    return true;
            }
            return false;
        }

        public static function moveArrayElementToFirstPosition(&$array, $key) {
            $array = array_reverse($array, true);
            $val = $array[$key];
            unset($array[$key]);
            $array[$key] = $val;
            $array = array_reverse($array, true);
        }

        public static function dateDiffInMillis(\DateTimeImmutable $date1, \DateTimeImmutable $date2) {
            $aMs = (int) $date1->format('Uv');
            $bMs = (int) $date2->format('Uv');

            return $aMs - $bMs;
        }

        public static function dateWithMillis() {
            return date('Y-m-d H:i:s') . substr((string) microtime(), 1, 4);
        }

        public static function dateWithNanos() {
            return date('Y-m-d H:i:s') . substr((string) microtime(), 1, 7);
        }

        public static function isArrayAssociative(array $arr) {
            if (array() === $arr) {
                return false;
            }
            return array_keys($arr) !== range(0, count($arr) - 1);
        }

        public static function curlMethod($method, $url, $postData = [], $headersKeyValueArray = [], $proxyIpPort = '', $proxyUser = '', $proxyPass = '', $maxRedirs = 0, $timeoutSeconds = 15, $protyType = '') {
            $headers = [];
            if (self::isArrayAssociative($headersKeyValueArray)) {
                foreach ($headersKeyValueArray as $key => $value) {
                    $headers[] = "$key: $value";
                }
            } else {
                $headers = $headersKeyValueArray;
            }
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $maxRedirs);
            curl_setopt($ch, CURLOPT_ENCODING, "");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeoutSeconds);
            if (!empty($proxyIpPort)) {
                curl_setopt($ch, CURLOPT_PROXY, $proxyIpPort);
            }
            if ($protyType === 'socks5') {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            }
            if ($protyType === 'http') {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            }
            if ($protyType === 'https') {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTPS);
            }
            if ($protyType === 'socks4') {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS4);
            }
            if (!empty($proxyUser)) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, "$proxyUser:$proxyPass");
            }
            if (!empty($postData)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            }
            if (!empty($headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            self::$curl_last_error = curl_error($ch);
            $body = curl_exec($ch);
            // extract header
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $header = substr($body, 0, $headerSize);
            $headerArray = self::getHeaders($header);
            $body = substr($body, $headerSize);

            curl_close($ch);
            return [$body, $headerArray, $httpCode];
        }

        public static function closePrevCurl() {
            if (!empty(self::$ch) && gettype(self::$ch) == 'resource') {
                curl_close(self::$ch);
            }
        }

        public static function checkPortIsOpenOnHost($ip, $port) {
            $fp = @fsockopen($ip, $port, $errno, $errstr, 5);
            if (!$fp) {
                return false;
            } else {
                fclose($fp);
                return true;
            }
        }

        public static function mime2ext($mime) {
            $mime_map = [
                'video/3gpp2' => '3g2',
                'video/3gp' => '3gp',
                'video/3gpp' => '3gp',
                'application/x-compressed' => '7zip',
                'audio/x-acc' => 'aac',
                'audio/ac3' => 'ac3',
                'application/postscript' => 'ai',
                'audio/x-aiff' => 'aif',
                'audio/aiff' => 'aif',
                'audio/x-au' => 'au',
                'video/x-msvideo' => 'avi',
                'video/msvideo' => 'avi',
                'video/avi' => 'avi',
                'application/x-troff-msvideo' => 'avi',
                'application/macbinary' => 'bin',
                'application/mac-binary' => 'bin',
                'application/x-binary' => 'bin',
                'application/x-macbinary' => 'bin',
                'image/bmp' => 'bmp',
                'image/x-bmp' => 'bmp',
                'image/x-bitmap' => 'bmp',
                'image/x-xbitmap' => 'bmp',
                'image/x-win-bitmap' => 'bmp',
                'image/x-windows-bmp' => 'bmp',
                'image/ms-bmp' => 'bmp',
                'image/x-ms-bmp' => 'bmp',
                'application/bmp' => 'bmp',
                'application/x-bmp' => 'bmp',
                'application/x-win-bitmap' => 'bmp',
                'application/cdr' => 'cdr',
                'application/coreldraw' => 'cdr',
                'application/x-cdr' => 'cdr',
                'application/x-coreldraw' => 'cdr',
                'image/cdr' => 'cdr',
                'image/x-cdr' => 'cdr',
                'zz-application/zz-winassoc-cdr' => 'cdr',
                'application/mac-compactpro' => 'cpt',
                'application/pkix-crl' => 'crl',
                'application/pkcs-crl' => 'crl',
                'application/x-x509-ca-cert' => 'crt',
                'application/pkix-cert' => 'crt',
                'text/css' => 'css',
                'text/x-comma-separated-values' => 'csv',
                'text/comma-separated-values' => 'csv',
                'application/vnd.msexcel' => 'csv',
                'application/x-director' => 'dcr',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
                'application/x-dvi' => 'dvi',
                'message/rfc822' => 'eml',
                'application/x-msdownload' => 'exe',
                'video/x-f4v' => 'f4v',
                'audio/x-flac' => 'flac',
                'video/x-flv' => 'flv',
                'image/gif' => 'gif',
                'application/gpg-keys' => 'gpg',
                'application/x-gtar' => 'gtar',
                'application/x-gzip' => 'gzip',
                'application/mac-binhex40' => 'hqx',
                'application/mac-binhex' => 'hqx',
                'application/x-binhex40' => 'hqx',
                'application/x-mac-binhex40' => 'hqx',
                'text/html' => 'html',
                'image/x-icon' => 'ico',
                'image/x-ico' => 'ico',
                'image/vnd.microsoft.icon' => 'ico',
                'text/calendar' => 'ics',
                'application/java-archive' => 'jar',
                'application/x-java-application' => 'jar',
                'application/x-jar' => 'jar',
                'image/jp2' => 'jp2',
                'video/mj2' => 'jp2',
                'image/jpx' => 'jp2',
                'image/jpm' => 'jp2',
                'image/jpeg' => 'jpeg',
                'image/pjpeg' => 'jpeg',
                'application/x-javascript' => 'js',
                'application/json' => 'json',
                'text/json' => 'json',
                'application/vnd.google-earth.kml+xml' => 'kml',
                'application/vnd.google-earth.kmz' => 'kmz',
                'text/x-log' => 'log',
                'audio/x-m4a' => 'm4a',
                'audio/mp4' => 'm4a',
                'application/vnd.mpegurl' => 'm4u',
                'audio/midi' => 'mid',
                'application/vnd.mif' => 'mif',
                'video/quicktime' => 'mov',
                'video/x-sgi-movie' => 'movie',
                'audio/mpeg' => 'mp3',
                'audio/mpg' => 'mp3',
                'audio/mpeg3' => 'mp3',
                'audio/mp3' => 'mp3',
                'video/mp4' => 'mp4',
                'video/mpeg' => 'mpeg',
                'application/oda' => 'oda',
                'audio/ogg' => 'ogg',
                'video/ogg' => 'ogg',
                'application/ogg' => 'ogg',
                'font/otf' => 'otf',
                'application/x-pkcs10' => 'p10',
                'application/pkcs10' => 'p10',
                'application/x-pkcs12' => 'p12',
                'application/x-pkcs7-signature' => 'p7a',
                'application/pkcs7-mime' => 'p7c',
                'application/x-pkcs7-mime' => 'p7c',
                'application/x-pkcs7-certreqresp' => 'p7r',
                'application/pkcs7-signature' => 'p7s',
                'application/pdf' => 'pdf',
                'application/octet-stream' => 'pdf',
                'application/x-x509-user-cert' => 'pem',
                'application/x-pem-file' => 'pem',
                'application/pgp' => 'pgp',
                'application/x-httpd-php' => 'php',
                'application/php' => 'php',
                'application/x-php' => 'php',
                'text/php' => 'php',
                'text/x-php' => 'php',
                'application/x-httpd-php-source' => 'php',
                'image/png' => 'png',
                'image/x-png' => 'png',
                'application/powerpoint' => 'ppt',
                'application/vnd.ms-powerpoint' => 'ppt',
                'application/vnd.ms-office' => 'ppt',
                'application/msword' => 'ppt',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
                'application/x-photoshop' => 'psd',
                'image/vnd.adobe.photoshop' => 'psd',
                'audio/x-realaudio' => 'ra',
                'audio/x-pn-realaudio' => 'ram',
                'application/x-rar' => 'rar',
                'application/rar' => 'rar',
                'application/x-rar-compressed' => 'rar',
                'audio/x-pn-realaudio-plugin' => 'rpm',
                'application/x-pkcs7' => 'rsa',
                'text/rtf' => 'rtf',
                'text/richtext' => 'rtx',
                'video/vnd.rn-realvideo' => 'rv',
                'application/x-stuffit' => 'sit',
                'application/smil' => 'smil',
                'text/srt' => 'srt',
                'image/svg+xml' => 'svg',
                'application/x-shockwave-flash' => 'swf',
                'application/x-tar' => 'tar',
                'application/x-gzip-compressed' => 'tgz',
                'image/tiff' => 'tiff',
                'font/ttf' => 'ttf',
                'text/plain' => 'txt',
                'text/x-vcard' => 'vcf',
                'application/videolan' => 'vlc',
                'text/vtt' => 'vtt',
                'audio/x-wav' => 'wav',
                'audio/wave' => 'wav',
                'audio/wav' => 'wav',
                'application/wbxml' => 'wbxml',
                'video/webm' => 'webm',
                'image/webp' => 'webp',
                'audio/x-ms-wma' => 'wma',
                'application/wmlc' => 'wmlc',
                'video/x-ms-wmv' => 'wmv',
                'video/x-ms-asf' => 'wmv',
                'font/woff' => 'woff',
                'font/woff2' => 'woff2',
                'application/xhtml+xml' => 'xhtml',
                'application/excel' => 'xl',
                'application/msexcel' => 'xls',
                'application/x-msexcel' => 'xls',
                'application/x-ms-excel' => 'xls',
                'application/x-excel' => 'xls',
                'application/x-dos_ms_excel' => 'xls',
                'application/xls' => 'xls',
                'application/x-xls' => 'xls',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
                'application/vnd.ms-excel' => 'xlsx',
                'application/xml' => 'xml',
                'text/xml' => 'xml',
                'text/xsl' => 'xsl',
                'application/xspf+xml' => 'xspf',
                'application/x-compress' => 'z',
                'application/x-zip' => 'zip',
                'application/zip' => 'zip',
                'application/x-zip-compressed' => 'zip',
                'application/s-compressed' => 'zip',
                'multipart/x-zip' => 'zip',
                'text/x-scriptzsh' => 'zsh',
            ];

            return isset($mime_map[$mime]) ? $mime_map[$mime] : false;
        }

        public static function getLastCurlHandlerBeforeRequest() {
            return self::$last_curl_handler_before_request;
        }

        public static function isJSON($string) {
            return is_string($string) && is_array(json_decode($string, true)) && (json_last_error() == JSON_ERROR_NONE) ? true : false;
        }

        private static function getHeaders($headers) {
            $headers = preg_replace('/^\r\n/m', '', $headers);
            $headers = preg_replace('/\r\n\s+/m', ' ', $headers);
            preg_match_all('/^([^: ]+):\s(.+?(?:\r\n\s(?:.+?))*)?\r\n/m', $headers . "\r\n", $matches);

            $result = array();
            foreach ($matches[1] as $key => $value)
                $result[$value] = (array_key_exists($value, $result) ? $result[$value] . "\n" : '') . $matches[2][$key];

            return $result;
        }

        public static function getFileExtension($file) {
            $parts = explode('.', $file);
            return end($parts);
        }

        public static function isLinux(&$osName = "") {
            $osName = PHP_OS;
            return !self::isWindows($osName);
        }

        public static function compareFloats($f1, $f2, $precision = 6) {
            $f1 = round($f1, $precision);
            $f2 = round($f2, $precision);
            return abs($f1 - $f2) < (1 / pow(10, $precision));
        }

        public static function isWindows(&$osName = "") {
            $osName = PHP_OS;
            return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        }

        public static function timestampWithMillis() {
            return round(microtime(true) * 1000);
        }

        public static function uuidV4($strtoupper = true) {
            $ret = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    // 32 bits for "time_low"
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    // 16 bits for "time_mid"
                    mt_rand(0, 0xffff),
                    // 16 bits for "time_hi_and_version",
                    // four most significant bits holds version number 4
                    mt_rand(0, 0x0fff) | 0x4000,
                    // 16 bits, 8 bits for "clk_seq_hi_res",
                    // 8 bits for "clk_seq_low",
                    // two most significant bits holds zero and one for variant DCE1.1
                    mt_rand(0, 0x3fff) | 0x8000,
                    // 48 bits for "node"
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
            if ($strtoupper) {
                return strtoupper($ret);
            }
            return strtolower($ret);
        }

        public static function println($msg) {
            echo self::dateWithMillis() . ' - ' . $msg . PHP_EOL;
        }

        public static function err_println($msg) {
            self::println('ERROR: ' . $msg);
        }

        public static function warn_println($msg) {
            self::println('WARNING: ' . $msg);
        }

        public static function randomIndexFromRange($percents) {
            //{"cvv_finder":70, "periodical_checker":30}
            $ranges = [];
            $offset = 0;
            foreach ($percents as $key => $percent) {
                $offset += $percent;
                $ranges[$key] = $offset;
            }
            $randInt = random_int(0, 100);
            foreach ($ranges as $key => $number) {
                if ($randInt <= $number) {
                    return $key;
                }
            }
            return false;
        }

        public static function randomBool() {
            return random_int(0, 1) === 1;
        }

        /**
         * Generate a random string, using a cryptographically secure 
         * pseudorandom number generator (random_int)
         * 
         * For PHP 7, random_int is a PHP core function
         * For PHP 5.x, depends on https://github.com/paragonie/random_compat
         * 
         * @param int $length      How many characters do we want?
         * @param string $keyspace A string of all possible characters
         *                         to select from
         * @return string
         */
        public static function generateHash($length = 64, $upper = false, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
            $pieces = [];
            $max = mb_strlen($keyspace, '8bit') - 1;
            for ($i = 0; $i < $length; ++$i) {
                $pieces [] = $keyspace[random_int(0, $max)];
            }
            $ret = implode('', $pieces);
            return $upper ? strtoupper($ret) : $ret;
        }

        public static function emptyDir($dirPath, $ignoreSubDirs = [], $removeRootDir = false) {
            //$files = glob(rtrim($dirPath, '/') . "/*"); // get all file names
            $files = array_diff(scandir(rtrim($dirPath, '/')), array('.', '..'));
            array_walk($files, function (&$value, $key) use ($dirPath) {
                $value = $dirPath . '/' . $value;
            });
            foreach ($files as $filePath) { // iterate files
                if (is_dir($filePath) && !in_array(basename($filePath), $ignoreSubDirs)) {
                    self::emptyDir($filePath, $ignoreSubDirs, true);
                } elseif (is_file($filePath)) {
                    unlink($filePath); // delete file
                }
            }
            if ($removeRootDir) {
                rmdir($dirPath);
            }
        }

        public static function zipFile($file, $dstZipFilePath) {
            $zip = new \ZipArchive();
            $zip->open($dstZipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            $zip->addFile(realpath($file), basename($file));
            $zip->close();
        }

        public static function zipDir($zipDir, $dstZipFilePath) {
// Get real path for our folder
            $rootPath = realpath($zipDir);
            $zip = new \ZipArchive();
            $zip->open($dstZipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($rootPath), \RecursiveIteratorIterator::LEAVES_ONLY);
            $directoryChecksum = "";
            foreach ($files as $name => $file) {
                // Skip directories (they would be added automatically)
                if (!$file->isDir()) {
                    // Get real and relative path for current file
                    $filePath = $file->getRealPath();
                    $relativePath = str_replace('\\', '/', substr($filePath, strlen($rootPath) + 1));
                    $directoryChecksum .= md5_file($filePath) . $relativePath;
                    $zip->addFile($filePath, $relativePath);
                }
            }
            $zip->close();
            return md5($directoryChecksum);
        }

        public static function deleteDir($dirPath, $deleteRootDir = true) {
            if (!is_dir($dirPath)) {
                throw new InvalidArgumentException("$dirPath must be a directory");
            }
            $files = glob(trim($dirPath, '/') . '/{,.}[!.,!..]*', GLOB_MARK | GLOB_BRACE);
            foreach ($files as $filePath) {
                if (is_dir($filePath)) {
                    self::deleteDir($filePath);
                } else {
                    unlink($filePath);
                }
            }
            if ($deleteRootDir) {
                rmdir($dirPath);
            }
        }

        public static function getRunningProcessCountByProcessNameContains($pname) {
            if (self::isLinux()) {
                $pname = implode('+', str_split($pname));
                return intval(shell_exec('pgrep -f -c "' . $pname . '"'));
            }
            if (self::isWindows()) {
                $cmd = '$(Get-WmiObject Win32_Process | select commandline | Select-String -Pattern "' . $pname . '" | Measure-Object -Line).Lines';
                $cmd = 'powershell.exe "' . $cmd . '"';
                return intval(shell_exec($cmd)) - 2;
            }
            return false;
        }

        public static function runDirectoryPhpFilesInBackground($dir) {
            $fileNames = self::getDirectoryFiles(DOMAIN_CONTROLLERS_DIR . $dir, 'php', true);
            foreach ($fileNames as $fn) {
                \system\util\Util::runBackground('php ' . START_PHP . ' ' . HOST . ' /_sys_' . $dir . '/' . basename($fn, ".php"));
            }
        }

        public static function runBackground($command, $forwardOutputToFile = "", $delaySeconds = 0, &$escapeshellcmd = null) {
            if (!self::isLinux()) {
                pclose(popen("start $command", "r"));
            } else {
                $outpout = [];
                $escapeshellcmd = escapeshellcmd($command);
                if ($delaySeconds > 0) {
                    $escapeshellcmd = '(sleep  ' . round($delaySeconds, 2) . 's ; ' . $escapeshellcmd . ') ';
                }
                if (empty($forwardOutputToFile)) {
                    $escapeshellcmd = $escapeshellcmd . ' >/dev/null 2>&1 &';
                } else {
                    $escapeshellcmd = $escapeshellcmd . ' >>' . $forwardOutputToFile . ' &';
                }
                return exec($escapeshellcmd, $outpout);
            }
        }

        public static function shuffle_assoc(&$array) {
            $keys = array_keys($array);

            shuffle($keys);

            foreach ($keys as $key) {
                $new[$key] = $array[$key];
            }

            $array = $new;

            return true;
        }

        public static function checkUploadedFile($requestFileVariableName) {
            if (!isset($_FILES[$requestFileVariableName])) {
                return 'No file was uploaded.';
            }
            $error = $_FILES[$requestFileVariableName]['error'];
            switch ($error) {
                case UPLOAD_ERR_OK :
                    return true;
                case UPLOAD_ERR_INI_SIZE :
                    return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';

                case UPLOAD_ERR_FORM_SIZE :
                    return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';

                case UPLOAD_ERR_PARTIAL :
                    return 'The uploaded file was only partially uploaded.';

                case UPLOAD_ERR_NO_FILE :
                    return 'No file was uploaded.';

                case UPLOAD_ERR_NO_TMP_DIR :
                    return 'Missing a temporary folder. Introduced in PHP 4.3.10 and PHP 5.0.3.';

                case UPLOAD_ERR_CANT_WRITE :
                    return 'Failed to write file to disk. Introduced in PHP 5.1.0.';

                case UPLOAD_ERR_EXTENSION :
                    return 'File upload stopped by extension. Introduced in PHP 5.2.0.';

                default :
                    return false;
            }
        }

        public static function cryptoJsAesDecrypt($passphrase, $jsonString) {
            $jsondata = json_decode($jsonString, true);
            if (empty($jsondata) || !array_key_exists('ct', $jsondata)) {
                return false;
            }
            $salt = hex2bin($jsondata["s"]);
            $ct = base64_decode($jsondata["ct"]);
            $iv = hex2bin($jsondata["iv"]);
            $concatedPassphrase = $passphrase . $salt;
            $md5 = array();
            $md5[0] = md5($concatedPassphrase, true);
            $result = $md5[0];
            for ($i = 1; $i < 3; $i++) {
                $md5[$i] = md5($md5[$i - 1] . $concatedPassphrase, true);
                $result .= $md5[$i];
            }
            $key = substr($result, 0, 32);
            $data = openssl_decrypt($ct, 'aes-256-cbc', $key, true, $iv);
            return $data;
        }

        public static function getJsonLastErrorMessage() {
            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    return ' - No errors';
                case JSON_ERROR_DEPTH:
                    return ' - Maximum stack depth exceeded';
                case JSON_ERROR_STATE_MISMATCH:
                    return ' - Underflow or the modes mismatch';
                case JSON_ERROR_CTRL_CHAR:
                    return ' - Unexpected control character found';
                case JSON_ERROR_SYNTAX:
                    return ' - Syntax error, malformed JSON';
                case JSON_ERROR_UTF8:
                    return ' - Malformed UTF-8 characters, possibly incorrectly encoded';
                default:
                    return ' - Unknown error';
            }
        }

        public static function resizeImageToGivenType($img, $newfilename, $w, $h, $type) {

//Check if GD extension is loaded
            if (!extension_loaded('gd') && !extension_loaded('gd2')) {
                trigger_error("GD is not loaded", E_USER_WARNING);
                return false;
            }

//Get Image size info
            $imgInfo = getimagesize($img);
            switch ($imgInfo[2]) {
                case IMAGETYPE_GIF :
                    $im = imagecreatefromgif($img);
                    break;
                case IMAGETYPE_JPEG :
                    $im = imagecreatefromjpeg($img);
                    break;
                case IMAGETYPE_PNG :
                    $im = imagecreatefrompng($img);
                    break;
                default :
                    trigger_error('Unsupported filetype!', E_USER_WARNING);
                    break;
            }

//If image dimension is smaller, do not resize
            if ($imgInfo[0] <= $w && $imgInfo[1] <= $h) {
                $nHeight = $imgInfo[1];
                $nWidth = $imgInfo[0];
            } else {
//yeah, resize it, but keep it proportional
                if ($w / $imgInfo[0] < $h / $imgInfo[1]) {
                    $nWidth = $w;
                    $nHeight = $imgInfo[1] * ($w / $imgInfo[0]);
                } else {
                    $nWidth = $imgInfo[0] * ($h / $imgInfo[1]);
                    $nHeight = $h;
                }
            }

            $nWidth = round($nWidth);
            $nHeight = round($nHeight);

            $newImg = imagecreatetruecolor($nWidth, $nHeight);
            $backgroundColor = imagecolorallocate($newImg, 255, 255, 255);
            imagefill($newImg, 0, 0, $backgroundColor);

            imagecopyresampled($newImg, $im, 0, 0, 0, 0, $nWidth, $nHeight, $imgInfo[0], $imgInfo[1]);

            if (strtolower($type) === 'png') {
                imagepng($newImg, $newfilename);
            } else if (strtolower($type) === 'gif') {
                imagegif($newImg, $newfilename);
            } else if (strtolower($type) === 'jpg') {
                imagejpeg($newImg, $newfilename);
            } else {
                trigger_error('Failed resize image!', E_USER_WARNING);
            }
            return true;
        }

        /**
         * Copy a file, or recursively copy a folder and its contents
         * @author      Aidan Lister <aidan@php.net>
         * @version     1.0.1
         * @link        http://aidanlister.com/2004/04/recursively-copying-directories-in-php/
         * @param       string   $source    Source path
         * @param       string   $dest      Destination path
         * @param       int      $permissions New folder creation permissions
         * @return      bool     Returns true on success, false on failure
         */
        public static function copyDirectory($source, $dest, $permissions = 0755) {
            $sourceHash = self::hashDirectory($source);
// Check for symlinks
            if (is_link($source)) {
                return symlink(readlink($source), $dest);
            }

// Simple copy for a file
            if (is_file($source)) {
                return copy($source, $dest);
            }

// Make destination directory
            if (!is_dir($dest)) {
                mkdir($dest, $permissions);
            }

// Loop through the folder
            $dir = dir($source);
            while (false !== $entry = $dir->read()) {
// Skip pointers
                if ($entry == '.' || $entry == '..') {
                    continue;
                }

// Deep copy directories
                if ($sourceHash != self::hashDirectory($source . "/" . $entry)) {
                    self::copyDirectory("$source/$entry", "$dest/$entry", $permissions);
                }
            }

// Clean up
            $dir->close();
            return true;
        }

// In case of coping a directory inside itself, there is a need to hash check the directory otherwise and infinite loop of coping is generated

        public static function hashDirectory($directory) {
            if (!is_dir($directory)) {
                return false;
            }

            $files = array();
            $dir = dir($directory);

            while (false !== ($file = $dir->read())) {
                if ($file != '.' and $file != '..') {
                    if (is_dir($directory . '/' . $file)) {
                        $files[] = self::hashDirectory($directory . '/' . $file);
                    } else {
                        $files[] = md5_file($directory . '/' . $file);
                    }
                }
            }

            $dir->close();

            return md5(implode('', $files));
        }
    }

}