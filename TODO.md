# TODO: Modify Code for Docker Connection

## Steps to Complete
- [x] Update docker-compose.yml: Change MYSQL_DATABASE to 'db__hotel', MYSQL_ROOT_PASSWORD to '1234', update PHP env DB_NAME to 'db__hotel', add volume and command to import db__hotel.sql on MySQL startup.
- [x] Modify connection.php: Replace hardcoded database connection values with environment variables (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME).
- [ ] Test the setup by running docker-compose up to verify connection.
