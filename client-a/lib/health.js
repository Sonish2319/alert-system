export const HEALTH_STATES = {
    HEALTHY: "HEALTHY",
    DEGRADED: "DEGRADED",
    DOWN: "DOWN",
    UNKNOWN: "UNKNOWN",
};

export function createInitialHealthState() {
    return {
        "server-e": {
            status: HEALTH_STATES.UNKNOWN,
            component: null,
            reason: null,
            message: null,
            timestamp: null,
            updated_at: null,
        },
    };
}