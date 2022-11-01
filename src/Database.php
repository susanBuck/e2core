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
        try {
            $prepared = $this->pdo->prepare($statement);
            $prepared->execute($data);
            return $prepared;
        } catch (\PDOException $e) {
            dump($statement);
            dump($e->getMessage());
        }
    }

    /**
     * Returns all row from the given $table
     */
    public function all($table)
    {
        return $this->run("SELECT * FROM ".$table." ORDER BY id DESC")->fetchAll();
    }

    /**
     * Insert a row into $table, given an array of $data where
     * each key corresponds to a field name
     */
    public function insert($table, $data = [])
    {
        $fields = array_keys($data);

        $sql = "INSERT INTO " . $table . "(" . implode(', ', $fields).") values (:" . implode(', :', $fields) . ")";

        $this->run($sql, $data);

        return true;
    }

    /**
     * Return all rows where the given $column matches the given $value
     */
    public function findByColumn($table, $column, $operator, $value)
    {
        $sql = "SELECT * FROM `" . $table . "` WHERE `" . $column . "` " . $operator . " :" . $column;
        
        $statement = $this->run($sql, [
            $column => $value
        ]);
        
        return ($statement) ? $statement->fetchAll() : null;
    }

    /**
    * Return a single row where the id matches the given $id
    */
    public function findById($table, $id)
    {
        $sql = "SELECT * FROM " . $table . " WHERE id = :id";
        
        $statement = $this->run($sql, ['id' => $id]);

        $results = $statement->fetch();

        return ($results) ? $results : null;
    }

    /**
     * Migration method used to create a new table
     */
    public function createTable($table, $columns)
    {
        # Drop table if it exists
        $sql = 'DROP TABLE IF EXISTS ' . $table . ';';
        $this->run($sql, []);

        # Create table
        $sql = ' CREATE TABLE ' . $table . ' (';

        # Set up table with auto-incremending primary key `id`
        $sql .= 'id int NOT NULL AUTO_INCREMENT,';
        $sql .= 'PRIMARY KEY (id), ';
        
        foreach ($columns as $name => $type) {
            $sql .= $name . ' ' . $type . ',';
        }

        $sql = rtrim($sql, ',').') ENGINE=InnoDB DEFAULT CHARSET=utf8;';
        
        $this->run($sql, []);

        return $sql;
    }
}