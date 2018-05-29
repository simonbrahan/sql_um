<?php

define('CHANGE_PATH','upgrades/');
define('DB_HOST', 'localhost');
define('DB_NAME', 'dbuptest');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'root');
define(
    'DB_CONN_ATTRS',
    [ \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ ]
);

/**
 * Check if app database is initialised
 *
 * @return boolean
 */
function isDbInitialised()
{
    $db = getGlobalDbConnection();

    return $db->query('SELECT COUNT(*) AS count FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = "' . DB_NAME . '"')
              ->fetch()
              ->count > 0;
}

/**
 * Create app database
 *
 * @return void
 */
function createDb()
{
    $db = getGlobalDbConnection();

    $db->query('CREATE DATABASE ' . DB_NAME);
}

/**
 * Get database connection without connecting to specific database
 * Useful in upgrade because we don't know the database exists yet
 *
 * @return \PDO
 */
function getGlobalDbConnection()
{
    static $conn = false;

    if (!$conn) {
        $conn = new \PDO(
            'mysql:host=' . DB_HOST . ';charset=utf8',
            DB_USERNAME,
            DB_PASSWORD,
            DB_CONN_ATTRS
        );
    }

    return $conn;
}

/**
 * Get database connection to the application database
 * Used once it is known to exist
 *
 * @return \PDO
 */
function getDbConnection()
{
    static $conn = false;

    if (!$conn) {
        $conn = new \PDO(
            'mysql:host=' . DB_HOST . ';dbcharset=utf8;dbname=' . DB_NAME,
            DB_USERNAME,
            DB_PASSWORD,
            DB_CONN_ATTRS
        );
    }

    return $conn;
}

/**
 * Get list of changes that have been run
 *
 * @return array
 */
function getChangeHistory()
{
    ensureChangeHistoryExists();

    $change_history_query = 'SELECT path FROM change_history ORDER BY run_at ASC';

    $db = getDbConnection();

    $res = $db->query($change_history_query);

    $output = [];
    while ($row = $res->fetch()) {
        $output[] = $row->path;
    }

    return $output;
}

/**
 * Ensure change history table exists
 */
function ensureChangeHistoryExists()
{
    $change_history_exists_query = 'SELECT COUNT(*) AS count FROM information_schema.TABLES
                                    WHERE TABLE_SCHEMA = "' . DB_NAME . '" AND TABLE_NAME = "change_history"';

    $db = getGlobalDbConnection();

    $res = $db->query($change_history_exists_query);

    if ($res->fetch()->count == 0) {
        $db->query('CREATE TABLE ' . DB_NAME . '.change_history(path VARCHAR(255) NOT NULL PRIMARY KEY, run_at INT NOT NULL)');
    }
}

/**
 * Get paths of changes to do, in order
 *
 * @return array
 */
function getChangesToDo()
{
    $changes_done = getChangeHistory();
    $changes_available = getChangeList();

    $changes_to_do = array_diff($changes_available, $changes_done);

    usort(
        $changes_to_do,
        function ($a, $b) {
            return strcasecmp(pathinfo($a, PATHINFO_BASENAME), pathinfo($b, PATHINFO_BASENAME));
        }
    );

    return $changes_to_do;
}

/**
 * Get available change file paths
 *
 * @return array
 */
function getChangeList()
{
    $search_dir = new \RecursiveDirectoryIterator(CHANGE_PATH);

    $flatten_search_dir = new \RecursiveIteratorIterator($search_dir);
    $match_query_files = new \RegexIterator(
        $flatten_search_dir,
        '/^.+\.sql$/i',
        \RecursiveRegexIterator::GET_MATCH
    );

    $prune_path_length = strlen(CHANGE_PATH);

    return array_values(
        array_map(
            function ($match) use ($prune_path_length) {
                return substr($match[0], $prune_path_length);
            },
            iterator_to_array($match_query_files)
        )
    );
}

/**
 * Record the running of a changeset in the app database
 *
 * @return void
 */
function recordChange($path)
{
    getDbConnection()->query(
        'INSERT INTO change_history (path, run_at) VALUES("' . $path . '", ' . time() . ')'
    );
}
