DROP PROCEDURE IF EXISTS rename_column_if_exists;
CREATE PROCEDURE rename_column_if_exists(
    IN name_of_table VARCHAR(255),
    IN name_of_column VARCHAR(255),
    IN new_column_name VARCHAR(255)
)
BEGIN
    SET @database_schema = DATABASE();

    -- Check if the column exists
    IF EXISTS (
        SELECT *
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = @database_schema
          AND TABLE_NAME = name_of_table
          AND COLUMN_NAME = name_of_column
    )
    THEN
        -- rename column
        SET @sql = CONCAT('ALTER TABLE ', name_of_table, ' RENAME COLUMN ', name_of_column, ' TO ', new_column_name);
        PREPARE statement FROM @sql;
        EXECUTE statement;
        DEALLOCATE PREPARE statement;
    ELSE
        -- Handle the situation where the column does not exist
        SELECT CONCAT('Cannot rename column ', name_of_column, ' because it does not exist in table ', name_of_table) AS Result;
    END IF;
END;