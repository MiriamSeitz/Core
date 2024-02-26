DROP PROCEDURE IF EXISTS add_column_if_missing;
CREATE PROCEDURE add_column_if_missing(
    IN name_of_table VARCHAR(255),
    IN name_of_column VARCHAR(255),
    IN column_definition VARCHAR(255)
)
BEGIN
    SET @database_schema = DATABASE();

    -- Check if the column exists
    IF NOT EXISTS (
        SELECT *
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = @database_schema
          AND TABLE_NAME = name_of_table
          AND COLUMN_NAME = name_of_column
    )
    THEN
        -- insert column
        SET @sql = CONCAT('ALTER TABLE ', name_of_table, ' ADD COLUMN ', name_of_column, ' ', column_definition);
        PREPARE statement FROM @sql;
        EXECUTE statement;
        DEALLOCATE PREPARE statement;
    ELSE
        -- Handle the situation where the column does already exists
        SELECT CONCAT('Column ', name_of_column, ' already exists in table ', name_of_table) AS Result;
    END IF;
END;