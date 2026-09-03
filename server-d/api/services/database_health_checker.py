from django.db import connection


class DatabaseHealthChecker:

    def check(self):
        try:
            with connection.cursor() as cursor:
                cursor.execute("SELECT 1")

            return True

        except Exception:
            return False