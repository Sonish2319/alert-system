const SERVER_E_URL =
    process.env.NEXT_PUBLIC_SERVER_E_URL;

export async function getOrders() {
    const response = await fetch(
        `${SERVER_E_URL}/api/v1/orders`,
        {
            cache: "no-store",
        }
    );

    if (!response.ok) {
        throw new Error(
            `Server E request failed: ${response.status}`
        );
    }

    return response.json();
}