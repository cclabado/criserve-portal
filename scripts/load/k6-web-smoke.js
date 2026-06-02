import http from 'k6/http';
import { check, sleep } from 'k6';

const baseUrl = __ENV.K6_BASE_URL || 'http://127.0.0.1:8000';
const clientEmail = __ENV.K6_CLIENT_EMAIL || '';
const clientPassword = __ENV.K6_CLIENT_PASSWORD || '';

export const options = {
    scenarios: {
        guest_pages: {
            executor: 'constant-vus',
            vus: Number(__ENV.K6_GUEST_VUS || 10),
            duration: __ENV.K6_GUEST_DURATION || '1m',
            exec: 'guestPages',
        },
        support_page: {
            executor: 'constant-vus',
            vus: Number(__ENV.K6_SUPPORT_VUS || 5),
            duration: __ENV.K6_SUPPORT_DURATION || '1m',
            exec: 'supportPages',
        },
        client_application_form: clientEmail && clientPassword ? {
            executor: 'constant-vus',
            vus: Number(__ENV.K6_CLIENT_VUS || 3),
            duration: __ENV.K6_CLIENT_DURATION || '1m',
            exec: 'clientApplicationForm',
        } : undefined,
    },
    thresholds: {
        http_req_failed: ['rate<0.02'],
        http_req_duration: ['p(95)<1500', 'p(99)<3000'],
    },
};

function extractCsrfToken(html) {
    const match = html.match(/name="csrf-token" content="([^"]+)"/i)
        || html.match(/name="_token" value="([^"]+)"/i);

    return match ? match[1] : null;
}

function loginAsClient() {
    const loginPage = http.get(`${baseUrl}/login`);
    const token = extractCsrfToken(loginPage.body);

    check(loginPage, {
        'login page ok': (response) => response.status === 200,
        'csrf token present': () => !!token,
    });

    const response = http.post(`${baseUrl}/login`, {
        _token: token,
        email: clientEmail,
        password: clientPassword,
    }, {
        redirects: 0,
    });

    check(response, {
        'login redirect': (res) => [302, 303].includes(res.status),
    });

    return response.cookies;
}

export function guestPages() {
    const routes = ['/', '/login', '/register'];

    for (const route of routes) {
        const response = http.get(`${baseUrl}${route}`);

        check(response, {
            [`${route} is reachable`]: (res) => res.status === 200,
        });
    }

    sleep(1);
}

export function supportPages() {
    const response = http.get(`${baseUrl}/support`);

    check(response, {
        'support page ok': (res) => res.status === 200,
    });

    sleep(1);
}

export function clientApplicationForm() {
    const cookies = loginAsClient();

    const response = http.get(`${baseUrl}/client/application`, {
        cookies,
    });

    check(response, {
        'client application form ok': (res) => res.status === 200,
    });

    sleep(1);
}
