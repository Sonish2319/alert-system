"use client";

import { useEffect, useRef, useState } from "react";
import {
    createInitialHealthState,
} from "../lib/health";
import {
    getServiceStatus,
    createHealthEventSource,
} from "../lib/alertClient";

export function useServiceHealth(service) {
    const [health, setHealth] = useState(
        createInitialHealthState()
    );

    const eventSourceRef = useRef(null);

    useEffect(() => {
        let mounted = true;

        async function synchronizeState() {
            try {
                const state =
                    await getServiceStatus(service);

                if (!mounted) {
                    return;
                }

                setHealth((current) => ({
                    ...current,
                    [service]: {
                        ...current[service],
                        ...state,
                    },
                }));
            } catch (error) {
                console.error(
                    "Initial health synchronization failed:",
                    error
                );
            }
        }

        synchronizeState();

        const eventSource =
            createHealthEventSource();

        eventSourceRef.current = eventSource;

        eventSource.addEventListener(
            "health",
            (event) => {
                try {
                    const data =
                        JSON.parse(event.data);

                    if (
                        data.service !== service
                    ) {
                        return;
                    }

                    setHealth((current) => ({
                        ...current,
                        [service]: {
                            ...current[service],
                            status: data.status,
                            component:
                                data.component,
                            reason:
                                data.reason,
                            message:
                                data.message,
                            timestamp:
                                data.timestamp,
                        },
                    }));
                } catch (error) {
                    console.error(
                        "Invalid health SSE event:",
                        error
                    );
                }
            }
        );

        eventSource.onerror = () => {
            console.warn(
                "Health SSE connection interrupted."
            );
        };

        return () => {
            mounted = false;

            eventSource.close();

            eventSourceRef.current = null;
        };
    }, [service]);

    return health[service];
}