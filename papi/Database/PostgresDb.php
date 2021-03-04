<?php

declare(strict_types=1);

namespace papi\Database;

use config\DatabaseConfig;

class PostgresDb
{
    public mixed $connection;

    private int $aliasCount = 0;

    private array $aliasValues = [];

    public static function getConnection(): mixed
    {
        $isLocal = DatabaseConfig::isLocal();
        $name = DatabaseConfig::getName();
        $user = DatabaseConfig::getUsername();
        $password = DatabaseConfig::getPassword();

        if ($isLocal) {
            return pg_connect("dbname = $name user = $user password = $password");
        }

        $host = DatabaseConfig::getServer();

        return pg_connect("host = $host dbname = $name user = $user password = $password");
    }

    public function __construct()
    {
        $this->connection = self::getConnection();
        //        set_error_handler(
        //            function ($number, $text) {
        //                if (str_contains($text, 'Query failed')) {
        //                    pg_close($this->connection);
        //                }
        //            }
        //        );
    }

    public function query(
        string $sql
    ): bool {
        $result = pg_query($this->connection, $sql);
        if ($result === false) {
            throw $this->throwError();
        }

        return true;
    }

    public function throwError(): \RuntimeException
    {
        $error = pg_last_error($this->connection);

        return new \RuntimeException('DB ERROR: '.$error);
    }

    public function clearAliases(): void
    {
        $this->aliasValues = [];
        $this->aliasCount = 0;
    }

    public function exists(
        string $table,
        ?array $filters = null,
    ): bool {
        $query = "select exists(select 1 from $table";

        if ($filters) {
            $this->addFilters($query, $filters);
        }
        $query .= ')';
        $queryParams = pg_query_params($this->connection, $query, $this->aliasValues);

        if ($queryParams === false) {
            throw $this->throwError();
        }
        if (($result = pg_fetch_row($queryParams)) === false) {
            throw $this->throwError();
        }

        return $result[0] === 't';
    }

    public function select(
        string $from,
        ?array $columns = null,
        ?array $filters = null,
        ?string $orderBy = null,
        ?string $order = null,
        ?int $limit = null
    ): array {
        if ($columns) {
            $query = 'select '.implode(',', $columns)." from $from";
        } else {
            $query = "select * from $from";
        }
        if ($filters) {
            $this->addFilters($query, $filters);
        }
        if ($orderBy) {
            if ($order && $order !== 'desc') {
                $order = 'asc';
            }
            $query .= ' order by '.pg_escape_string($orderBy)." $order";
        }
        if ($limit) {
            $query .= " limit $limit";
        }
        $queryParams = pg_query_params($this->connection, $query, $this->aliasValues);

        if ($queryParams === false) {
            throw $this->throwError();
        }

        return pg_fetch_all($queryParams);
    }

    public function delete(
        string $table,
        array $where = []
    ): int {
        $query = "delete from $table";
        $this->addFilters($query, $where);
        $queryParams = pg_query_params($this->connection, $query, $this->aliasValues);
        if ($queryParams === false) {
            throw $this->throwError();
        }

        return pg_affected_rows($queryParams);
    }

    public function insert(
        string $table,
        array $data
    ): array {
        $query = "insert into $table ";
        $query .= '('.implode(', ', array_keys($data)).')';
        $query .= ' values(';
        foreach ($data as $key => $condition) {
            if (array_key_first($data) !== $key) {
                $query .= ', ';
            }
            $this->addAlias($query, $condition);
        }
        $query .= ') returning *';
        $queryParams = pg_query_params($this->connection, $query, $this->aliasValues);

        if ($queryParams === false) {
            throw $this->throwError();
        }

        $result = pg_fetch_assoc($queryParams);

        if ($result === false) {
            throw $this->throwError();
        }

        return $result;
    }

    public function update(
        string $table,
        array $data,
        array $where
    ): int {
        $query = "update $table set ";
        $firstKey = array_key_first($data);

        foreach ($data as $key => $condition) {
            if (! is_string($key)) {
                throw new \RuntimeException('Array keys must be of type string');
            }
            if ($firstKey !== $key) {
                $query .= ',';
            }
            $query .= pg_escape_string($key).'=';
            $this->addAlias($query, $condition);
        }
        $this->addWhereConditions($query, $where);
        $queryParams = pg_query_params($this->connection, $query, $this->aliasValues);

        if ($queryParams === false) {
            throw $this->throwError();
        }

        return pg_affected_rows($queryParams);
    }

    private function addAlias(string &$query, mixed $value): void
    {
        $query .= ' $'.++$this->aliasCount;
        $this->aliasValues[] = $value;
    }

    private function addWhereConditions(string &$query, array $where): void
    {
        $query .= ' where ';
        $firstKey = array_key_first($where);
        foreach ($where as $key => $condition) {
            if (! is_string($key)) {
                throw new \RuntimeException('Array keys must be of type string');
            }
            if ($firstKey !== $key) {
                $query .= ' and ';
            }
            $query .= pg_escape_string($key);
            $this->addAlias($query, $condition);
        }
    }

    private function addFilters(string &$query, array $filters): void
    {
        $query .= ' where ';
        $firstKey = array_key_first($filters);
        foreach ($filters as $key => $condition) {
            if (! is_string($key)) {
                throw new \RuntimeException('Array keys must be of type string');
            }
            if ($firstKey !== $key) {
                $query .= ' and ';
            }
            $query .= pg_escape_string($key);
            if (! in_array(substr($key, -1), ['<', '>', '='])) {
                $query .= '=';
            }
            $this->addAlias($query, $condition);
        }
    }
}