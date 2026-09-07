"use client";

import { useMemo, useState } from "react";
import { useServiceHealth } from "../hooks/useServiceHealth";
import { CircuitBreaker } from "../lib/circuitBreaker";
import { getOrders } from "../lib/serverE";

export default function Home() {
    const health = useServiceHealth("server-e");

    const circuitBreaker = useMemo(
        () => new CircuitBreaker(),
        []
    );

    circuitBreaker.setState(
        "server-e",
        health.status
    );

    const [orders, setOrders] = useState(null);
    const [error, setError] = useState(null);

    async function loadOrders() {
        setError(null);

        if (
            !circuitBreaker.isRequestAllowed(
                "server-e"
            )
        ) {
            setError(
                "Server E is DOWN. Request blocked by local circuit breaker."
            );

            return;
        }

        try {
            const data = await getOrders();

            setOrders(data);
        } catch (err) {
            setError(err.message);
        }
    }

    return (
        <main
            style={{
                padding: "40px",
                fontFamily: "Arial",
            }}
        >
            <h1>Client A</h1>

            <h2>Server E Health</h2>

            <p>
                Status:{" "}
                <strong>
                    {health.status}
                </strong>
            </p>

            <p>
                Component:{" "}
                {health.component ?? "-"}
            </p>

            <p>
                Reason:{" "}
                {health.reason ?? "-"}
            </p>

            <p>
                Last update:{" "}
                {health.timestamp ?? "-"}
            </p>

            <hr />

            <h2>Server E API</h2>

            <button
                onClick={loadOrders}
                disabled={
                    health.status === "DOWN"
                }
            >
                Load Orders
            </button>

            {health.status === "DOWN" && (
                <p>
                    Server E is DOWN. The request
                    is blocked locally.
                </p>
            )}

            {error && (
                <p>
                    Error: {error}
                </p>
            )}

            {orders && (
                <pre>
                    {JSON.stringify(
                        orders,
                        null,
                        2
                    )}
                </pre>
            )}
        </main>
    );
}