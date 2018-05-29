<?php
require_once 'include.php';

if (!isDbInitialised()) {
    createDb();
}

$changes_to_do = getChangesToDo();

array_walk(
    $changes_to_do,
    function ($change_path) {
        getDbConnection()->query(file_get_contents(CHANGE_PATH . $change_path));

        recordChange($change_path);
    }
);
