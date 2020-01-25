<?php declare(strict_types=1);

namespace Limepie\Pdo;

class Mysql extends \Pdo
{
    public $info = [];

    public $debug = false;

    public function __construct(string $dsn, string $username = '', string $passwd = '', array $options = [])
    {
        $this->info = \parse_url($dsn);
        $parts      = \explode(';', $this->info['path']);

        foreach ($parts as $value) {
            $tmp             = \explode('=', $value, 2);
            $this->{$tmp[0]} = $tmp[1];
        }
        parent::__construct($dsn, $username, $passwd, $options);
    }

    /**
     * @param  $statement
     * @param array $bindParameters
     * @param  $mode
     *
     * @throws \PDOException
     *
     * @return array
     */
    public function gets($statement, $bindParameters = [], $mode = \PDO::FETCH_ASSOC)
    {
        try {
            // return parent::fetchAll($statement, $mode, $bindParameters) ?: null;

            if ($this->debug) {
                \Limepie\Timer::start();
            }
            $stmt   = self::execute($statement, $bindParameters);
            $mode   = self::getMode($mode);
            $result = $stmt->fetchAll($mode);
            $stmt->closeCursor();

            if ($this->debug) {
                $timer = \Limepie\Timer::stop();
                \pr($timer, $this->getErrorFormat($statement, $bindParameters));
            }

            return $result;
        } catch (\PDOException $e) {
            throw new Exception\Execute($e, $this->getErrorFormat($statement, $bindParameters));
        }
    }

    /**
     * @param  $statement
     * @param array $bindParameters
     * @param  $mode
     *
     * @throws \PDOException
     *
     * @return array
     */
    public function get($statement, $bindParameters = [], $mode = \PDO::FETCH_ASSOC)
    {
        try {
            // return parent::fetchOne($statement, $mode, $bindParameters) ?: null;

            if ($this->debug) {
                \Limepie\Timer::start();
            }
            $stmt   = self::execute($statement, $bindParameters);
            $mode   = self::getMode($mode);
            $result = $stmt->fetch($mode);
            $stmt->closeCursor();

            if ($this->debug) {
                $timer = \Limepie\Timer::stop();
                \pr($timer, $this->getErrorFormat($statement, $bindParameters));
            }

            return $result;
        } catch (\PDOException $e) {
            throw new Exception\Execute($e, $this->getErrorFormat($statement, $bindParameters));
        }
    }

    /**
     * @param  $statement
     * @param array $bindParameters
     * @param  $mode
     *
     * @throws \PDOException
     *
     * @return string
     */
    public function get1($statement, $bindParameters = [], $mode = \PDO::FETCH_ASSOC)
    {
        try {
            if ($this->debug) {
                \Limepie\Timer::start();
            }
            $stmt   = self::execute($statement, $bindParameters);
            $mode   = self::getMode($mode);
            $result = $stmt->fetch($mode);
            $stmt->closeCursor();

            if ($this->debug) {
                $timer = \Limepie\Timer::stop();
                \pr($timer, $this->getErrorFormat($statement, $bindParameters));
            }

            if (true === \is_array($result)) {
                foreach ($result as $key => $value) {
                    return $value;
                }
            }

            return false;
        } catch (\PDOException $e) {
            throw new Exception\Execute($e, $this->getErrorFormat($statement, $bindParameters));
        }
    }

    /**
     * @param  $statement
     * @param array $bindParameters
     *
     * @throws \PdoException
     *
     * @return bool
     */
    public function set($statement, $bindParameters = [])
    {
        try {
            return self::execute($statement, $bindParameters, true);
        } catch (\PDOException $e) {
            throw new Exception\Execute($e, $this->getErrorFormat($statement, $bindParameters));
        }
    }

    /*
    \Peanut\Phalcon\Db::name('master')->sets(
        'insert into test (a,b,c,d) values (:a,:b,:c,:d)', [
            [
                ':a' => 1,
                ':b' => 2,
                ':c' => 1,
                ':d' => 2,
            ],
            [
                ':a' => 1,
                ':b' => 2,
                ':c' => 1,
                ':d' => 2,
            ],
            [
                ':a' => 1,
                ':b' => 2,
                ':c' => 1,
                ':d' => 2,
            ],
        ]
    );
    =>
    insert into test(a,b,c,d) values(:a0, :b0, :c0, :d0),(:a1, :b1, :c1, :d1),(:a2, :b2, :c2, :d2)
    [
      [:a0] => 1
      [:b0] => 2
      [:c0] => 1
      [:d0] => 2
      [:a1] => 1
      [:b1] => 2
      [:c1] => 1
      [:d1] => 2
      [:a2] => 1
      [:b2] => 2
      [:c2] => 1
      [:d2] => 2
    ]
    */
    public function sets($statement, $bindParameters)
    {
        if (
            0 < \count($bindParameters)
            && 1 === \preg_match('/(?P<control>.*)(?:[\s]+)values(?:[^\(]+)\((?P<holders>.*)\)/Us', $statement, $m)
        ) {
            $holders = \explode(',', \preg_replace('/\s/', '', $m['holders']));

            $newStatements     = [];
            $newBindParameters = [];

            foreach ($bindParameters as $key => $value) {
                $statements = [];

                foreach ($holders as $holder) {
                    $statements[]                      = $holder . $key;
                    $newBindParameters[$holder . $key] = $value[$holder];
                }
                $newStatements[] = '(' . \implode(', ', $statements) . ')';
            }
            $newStatement = $m['control'] . ' values ' . \implode(', ', $newStatements);

            try {
                if (self::execute($newStatement, $newBindParameters, true)) {
                    return \count($bindParameters);
                }
            } catch (\PDOException $e) {
                throw $e;
            }
        }

        return false;
    }

    /**
     * @param  $statement
     * @param array $bindParameters
     *
     * @return int|false
     */
    public function setAndGetSequnce($statement, $bindParameters = [])
    {
        if (true === self::set($statement, $bindParameters)) {
            return parent::lastInsertId();
        }

        return false;
    }

    public function begin()
    {
        parent::setAttribute(\PDO::ATTR_AUTOCOMMIT, 0);

        return parent::beginTransaction();
    }

    public function commit()
    {
        if (parent::inTransaction()) {
            $return = parent::commit();
            parent::setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);

            return $return;
        }

        throw new Exception\Transaction('commit, There is no active transaction', 50001);
    }

    public function rollback()
    {
        if (parent::inTransaction()) {
            while (parent::inTransaction()) {
                if (false === parent::rollback()) {
                    return false;
                }
            }
            parent::setAttribute(\PDO::ATTR_AUTOCOMMIT, 1);

            return true;
        }

        throw new Exception\Transaction('rollback, There is no active transaction', 50001);
    }

    /**
     * @param  $callback
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function transaction(callable $callback)
    {
        try {
            if ($this->begin()) {
                $callback = $callback->bindTo($this);
                $return   = $callback();
                //$return = \call_user_func_array($callback, [$this]);

                //if (false === $return) {
                if (!$return) {
                    throw new Exception\Transaction('Transaction Failure', 50003);
                }

                if ($this->commit()) {
                    return $return;
                }
            }

            throw new Exception\Transaction('Transaction Failure', 50005);
        } catch (\Throwable $e) {
            $this->rollback();

            throw $e;
        }
    }

    /**
     * @param $descriptor
     * @param mixed $connect
     * @param mixed $statement
     * @param mixed $bindParameters
     * @param mixed $ret
     */
    // public function connect(string $dsn, string $username = '', string $passwd = '', array $options =[])
    // {
    //     try {
    //         $this->_pdo = new \Pdo($dsn, $username, $password, $options);
    //     } catch (\Throwable $e) {
    //         throw $e;
    //     }
    // }

    private function execute($statement, $bindParameters = [], $ret = false)
    {
        $stmt  = parent::prepare($statement);
        $binds = [];

        foreach ($bindParameters as $key => $value) {
            if (true === \is_array($value)) {
                $binds[$key] = $value[0];
            } else {
                $binds[$key] = $value;
            }
        }

        //pr($statement, $bindParameters);
        $result = $stmt->execute($binds);

        if (true === $ret) {
            $stmt->closeCursor();

            return $result;
        }

        return $stmt;
    }

    private function getMode($mode = null)
    {
        if (true === (null === $mode)) {
            $mode = self::getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE);
        }

        return $mode;
    }

    private function getErrorFormat($query, $binds)
    {
        $string = [];

        foreach ($binds as $key => $value) {
            $string[] = $key . ' => ' . $value;
        }

        return $query . ' [' . \implode(', ', $string) . ']';
    }
}
