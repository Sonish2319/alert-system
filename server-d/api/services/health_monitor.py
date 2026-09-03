import logging

from .database_health_checker import DatabaseHealthChecker
from .health_reporter import HealthReporter


logger = logging.getLogger(__name__)


class HealthMonitor:

    def __init__(
        self,
        database_health_checker=None,
        health_reporter=None,
    ):
        self.database_health_checker = (
            database_health_checker
            or DatabaseHealthChecker()
        )

        self.health_reporter = (
            health_reporter
            or HealthReporter()
        )

    def check_database(self):
        healthy = self.database_health_checker.check()

        if healthy:
            self.health_reporter.healthy(
                "mysql",
                "DATABASE_AVAILABLE",
            )

            return True

        logger.error(
            "Server D MySQL health check failed."
        )

        self.health_reporter.down(
            "mysql",
            "DATABASE_UNAVAILABLE",
            "Unable to connect to MySQL.",
        )

        return False