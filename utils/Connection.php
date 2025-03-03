<?php
include_once UTILS_PATH . 'Env.php';

class Connection
{
    protected static ?PDO $PDO = null;

    /**
     * Returns a singleton instance of the PDO connection.
     *
     * @throws PDOException If the connection to the database fails.
     */
    public static function getInstance(): PDO
    {
        if (self::$PDO === null) {
            $db_name = getenv('DB_NAME');
            $host = getenv('DB_HOST');
            $user = getenv('DB_USER');
            $password = getenv('DB_PASS');
            $charset = getenv('DB_CHARSET');

            $dsn = "mysql:host={$host};dbname={$db_name};charset={$charset}";
            self::$PDO = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        }
        return self::$PDO;
    }
}

    // private function onGoingProcessRelations($data)
    // {
    //     $parent_table = rtrim($this->table, 's');

    //     foreach ($data as $body) {
    //         foreach ($this->relations as $table => $relation) {
    //             $this->resetQuery();
    //             $table_id = is_array($data) ? $body : $data;

    //             $query = $this->table($table)->select($relation->tables);

    //             // Handle one-to-many and many-to-one
    //             if ($relation->type === 'has') {
    //                 $relationQuery = $query->where($parent_table . "_id", $table_id->id);
    //             } elseif ($relation->type === 'belongsTo') {
    //                 $parent_table_id = $table_id->{$parent_table . "_id"};
    //                 $relationQuery = $query->where("id", $parent_table_id);
    //             }

    //             // Handle many-to-many
    //             if (isset($relation->pivotTable)) {
    //                 $pivotQuery = $this->table($relation->pivotTable)
    //                     ->select([$relation->relatedTable . '.*'])
    //                     ->where($relation->pivotTable . '.' . $parent_table . '_id', $table_id->id);

    //                 // Join pivot with related table
    //                 $query = $pivotQuery->join($relation->relatedTable, $relation->pivotTable . '.related_id', '=', $relation->relatedTable . '.id');
    //                 $relationQuery = $query;
    //             }

    //             $body->{$table} = $relationQuery->get();
    //         }
    //     }

    //     return $data;
    // }

    // public function onGoingWith($relations = []): self
    // {
    //     $result = new stdClass();

    //     foreach ($relations as $relation) {
    //         $relationSelection = explode(':', $relation);
    //         $parts = explode('.', $relationSelection[0]);
    //         $tables = isset($relationSelection[1]) ? explode('.', $relationSelection[1]) : ['*'];

    //         $relationObj = new stdClass();
    //         $relationObj->type = $parts[0] ?? null;
    //         $relationObj->mode = $parts[1] ?? null;
    //         $relationObj->tables = $tables;

    //         // Handle many-to-many relations
    //         if ($relationObj->mode === 'many' && isset($parts[2]) && $parts[2] === 'pivot') {
    //             // Extract pivot table and related tables
    //             $pivotTable = $parts[3] ?? null;
    //             $relatedTable = $parts[4] ?? null;

    //             // Define how to fetch the many-to-many data using the pivot table
    //             $relationObj->pivotTable = $pivotTable;
    //             $relationObj->relatedTable = $relatedTable;
    //         }

    //         $result->{$parts[2]} = $relationObj;
    //     }

    //     $this->relations = $result;
    //     return $this;
    // }
