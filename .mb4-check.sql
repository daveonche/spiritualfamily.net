SELECT table_name, engine, table_collation
FROM information_schema.tables
WHERE table_schema = DATABASE()
  AND table_name LIKE 'elggjr\_%'
ORDER BY table_name;
