DELIMITER $$

CREATE PROCEDURE UpdateRandomSheetMusicStats()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE current_id INT;
    DECLARE cur CURSOR FOR SELECT id FROM sheet_music;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO current_id;
        IF done THEN
            LEAVE read_loop;
        END IF;

        UPDATE sheet_music 
        SET 
            rating = ROUND(4.5 + RAND() * 0.4, 1),
            reviews_count = FLOOR(50 + RAND() * 950),
            downloads_count = FLOOR(100 + RAND() * 9000),
            views_count = FLOOR(1000 + RAND() * 30000)
        WHERE id = current_id;
    END LOOP;

    CLOSE cur;
END$$

DELIMITER ;

-- Call the procedure
CALL UpdateRandomSheetMusicStats();