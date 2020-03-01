<?php declare(strict_types=1);

namespace Limepie;

class Request
{
    public $rawBody;

    public $bodies = [];

    public $urlParts = [];

    public $url;

    public $scheme;

    public $host;

    public $path;

    public $uri;

    public $segments = [];

    public $parameters = [];

    public $requestId;

    public $httpMethodParameterOverride = false;

    public $fileKeys = ['error', 'name', 'size', 'tmp_name', 'type'];

    public $language = 'ko';

    public $locale = 'ko_KR';

    public $locales = [
        'ko' => 'ko_KR',
        'en' => 'en_US',
        'zh' => 'zh_CN',
        'ja' => 'ja_JP',
    ];

    final public function __construct()
    {
        $this->url      = $this->getUrl();
        $this->urlParts = \parse_url($this->url);
        $this->rawBody  = \file_get_contents('php://input');
        $this->bodies   = $this->getBodyAll();
        $this->uri      = $this->getRequestUri();

        $tmp         = \explode('?', $this->uri, 2);
        $this->path  = $tmp[0] ?? '';
        $this->query = $tmp[1] ?? '';

        if ($this->path) {
            $this->segments = \explode('/', \trim($this->path, '/'));
            for ($i = 0, $j = \count($this->segments); $i < $j; $i += 2) {
                $this->parameters[$this->segments[$i]] = $this->segments[$i + 1] ?? '';
            }
        }

        $this->language = $this->getBestLanguage();

        if (true === isset($this->locales[$this->language])) {
            $this->locale = $this->locales[$this->language];
        }

        if (true === \method_exists($this, '__init')) {
            $this->__init();
        } elseif (true === \method_exists($this, '__init__')) {
            $this->__init__();
        }
    }

    public function setLocale($language)
    {
        $this->language = \explode('_', $language)[0];

        if (true === isset($this->locales[$this->language])) {
            $this->locale = $this->locales[$this->language];
        }
    }

    public function bindTextDomain($domain, $path)
    {
        $charset = 'UTF-8';

        \setlocale(\LC_MESSAGES, $this->locale . '.' . $charset);
        \bindtextdomain($domain, $path);
        \bind_textdomain_codeset($domain, $charset);
        \textdomain($domain);
        //pr($domain, $path, $domain, "{$locale}.{$charset}");
        return $this->locale . '.' . $charset;
    }

    /**
     * Gets HTTP schema (http/https)
     */
    public function getScheme() : string
    {
        if ($this->scheme) {
            return $this->scheme;
        }

        $https = $this->getServer('HTTP_X_FORWARDED_PROTO');

        if ($https) {
            $this->this->scheme = $https;
        } else {
            $https = $this->getServer('HTTPS');

            if ($https) {
                if ('off' === $https) {
                    $this->scheme = 'http';
                } else {
                    $this->scheme = 'https';
                }
            } else {
                $this->scheme = 'http';
            }
        }
        return (string) $this->scheme;

    }

    public function getHost() : string
    {
        if ($this->host) {
            return $this->host;
        }

        $this->host = $this->getServer('HTTP_HOST');

        if (!$this->host) {
            $this->host = $this->getServer('SERVER_NAME');

            if (!$this->host) {
                $this->host = $this->getServer('SERVER_ADDR');
            }
        }

        return (string) $this->host;
    }

    public function getRequestUri()
    {
        if ($this->uri) {
            return $this->uri;
        }
        $parts     = \explode('?', (string) $this->getServer('REQUEST_URI'), 2);
        $this->uri = \trim($parts[0], '/');

        if ($this->uri) {
            $this->uri = '/' . $this->uri;
        }

        if (true === isset($parts[1]) && $parts[1]) {
            $this->uri .= '?' . $parts[1];
        }

        return $this->uri;
    }

    public function getPath(?int $step = null, $length = null)
    {
        if (null !== $step) {
            $segments = \array_slice($this->segments, $step, $length);

            if ($segments) {
                return '/' . \implode('/', $segments);
            }

            return '';
        }

        return $this->path;
    }

    public function getQueryString($append = '')
    {
        if($this->query) {
            return $append . \ltrim($this->query, $append);
        } else {
            return '';
        }
    }

    public function getSegments() : array
    {
        return $this->segments;
    }

    public function getSegment($index)
    {
        return $this->segments[$index] ?? '';
    }

    public function getParameters() : array
    {
        return $this->parameters;
    }

    public function getParameter($key)
    {
        return $this->parameters[$key] ?? '';
    }

    public function getUrl()
    {
        $url = '';
        $url .= $this->getSchemeHost();
        $url .= $this->getRequestUri();

        return $url;
    }

    public function getSchemeHost()
    {
        return $this->getScheme() . '://' . $this->getHost();
    }

    public function getBodies()
    {
        return $this->bodies;
    }

    // alias application getProperties
    public function getApplicationProperties() : array
    {
        if (true === Di::has('application')) {
            return Di::get('application')->getProperties();
        }
        // ERRORCODE: 20007, service provider not found
        throw new Exception('"application" service provider not found', 20007);
    }

    // alias application getNamespace
    public function getApplicationNamespaceName()
    {
        if (true === Di::has('application')) {
            return Di::get('application')->getNamespaceName();
        }
        // ERRORCODE: 20009, service provider not found
        throw new Exception('"application" service provider not found', 20009);
    }

    // alias application getController
    public function getApplicationControllerName()
    {
        if (true === Di::has('application')) {
            return Di::get('application')->getControllerName();
        }
        // ERRORCODE: 20009, service provider not found
        throw new Exception('"application" service provider not found', 20009);
    }

    // alias application getAction
    public function getApplicationActionName()
    {
        if (true === Di::has('application')) {
            return Di::get('application')->getActionName();
        }
        // ERRORCODE: 20009, service provider not found
        throw new Exception('"application" service provider not found', 20010);
    }

    // alias application getPath
    public function getApplicationPath()
    {
        if (true === Di::has('application')) {
            return Di::get('application')->getPath();
        }
        // ERRORCODE: 20009, service provider not found
        throw new Exception('"application" service provider not found', 20009);
    }

    public function extractDomain($domain)
    {
        $matches = [];

        if (1 === \preg_match("/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i", $domain, $matches)) {
            return $matches['domain'];
        }

        return $domain;
    }

    public function getDomain($host = null)
    {
        if (null === $host) {
            $host = $this->getHost();
        }

        return $this->extractDomain($host);
    }

    public function getDefaultHost()
    {
        return $this->getScheme() . '://www.' . $this->getDomain();
    }

    public function getSubDomain($returnDefault = true)
    {
        $host      = $this->getHost();
        $domain    = $this->extractDomain($host);
        $subDomain = \preg_replace('#(\.)?' . $domain . '#', '', $host);

        if (false === $returnDefault) {
            return $subDomain;
        }

        return $subDomain ?: (\Limepie\is_cli() ? 'cli' : 'www');
    }

    public function getBestAccept() : string
    {
        return $this->getBestQuality($this->getAcceptableContent(), 'accept');
    }

    /**
     * Gets an array with mime/types and their quality accepted by the browser/client from _SERVER["HTTP_ACCEPT"]
     */
    public function getAcceptableContent() : array
    {
        return $this->getQualityHeader('HTTP_ACCEPT', 'accept');
    }

    /**
     * Gets content type which request has been made
     */
    public function getContentType() : ?string
    {
        $contentType = $this->getServer('CONTENT_TYPE');

        if ($contentType) {
            return $contentType;
        }
        // @see https://bugs.php.net/bug.php?id=66606
        $httpContentType = $this->getServer('HTTP_CONTENT_TYPE');

        if ($httpContentType) {
            return $httpContentType;
        }

        return null;
    }

    /**
     * Gets HTTP raw request body
     */
    public function getRawBody() : string
    {
        return $this->rawBody;
    }

    /**
     * Gets web page that refers active request. ie: http://www.google.com
     */
    public function getHTTPReferer() : string
    {
        return $this->getServer('HTTP_REFERER');
    }

    /**
     * Gets decoded JSON HTTP raw request body
     * return <\stdClass> | array | bool
     *
     * @param bool $associative
     */
    public function getJsonRawBody(bool $associative = false)
    {
        $rawBody = $this->getRawBody();

        if ('string' !== \gettype($rawBody)) {
            return false;
        }

        return \json_decode($rawBody, $associative);
    }

    /**
     * Gets best language accepted by the browser/client from
     * _SERVER["HTTP_ACCEPT_LANGUAGE"]
     */
    public function getBestLanguage()
    {
        return $this->getBestQuality($this->getLanguages(), 'language');
    }

    /**
     * Gets languages array and their quality accepted by the browser/client from _SERVER["HTTP_ACCEPT_LANGUAGE"]
     */
    public function getLanguages() : array
    {
        return $this->getQualityHeader('HTTP_ACCEPT_LANGUAGE', 'language');
    }

    /**
     * Gets HTTP method which request has been made
     *
     * If the X-HTTP-Method-Override header is set, and if the method is a POST,
     * then it is used to determine the "real" intended HTTP method.
     *
     * The _method request parameter can also be used to determine the HTTP method,
     * but only if setHttpMethodParameterOverride(true) has been called.
     *
     * The method is always an uppercased string.
     */
    final public function getMethod() : string
    {
        $returnMethod  = '';
        $requestMethod = $this->getServer('REQUEST_METHOD');

        if ($requestMethod) {
            $returnMethod = \strtoupper($returnMethod);

            if ('POST' === $returnMethod) {
                $overridedMethod = $this->getHeader('X-HTTP-METHOD-OVERRIDE');

                if (0 === \strlen($overridedMethod)) { //empty
                    $returnMethod = \strtoupper($overridedMethod);
                } elseif ($this->httpMethodParameterOverride) {
                    $returnMethod = \strtoupper($_REQUEST['_method']);
                }
            }
        }

        if (!$this->isValidHttpMethod($returnMethod)) {
            return 'GET';
        }

        return $returnMethod;
    }

    public function getHeader($key) : string
    {
    }

    /**
     * Gets HTTP user agent used to made the request
     */
    public function getUserAgent() : string
    {
        return $this->getServer('HTTP_USER_AGENT');
    }

    /**
     * Gets information about the port on which the request is made.
     */
    public function getPort() : int
    {
        $host = $this->getServer('HTTP_HOST');

        if ($host) {
            if (false !== \strpos($host, ':')) {
                $pos = \strrpos(host, ':');

                if (false !== $pos) {
                    return (int) \substr($host, $pos + 1);
                }
            }

            return 'https' === $this->getScheme() ? 443 : 80;
        }

        return (int) $this->getServer('SERVER_PORT');
    }

    /**
     * Gets HTTP URI which request has been made
     */
    final public function getURI() : string
    {
        return $this->getServer('REQUEST_URI');
    }

    public function getServer($key) : ?string
    {
        return $_SERVER[$key] ?? null;
    }

    // get $_REQUEST[$key]
    public function get($key)
    {
        return $_GET[$key] ?? null;
    }

    // get $_PUT[$key]
    public function getPut()
    {
    }

    // get $_GET[$key]
    public function getQuery($key)
    {
        return $_GET[$key] ?? null;
    }

    // get $_POST[$key]
    public function getPost($key)
    {
        return $_POST[$key] ?? null;
    }

    public function isCli()
    {
        return 'cli' === \php_sapi_name();
    }

    public function getRequestMethod()
    {
        return $this->getServer('REQUEST_METHOD');
    }

    public function getRequestId()
    {
        if (!$this->requestId) {
            if (true === isset($_SERVER['HTTP_REQUEST_ID'])) {
                $this->requestId = $_SERVER['HTTP_REQUEST_ID'];
            } else {
                $this->requestId = \Limepie\uniqid(32);
            }
        }

        return $this->requestId;
    }

    public function getBodyAll()
    {
        $rawBody = $this->getRawBody();

        $contentType = $this->getContentType();

        if ($contentType) {
            $contentType = \explode(';', $contentType)[0];
        }

        switch ($contentType) {
            case 'application/x-www-form-urlencoded':
                $parserd = [];
                \parse_str($rawBody, $parserd);

                $this->bodies = $parserd;

                return $this->bodies;

                break;
            case 'multipart/form-data':
                if (
                    'POST' === $_SERVER['REQUEST_METHOD']
                    && (empty($_POST) && empty($_FILES))
                    && 0 < $_SERVER['CONTENT_LENGTH']
                ) {
                    throw new Exception(\sprintf('The server was unable to handle that much POST data (%s bytes) due to its current configuration', $_SERVER['CONTENT_LENGTH']), 20012);
                }

                $this->bodies = $_POST;

                return $this->bodies;

                break;
            case 'application/xml':
                throw new Exception('xml content type not support', 415);

            break;

                break;
            case 'application/json':
            case 'text/javascript':
            default:
                $rawBody = \str_replace(' \\', '', $rawBody);
                $json    = \json_decode($rawBody, true);

                if (0 < \strlen($rawBody)) {
                    $type = \json_last_error();

                    if ($type) {
                        switch ($type) {
                            case \JSON_ERROR_DEPTH:
                                $message = 'Maximum stack depth exceeded';

                            break;
                            case \JSON_ERROR_CTRL_CHAR:
                                $message = 'Unexpected control character found';

                            break;
                            case \JSON_ERROR_SYNTAX:
                                $message = 'Syntax error, malformed JSON';

                            break;
                            case \JSON_ERROR_NONE:
                                $message = 'No errors';

                            break;
                            case \JSON_ERROR_UTF8:
                                $message = 'Malformed UTF-8 characters';

                            break;
                            default:
                                $message = 'Invalid JSON syntax';
                        }

                        throw new Exception($message, 20013);
                    }
                } else {
                    $json = [];
                }

                $this->bodies = $json;

                return $this->bodies;

                break;
        }
    }

    /**
     * Checks whether request has been made using ajax
     */
    public function isAjax() : bool
    {
        return 'XMLHttpRequest' === $this->getServer('HTTP_X_REQUESTED_WITH');
    }

    /**
     * Checks whether HTTP method is CONNECT. if _SERVER["REQUEST_METHOD"]==="CONNECT"
     */
    public function isConnect() : bool
    {
        return 'CONNECT' === $this->getMethod();
    }

    /**
     * Checks whether HTTP method is DELETE. if _SERVER["REQUEST_METHOD"]==="DELETE"
     */
    public function isDelete() : bool
    {
        return 'DELETE' === $this->getMethod();
    }

    /**
     * Checks whether HTTP method is GET. if _SERVER["REQUEST_METHOD"]==="GET"
     */
    public function isGet() : bool
    {
        return 'GET' === $this->getMethod();
    }

    /**
     * Checks whether HTTP method is HEAD. if _SERVER["REQUEST_METHOD"]==="HEAD"
     */
    public function isHead() : bool
    {
        return 'HEAD' === $this->getMethod();
    }

    /**
     * Check if HTTP method match any of the passed methods
     * When strict is true it checks if validated methods are real HTTP methods
     *
     * @param mixed $methods
     * @param bool  $strict
     */
    public function isMethod($methods, bool $strict = false) : bool
    {
        $httpMethod = $this->getMethod();

        if ('string' === \gettype($methods)) {
            if ($strict && !$this->isValidHttpMethod($methods)) {
                throw new Exception('Invalid HTTP method: ' . $methods, 20013);
            }

            return $methods === $httpMethod;
        }

        if ('array' === \gettype($methods)) {
            foreach ($methods as $method) {
                if ($this->isMethod($method, $strict)) {
                    return true;
                }
            }

            return false;
        }

        if ($strict) {
            throw new Exception('Invalid HTTP method: non-string', 20014);
        }

        return false;
    }

    /**
     * Checks whether HTTP method is OPTIONS. if _SERVER["REQUEST_METHOD"]==="OPTIONS"
     */
    public function isOptions() : bool
    {
        return 'OPTIONS' === $this->getMethod();
    }

    /**
     * Checks whether HTTP method is PATCH. if _SERVER["REQUEST_METHOD"]==="PATCH"
     */
    public function isPatch() : bool
    {
        return 'PATCH' === $this->getMethod();
    }

    /**
     * Checks whether HTTP method is POST. if _SERVER["REQUEST_METHOD"]==="POST"
     */
    public function isPost() : bool
    {
        return 'POST' === $this->getMethod();
    }

    /**
     * Checks whether HTTP method is PUT. if _SERVER["REQUEST_METHOD"]==="PUT"
     */
    public function isPut() : bool
    {
        return 'PUT' === $this->getMethod();
    }

    /**
     * Checks whether HTTP method is PURGE (Squid and Varnish support). if _SERVER["REQUEST_METHOD"]==="PURGE"
     */
    public function isPurge() : bool
    {
        return 'PURGE' === $this->getMethod();
    }

    /**
     * Checks whether request has been made using any secure layer
     */
    public function isSecure() : bool
    {
        return 'https' === $this->getScheme();
    }

    /**
     * Checks if the `Request::getHttpHost` method will be use strict validation of host name or not
     */
    public function isStrictHostCheck() : bool
    {
        return $this->strictHostCheck;
    }

    /**
     * Checks whether request has been made using SOAP
     */
    public function isSoap() : bool
    {
        $tmp = $this->getServer('HTTP_SOAPACTION');

        if ($tmp) {
            return true;
        }
        $contentType = $this->getContentType();

        if (0 === \strlen($contentType)) {
            return false !== \strpos($contentType, 'application/soap+xml');
        }

        return false;
    }

    /**
     * Checks whether HTTP method is TRACE. if _SERVER["REQUEST_METHOD"]==="TRACE"
     */
    public function isTrace() : bool
    {
        return 'TRACE' === $this->getMethod();
    }

    /**
     * Checks if a method is a valid HTTP method
     *
     * @param string $method
     */
    public function isValidHttpMethod(string $method) : bool
    {
        switch (\strtoupper($method)) {
            case 'GET':
            case 'POST':
            case 'PUT':
            case 'DELETE':
            case 'HEAD':
            case 'OPTIONS':
            case 'PATCH':
            case 'PURGE': // Squid and Varnish support
            case 'TRACE':
            case 'CONNECT':
                return true;
        }

        return false;
    }

    /**
     * Sets if the `Request::getHttpHost` method must be use strict validation of host name or not
     *
     * @param bool $flag
     */
    public function setStrictHostCheck(bool $flag = true) : self
    {
        $this->strictHostCheck = flag;

        return $this;
    }

    public function convertFileArray($data)
    {
        if (false === \is_array($data)) {
            return $data;
        }

        if (
            false === $this->isFile($data)
            || false === $this->isMultiFile($data)
        ) {
            // \pr([
            //     $data,
            //     [$this->fileKeys, $keys],
            //     [!$this->isFile($data), $this->fileKeys !== $keys],
            //     [!$this->isFile($data), !isset($data['name'])],
            //     [!$this->isMultiFile($data), !\is_array($data['name'] ?? '')],
            // ]);

            return $data;
        }

        $files = $data;

        foreach ($this->fileKeys as $k) {
            unset($files[$k]);
        }

        foreach ($data['name'] as $key => $name) {
            $files[$key] = $this->convertFileArray([
                'error'    => $data['error'][$key],
                'name'     => $name,
                'type'     => $data['type'][$key],
                'tmp_name' => $data['tmp_name'][$key],
                'size'     => $data['size'][$key],
            ]);
        }

        return $files;
    }

    // multi file일때만 true
    public function isMultiFile($array, $isMulti = true)
    {
        if (
            true === isset($array['name'])
            && true === isset($array['type'])
            && true === isset($array['tmp_name'])
            && true === isset($array['error'])
            && true === isset($array['size'])
        ) {
            if (true === \is_array($array['name'])) {
                return true;
            }
        }

        return false;
    }

    // 파일인지, 멀티 체크안하고 파일이면 true
    public function isFile($array)
    {
        if (
            true === isset($array['name'])
            && true === isset($array['type'])
            && true === isset($array['tmp_name'])
            && true === isset($array['error'])
            && true === isset($array['size'])
        ) {
            return true;
        }

        return false;
    }

    public function convertFileInformation($file)
    {
        $file = $this->convertFileArray($file);

        if (true === $this->isFile($file)) {
            if (\UPLOAD_ERR_NO_FILE === $file['error']) {
                $file = null;
            } else {
                $file = [
                    'name'     => $file['name'],
                    'type'     => $file['type'],
                    'tmp_name' => $file['tmp_name'],
                    'error'    => $file['error'],
                    'size'     => $file['size'],
                ];
            }
        } else {
            $file = \array_map([$this, 'convertFileInformation'], $file);
            $file = \array_filter($file);
        }

        return $file;
    }

    public function getFileAll()
    {
        if (true === isset($_FILES) && $_FILES) {
            return $this->convertFileInformation($_FILES);
        }

        return [];
    }

    /**
     * Process a request header and return the one with best quality
     *
     * @param array   $qualityParts
     * @param ?string $name
     */
    final protected function getBestQuality(array $qualityParts, ?string $name) : string
    {
        $i            = 0;
        $quality      = 0.0;
        $selectedName = '';

        foreach ($qualityParts as $accept) {
            if (0 === $i) {
                $quality      = (float) $accept['quality'];
                $selectedName = $accept[$name];
            } else {
                $acceptQuality = (float) $accept['quality'];

                if ($acceptQuality > $quality) {
                    $quality      = $acceptQuality;
                    $selectedName = $accept[$name];
                }
            }
            $i++;
        }

        return $selectedName;
    }

    final protected function getQualityHeader(?string $serverIndex, ?string $name) : array
    {
        $returnedParts = [];
        $parts         = \preg_split('/,\\s*/', (string) $this->getServer($serverIndex), -1, \PREG_SPLIT_NO_EMPTY);

        foreach ($parts as $part) {
            $headerParts = [];
            $tmp         = \preg_split("/\s*;\s*/", \trim($part), -1, \PREG_SPLIT_NO_EMPTY);

            foreach ($tmp as $headerPart) {
                if (false !== \strpos($headerPart, '=')) {
                    $split = \explode('=', $headerPart, 2);

                    if ('q' === $split[0]) {
                        $headerParts['quality'] = (float) $split[1];
                    } else {
                        $headerParts[$split[0]] = $split[1];
                    }
                } else {
                    $headerParts[$name]     = $headerPart;
                    $headerParts['quality'] = 1.0;
                }
            }

            $returnedParts[] = $headerParts;
        }

        return $returnedParts;
    }
}

namespace Limepie\Request;

class Url
{
    public static $url;

    public static function create($url)
    {
        Url::$url = $url;
    }

    public static function getPort()
    {
    }

    /**
     * 예)  http://localhost:8080/project/list.jsp
     *  [return]        /project/list.js
     *
     * @return void
     */
    public static function getRequestURI()
    {
    }

    public static function getScheme()
    {
    }

    public static function getHost()
    {
    }

    /**
     * path 메소드는 request의 URI를 반환합니다.
     * 따라서 들어오는 request가 http://domain.com/foo/bar/를 대상으로 한다면 path 메소드는 /foo/bar를 반환합니다:
     *
     * @param mixed $removeLeadingSlashes
     *
     * @return void
     */
    public static function getPath($removeLeadingSlashes = false)
    {
    }

    public static function getQuery()
    {
    }

    /**
     * 전체 경로를 가져옵니다. query string 제외
     * 예) http://localhost:8080/project/list.jsp?bid=free
     * [return]   http://localhost:8080/project/list.jsp
     *
     * @return void
     */
    public static function getRequestURL()
    {
    }

    public static function getUrl()
    {
    }

    /**
     * Get방식으로 넘어온 쿼리문자열을 구하기 위한 request 객체의 메소드는 getQueryString() 입니다. 이 getQueryString() 메소드는 쿼리문자열이 없을때는 null을 리턴해 줍니다.
     * http://localhost/community/board.jsp?bid=free&page=1
     * bid=free&page=1
     *
     * @return void
     */
    public static function getQueryString()
    {
    }
}
