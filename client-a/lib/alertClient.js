const ALERT_SERVER_URL =
    process.env.NEXT_PUBLIC_ALERT_SERVER_URL;

export async function getServiceStatus(service) {
    const response = await fetch(
        `${ALERT_SERVER_URL}/api/v1/services/${service}/status`,
        {
            cache: "no-store",
        }
    );

    if (response.status === 404) {
        return {
            service,
            status: "UNKNOWN",
        };
    }

    if (!response.ok) {
        throw new Error(
            `Failed to fetch health status: ${response.status}`
        );
    }

    return response.json();
}

export function createHealthEventSource() {
    return new EventSource(
        `${ALERT_SERVER_URL}/api/v1/events/stream`
    );
}