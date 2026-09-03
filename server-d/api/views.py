from django.db import connection
from django.http import JsonResponse
from django.views import View


class ProductListView(View):

    def get(self, request):
        with connection.cursor() as cursor:
            cursor.execute("SELECT 1")

        return JsonResponse({
            "success": True,
            "server": "server-d",
            "message": "Server D API is working.",
        })


class HealthLiveView(View):

    def get(self, request):
        return JsonResponse({
            "status": "ok",
            "service": "server-d",
            "check": "live",
        })


class HealthReadyView(View):  # The server exists, but it currently isn't ready to handle requests. if 503 is returned, the server is not ready to handle requests.

    def get(self, request):
        try:
            with connection.cursor() as cursor:
                cursor.execute("SELECT 1")

            return JsonResponse({
                "status": "ok",
                "service": "server-d",
                "check": "ready",
                "dependencies": {
                    "mysql": "healthy",
                },
            })

        except Exception:
            return JsonResponse({
                "status": "error",
                "service": "server-d",
                "check": "ready",
                "dependencies": {
                    "mysql": "unhealthy",
                },
            }, status=503)