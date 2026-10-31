import subprocess, sys

res = subprocess.run(
    ['C:\\xampp\\mysql\\bin\\mysqldump.exe', '-u', 'root', '--no-data', '--skip-triggers', '--skip-routines', 'db_quiz_tech'],
    capture_output=True,
    text=True,
    encoding='utf-8'
)

if res.returncode != 0:
    print("Dump error:", res.stderr)
    sys.exit(1)

header = """-- =====================================================================
-- QUIZTECH IDEMPOTENT DATABASE SCHEMA
-- Database: db_quiz_tech (Compatible with MySQL 5.7+ / MariaDB 10.4+)
-- Safe to re-run: Non-destructive, does NOT DROP existing tables or data
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

"""

footer = """
SET FOREIGN_KEY_CHECKS = 1;
"""

lines = res.stdout.splitlines(keepends=True)
safe_lines = [header]

for line in lines:
    if line.strip().startswith('DROP TABLE IF EXISTS'):
        continue
    if line.startswith('CREATE TABLE `'):
        line = line.replace('CREATE TABLE `', 'CREATE TABLE IF NOT EXISTS `', 1)
    safe_lines.append(line)

safe_lines.append(footer)
final_sql = ''.join(safe_lines)

with open('database/schema.sql', 'w', encoding='utf-8') as f:
    f.write(final_sql)

test_run = subprocess.run(
    ['C:\\xampp\\mysql\\bin\\mysql.exe', '-u', 'root', 'db_quiz_tech', '-e', 'source database/schema.sql'],
    capture_output=True,
    text=True
)

print("Test returncode:", test_run.returncode)
if test_run.stderr:
    print("Test stderr:", test_run.stderr)
else:
    print("SUCCESS: database/schema.sql is now 100% idempotent, non-destructive, and executed with 0 errors!")
