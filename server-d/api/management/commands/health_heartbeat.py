from django.core.management.base import BaseCommand

from api.services.health_reporter import HealthReporter


class Command(BaseCommand):

    help = "Send Server D heartbeat to the Alert Server"

    def handle(self, *args, **options):

        reporter = HealthReporter()

        success = reporter.heartbeat()

        if success:
            self.stdout.write(
                self.style.SUCCESS(
                    "Heartbeat sent successfully."
                )
            )

            return

        self.stdout.write(
            self.style.ERROR(
                "Failed to send heartbeat."
            )
        )