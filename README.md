# sql_um
PHP SQL upgrade manager

This script allows you to create and apply changes to an SQL database in order.
The database will store its own record of the changes previously made, allowing for upgrades of existing databases.

## Example

    php newupgrade.php box/create_box_table

This command will create a new file named `upgrades/box/[TIMESTAMP]_create_box_table.sql`. Add something like:

    CREATE TABLE box (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(30) NOT NULL,
        created_at TIMESTAMP
    );

Now check the database credentials in `include.php` then run:

    php upgrade.php

Your database should be created. The `box` table described above should be built, 
as well as a `change_history` table that will show one record; the `add_box_table` sql script and a timestamp.
Running `php upgrade.php` more than once will have no effect.

Now create another changeset:

    php newupgrade.php box/add_box_colour

And add the SQL:

    ALTER TABLE box
        ADD colour varchar(30) DEFAULT null;

Running `php upgrade.php` again will run the new query and update your table. 
Deleting the database and running `php upgrade.php` will give you an identical database again, with the changesets run in the order they were added to the codebase.
