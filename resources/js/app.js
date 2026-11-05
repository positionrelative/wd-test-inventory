// Calls the API endpoints – same origin, so no CORS needed
export async function api(method, path, body) {
    const res = await fetch(`/api/v1${path}`, {
        method,
        headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
        body: body ? JSON.stringify(body) : undefined,
    });

    return { status: res.status, data: await res.json().catch(() => null) };
}

document.querySelector('#ping')?.addEventListener('click', async () => {
    const out = document.querySelector('#output');
    out.textContent = 'Loading…';
    const { status, data } = await api('GET', '/ping');
    out.textContent = `HTTP ${status}\n${JSON.stringify(data, null, 2)}`;
});
