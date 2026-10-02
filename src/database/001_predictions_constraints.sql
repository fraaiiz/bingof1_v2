ALTER TABLE users
    ADD CONSTRAINT uq_users_login UNIQUE (user_login),
    ADD CONSTRAINT uq_users_email UNIQUE (user_email);

ALTER TABLE predictions
    ADD CONSTRAINT uq_predictions_user_course UNIQUE (user_id, course_id);