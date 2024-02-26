DROP PROCEDURE IF EXISTS remove_column_if_exists;
CREATE PROCEDURE remove_column_if_exists(
    IN name_of_table VARCHAR(255),
    IN name_of_column VARCHAR(255)
)
BEGIN
    DECLARE column_count INT;
    SET @table_schema = DATABASE();

    -- Check if the column exists
    IF EXISTS (
        SELECT *
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = @database_schema
          AND TABLE_NAME = name_of_table
          AND COLUMN_NAME = name_of_column
    )
    THEN
        -- drop column
        SET @sql = CONCAT('ALTER TABLE ', name_of_table, ' DROP COLUMN ', name_of_column);
        PREPARE statement FROM @sql;
        EXECUTE statement;
        DEALLOCATE PREPARE statement;
    ELSE
        -- Handle the situation where the column does not exist
        SELECT CONCAT('Cannot remove column ', name_of_column, ' because it does not exist in table ', name_of_table) AS Result;
    END IF;
END