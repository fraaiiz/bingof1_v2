ALTER TABLE bingos
    ADD UNIQUE KEY uq_bingos_user_season (user_id, saison_id);

ALTER TABLE bingo_cases
    ADD UNIQUE KEY uq_bingo_cases_bingo_position (bingo_id, position);
