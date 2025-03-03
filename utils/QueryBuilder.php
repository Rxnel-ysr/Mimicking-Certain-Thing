<?php
// QueryBuilder.php
include_once UTILS_PATH . 'Connection.php';

class Query extends Connection
{
    # Base var
    protected $query;
    protected $table;
    protected $columns = [];
    protected $fillable = [];
    protected $guarded = [];
    protected $primaryKeyColumn;
    protected $usedSelect = false;
    protected $bindings = [];
    protected $stmt;
    protected $pdo;

    # Relations var
    protected $parentTable;
    protected $related = [];
    protected $relations = [];
    protected $method_1 = true;
    protected $allowBruteForceSearching = false;

    # Attributes
    protected $restrain = true;

    /**
     * Query constructor, reuses the singleton PDO connection.
     */
    public function __construct()
    {
        $this->pdo = Connection::getInstance();
    }

    /**
     * Get ip of person or thing that made the query.
     */
    private function getRequestIp()
    {
        return '[' . $_SERVER['REMOTE_ADDR'] . ']:';
    }

    /**
     * Determine to use WHERE or AND in query.
     * 
     * @return string WHERE or AND
     */
    private function whereOrAnd()
    {
        return (strpos($this->query, 'WHERE') === false ? ' WHERE' : ' AND') . ' ';
    }

    /**
     * 
     */
    private function decideSelect()
    {
        return (!empty($this->columns) ? implode(', ', $this->columns) : '*');
    }

    private function filterColumns($columns = [])
    {
        return array_diff_key(array_intersect_key($columns, $this->fillable), $this->guarded);
    }

    /**
     * The very first method you'll use to specify the table, or the query just won't work.
     * 
     */
    public function table($table, $primaryKeyColumn = 'id'): self
    {
        $this->table = $table;
        $this->primaryKeyColumn = $primaryKeyColumn;
        return $this;
    }

    /**
     * To avoid overwhelming our potato server, if don't need all the data fetched from query, use this, I recommended it.
     * 
     * @param array $columns Mention each columns to be selected
     */
    public function select($columns = ['*']): self
    {
        $columns = implode(', ', $columns);
        $this->query = 'SELECT ' . $columns . ' FROM ' . $this->table;
        $this->usedSelect = true;
        return $this;
    }

    /**
     * Switches between "id_<name>" and "<name>_id" formats.
     *
     * This function checks the input pattern and switches it to the opposite format.
     * - If the input matches "id_<name>", it converts to "<name>_id".
     * - If the input matches "<name>_id", it converts to "id_<name>".
     *
     * @param string $column The column name to switch.
     * 
     * @return string The switched column name.
     */
    private function switchColumnPattern(string $column): string
    {
        // Match 'id_<name>'
        if (preg_match('/^id_(\w+)$/', $column, $matches)) {
            return "{$matches[1]}_id";
        }

        // Match '<name>_id'
        if (preg_match('/^(\w+)_id$/', $column, $matches)) {
            return 'id_' . $matches[1];
        }

        // Return the original column if no pattern matches
        return $column;
    }

    // My old docs, look sucks haha
    /**
     * Use this if you have relations in your table. 
     * 
     * The format is Array ['type.mode.table']. 
     * 
     * Table is your relation table. 
     * 
     * Type, which has [ 'has' / 'belongsTo' ]. 
     * 
     * Mode, also have two type [ 'one' / 'many' ].
     * 
     * 
     */

    /**
     * Define and handle relations in your table.
     *
     * This method allows you to specify relations for your table in a structured format:
     * 
     * `['type.mode.table']`
     * 
     * ### Parameters:
     * - `type`: Defines the relationship type. Available options:
     *   - `'has'`: Indicates that the current table "has" a related table.
     *   - `'belongsTo'`: Indicates that the current table "belongs to" another table.
     * 
     * - `mode`: Defines the cardinality of the relationship. Available options:
     *   - `'one'`: Represents a one-to-one relationship.
     *   - `'many'`: Represents a one-to-many relationship.
     * 
     * - `table`: Specifies the name of the related table.
     * 
     * ### Extended Selection (optional):
     * - You can refine the selection of columns from the related table by appending column names after a colon (`:`).
     * - Example: `'has.one.users:id.name.email'`
     *   - Type: `'has'`
     *   - Mode: `'one'`
     *   - Table: `'users'`
     *   - Selected Columns: `'id', 'name', 'email'`
     * 
     * ### Optional Features:
     * - You can further define pivot tables or foreign key columns for many-to-many relationships.
     * - Example: `['belongsTo.many.tags:id.name:post_tag']`
     *   - Type: `'belongsTo'`
     *   - Mode: `'many'`
     *   - Table: `'tags'`
     *   - Columns to be selected in `Table`: `'id','name'`
     *   - Pivot table `'post_tag'`
     * 
     *  `Note`: Type will be ignored if you setting pivot table.
     * 
     * ### Usage Example:
     * ```php
     * $object->with([
     *     'has.one.users:id.name',
     *     'belongsTo.many.posts',
     * ]);
     * ```
     * 
     * ### Behavior:
     * - The method processes the provided relations and stores them in the `relations` property as an object.
     * - Each relation is represented as an object containing:
     *   - `type` (string): The relationship type (`has` or `belongsTo`).
     *   - `mode` (string): The cardinality (`one` or `many`).
     *   - `columns` (array): The columns to be selected from the related table.
     *   - `foreign_key_column` (string, optional): The foreign key column used for many-to-many relationships.
     *   - `pivot_table` (string, optional): The pivot table for many-to-many relationships.
     * 
     * The person who make this is sick when he do it, let appreciated his Masochism.
     * 
     * Just remember to name foreign key column to like `<name>_id`, or `id_<name>` but not in completely different name, this code is not sentient enough to do that.
     * 
     * @param array $relations List of relations in the defined format.
     * @param bool $allowBruteForceSearching Set this to if you permitted brute force to search column name pattern
     * @return self Fluent interface for method chaining.
     */
    public function with($relations = [], $allowBruteForceSearching = false): self
    {
        $result = new stdClass();
        $this->allowBruteForceSearching = $allowBruteForceSearching;
        foreach ($relations as $relation) {
            $relationSelection = explode(':', $relation);
            $parts = explode('.', $relationSelection[0]);
            $columns =  (isset($relationSelection[1]))
                ? explode('.', $relationSelection[1])
                : ['*'];

            $relation = new stdClass();
            $relation->type = $parts[0] ?? null;
            $relation->mode = $parts[1] ?? null;
            (isset($parts[3])) ? $relation->foreign_key_column = $parts[3] : '';
            (isset($parts[4])) ? $relation->primary_key_column = $parts[4] : '';
            (isset($relationSelection[2])) ? $relation->pivot_table = $relationSelection[2] : '';

            $relation->columns = $columns;

            $result->{$parts[2]} = $relation;
        }

        $this->relations = $result;
        return $this;
    }

    // // SELECT table
    // public function table($table)
    // {
    //     $this->query = "SELECT * FROM $table";
    //     return $this;
    // }

    /**
     * where clause for all the method, you'll need to use this often if you're a backend, or else you'll be cooked
     * 
     * @param string $column
     * @param mixed|array $value Value to match in column
     * @param string $operator Defaulted to '='
     */
    public function where($column, $value, $operator = '='): self
    {
        if ($operator === 'BETWEEN' || $operator === 'NOT BETWEEN') {
            if (!is_array($value) || count($value) !== 2) {
                throw new InvalidArgumentException('The BETWEEN operator requires an array with exactly two values.');
            }

            $this->query .= $this->whereOrAnd() . $column . ' ' . $operator . ' ? AND ?';
            $this->bindings = array_merge($this->bindings, $value);

            return $this;
        }

        if (is_array($value)) {
            $placeholders = implode(', ', array_fill(0, count($value), '?'));

            $this->query .= $this->whereOrAnd() . $column . ($operator == '!=' ? ' NOT IN' : ' IN') . ' (' . $placeholders . ')';
            $this->bindings = array_merge($this->bindings, $value);
            return $this;
        }

        if ($value == null || strtolower($value) == 'null') {
            $this->query .= $this->whereOrAnd() . $column . ($operator == '!=' ? ' IS NOT NULL' : ' IS NULL');
            return $this;
        }

        $this->query .= $this->whereOrAnd() .  $column . ' ' . $operator . '?';
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Or where clause for all the method, addition to where()
     * 
     * @param string $column
     * @param mixed|array $value Value to match in column
     * @param string $operator Defaulted to '='
     */
    public function orWhere($column, $value, $operator = '=')
    {
        $this->query .= ' OR';

        if ($operator === 'BETWEEN' || $operator === 'NOT BETWEEN') {
            if (!is_array($value) || count($value) !== 2) {
                throw new InvalidArgumentException('The BETWEEN operator requires an array with exactly two values.');
            }

            $this->query .= ' ' . $column . ' ' . $operator . ' ? AND ?';
            $this->bindings = array_merge($this->bindings, $value);
            return $this;
        }

        if (is_array($value)) {
            $placeholders = implode(', ', array_fill(0, count($value), '?'));

            $this->query .= ' ' . $column . ($operator == '!=' ? ' NOT IN' : ' IN') . ' (' . $placeholders . ')';
            $this->bindings = array_merge($this->bindings, $value);
            return $this;
        }

        if ($value == null || strtolower($value) == 'null') {
            $this->query .= ' ' . $column . ($operator == '!=' ? ' IS NOT NULL' : ' IS NULL');
            return $this;
        }

        $this->query .=  ' ' . $column . ' ' . $operator . ' ?';
        $this->bindings[] = $value;
        return $this;
    }

    /**
     * Dedicated to only find row matched given primary key
     * 
     * @param int $primaryKey Primary key to search
     * 
     * @return self|false Will return an object or false if there is no match found
     */
    public function find($primaryKey)
    {
        $this->query .= $this->whereOrAnd() . $this->primaryKeyColumn . ' = ?';
        $this->bindings[] = $primaryKey;
        return $this;
    }

    /**
     * Friendly reminder, you need to add table() before this, so that all these method will work or you can just make new class extending this class
     * @param array $data `['column' => 'value']`
     */
    public function insert($data = []): self
    {
        $columns = implode(', ', array_keys($this->filterColumns($data)));
        $placeholders = implode(', ', array_fill(0, count($this->filterColumns($data)), '?'));

        $this->query = 'INSERT INTO ' . $this->table . ' (' . $columns . ') VALUES (' . $placeholders . ')';
        $this->bindings = array_values($data);
        $this->stmt = $this->pdo->prepare($this->query);
        $this->stmt->execute($this->bindings);
        return $this;
    }

    /**
     * Get all record within array and inside array there is all of object from query
     * 
     */
    public function get($skipRelations = false)
    {
        try {
            $query = $this->usedSelect ? '' : 'SELECT ' . $this->decideSelect() . ' FROM ' . $this->table;
            error_log($this->getRequestIp() . $query . $this->query);
            error_log(json_encode($this->bindings));

            $this->stmt = $this->pdo->prepare($query . $this->query);
            $this->stmt->execute($this->bindings);
            $this->resetQuery();

            $main_body = $this->stmt->fetchAll(PDO::FETCH_OBJ) ?: [];

            if (empty($this->relations) || $skipRelations) {
                return $main_body;
            }

            return $this->processRelations($main_body);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return [];
        }
    }

    // /**
    //  * Prepare a query for fetching related data.
    //  */
    // private function prepareRelationQuery(string $relation, string $foreignKey, $value)
    // {
    //     $this->bindings = [];
    //     $this->query = '';
    //     return $this->table($relation)->where($foreignKey, $value);
    // }

    /**
     * Get the very first record in order as an object
     */
    public function first($skipRelations = false): object|null
    {
        try {
            $query = $this->usedSelect ? '' : 'SELECT ' . $this->decideSelect() . ' FROM ' . $this->table;
            $this->query .= ' LIMIT 1';
            error_log($this->getRequestIp() . $query . $this->query);
            error_log(json_encode($this->bindings));

            $this->stmt = $this->pdo->prepare($query . $this->query);
            $this->stmt->execute($this->bindings);
            $this->resetQuery();

            $main_body = $this->stmt->fetch(PDO::FETCH_OBJ);
            if (!$main_body) {
                return null;
            }

            if (empty($this->relations) || $skipRelations) {
                return $main_body;
            }

            return $this->processRelations($main_body);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return null;
        }
    }

    /**
     * Dedicated to only find and fetch row matched given primaryKey
     * 
     * @param int $primaryKey Primary key primaryKey to search
     * 
     * @return object|false Will return an object or false if there is no match found
     */
    public function fetchWherePrimary($primaryKey, $skipRelations = false): object|false
    {
        $query = $this->usedSelect ? '' : 'SELECT ' . $this->decideSelect() . ' FROM ' . $this->table;
        $this->query .= $this->whereOrAnd() . $this->primaryKeyColumn . ' = ?';
        $this->bindings[] = $primaryKey;
        error_log($this->getRequestIp() . $query . $this->query);
        error_log(json_encode($this->bindings));

        $this->stmt = $this->pdo->prepare($query . $this->query);
        $this->stmt->execute($this->bindings);

        $user_data = $this->stmt->fetch(PDO::FETCH_OBJ);
        if (!$user_data) {
            return false;
        }
        return (!empty($this->relations) && !$skipRelations) ? $this->processRelations($user_data) : $user_data;
    }


    /**
     * Same like delete(), don't forget to add where() before this
     * 
     * @param array $data Data that will be updated in database, accept a key value pairs array, example:
     * 
     *  ```php
     * $object->table('user')->find(1)->update(["username" => "new_username"]);
     * ```
     * 
     * That will select id `1` at table `user` and update column `username` with `new_username`
     * 
     * @param array $data `['column' => 'value']`
     * @param bool $ignoreWhereWarning [optional]
     * 
     * Set this to `true` to bypass where warning and if you `EXTREMELY` aware and have `CONSENT` of what you wanna do.
     * 
     */
    public function update($data = [], $ignoreWhereWarning = false)
    {
        if (strpos($this->query, 'WHERE') === false && !$ignoreWhereWarning) {
            error_log('You need to specify what to update in ' . $this->table . ' table, else you\'ll update everything');
            throw new Exception("Missing where clause");
        }
        if ($ignoreWhereWarning) {
            error_log('WARNING: Ignoring WHERE clause, all records will be updated!');
        }
        $set = [];
        foreach (array_keys($data) as $key) {
            $set[] = $key . ' = ?';
        }
        $this->bindings = array_merge(array_values($this->filterColumns($data)), $this->bindings);
        $query = 'UPDATE ' . $this->table . ' SET ' . implode(', ', $set);
        error_log($this->getRequestIp() . $query . $this->query);
        error_log(json_encode($this->bindings));
        $this->stmt = $this->pdo->prepare($query . $this->query);
        $this->stmt->execute($this->bindings);
        return $this->stmt;
    }

    /**
     * This will delete everything if you are no specify which row to delete with where().
     * Anyways, be careful with this one, but I am not that careless, I'll put exception here just in case there is no where()
     * 
     * @param bool $ignoreWhereWarning Set this to `true` to bypass where warning and if you `EXTREMELY` aware of what you wanna do
     */
    public function delete($ignoreWhereWarning = false)
    {
        if (strpos($this->query, 'WHERE') === false && !$ignoreWhereWarning) {
            error_log('You need to specify what to delete in ' . $this->table . ' table, else you\'ll delete everything');
            throw new Exception('Missing where clause');
        }
        if ($ignoreWhereWarning) {
            error_log('WARNING: Ignoring WHERE clause, all records will be deleted!');
        }
        $query = 'DELETE FROM ' . $this->table;
        error_log($this->getRequestIp() . $query . $this->query);
        error_log(json_encode($this->bindings));
        $this->stmt = $this->pdo->prepare($query . $this->query);
        $this->stmt->execute($this->bindings);
        return $this->stmt;
    }

    /**
     * Processes relationships for a given data set and retrieves related data based on the defined relationships.
     *
     * @deprecated The ways this handle relation is really not effective, I'll make new one
     * This method dynamically handles relationships defined in `$this->relations` for both single objects 
     * and arrays of objects. It supports two main types of relationships: 
     * 'has' and 'belongsTo', as well as many-to-many relationships using pivot tables.
     *
     * Key Features:
     * 1. **Single Object or Array**: Adapts automatically to process either a single object or an array of objects.
     * 2. **Dynamic Foreign Key Resolution**: Resolves foreign key column names dynamically for 'has', 'belongsTo', and pivot table cases.
     * 3. **Fallback Mechanism**: Handles edge cases where column naming conventions vary between tables by attempting alternative naming methods.
     * 4. **Recursive Query Resetting**: Ensures that queries do not interfere with each other by resetting state after every operation.
     * 
     * Behavior:
     * - If the data is an array, the function processes each object in the array and fetches related data for each one.
     * - If the data is a single object, it fetches related data for that object and merges the results into the original object.
     *
     * Many-to-Many Relationships:
     * - Handles cases where a pivot table is defined in the relationship.
     * - Dynamically determines the correct foreign key columns for both parent and related tables.
     * - Fetches related data using the IDs extracted from the pivot table.
     *
     * @param array|object $data The input data to process, either as an array of objects or a single object.
     * 
     * @return array|object Processed data with related data merged in:
     *   - If input is an array, returns an array with related data added to each object.
     *   - If input is a single object, returns the object with the related data merged in.
     *
     * @throws PDOException If there is an issue fetching related data or resolving relationships.
     */
    // From my self
    private function processRelationsOld($data)
    {
        $parent_table = rtrim($this->table, 's');

        foreach ($data as $body) {
            $cache = [];
            foreach ($this->relations as $table => $relation) {
                $array_or_object = (is_array($data)) ? $body : $data;

                if (!isset($relation->pivot_table)) {
                    $query = $this->table($table)
                        ->select($relation->columns);

                    if ($relation->type === 'has') {
                        $relationQuery = $query->where(
                            (!isset($relation->foreign_key_column)) ? $parent_table . '_id' : $relation->foreign_key_column,
                            $array_or_object->id
                        );
                    } else {
                        $parent_id_table = (!isset($relation->foreign_key_column)) ? rtrim($table, 's') . '_id' : $relation->foreign_key_column;

                        $relationQuery = $query->where(
                            'id',
                            $array_or_object->$parent_id_table
                        );
                    }

                    $relation_data = $cache[$table] = ($relation->mode === 'one')
                        ? $relationQuery->first(true)
                        : $relationQuery->get(true);

                    if (is_array($data)) {
                        $body->{$table} = $relation_data;
                    } else {
                        $this->related[$table] = $relation_data;
                    }
                } else {
                    $this->method_1 = (isset($relation->foreign_key_column)) ?  preg_match('/^(\w+)_id$/', $relation->foreign_key_column) : (($this->method_1) ? true : false);

                    // Determine the foreign key column name based on the method, where original method id english naming e.g. '<column_name>_id'
                    $pivot_parent_table = (!isset($relation->foreign_key_column))
                        ? (($this->method_1 === true) ? $parent_table . '_id'
                            : 'id_' . $parent_table)
                        : ((preg_match('/^(\w+)_id$/', $relation->foreign_key_column))
                            ? $parent_table . '_id'
                            : 'id_' . $parent_table);

                    #====== Fetching pivot ids ====#
                    error_log("Fetching $relation->pivot_table belonged to $this->table id: " . $array_or_object->id);

                    $pivot_table_data = $this->table($relation->pivot_table)
                        ->where($pivot_parent_table, $array_or_object->id)
                        ->get(true);

                    // If it fails, try again, but with the alternative method, if permitted
                    if (!$pivot_table_data && $this->allowBruteForceSearching == false) {
                        // throw new Exception("Preventing additional query due no match found, check your naming pattern");
                        error_log("Leaving this relation due allowBruteForceSearching set to false, skipping this relation...");
                        continue;
                    }

                    if (!$pivot_table_data) {
                        error_log("No matched column found, trying other way with ($pivot_parent_table).");
                        $this->method_1 = false;
                        $pivot_parent_table = $this->switchColumnPattern($pivot_parent_table);
                        error_log("Decided column name: $pivot_parent_table");

                        $pivot_table_data = $this->table($relation->pivot_table)
                            ->where($pivot_parent_table, $array_or_object->id)
                            ->get(true);

                        if (!$pivot_table_data) {
                            error_log("Are you sure these columns are the right ones? or its really don't exists?\n\nSkipping...");
                            continue;
                        }
                    }

                    error_log("Success!\nResult: \n" . json_encode($pivot_table_data, JSON_PRETTY_PRINT));
                    #===========================#

                    // Determine the foreign key column for the related table
                    $belonged_table_column = rtrim($table, 's'); // Remove trailing 's'

                    error_log("With method 1: ");
                    error_log(($this->method_1 == true) ? "true" : "false");
                    error_log("Setting attribute name.");

                    $belonged_table = (!isset($relation->foreign_key_column))
                        ? (($this->method_1 === true)
                            ? $belonged_table_column . '_id'
                            :  'id_' . $belonged_table_column
                        )
                        : ((preg_match('/^(\w+)_id$/', $relation->foreign_key_column))
                            ? $belonged_table_column . '_id'
                            : 'id_' . $belonged_table_column);

                    error_log("Belonged table column: $belonged_table_column");
                    error_log("Decided attribute name: " . $belonged_table);
                    // die();

                    // Get all the ids we needed from desired table.
                    $belonged_ids = array_map(function ($item) use ($belonged_table) {
                        return $item->$belonged_table;
                    }, $pivot_table_data);

                    if (empty($belonged_ids) || $belonged_ids[0] === null) {

                        error_log("Attribute ($belonged_table) not found inside Pivot table query result, switching method.");
                        $belonged_table = $this->switchColumnPattern($belonged_table);
                        error_log("Decided attribute name: $belonged_table");

                        $belonged_ids = array_map(function ($item) use ($belonged_table) {
                            return $item->$belonged_table;
                        }, $pivot_table_data);

                        error_log("Matched!");
                    }

                    $query = $this->table($table)
                        ->select($relation->columns)
                        ->where('id', $belonged_ids);

                    $relation_data = $cache[$table] = ($relation->mode === 'one')
                        ? $query->first(true)
                        : $query->get(true);
                    $this->resetQuery();


                    if (is_array($data)) {
                        $body->{$table} = $relation_data;
                    } else {
                        $this->related[$table] = $relation_data;
                    }
                }
            }
        }
        return !is_array($data)
            ? (object)array_merge((array)$data, $this->related)
            : $data;
    }

    public function processRelations($data)
    {
        $parent_table = rtrim($this->table, 's');

        foreach ($this->relations as $relation_table_name => $relation) {
            if (isset($relation->pivot_table)) {
                $main_body = $data;
                // printAsJson($main_body);
                $main_body_ids = array_map(fn($body) => $body->id, $main_body);
                // echo "you are here";
                $related_foreign_key_column = $relation->foreign_key_column ?? rtrim($relation_table_name, 's') . '_id';

                $pivot_table_data = $this->table($relation->pivot_table)->where($relation->primary_key_column ?? $parent_table . '_id', $main_body_ids)->get(true);

                $related_table_ids = array_column($pivot_table_data, $related_foreign_key_column);;
                $related_data = $this->table($relation_table_name)->select($relation->columns)->where('id', $related_table_ids)->get(true);

                return $this->linkManyToManyRelation($main_body, $pivot_table_data, $related_data, $relation->primary_key_column ?? $parent_table . '_id', $related_foreign_key_column, $relation_table_name);
            } else {
                $parent_id_table = $this->primaryKeyColumn;
                foreach ($data as $body) {
                    $array_or_object = is_array($data) ? $body : $data;

                    $query = $this->table($relation_table_name)
                        ->select($relation->columns);

                    if ($relation->type === 'has') {
                        $relationQuery = $query->where(
                            $relation->foreign_key_column ?? $parent_table . '_id',
                            $array_or_object->$parent_id_table
                        );
                    } else {
                        $parent_id_table = $relation->foreign_key_column ?? rtrim($relation_table_name, 's') . '_id';

                        $relationQuery = $query->where(
                            'id',
                            $array_or_object->$parent_id_table
                        );
                    }

                    $relation_data = ($relation->mode === 'one')
                        ? $relationQuery->first(true)
                        : $relationQuery->get(true);

                    if (is_array($data)) {
                        $body->{$relation_table_name} = $relation_data;
                    } else {
                        $this->related[$relation_table_name] = $relation_data;
                    }
                }
                return !is_array($data)
                    ? (object)array_merge((array)$data, $this->related)
                    : $data;
            }
        }
    }

    # These are working, but not satisfying
    // if (is_array($data)) {
    //     $main_body_primary_key = ($relation->type === 'has') ? $this->primaryKeyColumn : rtrim($relation_table_name, 's') . '_id';
    //     $foreign_key_column = ($relation->type === 'has') ? $parent_table . '_id' : $this->primaryKeyColumn;

    //     $main_body_ids = array_map(fn($body) => $body->$main_body_primary_key, $data);
    //     $related_query = $this->table($relation_table_name)->select($relation->columns);

    //     $related_query = ($relation->type === 'has')
    //         ? $related_query->where($relation->foreign_key_column ?? $parent_table . '_id', $main_body_ids)
    //         : $related_query->where('id', $main_body_ids);

    //     $related_data =

    //         $related_query->get(true);

    //     // printAsJson($related_data);
    //     // die();

    //     return (is_array($related_data))
    //         ? $this->arrayLinkRelation($data, $related_data, $main_body_primary_key, $relation->foreign_key_column ?? $foreign_key_column, $relation_table_name)
    //         : array_map(function ($body) use ($relation_table_name, $related_data) {
    //             return (object)array_merge((array)$body, [$relation_table_name => $related_data]);
    //         }, $data);
    // } else {
    // }

    private function linkManyToManyRelation(
        array $mainItems,      // Main dataset (e.g., posts)
        array $pivotTable, // Relation mappings (e.g., post_tag table)
        array $relatedItems,   // Related dataset (e.g., tags)
        string $mainKey,       // Key in pivotTable to link with mainItems (e.g., post_id)
        string $relationKey, // Key in pivotTable to link with relatedItems (e.g., tag_id)
        string $tableName = 'related'
    ): array {
        $linkedResult = []; // Final result to hold enriched main items

        foreach ($mainItems as $mainItem) {
            // Filter mappings for the current main item
            $filteredMappings = array_filter(
                $pivotTable,
                fn($mapping) => $mapping->$mainKey == $mainItem->id
            );

            // Map filtered relations to the actual related items
            $linkedRelations = array_map(function ($mapping) use ($relatedItems, $relationKey) {
                foreach ($relatedItems as $relatedItem) {
                    if ($relatedItem->id == $mapping->$relationKey) {
                        return $relatedItem; // Found related item
                    }
                }
            }, $filteredMappings);

            // Attach related items to the main item if they exist
            $enrichedItem = clone $mainItem;
            if (!empty($linkedRelations)) {
                $enrichedItem->$tableName = array_values(array_filter($linkedRelations));
            } else {
                $enrichedItem->$tableName = [];
            }

            $linkedResult[] = $enrichedItem; // Add enriched item to result
        }

        return $linkedResult;
    }

    private function objectLinkRelation(
        object $main,
        array $related,
        string $primaryKey,
        string $foreignKey,
        string $tableName = 'related'
    ) {
        // Filter related items that match the relationship
        $relatedItems = array_filter($related, function ($relation) use ($main, $primaryKey, $foreignKey) {
            return $relation->$foreignKey == $main->$primaryKey;
        });

        // Convert related items to an indexed array
        $relatedItems = array_values($relatedItems);

        // Clone the main object to prevent mutation
        $mainWithRelation = clone $main;

        // Add the related items under the specified relation name
        $mainWithRelation->$tableName = $relatedItems;

        return $mainWithRelation;
    }

    private function arrayLinkRelation(
        array $main,
        array $related,
        string $primaryKey,
        string $foreignKey,
        string $tableName = 'related'
    ) {
        $result = array_map(function ($mainItem) use ($related, $primaryKey, $foreignKey, $tableName) {
            $relatedItems = array_filter($related, function ($relatedItem) use ($mainItem, $primaryKey, $foreignKey) {
                return $relatedItem->$foreignKey == $mainItem->$primaryKey;
            });

            $mainItem->{$tableName} = array_values($relatedItems); // Add related items as 'posts'
            return (object)$mainItem; // Convert main item to stdClass
        }, $main);

        return $result;
    }




    # from ChatGPT, but not working.. I am planning to get the most efficient way
    // private function processRelationsBatch($data)
    // {
    //     $parent_table = rtrim($this->table, 's');
    //     $parent_ids = array_map(fn($obj) => $obj->id, $data);

    //     foreach ($this->relations as $table => $relation) {
    //         if (isset($relation->pivot_table)) {
    //             // Fetch pivot data in one query
    //             $pivot_data = $this->table($relation->pivot_table)
    //             ->where("$parent_table" . "_id", $parent_ids)
    //             ->get();

    //             // Map pivot IDs to parent IDs
    //             $pivot_map = [];
    //             foreach ($pivot_data as $row) {
    //                 $pivot_map[$row->{$parent_table . '_id'}][] = $row->{rtrim($table, 's') . '_id'};
    //             }

    //             // Fetch related data in one query
    //             $related_ids = array_unique(array_merge(...array_values($pivot_map)));
    //             $related_data = $this->table($table)
    //             ->where('id', $related_ids)
    //             ->get();

    //             // Map related data back to parent objects
    //             $related_map = [];
    //             foreach ($related_data as $related) {
    //                 $related_map[$related->id] = $related;
    //             }

    //             foreach ($data as $item) {
    //                 $related_ids = $pivot_map[$item->id] ?? [];
    //                 $item->{$table} = array_map(fn($id) => $related_map[$id] ?? null, $related_ids);
    //             }
    //         } else {
    //             // Handle "has" and "belongsTo" relationships in bulk
    //             $foreign_key = $relation->foreign_key_column ?? ($relation->type === 'has'
    //             ? $parent_table . "_id"
    //             : rtrim($table, 's') . "_id");

    //             $related_data = $this->table($table)
    //             ->where($foreign_key, $parent_ids)
    //             ->get();

    //             // Map data back to parents
    //             $related_map = [];
    //             foreach ($related_data as $related) {
    //                 $key = $relation->type === 'has' ? $related->{$foreign_key} : $related->id;
    //                 $related_map[$key][] = $related;
    //             }

    //             foreach ($data as $item) {
    //                 $item->{$table} = $related_map[$item->id] ?? [];
    //             }
    //         }
    //     }

    //     return $data;
    // }

    // } else {
    //     // $fk_key_id = end($this->bindings);
    //     $parent_table = rtrim($this->table, 's');

    //     foreach ($this->relations as $table => $relation) {
    //         // error_log(json_encode($relation));
    //         if ($relation->type == 'has') {
    //             $this->bindings = [];
    //             $this->query = '';

    //             $relationQuery = $this->table($table)->select($relation->tables)->where(
    //                 "{$parent_table}_id",
    //                 $data->id
    //             );

    //             $this->related[$table] = ($relation->mode == 'one') ? $relationQuery->first(true) : $relationQuery->get(true);
    //         } else if ($relation->type == 'belongsTo') {
    //             $this->bindings = [];
    //             $this->query = '';
    //             $parent_table_id = rtrim($table, 's') . "_id";
    //             $relationQuery = $this->table($table)->select($relation->tables)->where(
    //                 'id',
    //                 $data->$parent_table_id
    //             );

    //             $this->related[$table] = ($relation->mode == 'one') ? $relationQuery->first(true) : $relationQuery->get(true);
    //         }
    //     }

    //     return (object)array_merge((array)$data, $this->related);
    // }

    public function limit($limitNumber)
    {
        $this->query .= ' LIMIT ' . $limitNumber;
        return $this;
    }

    /**
     * I almost forgot how query works, I take quick asking to ChatGPt and now I remember, haha.
     * Available option for type is INNER, LEFT, and RIGHT. Defaulted to INNER
     * 
     * @param string $table
     * @param string $ownerTableColumn
     * @param string $foreignKey
     * @param string $operator defaulted to '='
     * @param string $type defaulted to INNER
     * 
     */
    public function join($table, $ownerTableColumn, $foreignKey, $operator = '=', $type = 'INNER'): self
    {
        $this->query .= ' ' . $type . ' JOIN ' . $table . ' ON ' . $ownerTableColumn . ' ' . $operator . ' ' . $foreignKey;
        return $this;
    }

    /**
     * This method is means to order result by ascending or descending order.
     * In case I forget I'll just put option here ASC/DESC ~Ronel
     * 
     * @param string $column
     * @param string $direction ['ASC','DESC']
     * 
     */
    public function orderBy($column, $direction = 'ASC'): self
    {
        $this->query .= ' ORDER BY ' . $column . ' ' . $direction;
        return $this;
    }

    public function restrain($state)
    {
        $this->restrain = $state;
        return $this;
    }

    /**
     * This method can be used with execute() to perform a raw sql query (Prepared for old but gold)
     * 
     * @param string $sql Sql query to be perform
     * @param array $bindings Use this if you prefer placeholder
     */
    public function raw($sql, $bindings = []): self
    {
        $this->query .= $sql . ' ';
        $this->bindings = array_merge($this->bindings, $bindings);
        return $this;
    }

    /**
     * This method was used there is no direct execution by insert() and update() now its deprecated.
     * You can just use others for regular CRUD operations, or you still can use it for raw() query to perform complex query
     *  @return void
     * 
     */
    public function execute(): void
    {
        $upperQuery = strtoupper($this->query);

        if ($this->restrain && str_contains($upperQuery, 'DROP')) {
            throw new Exception('Drop action detected, aborted unless explicitly restrain set to false.');
        }

        if (
            $this->restrain &&
            (str_contains($upperQuery, 'DELETE') || str_contains($upperQuery, 'UPDATE')) &&
            !str_contains($upperQuery, 'WHERE')
        ) {
            throw new Exception('Delete/Update action without where clause detected, aborted unless explicitly restrain set to false.');
        }
        error_log($this->getRequestIp() . $this->query);
        error_log(json_encode($this->bindings));
        $this->stmt = $this->pdo->prepare($this->query);
        $this->stmt->execute($this->bindings);
    }

    /**
     * Fetch one result from raw query.
     * Will return object if success or else null if there is none
     * 
     * @return object|null
     */
    public function fetch(): object|false
    {
        return $this->stmt->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Fetch all result from raw query.
     * Will always return Array, whatever, if there is no match, will return empty array
     */
    public function fetchAll(): array
    {
        return $this->stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * This thing first spawned to me when I leaning python sqlite query, hoo so all are same?
     * 
     * @return string|false Returned id of last successful query or false if no insert query were made 
     * 
     */
    public function lastInsertId(): string|false
    {
        return ($this->pdo->lastInsertId() == '0') ? false : $this->pdo->lastInsertId();
    }

    public function lastRowId(): string
    {
        return $this->stmt->lastRowId();
    }

    /**
     * I am not sure if this a correct way to closing a connection, this just set the pdo to null.
     * Update! now I know the correct way!
     * 
     * @return bool
     */
    public function close(): bool
    {
        $this->pdo = null;
        return $this->stmt->closeCursor();
    }

    /**
     * Reset the query builder
     * 
     */
    public function resetQuery()
    {
        $this->query = '';
        $this->bindings = [];
        $this->usedSelect = false;
        return $this;
    }

    /**
     * Get numbers of affected row from previous query
     * 
     */
    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    # On process code, I'll make this code work later #
    # partly working,but it giving me work todo:
    # Nvm, I know other way
    // private function processRelations($data)
    // {
    //     $parent_table = rtrim($this->table, 's');

    //     // Prepare data to minimize query resets
    //     $relationMap = [];
    //     foreach ($this->relations as $table => $relation) {
    //         if (!isset($relation->pivot_table)) {
    //             $relationMap[$table] = [
    //                 'query' => $this->table($table)->select($relation->columns),
    //                 'type' => $relation->type,
    //                 'foreign_key_column' => $relation->foreign_key_column ?? $parent_table . '_id',
    //                 'mode' => $relation->mode
    //             ];
    //         } else {
    //             $relationMap[$table] = [
    //                 'pivot_table' => $relation->pivot_table,
    //                 'foreign_key_column' => $relation->foreign_key_column ?? "{$parent_table}_id",
    //                 'mode' => $relation->mode,
    //                 'columns' => $relation->columns
    //             ];
    //         }
    //     }

    //     foreach ($data as $body) {
    //         $this->resetQuery();
    //         foreach ($relationMap as $table => $relation) {
    //             $this->resetQuery();
    //             $array_or_object = (is_array($data)) ? $body : $data;
    //             if (!isset($relation['pivot_table'])) {
    //                 $query = $relation['query'];
    //                 if ($relation['type'] === 'has') {
    //                     // error_log("Youre here");
    //                     $parent_id_table = $relation['foreign_key_column'];
    //                     // $this->resetQuery();
    //                     $relationQuery = $query->where(
    //                         $relation['foreign_key_column'],
    //                         $array_or_object->$parent_id_table
    //                     );
    //                 } else {
    //                     // $this->resetQuery();
    //                     $relationQuery = $query->where(
    //                         'id',
    //                         $array_or_object->id
    //                     );

    //                     // print_r(json_encode($array_or_object,JSON_PRETTY_PRINT));
    //                     // continue;

    //                 }

    //                 $relation_data = ($relation['mode'] === 'one')
    //                     ? $relationQuery->first(true)
    //                     : $relationQuery->get(true);

    //                 if (is_array($data)) {
    //                     $body->{$table} = $relation_data;
    //                 } else {
    //                     $this->related[$table] = $relation_data;
    //                 }
    //             } else {
    //                 $this->method_1 = (isset($relation['foreign_key_column'])) ? preg_match('/^\w+_id$/', $relation['foreign_key_column']) : (($this->method_1) ? true : false);

    //                 $pivot_parent_table = $relation['foreign_key_column'] ?? (($this->method_1) ? $parent_table . '_id' : 'id_' . $parent_table);

    //                 $pivot_table_data = $this->table($relation['pivot_table'])
    //                     ->where($pivot_parent_table, $array_or_object->id)
    //                     ->get(true);
    //                 $this->resetQuery();

    //                 if (!$pivot_table_data) {
    //                     $this->method_1 = false;
    //                     $pivot_parent_table = $this->switchColumnPattern($pivot_parent_table);

    //                     $pivot_table_data = $this->table($relation['pivot_table'])
    //                         ->where($pivot_parent_table, $array_or_object->id)
    //                         ->get(true);
    //                     $this->resetQuery();

    //                     if (!$pivot_table_data) {
    //                         die('Pivot table data not found.');
    //                     }
    //                 }

    //                 $belonged_table_column = rtrim($table, 's');
    //                 $belonged_table = $relation['foreign_key_column'] ?? (($this->method_1) ? $belonged_table_column . '_id' : 'id_' . $belonged_table_column);

    //                 $belonged_ids = array_map(function ($item) use ($belonged_table) {
    //                     return $item->$belonged_table;
    //                 }, $pivot_table_data);

    //                 if (empty($belonged_ids) || $belonged_ids[0] === null) {
    //                     $belonged_table = $this->switchColumnPattern($belonged_table);
    //                     $belonged_ids = array_map(function ($item) use ($belonged_table) {
    //                         return $item->$belonged_table;
    //                     }, $pivot_table_data);
    //                 }

    //                 $query = $this->table($table)
    //                     ->select($relation['columns'])
    //                     ->where('id', $belonged_ids);

    //                 $relation_data = ($relation['mode'] === 'one')
    //                     ? $query->first(true)
    //                     : $query->get(true);
    //                 $this->resetQuery();

    //                 if (is_array($data)) {
    //                     $body->{$table} = $relation_data;
    //                 } else {
    //                     $this->related[$table] = $relation_data;
    //                 }
    //             }
    //         }
    //     }
    //     return !is_array($data)
    //     ? (object)array_merge((array)$data, $this->related)
    //     : $data;
    // }
}
