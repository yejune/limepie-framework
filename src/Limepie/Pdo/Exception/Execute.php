<?php declare(strict_types=1);

namespace Limepie\Pdo\Exception;

class Execute extends \Limepie\Exception
{
    public function __construct($e, $query)
    {
        parent::__construct($e);
        $current = $this->currentTrace();

        if ($current) {
            $this->setFile($current['file']);
            $this->setLine($current['line']);
        }
        $this->setDisplayMessage('잠시후 다시 시도해주세요.');
        $this->setMessage($e->getMessage() . ',' . \PHP_EOL . $query);
    }

    public function currentTrace()
    {
        $trace = $this->getTrace();

        foreach ($trace as $row) {
//            if (false === \strpos($row['file'], '/limepie-framework/src/')) {
            return $row;
            //           }
        }

        return false;
    }
}
