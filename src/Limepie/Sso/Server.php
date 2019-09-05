<?php declare(strict_types=1);

namespace Limepie\Sso;

/**
 * Single sign-on server.
 * @ https://github.com/legalthings/sso
 *
 * The SSO server is responsible of managing users sessions which are available for brokers.
 *
 * To use the SSO server, extend this class and implement the abstract methods.
 * This class may be used as controller in an MVC application.
 */
abstract class Server
{
    /**
     * Cache that stores the special session data for the brokers.
     *
     * @var Cache
     */
    protected $cache;

    /**
     * @var string
     */
    protected $returnType;

    /**
     * @var mixed
     */
    protected $brokerId;

    /**
     * Class constructor
     *
     * @param array $options
     */
    public function __construct($cache)
    {
        $this->cache = $cache;
    }

    /**
     * Start the session for broker requests to the SSO server
     */
    public function startBrokerSession()
    {
        if (true === isset($this->brokerId)) {
            return;
        }
        $sid = $this->getBrokerSessionID();

        if (false === $sid) {
            throw new \Exception("Broker didn't send a session key", 400);
        }
        $linkedId = $this->cache->get($sid);

        if (!$linkedId) {
            $this->cache->delete($sid);
            throw new \Exception("The broker session id isn't attached to a user session", 403);
        }

        if (\PHP_SESSION_ACTIVE === \session_status()) {
            if (\session_id() !== $linkedId) {
                throw new \Exception('Session has already started', 400);
            }

            return;
        }
        \session_id($linkedId);
        \session_start();
        $this->brokerId = $this->validateBrokerSessionId($sid);
    }

    /**
     * Attach a user session to a broker session
     */
    public function attach($broker = null, $token =null, $checksum = null)
    {
        $this->detectReturnType();

        if (true === empty($broker)) {
            if(true === empty($_REQUEST['broker'])) {
                throw new \Exception('No broker specified', 400);
            } else {
                $broker = $_REQUEST['broker'];
            }
        }

        if (true === empty($token)) {
            if (true === empty($_REQUEST['token'])) {
                throw new \Exception('No token specified', 400);
            } else {
                $token = $_REQUEST['token'];
            }
        }

        if (true === empty($checksum)) {
            if (true === empty($_REQUEST['checksum'])) {
                throw new \Exception('No token checksum', 400);
            } else {
                $checksum = $_REQUEST['checksum'];
            }
        }

        if (!$this->returnType) {
            throw new \Exception('No return url specified', 400);
        }

        if ($checksum !== $this->generateAttachChecksum($broker, $token)) {
            throw new \Exception('Invalid checksum', 400);
        }

        $this->startUserSession();
        $sid = $this->generateSessionId($broker, $token);
        $this->cache->set($sid, $this->getSessionData('id'));
        $this->outputAttachSuccess();
    }

    /**
     * Authenticate
     */
    public function login()
    {
        $this->startBrokerSession();

        if (true === empty($_POST['username'])) {
            $this->fail('No username specified', 400);
        }

        if (true === empty($_POST['password'])) {
            $this->fail('No password specified', 400);
        }
        if($userInfo = $this->authenticate($_POST['username'], $_POST['password'])) {
            $this->setSessionData('sso_user', $userInfo['seq']);
            $this->userInfo();
        } else {
            $this->fail("Invalid credentials", 400);
        }
    }

    public function snsLogin()
    {
        $this->startBrokerSession();

        if (true === empty($_POST['seq'])) {
            $this->fail('No seq specified', 400);
        }

        if($userInfo = $this->snsAuthenticate($_POST['seq'])) {
            $this->setSessionData('sso_user', $userInfo['seq']);
            $this->userInfo();
        } else {
            $this->fail("Invalid credentials", 400);
        }
    }

    /**
     * Log out
     */
    public function logout()
    {
        $this->startBrokerSession();
        $this->setSessionData('sso_user', null);
        \header('Content-type: application/json; charset=UTF-8');
        \http_response_code(204);
    }

    /**
     * Ouput user information as json.
     */
    public function userInfo()
    {
        $this->startBrokerSession();
        $user     = null;
        $seq = $this->getSessionData('sso_user');

        if ($seq) {
            $user = $this->getUserInfo($seq);

            if (!$user) {
                throw new \Exception('User not found', 500);
            } // Shouldn't happen
        }
        \header('Content-type: application/json; charset=UTF-8');
        echo \json_encode($user, \JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get session ID from header Authorization or from $_GET/$_POST
     */
    protected function getBrokerSessionID()
    {
        $headers = \getallheaders();

        if (true === isset($headers['Authorization']) && 0 === \strpos($headers['Authorization'], 'Bearer')) {
            $headers['Authorization'] = \substr($headers['Authorization'], 7);

            return $headers['Authorization'];
        }

        if (true === isset($_GET['access_token'])) {
            return $_GET['access_token'];
        }

        if (true === isset($_POST['access_token'])) {
            return $_POST['access_token'];
        }

        if (true === isset($_GET['sso_session'])) {
            return $_GET['sso_session'];
        }

        return false;
    }

    /**
     * Validate the broker session id
     *
     * @param string $sid session id
     *
     * @return string the broker id
     */
    protected function validateBrokerSessionId($sid)
    {
        $matches = null;

        if (!\preg_match('/^SSO-(?P<broker_id>\w*+)-(?P<token>\w*+)-([a-z0-9]*+)$/', $this->getBrokerSessionID(), $matches)) {
            throw new \Exception('Invalid session id');
        }
        $brokerId = $matches['broker_id'];
        $token    = $matches['token'];

        if ($this->generateSessionId($brokerId, $token) !== $sid) {
            $this->cache->delete($sid);
            throw new \Exception('Checksum failed: Client IP address may have changed', 403);
        }

        return $brokerId;
    }

    /**
     * Start the session when a user visits the SSO server
     */
    protected function startUserSession()
    {
        if (\PHP_SESSION_ACTIVE !== \session_status()) {
            \session_start();
        }
    }

    /**
     * Generate session id from session token
     *
     * @param string $brokerId
     * @param string $token
     *
     * @return string
     */
    protected function generateSessionId($brokerId, $token)
    {
        $broker = $this->getBrokerInfo($brokerId);

        if (false === isset($broker)) {
            return null;
        }

        return "SSO-{$brokerId}-{$token}-" . \hash('sha256', 'session' . $token . $broker['secret']);
    }

    /**
     * Generate session id from session token
     *
     * @param string $brokerId
     * @param string $token
     *
     * @return string
     */
    protected function generateAttachChecksum($brokerId, $token)
    {
        $broker = $this->getBrokerInfo($brokerId);

        if (!$broker) {
            return null;
        }

        return \hash('sha256', 'attach' . $token . $broker['secret']);
    }

    /**
     * Detect the type for the HTTP response.
     * Should only be done for an `attach` request.
     */
    protected function detectReturnType()
    {
        if (!empty($_GET['return_url'])) {
            $this->returnType = 'redirect';
        } elseif (!empty($_GET['callback'])) {
            $this->returnType = 'jsonp';
        } elseif (false !== \strpos($_SERVER['HTTP_ACCEPT'], 'image/')) {
            $this->returnType = 'image';
        } elseif (false !== \strpos($_SERVER['HTTP_ACCEPT'], 'application/json')) {
            $this->returnType = 'json';
        }
    }

    /**
     * Output on a successful attach
     */
    protected function outputAttachSuccess()
    {
        if ('image' === $this->returnType) {
            $this->outputImage();
        }

        if ('json' === $this->returnType) {
            \header('Content-type: application/json; charset=UTF-8');
            echo \json_encode(['success' => 'attached'], \JSON_UNESCAPED_UNICODE);
        }

        if ('jsonp' === $this->returnType) {
            $data = \json_encode(['success' => 'attached']);
            echo $_REQUEST['callback'] . "(${data}, 200);";
        }

        if ('redirect' === $this->returnType) {
            if(true === isset($_REQUEST['return_url'])) {
                $url = $_REQUEST['return_url'];

                \header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
                \header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
                \header("Location: ${url}", true, 307);
                echo "You're being redirected to <a href='{$url}'>${url}</a>";
                exit;
            }
        }
    }

    /**
     * Output a 1x1px transparent image
     */
    public function outputImage()
    {
        \header('Content-Type: image/png');
        echo \base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQ'
            . 'MAAAAl21bKAAAAA1BMVEUAAACnej3aAAAAAXRSTlMAQObYZg'
            . 'AAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=', true);
        exit;
    }

    /**
     * Set session data
     *
     * @param string $key
     * @param string $value
     */
    protected function setSessionData($key, $value)
    {
        if (false === isset($value)) {
            unset($_SESSION[$key]);

            return;
        }
        $_SESSION[$key] = $value;
    }

    /**
     * Get session data
     *
     * @param type $key
     */
    protected function getSessionData($key)
    {
        if ('id' === $key) {
            return \session_id();
        }

        return $_SESSION[$key] ?? null;
    }

    /**
     * An error occured.
     *
     * @param string $message
     * @param int    $http_status
     */
    protected function fail($message, $http_status = 500)
    {
        if (500 === $http_status) {
            \trigger_error($message, \E_USER_WARNING);
        }

        if ('jsonp' === $this->returnType) {
            echo $_REQUEST['callback'] . '(' . \json_encode(['error' => $message], \JSON_UNESCAPED_UNICODE) . ", ${http_status});";

            exit();
        }

        if ('redirect' === $this->returnType) {
            $url = $_REQUEST['return_url'] . '?sso_error=' . $message;
            \header("Location: ${url}", true, 307);
            echo "You're being redirected to <a href='{$url}'>${url}</a>";

            exit();
        }
        \http_response_code($http_status);
        \header('Content-type: application/json; charset=UTF-8');
        echo \json_encode(['error' => $message], \JSON_UNESCAPED_UNICODE);

        exit();
    }

    /**
     * Authenticate using user credentials
     *
     * @param string $username
     * @param string $password
     *
     * @return array
     */
    abstract protected function authenticate($username, $password);
    abstract protected function snsAuthenticate($seq);

    /**
     * Get the secret key and other info of a broker
     *
     * @param string $brokerId
     *
     * @return array
     */
    abstract protected function getBrokerInfo($brokerId);

    /**
     * Get the information about a user
     *
     * @param string $username
     *
     * @return array|object
     */
    abstract protected function getUserInfo($username);
}
