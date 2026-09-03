import logging

import requests
from django.conf import settings


logger = logging.getLogger(__name__)


class HealthReporter:

    def report(
        self,
        status,
        component,
        reason,
        message=None,
    ):
        payload = {
            "service": settings.HEALTH_SERVICE_NAME,
            "status": status,
            "component": component,
            "reason": reason,
            "message": message,
        }

        try:
            response = requests.post(
                f"{settings.AL_URL}/api/v1/health-events",
                json=payload,
                headers={
                    "Authorization": f"Bearer {settings.AL_HEALTH_TOKEN}",
                    "Accept": "application/json",
                },
                timeout=3,
            )

            if response.ok:
                logger.info(
                    "Health event sent to AL",
                    extra={
                        "payload": payload,
                        "response": response.json(),
                    },
                )

                return True

            logger.error(
                "AL rejected health event",
                extra={
                    "status_code": response.status_code,
                    "response": response.text,
                    "payload": payload,
                },
            )

            return False

        except requests.RequestException as exception:
            logger.error(
                "Unable to communicate with AL: %s",
                exception,
            )

            return False

    def healthy(
        self,
        component,
        reason="SERVICE_AVAILABLE",
    ):
        return self.report(
            "HEALTHY",
            component,
            reason,
        )

    def degraded(
        self,
        component,
        reason,
        message=None,
    ):
        return self.report(
            "DEGRADED",
            component,
            reason,
            message,
        )

    def down(
        self,
        component,
        reason,
        message=None,
    ):
        return self.report(
            "DOWN",
            component,
            reason,
            message,
        )

    def heartbeat(self):
        return self.report(
            "HEALTHY",
            "application",
            "HEARTBEAT",
        )