<?php

/**
 *
 * @author       Adrian de la Rosa Bretin
 * @version      1.0 (04/08/2013)
 *
 * @copyright    La Cuarta Edad
 *
 */

namespace Vendor\MVC;

class Response
{

    public static $mimeTypes
        = array(
            'html' => array('text/html', '*/*'),
            'json' => 'application/json',
            'xml' => array('application/xml', 'text/xml'),
            'rss' => 'application/rss+xml',
            'bin' => 'application/octet-stream',
            'csv' => 'text/csv',
            'eot' => 'application/vnd.ms-fontobject',
            'flv' => 'video/x-flv',
            'gtar' => 'application/x-gtar',
            'gz' => 'application/x-gzip',
            'bz2' => 'application/x-bzip',
            'ico' => 'image/x-icon',
            'js' => 'application/javascript',
            'latex' => 'application/x-latex',
            'otf' => 'font/otf',
            'pdf' => 'application/pdf',
            'pgn' => 'application/x-chess-pgn',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            'swf' => 'application/x-shockwave-flash',
            'tar' => 'application/x-tar',
            'ttc' => 'font/ttf',
            'ttf' => 'font/ttf',
            'zip' => 'application/zip',
            'mp3' => 'audio/mpeg',
            'mpga' => 'audio/mpeg',
            'ogg' => 'audio/ogg',
            'oga' => 'audio/ogg',
            'spx' => 'audio/ogg',
            'wav' => 'audio/x-wav',
            'aac' => 'audio/aac',
            'c' => 'text/plain',
            'css' => 'text/css',
            'h' => 'text/plain',
            'htm' => array('text/html', '*/*'),
            'ics' => 'text/calendar',
            'rtf' => 'text/rtf',
            'rtx' => 'text/richtext',
            'tpl' => 'text/template',
            'txt' => 'text/plain',
            'text' => 'text/plain',
            'avi' => 'video/x-msvideo',
            'mov' => 'video/quicktime',
            'movie' => 'video/x-sgi-movie',
            'mpe' => 'video/mpeg',
            'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg',
            'qt' => 'video/quicktime',
            'ogv' => 'video/ogg',
            'webm' => 'video/webm',
            'mp4' => 'video/mp4',
            'm4v' => 'video/mp4',
            'f4v' => 'video/mp4',
            'f4p' => 'video/mp4',
            'm4a' => 'audio/mp4',
            'f4a' => 'audio/mp4',
            'f4b' => 'audio/mp4',
            'gif' => 'image/gif',
            'ief' => 'image/ief',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'pbm' => 'image/x-portable-bitmap',
            'pgm' => 'image/x-portable-graymap',
            'png' => 'image/png',
            'pnm' => 'image/x-portable-anymap',
            'ppm' => 'image/x-portable-pixmap',
            'ras' => 'image/cmu-raster',
            'rgb' => 'image/x-rgb',
            'tif' => 'image/tiff',
            'tiff' => 'image/tiff',
            'xbm' => 'image/x-xbitmap',
            'xpm' => 'image/x-xpixmap',
            'xwd' => 'image/x-xwindowdump',
            'mime' => 'www/mime',
            'javascript' => 'application/javascript',
            'form' => 'application/x-www-form-urlencoded',
            'file' => 'multipart/form-data',
            'xhtml' => array(
                'application/xhtml+xml',
                'application/xhtml',
                'text/xhtml'
            ),
            'xhtml-mobile' => 'application/vnd.wap.xhtml+xml',
            'atom' => 'application/atom+xml',
            'woff' => 'application/x-font-woff',
            'webp' => 'image/webp',
            'appcache' => 'text/cache-manifest',
            'manifest' => 'text/cache-manifest',
            'rdf' => 'application/xml',
            'crx' => 'application/x-chrome-extension',
            'oex' => 'application/x-opera-extension',
            'xpi' => 'application/x-xpinstall',
            'webapp' => 'application/x-web-app-manifest+json',
        );

    public static $statusCodes
        = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Time-out',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Large',
            415 => 'Unsupported Media Type',
            416 => 'Requested range not satisfiable',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Time-out'
        );

    private $_headers = array();
    private $_body = '';
    private $_statusCode = 200;

    public function body($content = null)
    {
        if (!isset($content)) {
            return $this->_body;
        }

        $this->_body = $content;

        return $this;
    }

    public function cache($since, $time = '+1 day')
    {
        if (!is_int($time)) {
            $time = strtotime($time);
        }
        $this->_headers['Cache-Control'] = 'public';
        $this->_headers['Date'] = gmdate("D, j M Y G:i:s ") . 'GMT';
        $this->modified($since);
        $this->expires($time);

        return $this;
    }

    public function disableCache()
    {
        $this->_headers['Expires'] = 'Mon, 26 Jul 1997 05:00:00 GMT';
        $this->_headers['Last-Modified'] = gmdate("D, d M Y H:i:s") . " GMT";
        $this->_headers['Cache-Control']
            = 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0';

        return $this;
    }

    public function expires($time = null)
    {
        if (!isset($time)) {
            return $this->_headers['Expires'];
        }

        $this->_headers['Expires'] = gmdate('D, j M Y H:i:s', $time) . ' GMT';

        return $this;
    }

    public function header($header = null, $value = null)
    {
        if (!isset($header)) {
            return $this->_headers;
        }

        $this->_headers[$header] = $value;

        return $this;
    }

    public function modified($time = null)
    {
        if (!isset($time)) {
            return $this->_headers['Last-Modified'];
        }

        $this->_headers['Last-Modified'] = gmdate('D, j M Y H:i:s', $time)
            . ' GMT';

        return $this;
    }

    public function send()
    {
        global $_SERVER;

        header(
            "{$_SERVER['SERVER_PROTOCOL']} {$this->_statusCode} "
            . self::$statusCodes[$this->_statusCode]
        );

        foreach ($this->_headers as $key => $value) {
            if (is_array($value)) {
                $value = implode(", ", $value);
            }
            header("{$key}: {$value}");
        }

        return $this->_body;
    }

    public function statusCode($code = null)
    {
        if (!isset($code)) {
            return $this->_statusCode;
        }

        $this->_statusCode = $code;

        return $this;
    }

    public function type($contentType = null)
    {
        if (!isset($contentType)) {
            return $this->_headers['Content-Type'];
        }

        if (isset(self::$mimeTypes[$contentType])) {
            $this->_headers["Content-Type"] = self::$mimeTypes[$contentType];
        }

        return $this;
    }

}