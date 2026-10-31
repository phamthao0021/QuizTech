-- Optional data repair: only the original 8-clue seed crossword, no deletions.
-- BACK UP database before running. Review affected IDs with the SELECT below.
SET NAMES utf8mb4;
SELECT c.id,c.title,COUNT(k.id) clue_count FROM practice_crosswords c JOIN practice_crossword_clues k ON k.crossword_id=c.id GROUP BY c.id,c.title;
START TRANSACTION;
SET @seed_cw := (SELECT c.id FROM practice_crosswords c JOIN practice_crossword_clues k ON k.crossword_id=c.id
 WHERE c.title='Web & Data Fundamentals' AND c.rows_count=9 AND c.cols_count=9
 GROUP BY c.id HAVING COUNT(*)=8 AND SUM(k.answer IN ('HTML','HTTP','MYSQL','LINUX','LOGIC','API','RAM','SQL'))=8 LIMIT 1);
-- Keep original answers/clues, relocate only the known conflicting seed placements.
UPDATE practice_crossword_clues SET row_start=0,col_start=0 WHERE crossword_id=@seed_cw AND answer='HTML' AND direction='across';
UPDATE practice_crossword_clues SET row_start=0,col_start=0 WHERE crossword_id=@seed_cw AND answer='HTTP' AND direction='down';
UPDATE practice_crossword_clues SET row_start=0,col_start=5 WHERE crossword_id=@seed_cw AND answer='MYSQL' AND direction='down';
UPDATE practice_crossword_clues SET row_start=0,col_start=6 WHERE crossword_id=@seed_cw AND answer='LINUX' AND direction='down';
UPDATE practice_crossword_clues SET row_start=0,col_start=7 WHERE crossword_id=@seed_cw AND answer='LOGIC' AND direction='down';
UPDATE practice_crossword_clues SET row_start=6,col_start=0 WHERE crossword_id=@seed_cw AND answer='API' AND direction='across';
UPDATE practice_crossword_clues SET row_start=7,col_start=0 WHERE crossword_id=@seed_cw AND answer='RAM' AND direction='across';
UPDATE practice_crossword_clues SET row_start=8,col_start=0 WHERE crossword_id=@seed_cw AND answer='SQL' AND direction='across';
COMMIT;
