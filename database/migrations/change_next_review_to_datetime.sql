-- Change next_review column from DATE to DATETIME to support minute/hour intervals
ALTER TABLE cards MODIFY COLUMN next_review DATETIME NULL;
