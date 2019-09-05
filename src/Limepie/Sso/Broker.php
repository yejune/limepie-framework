<?php declare(strict_types=1);

namespace Limepie\Sso;

/**
 * Single sign-on broker.
 *
 * @see https://github.com/legalthings/sso
 *
 * The broker lives on the website visited by the user. The broken doesn't have any user credentials stored. Instead it
 * will talk to the SSO server in name of the user, verifying credentials and getting user information.
 */
class Broker
{
    /**
     * My identifier, given by SSO provider.
     *
     * @var string
     */
    public $broker;

    /**
     * Session token of the client
     *
     * @var string
     */
    public $token;

    /**
     * Url of SSO server
     *
     * @var string
     */
    protected $url;

    /**
     * My secret word, given by SSO provider.
     *
     * @var string
     */
    protected $secret;

    /**
     * User info recieved from the server.
     *
     * @var array
     */
    protected $userinfo;

    /**
     * Cookie lifetime
     *
     * @var int
     */
    protected $cookieLifetime;

    protected $cookieSecure = false;

    protected $cookieHttpOnly = false;

    protected $cookieDomain = '';

    /**
     * Class constructor
     *
     * @param string $url            Url of SSO server
     * @param string $broker         My identifier, given by SSO provider.
     * @param string $secret         My secret word, given by SSO provider.
     * @param mixed  $cookieLifetime
     * @param mixed  $cookieSecure
     * @param mixed  $cookieHttpOnly
     */
    //public function __construct($url, $broker, $secret, $cookieLifetime = 3600)
    public function __construct($url, $broker, $secret, $cookieLifetime = 0, $cookieSecure = true, $cookieHttpOnly = true)
    {
        if (!$url) {
            throw new \InvalidArgumentException('SSO server URL not specified');
        }

        if (!$broker) {
            throw new \InvalidArgumentException('SSO broker id not specified');
        }

        if (!$secret) {
            throw new \InvalidArgumentException('SSO broker secret not specified');
        }
        $this->url            = $url;
        $this->broker         = $broker;
        $this->secret         = $secret;
        $this->cookieLifetime = $cookieLifetime;
        $this->cookieSecure   = $cookieSecure;
        $this->cookieHttpOnly = $cookieHttpOnly;

        if (true === isset($_COOKIE[$this->getCookieName()])) {
            $this->token = $_COOKIE[$this->getCookieName()];
        }
    }

    /**
     * Magic method to do arbitrary request
     *
     * @param string $fn
     * @param array  $args
     *
     * @return mixed
     */
    public function __call($fn, $args)
    {
        $sentence = \strtolower(\preg_replace('/([a-z0-9])([A-Z])/', '$1 $2', $fn));
        $parts    = \explode(' ', $sentence);
        $method   = 1 < \count($parts) && \in_array(\strtoupper($parts[0]), ['GET', 'DELETE'], true)
            ? \strtoupper(\array_shift($parts))
            : 'POST';
        $command = \implode('-', $parts);

        return $this->request($method, $command, $args);
    }

    /**
     * Generate session token
     */
    public function generateToken()
    {
        if (true === isset($this->token)) {
            return;
        }
        $this->token = \base_convert(\md5(\uniqid((string) \mt_rand(), true)), 16, 36);
        $lifetime    = $this->cookieLifetime ?: 0;
        \setcookie(
            $this->getCookieName(),
            $this->token,
            $lifetime,
            '/',
            $this->cookieDomain,
            $this->cookieSecure,
            $this->cookieHttpOnly
        );
    }

    /**
     * Clears session token
     */
    public function clearToken()
    {
        \setcookie(
            $this->getCookieName(),
            '',
            \time() - 3600,
            '/',
            $this->cookieDomain,
            $this->cookieSecure,
            $this->cookieHttpOnly
        );
        $this->token = null;
    }

    /**
     * Check if we have an SSO token.
     *
     * @return bool
     */
    public function isAttached()
    {
        return isset($this->token) && $this->token;
    }

    /**
     * Get URL to attach session at SSO server.
     *
     * @param array $params
     *
     * @return string
     */
    public function getAttachUrl($params = [])
    {
        $this->generateToken();

        $data = [
            'command'  => 'attach',
            'broker'   => $this->broker,
            'token'    => $this->token,
            'checksum' => \hash('sha256', 'attach' . $this->token . $this->secret),
        ] + $_GET;

        return $this->url . '?' . \http_build_query($data + $params);
    }

    /**
     * Attach our session to the user's session on the SSO server.
     *
     * @param string|true $returnUrl The URL the client should be returned to after attaching
     */
    public function attach($returnUrl = null)
    {
        if ($this->isAttached()) {
            return;
        }
        // pr($returnUrl, $this);
        if (true === $returnUrl) {
            $protocol  = !empty($_SERVER['HTTPS']) ? 'https://' : 'http://';
            $returnUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        }
        $params = ['return_url' => $returnUrl];
        $url    = $this->getAttachUrl($params);
        //$url = str_replace('//', '//test:test@', $url);

        \header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        \header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        \header("Location: ${url}#+", true, 307);
        echo "You're redirected to <a href='${url}'>${url}</a>";

        exit();
    }

    public function getAttachImageUrl()
    {
        $this->generateToken();

        return $this->url
            . '/image/'
            . $this->broker . '/'
            . $this->token . '/'
            . \time() . '/'
            . \hash('sha256', 'attach' . $this->token . $this->secret)
            . '.png';
    }

    /**
     * Log the client in at the SSO server.
     *
     * Only brokers marked trused can collect and send the user's credentials. Other brokers should omit $username and
     * $password.
     *
     * @param string $username
     * @param string $password
     *
     * @throws Exception if login fails eg due to incorrect credentials
     *
     * @return array user info
     */
    public function login($username = null, $password = null)
    {
        if (false === isset($username) && isset($_POST['email'])) {
            $username = $_POST['email'];
        }

        if (false === isset($password) && isset($_POST['password'])) {
            $password = $_POST['password'];
        }
        //pr($username, $password);
        $result         = $this->request('POST', 'login', \compact('username', 'password'));
        $this->userinfo = $result;

        return $this->userinfo;
    }

    public function snsLogin($seq)
    {
        $result         = $this->request('POST', 'snsLogin', \compact('seq'));
        $this->userinfo = $result;

        return $this->userinfo;
    }

    /**
     * Logout at sso server.
     */
    public function logout()
    {
        $this->request('POST', 'logout', 'logout');
    }

    /**
     * Get user information.
     *
     * @return object|null
     */
    public function getUserInfo()
    {
        if (false === isset($this->userinfo)) {
            $this->userinfo = $this->request('GET', 'userInfo');
        }

        return $this->userinfo;
    }

    /**
     * Get the cookie name.
     *
     * Note: Using the broker name in the cookie name.
     * This resolves issues when multiple brokers are on the same domain.
     *
     * @return string
     */
    protected function getCookieName()
    {
        return \base58_encode($this->broker);
    }

    /**
     * Generate session id from session key
     *
     * @return string
     */
    protected function getSessionId()
    {
        if (false === isset($this->token)) {
            return null;
        }
        $checksum = \hash('sha256', 'session' . $this->token . $this->secret);

        return "SSO-{$this->broker}-{$this->token}-${checksum}";
    }

    /**
     * Get the request url for a command
     *
     * @param string $command
     * @param array  $params  Query parameters
     *
     * @return string
     */
    protected function getRequestUrl($command, $params = [])
    {
        $params['command'] = $command;

        return $this->url . '?' . \http_build_query($params);
    }

    /**
     * Execute on SSO server.
     *
     * @param string       $method  HTTP method: 'GET', 'POST', 'DELETE'
     * @param string       $command Command
     * @param array|string $data    Query or post parameters
     *
     * @return array|object
     */
    protected function request($method, $command, $data = null)
    {
        if (!$this->isAttached()) {
            throw new NotAttachedException('No token');
        }
        $url = $this->getRequestUrl($command, !$data || 'POST' === $method ? [] : $data);
        $ch  = \curl_init($url);
        \curl_setopt($ch, \CURLOPT_RETURNTRANSFER, true);
        \curl_setopt($ch, \CURLOPT_CUSTOMREQUEST, $method);
        \curl_setopt($ch, \CURLOPT_HTTPHEADER, ['Accept: application/json', 'Authorization: Bearer ' . $this->getSessionID()]);
        //\curl_setopt($ch, \CURLOPT_FOLLOWLOCATION, true);
        //\curl_setopt($ch, \CURLOPT_HEADER, 1); //헤더를 포함한다.

        //\pr($url, ['Accept: application/json', 'Authorization: Bearer ' . $this->getSessionID()]);

        if ('POST' === $method && !empty($data)) {
            $post = \is_string($data) ? $data : \http_build_query($data);
            \curl_setopt($ch, \CURLOPT_POSTFIELDS, $post);
        }

        //pr($url, $post ?? $data, 'Authorization: Bearer ' . $this->getSessionID());

        $response = \curl_exec($ch);
        //pr($response);
        // $split_result = \explode("\r\n\r\n", $curl_result, 2);
        // $header       = $split_result[0];
        // $response     = $split_result[1];
        //\pr($response);

        if (0 !== \curl_errno($ch)) {
            $message = 'Server request failed: ' . \curl_error($ch);

            throw new Exception($message);
        }
        $httpCode      = \curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        [$contentType] = \explode(';', \curl_getinfo($ch, \CURLINFO_CONTENT_TYPE));
        //pr($contentType);
        if ('application/json' !== $contentType) {
            $message = 'Expected application/json response, got ' . $contentType;
            //\pr($response);

            throw new Exception($message);
        }
        $data = \json_decode($response, true);

        if (403 === $httpCode) {
            //$this->clearToken();
            throw new NotAttachedException($data['error'] ?: $response, $httpCode);
        }

        if (400 <= $httpCode) {
            throw new Exception($data['error'] ?: $response, $httpCode);
        }

        return $data;
    }
}
