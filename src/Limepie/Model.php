<?php declare(strict_types=1);

namespace Limepie;

class Model implements \Iterator, \ArrayAccess, \Countable
{
    public $tableName;

    public $primaryKeyName;

    public $sequenceName;

    public $primaryKeyValue;

    public $fields = [];

    public $timestampFields = [];

    public $attributes = [];

    public $selectFields = '*';

    public $orderBy = '';

    public $arrayKeyName = 'seq';

    public $onArrayKeyName;

    public $pdo;

    public $offset;

    public $limit;

    public $query;

    public $binds = [];

    public $oneToOne = [];

    public $oneToMany = [];

    public $left = '';

    public $right = '';

    public $and = [];

    public function __construct($pdo = '', $attributes = [])
    {
        if ($pdo) {
            $this->setConnect($pdo);
        }

        if ($attributes) {
            $type = 0;

            // 정해진 필드만
            if (1 === $type) {
                foreach ($this->allFields as $field) {
                    if (true === isset($attributes[$field])) {
                        $this->attributes[$field] = $attributes[$field];
                    } elseif (true === isset($attributes[$this->tableName . '_' . $field])) {
                        $field1                   = $this->tableName . '_' . $field;
                        $this->attributes[$field] = $attributes[$field1];
                    } else {
                        $this->attributes[$field] = null;
                    }
                }

                $this->primaryKeyValue = $this->attributes[$this->primaryKeyName];
            } else {
                $this->attributes      = $attributes;
                $this->primaryKeyValue = $this->attributes[$this->primaryKeyName];
            }
        }
    }

    public function __invoke($pdo = null)
    {
        if ($pdo) {
            $this->setConnect($pdo);
        }

        return $this;
    }

    public function __call($name, $arguments)
    {
        // 주의: $name 의 값은 대소를 구분합니다.
        // //pr("Calling object method '$name' " . implode(', ', $arguments));

        if (0 === \strpos($name, 'getBy')) {
            $this->attributes    = [];
            $whereKey            = \Limepie\decamelize(\substr($name, 5));
            [$condition, $binds] = $this->getConditionAndBinds($whereKey, $arguments);
            $selectFields        = $this->getSelectFields();
            $orderBy             = $this->getOrderBy();
            $limit               = $this->getLimit();
            $sql                 = "
SELECT
    {$selectFields}
FROM
    `{$this->tableName}`
WHERE
    {$condition}
{$orderBy}
{$limit}
";

            $this->query = $sql;
            $this->binds = $binds;

            $attributes = $this->getConnect()->get($sql, $binds);

            if ($attributes) {
                $this->attributes      = $this->getRelation($attributes);
                $this->primaryKeyValue = $this->attributes[$this->primaryKeyName];

                return $this;
            }

            return [];
        } elseif (0 === \strpos($name, 'getsBy')) {
            // where에 pk나 uk인지 검사?
            $this->attributes      = [];
            $this->primaryKeyValue = '';
            $whereKey              = \Limepie\decamelize(\substr($name, 6));
            [$condition, $binds]   = $this->getConditionAndBinds($whereKey, $arguments);
            $selectFields          = $this->getSelectFields();
            $orderBy               = $this->getOrderBy();
            $limit                 = $this->getLimit();

            $sql = "
SELECT
    {$selectFields}
FROM
    `{$this->tableName}`
WHERE
    {$condition}
{$orderBy}
{$limit}
";

            $this->query = $sql;
            $this->binds = $binds;

            $data       = $this->getConnect()->gets($sql, $binds);
            $attributes = [];

            $class = \get_called_class();

            foreach ($data as $index => $row) {
                $attributes[$row[$this->arrayKeyName]] = new $class($this->getConnect(), $row, $this);
            }

            if ($attributes) {
                $this->attributes = $this->getRelations($attributes);

                return $this;
            }

            return [];
        } elseif (0 === \strpos($name, 'set')) {
            $fieldName                    = \Limepie\decamelize(\substr($name, 3));
            $this->attributes[$fieldName] = $arguments[0];

            return $this;
        } elseif ('gets' === $name) {
            $this->attributes      = [];
            $this->primaryKeyValue = '';
            $selectFields          = $this->getSelectFields();
            $orderBy               = $this->getOrderBy($arguments[0]['order'] ?? null);
            $limit                 = $this->getLimit();

            if (true === isset($arguments[0]['condition'])) {
                $condition = '    WHERE ' . $arguments[0]['condition'];
            } else {
                $condition = '';
            }

            if (true === isset($arguments[0]['binds'])) {
                $binds = $arguments[0]['binds'];
            } else {
                $binds = [];
            }

            $sql = "
SELECT
    {$selectFields}
FROM
    `{$this->tableName}`
{$condition}
{$orderBy}
{$limit}
";

            // if ($arguments[0]['order'] ?? false) {
            //     $sql .= 'ORDER BY' . \PHP_EOL . '    ' . $arguments[0]['order'];
            // }

            $this->query = $sql;
            $this->binds = $binds;

            $data = $this->getConnect()->gets($sql, $binds);

            $class = \get_called_class();

            $attributes = [];

            foreach ($data as $index => $row) {
                //index에서 seq로 변경
                $attributes[$row[$this->arrayKeyName]] = new $class($this->getConnect(), $row);
            }

            if ($attributes) {
                $attributes = $this->getRelations($attributes);
                $this->attributes = $attributes;

                return $this;
            }

            return [];
        } elseif (0 === \strpos($name, 'gets')) {
            $this->attributes      = [];
            $this->primaryKeyValue = '';
            $selectFields          = $this->getSelectFields();
            $orderBy               = $this->getOrderBy($arguments[0]['order'] ?? null);
            $limit                 = $this->getLimit();

            $sql = "
SELECT
    {$selectFields}
FROM
    `{$this->tableName}`
{$orderBy}
{$limit}
";

            $this->query = $sql;
            //$this->binds = $binds;

            $data       = $this->getConnect()->gets($sql);
            $attributes = [];

            $class = \get_called_class();

            foreach ($data as $index => $row) {
                //index에서 seq로 변경
                $attributes[$row[$this->arrayKeyName]] = new $class($this->getConnect(), $row);
            }


            if ($attributes) {
                $attributes = $this->getRelations($attributes);
                $this->attributes = $attributes;

                return $this;
            }

            return [];
        } elseif ('get' === $name) {
            $condition    = $arguments[0]['condition'];
            $binds        = $arguments[0]['binds'];
            $selectFields = $this->getSelectFields();

            $sql = "
SELECT
    {$selectFields}
FROM
    `{$this->tableName}`
WHERE
    {$condition}
LIMIT 1
";

            $this->query = $sql;
            $this->binds = $binds;

            $this->attributes = $this->getConnect()->get($sql, $binds);
            //pr($sql, $this->attributes, $binds);
            // where -> primaray

            if ($this->attributes) {
                $this->primaryKeyValue = $this->attributes[$this->primaryKeyName];

                return $this;
            }
        } elseif (0 === \strpos($name, 'get')) {
            $fieldName = \Limepie\decamelize(\substr($name, 3));

            if (!$fieldName) {
                throw new \Limepie\Exception('"' . $fieldName . '" field not found', 999);
            }
            //\pr($fieldName, $this->attributes[$fieldName]);

            return $this->attributes[$fieldName];
        } else {
            throw new \Limepie\Exception('"' . $name . '" function not found', 999);
        }
    }

    public function __debugInfo()
    {
        return $this->toArray();
    }

    public function getRelation($attributes)
    {
        if ($this->oneToOne) {
            foreach ($this->oneToOne as $class) {
                if ($class->left) {
                    $left = $class->left;
                } else {
                    $left = 'seq';
                }

                if ($class->right) {
                    $right = $class->right;
                } else {
                    $right = $class->tableName . '_seq';
                }
                //\pr($attributes, $this->tableName, $left, $right);
                $functionName                             = 'getBy' . \Limepie\camelize($right);

                $array = [$attributes[$left]];
                foreach($class->and as $key => $value) {
                    $functionName .= 'And'.ucfirst($key);
                    $array[] = $value;
                }
                //$data         = $class($this->getConnect())->{$functionName}($attributes[$left]);
                $data = call_user_func_array([$class($this->getConnect()), $functionName], $array);

                $attributes[$class->tableName . '_model'] = $data;
            }
        }

        if ($this->oneToMany) {
            //pr($this->tableName, $this->oneToMany, $attributes);
            foreach ($this->oneToMany as $class) {
                if ($class->left) {
                    $left = $class->left;
                } else {
                    $left = 'seq';
                }

                if ($class->right) {
                    $right = $class->right;
                } else {
                    $right = $class->tableName . '_seq';
                }

                $key = $left;

                if ($class->onArrayKeyName) {
                    $key = $class->onArrayKeyName;
                }

                $functionName = 'getsBy' . \Limepie\camelize($right);
                $array = [$attributes[$left]];
                foreach($class->and as $key => $value) {
                    $functionName .= 'And'.ucfirst($key);
                    $array[] = $value;
                }
                //$data         = $class($this->getConnect())->{$functionName}($attributes[$left]);
                $data = call_user_func_array([$class($this->getConnect()), $functionName], $array);
                $group        = [];

                foreach ($data as $row) {
                    $group[$row[$key]] = $row;
                }
                $attributes[$class->tableName . '_models'] = $group;
            }
        }

        return $attributes;
    }

    public function getRelations($attributes)
    {
        // $a = [];
        // foreach($attributes as $r) {
        //     pr($r);
        //     $a[$r['seq']] = $this->getRelation($r);
        // }
        // return $a;
        if ($this->oneToOne) {
            //pr($this->oneToOne);
            foreach ($this->oneToOne as $class) {
                if ($class->left) {
                    $left = $class->left;
                } else {
                    $left = 'seq';
                }

                if ($class->right) {
                    $right = $class->right;
                } else {
                    $right = $class->tableName . '_seq';
                }

                //pr($attributes, $this->tableName, $class->tableName, $left, $right);

                $seqs = [];

                foreach ($attributes as $row) {
                    $seqs[] = $row[$left];
                }

                if ($seqs) {
                    $functionName = 'getsBy' . \Limepie\camelize($right);
                    //pr($attributes, $this->tableName, $class, $functionName, $right);

                    $array = [$seqs];
                    foreach($class->and as $key => $value) {
                        $functionName .= 'And'.ucfirst($key);
                        $array[] = $value;
                    }
                    //$data = $class($this->getConnect())->{$functionName}($seqs);
                    //pr($array);
                    $data = call_user_func_array([$class($this->getConnect()), $functionName], $array);



                    if ($data) {
                        foreach ($attributes as $row) {
                            $row->offsetSet($class->tableName . '_model', $data[$row[$left]]);
                        }
                    }
                }
            }
        }

        if ($this->oneToMany) {
            //pr($this->tableName, $this->oneToMany, $attributes);
            foreach ($this->oneToMany as $class) {
                if ($class->left) {
                    $left = $class->left;
                } else {
                    $left = 'seq';
                }

                if ($class->right) {
                    $right = $class->right;
                } else {
                    $right = $class->tableName . '_seq';
                }

                $key = $left;

                if ($class->onArrayKeyName) {
                    $key = $class->onArrayKeyName;
                }

                $seqs = [];

                foreach ($attributes as $row) {
                    $seqs[] = $row[$left];
                }
                $functionName = 'getsBy' . \Limepie\camelize($right);

                $array = [$seqs];
                foreach($class->and as $key => $value) {
                    $functionName .= 'And'.ucfirst($key);
                    $array[] = $value;
                }
                //$data = $class($this->getConnect())->{$functionName}($seqs);
                //pr($array);
                $data = call_user_func_array([$class($this->getConnect()), $functionName], $array);

                if ($data) {
                    $group = [];

                    foreach ($data as $row) {
                        $group[$row[$right]][$row[$key]] = $row;
                    }
                    //pr($attributes, $group);
                    if ($group) {
                        foreach ($attributes as $att) {
                            //pr($group[$att[$left]]??[]);
                            $att->offsetSet($class->tableName . '_models', $group[$att[$left]] ?? []);
                        }
                    }
                }
            }
        }

        return $attributes;
    }

    public function getConditionAndBinds($whereKey, $arguments)
    {
        if (false !== \strpos($whereKey, '_and_')) {
            $whereKeys = \explode('_and_', $whereKey);
            $conds     = [];
            $binds     = [];

            foreach ($whereKeys as $index => $key) {
                if (true === \is_array($arguments[$index])) {
                    $_conditions = [];

                    foreach ($arguments[$index] as $index2 => $value2) {
                        $_conditions[]               = ":{$key}{$index2}";
                        $binds[':' . $key . $index2] = $value2;
                    }
                    $conds[] = "`{$this->tableName}`.`{$key}` IN (" . \implode(', ', $_conditions) . ')';
                } else {
                    $conds[]     = "`{$this->tableName}`." . '`' . $key . '`' . ' = :' . $key;
                    $binds[$key] = $arguments[$index];
                }
            }
            $condition = \implode(' AND ', $conds);
        } else {
            $whereValue = $arguments[0];

            if (true === \is_array($whereValue)) {
                $conditions = [];
                $binds      = [];

                foreach ($whereValue as $index => $value) {
                    $conditions[]                    = ":{$whereKey}{$index}";
                    $binds[':' . $whereKey . $index] = $value;
                }
                $condition = "`{$this->tableName}`.`{$whereKey}` IN (" . \implode(', ', $conditions) . ')';
            } else {
                $condition = "`{$this->tableName}`.`{$whereKey}` = :{$whereKey}";
                $binds     = [
                    ':' . $whereKey => $whereValue,
                ];
            }
        }

        return [$condition, $binds];
    }

    public function on($left, $right)
    {
        $this->left  = $left;
        $this->right = $right;

        return $this;
    }

    public function and($key, $value)
    {
        $this->and[$key] = $value;

        return $this;
    }

    public function oneToOne($class)
    {
        //pr([new $class]);
        $this->oneToOne[] = $class;

        return $this;
    }

    public function oneToMany($class)
    {
        //pr([new $class]);
        $this->oneToMany[] = $class;

        return $this;
    }

    public function limit($offset, $limit)
    {
        $this->offset = $offset;
        $this->limit  = $limit;

        return $this;
    }

    public function getLimit()
    {
        return $this->limit ? ' limit ' . $this->offset . ', ' . $this->limit : '';
    }

    public function getConnect()
    {
        if (!$this->pdo) {
            throw new \Exception('db connection not found');
        }

        return $this->pdo;
    }

    public function setConnect($connect)
    {
        return $this->pdo = $connect;
    }

    public function count()
    {
        return \count($this->attributes);
    }

    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->attributes[$offset];
        //return isset($this->attributes[$offset]) ? $this->attributes[$offset] : null;
    }

    public static function x__callStatic($name, $arguments)
    {
    }

    //iterator_to_array
    public function toArray()
    {
        if (true === isset($this->attributes[$this->primaryKeyName])) {
            return $this->attributes;
        }
        $attributes = [];

        foreach ($this->attributes as $index => $attribute) {
            //index에서 seq로 변경
            $attributes[$attribute[$this->primaryKeyName]] = $attribute->toArray();
        }

        return $attributes;
    }

    public function rewind()
    {
        \reset($this->attributes);
    }

    public function current()
    {
        $var = \current($this->attributes);

        return $var;
    }

    public function key()
    {
        $var = \key($this->attributes);

        return $var;
    }

    public function next()
    {
        $var = \next($this->attributes);

        return $var;
    }

    public function valid()
    {
        $key = \key($this->attributes);
        $var = (null !== $key && false !== $key);

        return $var;
    }

    public function create($debug = false)
    {
        // //pr([
        //     'type' => 'create',
        //     'table' => $this->tableName,
        //     'attributes' => $this->attributes,
        //     'where' => $this->primaryKeyName
        // ]);
        $fields = [];
        $binds  = [];
        $values = [];

        foreach ($this->attributes as $field => $value) {
            if ($this->sequenceName === $field) {
            } else {
                if (true === \is_array($value)) {
                    $fields[] = '`' . $field . '`';

                    $values[] = $value[0];

                    foreach ($value[1] as $vKey => $vValue) {
                        $binds[':' . $vKey] = $vValue;
                    }
                } else {
                    $fields[]            = '`' . $field . '`';
                    $binds[':' . $field] = $value;
                    $values[]            = ':' . $field;
                }
            }
        }
        $field  = \implode(', ', $fields);
        $values = \implode(', ', $values);
        $sql    = <<<SQL
INSERT INTO
    `{$this->tableName}`
({$field})
    VALUES
({$values})
SQL;

        //pr($sql, $binds);
        $result = false;

        if ($this->sequenceName) {
            $seq                                     = $this->getConnect()->setAndGetSequnce($sql, $binds);
            $this->attributes[$this->primaryKeyName] = $seq;
        } else {
            if ($this->getConnect()->set($sql, $binds)) {
                $seq = $this->attributes[$this->primaryKeyName];
            }
        }
        // $this->attributes = $this->getConnect()->get('select * from '.$this->tableName.' Where '.$this->primaryKeyName .' = :'.$this->primaryKeyName,[
        //     $this->primaryKeyName = $seq
        // ]);
        if ($debug) {
            \Limepie\pr($sql, $binds, [$this->primaryKeyName => $seq]);
        }

        //return $seq ? true : false;
        if ($seq) {
            $this->primaryKeyValue = $seq;

            return $this;
        }

        return false;
    }

    public function delete($debug = false)
    {
        if (true === isset($this->attributes[$this->primaryKeyName])) {
            $sql = "DELETE
FROM
    `{$this->tableName}`
WHERE
    `{$this->primaryKeyName}` = :{$this->primaryKeyName}
";

            $binds = [
                $this->primaryKeyName => $this->attributes[$this->primaryKeyName],
            ];

            if ($debug) {
                \Limepie\pr($sql, $binds);
            }

            if ($this->getConnect()->set($sql, $binds)) {
                $this->primaryKeyValue = '';
                $this->attributes      = [];

                return $this;
            }

            return false;
        }
        $result = false;

        //\pr($this->attributes);

        foreach ($this->attributes as $index => &$object) {
            $sql = "DELETE
FROM
    `{$object->tableName}`
WHERE
    `{$object->primaryKeyName}` = :{$object->primaryKeyName}
";
            //\pr($object);
            $binds = [
                $object->primaryKeyName => $object->attributes[$object->primaryKeyName],
            ];

            if ($debug) {
                \Limepie\pr($sql, $binds);
            }

            if ($this->getConnect()->set($sql, $binds)) {
                $object->primaryKeyValue = '';
                $object->attributes      = [];
                unset($ojbect);
            } else {
                $result = true;
            }
        }

        if ($result) {
            return $this;
        }

        return false;
    }

    // TODO: db에서 가져온것과 비교해서 바뀌지 않으면 업데이트 하지 말기
    public function update($debug = false)
    {
        // //pr([
        //     'type' => 'create',
        //     'table' => $this->tableName,
        //     'attributes' => $this->attributes,
        //     'where' => $this->primaryKeyName
        // ]);
        $fields = [];
        $binds  = [
            ':' . $this->primaryKeyName => $this->attributes[$this->primaryKeyName],
        ];

        foreach ($this->fields as $field) {
            if (true === \is_array($this->attributes[$field])) {
                $fields[] = "`{$this->tableName}`." . '`' . $field . '` = ' . $this->attributes[$field][0];

                foreach ($this->attributes[$field][1] as $vKey => $vValue) {
                    $binds[':' . $vKey] = $vValue;
                }
            } else {
                $fields[]            = "`{$this->tableName}`." . '`' . $field . '` = :' . $field;
                $binds[':' . $field] = $this->attributes[$field];
            }
        }
        $field = \implode(', ', $fields);
        //$bind  = \implode(', ', \array_keys($binds));
        $where = $this->primaryKeyName;
        $sql   = <<<SQL
UPDATE
    `{$this->tableName}`
SET
    {$field}
WHERE
   `{$where}` = :{$where}
SQL;

        if ($debug) {
            \Limepie\pr($sql, $binds);
        }

        if ($this->getConnect()->set($sql, $binds)) {
            return $this;
        }

        return false;
        //return $this;
    }

    public function getSelectFields()
    {
        $fields = [];

        foreach ($this->selectFields as $column => $alias) {
            if (true === \is_numeric($column)) {
                $fields[] = "`{$this->tableName}`." . '`' . $alias . '`';
            } else {
                $fields[] = $column . ' AS `' . $alias . '`';
            }
        }

        return \implode(', ', $fields);
    }

    public function getOrderBy($orderBy = null)
    {
        $sql = '';

        if ($orderBy) {
            $sql .= \PHP_EOL . 'ORDER BY' . \PHP_EOL . '    ' . $orderBy;
        } elseif ($this->orderBy) {
            $sql .= \PHP_EOL . 'ORDER BY' . \PHP_EOL . '    ' . $this->orderBy;
        }

        return $sql;
    }

    public function orderBy($orderBy)
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    public function addColumn($column, $aliasName)
    {
        $this->selectFields[$column] = $aliasName;

        return $this;
    }

    public function columns($columns)
    {
        $this->selectFields = $columns;

        return $this;
    }

    public function addColumns($columns)
    {
        $this->selectFields += $columns;

        return $this;
    }

    public function arrayKey($arrayKeyName)
    {
        $this->arrayKeyName = $arrayKeyName;

        return $this;
    }

    public function onArrayKey($arrayKeyName)
    {
        $this->onArrayKeyName = $arrayKeyName;

        return $this;
    }

    public function debug()
    {
        \pr($this->query, $this->binds);
        //exit;
    }
}
