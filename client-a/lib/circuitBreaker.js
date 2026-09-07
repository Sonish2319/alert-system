export class CircuitBreaker {
    constructor() {
        this.states = {};
    }

    setState(service, status) {
        this.states[service] = status;
    }

    getState(service) {
        return this.states[service] ?? "UNKNOWN";
    }

    isRequestAllowed(service) {
        const state = this.getState(service);

        return state !== "DOWN";
    }
}