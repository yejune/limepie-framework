<?php declare(strict_types=1);

namespace Limepie;

class Template
{
    /**
     * @var bool|string
     */
    public $compileCheck = true;

    /**
     * @var string
     */
    public $compileRoot = '.';

    /**
     * @var string
     */
    public $templateRoot = '.';

    /**
     * @var array
     */
    public $tpl_ = [];

    /**
     * @var array
     */
    public $cpl_ = [];

    /**
     * @var array
     */
    //public $var_ = [];
    public $var_ = ['' => []];

    /**
     * @var string
     */
    public $skin;

    /**
     * @var string
     */
    public $tplPath;

    /**
     * @var int
     */
    public $permission = 0777;

    /**
     * @var bool
     */
    public $phpengine = true;

    /**
     * @var array
     */
    public $relativePath = [];

    /**
     * @var string
     */
    public $ext = '.php';

    public $notice = false;

    public $debug = false;

    public $noticeReporting = 0;

    public $prefilter;

    public $postfilter;

    public $plugin_dir;

    public $pluginExtension = 'php';

    public $_current_scope = '';

    public function __construct()
    {
    }

    public function assignAndDefine($arr)
    {
        $this->assign($arr[0]);
        $this->define($arr[1]);
    }

    /**
     * @param $key
     * @param $value
     * @param mixed $arg
     */
    public function assign($arg)
    {
        if (\is_array($arg)) {
            $var = \array_merge($var = &$this->var_[$this->_current_scope], $arg);
        } else {
            $this->var_[$this->_current_scope][$arg] = \func_get_arg(1);
        }
    }

    public function setScope($scope = '', $arg = [])
    {
        $this->var_[$scope] = $arg;
    }

    /**
     * @param $fid
     * @param $path
     * @param mixed $fids
     */
    public function define($fids, $path = false)
    {
        if (true === \is_array($fids)) {
            foreach ($fids as $fid => $path) {
                $this->_define($fid, $path);
            }
        } else {
            $this->_define($fids, $path);
        }
    }

    /**
     * @param  $fid
     * @param  $print
     *
     * @return mixed
     */
    public function show($fid, $print = false)
    {
        if (true === $print) {
            $this->printContents($fid);
        } else {
            if ($this->debug) {
                $this->printContents($fid);

                return 'limepie template debugging...';
            }

            return $this->getContents($fid);
        }
    }

    /**
     * @param  $fid
     *
     * @return mixed
     */
    public function getContents($fid)
    {
        \ob_start();

        try {
            $this->printContents($fid);
        } catch (\Throwable $e) {
            \ob_end_clean();

            throw $e;
        }
        $content = \ob_get_contents();
        \ob_end_clean();

        return $content;
    }

    /**
     * @param  $fid
     * @param mixed $addAssign
     * @param mixed $scope
     *
     * @return null
     */
    public function printContents($fid, $addAssign = [], $scope = '') : void
    {
        if (true === isset($this->tpl_[$fid]) && !$this->tpl_[$fid]) {
            return;
        }

        if (true === isset($this->var_[$fid])) {
            $scope = $fid;
        }

        $this->noticeReporting = \error_reporting();

        if ($this->notice) {
            \error_reporting($this->noticeReporting | \E_NOTICE);
            \set_error_handler([$this, 'templateNoticeHandler']);
            $this->requireFile($this->getCompilePath($fid), $addAssign, $scope);
            \restore_error_handler();
        } else {
            \error_reporting($this->noticeReporting & ~\E_NOTICE);
            $this->requireFile($this->getCompilePath($fid), $addAssign, $scope);
        }
        \error_reporting($this->noticeReporting);
    }

    public function compilePath($filename)
    {
        $this->_define('*', $filename);

        return $this->getCompilePath('*');
    }

    /**
     * @param  $fid
     *
     * @return mixed
     */
    public function getCplPath($fid)
    {
        return $this->compileRoot . $this->cpl_[$fid] . $this->ext;
    }

    public function setTemplateRoot($path)
    {
        $this->templateRoot = \rtrim($path, '/');
    }

    public function setCompileRoot($path)
    {
        $this->compileRoot = \rtrim($path, '/');
    }

    public function setSkin($name)
    {
        $this->skin = \trim($name, '/') . '/';
    }

    public function setPhpEngine($val = true)
    {
        $this->phpengine = $val;
    }

    public function setNotice($val = true)
    {
        $this->notice = $val;
    }

    public function setCompileCheck($val = true)
    {
        $this->compileCheck = $val;
    }

    /**
     * @param  $fid
     *
     * @return mixed
     */
    public function getTplPath($fid)
    {
        $path = '';

        if (true === isset($this->tpl_[$fid])) {
            $path = $this->tpl_[$fid];
        } else {
            throw new Exception('template id "' . $fid . '" is not defined', 11001);
        }

        if (0 === \strpos($path, '/')) {
            $tplPath = $path;
        } else {
            $tplPath = $path;
        }
        $tplPath2 = \Limepie\stream_resolve_include_path($tplPath);

        if (false === $tplPath2) {
            throw new Exception('cannot find defined template "' . $tplPath . '"', 11002);
        }

        $this->cpl_[$fid] = $tplPath2;

        return $tplPath2;
    }

    public function templateNoticeHandler($type, $msg, $file, $line)
    {
        switch ($type) {
            case \E_NOTICE:
                $msg = 'Template Notice #1: ' . $msg;

                break;
            case \E_WARNING:
            case \E_USER_WARNING:
                $msg = 'Warning: ' . $msg;

                break;
            case \E_USER_NOTICE:
                $msg = 'Notice: ' . $msg;

                break;
            case \E_USER_ERROR:
                $msg = 'Fatal: ' . $msg;

                break;
            default:
                $msg = 'Unknown: ' . $msg;

                break;
        }

        $exception = new Exception($msg, 11003);
        $exception->setFile($file);
        $exception->setLine($line);

        throw $exception;
    }

    public function defined($fid)
    {
        return isset($this->tpl_[$fid]);
    }

    /**
     * @param $fid
     * @param $path
     */
    private function _define($fid, $path)
    {
        //pr($path);
        $this->tpl_[$fid] = $path; //ltrim($path, $this->templateRoot);
    }

    /**
     * @param  $fid
     *
     * @return mixed
     */
    private function getCompilePath($fid)
    {
        $tplPath = $this->getTplPath($fid);
        $cplPath = $this->getCplPath($fid);

        if (false === $this->compileCheck) {
            return $cplPath;
        }

        if (false === $tplPath) {
            throw new Exception('cannot find defined template "' . $tplPath . '"', 11004);
        }
        //( 24 + 1 + 40 + 1 ) + ( 11 + 1 )
        $cplHead = "<?php /* Peanut\Template " . \sha1_file($tplPath, false) . ' ' . \date('Y/m/d H:i:s', \filemtime($tplPath)) . ' ' . $tplPath . ' ';

        if ('dev' !== $this->compileCheck && false !== $cplPath) {
            if (true === \file_exists($cplPath)) {
                $fp   = \fopen($cplPath, 'rb');
                $head = \fread($fp, \strlen($cplHead) + 9);
                \fclose($fp);

                if (9 < \strlen($head)
                    && \substr($head, 0, 66) === \substr($cplHead, 0, 66) && \filesize($cplPath) === (int) \substr($head, -9)) {
                    return $cplPath;
                }
            }
        }

        $compiler = new Template\Compiler();
        $compiler->execute($this, $fid, $tplPath, $cplPath, $cplHead);

        $cplPath2 = $cplPath;
        //\pr($cplPath2);

        if (false === $cplPath2) {
            throw new Exception('cannot find defined template compile "' . $cplPath . '"', 11005);
        }

        return $cplPath2;
    }

    /**
     * @param $tplPath
     * @param mixed $addAssign
     * @param mixed $TPL_SCP
     */
    private function requireFile($tplPath, $addAssign = [], $TPL_SCP)
    {
        \extract($this->var_['']);

        // foreach ($this->var_[$TPL_SCP] as $k => $v) {
        //     if (true === \is_array($v)) {
        //         foreach ($v as $k1 => $v2) {
        //             ${$k}[$k1] = $v2;
        //         }
        //     } else {
        //         ${$k} = $v;
        //     }
        // }
        //unset ($k);  unset ($v);  unset ($V);
        if (true === isset($this->var_[$TPL_SCP])) {
            ${$TPL_SCP} = $this->var_[$TPL_SCP];
        }
        \extract($addAssign);

        if (true === \file_exists($tplPath)) {
            require $tplPath;
        }
    }
}
