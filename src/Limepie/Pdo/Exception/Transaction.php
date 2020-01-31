<?php declare(strict_types=1);

namespace Limepie\Pdo\Exception;

class Transaction extends \Limepie\Exception
{
    public function __construct($e, int $code = 0)
    {
        parent::__construct($e, $code);
        $current = $this->currentTrace();

        if ($current) {
            $this->setFile($current['file']);
            $this->setLine($current['line']);
        }
    }

    public function currentTrace()
    {
        $trace = $this->getTrace();
        foreach ($trace as $row) {
            if ('Limepie\Pdo\Mysql' !== $row['class']) {
                return $row;
            }
        }

        return false;
    }
}
