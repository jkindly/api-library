-- Provisions a dedicated test database, a copy (by structure) of the main one,
-- used only by the automated test suite. Runs once, when the PostgreSQL data
-- directory is first initialised.
CREATE DATABASE app_test OWNER app;
