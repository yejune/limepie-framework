<?php declare(strict_types=1);

namespace Limepie;

class Exception extends \Exception
{
    public $isLocal = false;

    public $_trace;

    public $_previous;

    public function __construct($e, int $code = 0, Throwable $previous = null)
    {
        if (true === \is_object($e)) {
            $this->setMessage($e->getMessage());
            $this->setLine($e->getLine());
            $this->setFile($e->getFile());
            $this->setCode(0 < $code ? $code : $e->getCode());
            $this->_trace    = $e->getTrace();
            $this->_previous = $e->getPrevious();
            $this->isLocal   = true;
        } else {
            $this->setMessage($e);
            $this->setCode($code);
            $this->setPrevious($previous);
        }
    }

    public function __toString()
    {
        if (false !== \strpos($this->getFile(), 'resource/Framework/Limepie')) {
            $traces = $this->getTraces();

            foreach ($traces as $trace) {
                if (true === isset($trace['file'])) {
                    if (false === \strpos($trace['file'], 'resource/Framework/Limepie')) {
                        $filename = $trace['file'];
                        $line     = $trace['line'];

                        if (true === \Limepie\is_cli()) {
                            $message = "{$this->code}: {$this->message} in {$filename} on line {$line}";
                        } elseif (true === \Limepie\is_ajax()) {
                            $message = \json_encode([
                                'message' => "{$this->code}: {$this->message} in {$filename} on line {$line}",
                            ], \JSON_UNESCAPED_UNICODE);
                        } else {
                            $message = "{$this->code}: {$this->message} in <b>{$filename}</b> on line <b>{$line}</b>\n\n";
                        }

                        break;
                    }
                }
            }
        } else {
            $filename = $this->file;
            $line     = $this->line;

            if (true === \Limepie\is_cli()) {
                $message = "{$this->code}: {$this->message} in {$filename} on line {$line}";
            } elseif (true === \Limepie\is_ajax()) {
                $message = \json_encode([
                    'message' => "{$this->code}: {$this->message} in {$filename} on line {$line}",
                ], \JSON_UNESCAPED_UNICODE);
            } else {
                $message = "{$this->code}: {$this->message} in <b>{$filename}</b> on line <b>{$line}</b>\n\n";
            }
        }
        //\pr($this->getTraces());

        echo $message;
        \pr($this->getTraces());

        exit;

        return '';
    }

    public function getTraces()
    {
        if (true === $this->isLocal) {
            $traces = $this->_trace;
        } else {
            $traces = parent::getTrace();
        }

        return $traces;
    }

    public function getTracesString()
    {
        if (true === $this->isLocal) {
            $message = $this->getTraceAsStringFromLocal();
        } else {
            $message = $this->getTraceAsString();
        }

        return $message;
    }

    public function getTraceAsStringFromLocal() : string
    {
        $rtn   = '';
        $count = 0;

        foreach ($this->_trace as $frame) {
            $args = '';

            if (true === isset($frame['args'])) {
                $args = [];

                foreach ($frame['args'] as $arg) {
                    if (true === \is_string($arg)) {
                        $args[] = "'" . $arg . "'";
                    } elseif (true === \is_array($arg)) {
                        $args[] = 'Array';
                    } elseif (null === $arg) {
                        $args[] = 'NULL';
                    } elseif (true === \is_bool($arg)) {
                        $args[] = ($arg) ? 'true' : 'false';
                    } elseif (true === \is_object($arg)) {
                        $args[] = \get_class($arg);
                    } elseif (true === \is_resource($arg)) {
                        $args[] = \get_resource_type($arg);
                    } else {
                        $args[] = $arg;
                    }
                }
                $args = \implode(', ', $args);
            }
            $rtn .= \sprintf(
                "#%s %s(%s): %s(%s)\n",
                $count,
                $frame['file'] ?? 'unknown file',
                $frame['line'] ?? 'unknown line',
                (true === isset($frame['class'])) ? $frame['class'] . $frame['type'] . $frame['function'] : $frame['function'],
                $args
            );
            $count++;
        }

        return $rtn;
    }

    public function setLine(int $line) : void
    {
        $this->line = $line;
    }

    public function setFile(string $file) : void
    {
        $this->file = $file;
    }

    public function setMessage(string $message) : void
    {
        $this->message = $message;
    }

    public function setCode($code) : void
    {
        $this->code = $code;
    }

    public function setTrace(array $trace) : void
    {
        $this->trace = $trace;
    }

    public function setPrevious(/*?*/array $previous = null) : void
    {
        $this->previous = $previous;
    }

    public function throw() : void
    {
        exit($this->__toString());
    }
}
