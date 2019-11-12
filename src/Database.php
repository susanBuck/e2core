<?php
namespace E2;

class Database
{
    private $pdo;

    /**
     * Establish a PDO connection
     */
    public function __construct($host, $database, $username, $password, $charset)
    {
        $dsn = "mysql:host=$host;dbname=$database;charset=$charset";
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $this->pdo = new \PDO($dsn, $username, $password, $options);
        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Generic method for preparing a given $statement with the provided $data
     */
    public function run($statement, $data = null)
    {
        $statement = $this->pdo->prepare($statement);
        $statement->execute($data);
        return $statement->fetchAll();
    }

    /**
     * Returns all row from the given $table
     */
    public function all($table)
    {
        return $this->run("SELECT * FROM ".$table);
    }

    /**
     * Insert a row into $table, given an array of $data where
     * each key corresponds to a field name
     */
    public function insert($table, $data = [])
    {
        $fields = array_keys($data);

        $sql = "INSERT INTO ".$table. "(".implode(', ', $fields).") values (:".implode(', :', $fields).")";

        return $this->run($sql, $data);
    }
}
