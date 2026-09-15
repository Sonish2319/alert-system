# General Application Health & Alert System

A local, technology-independent **Application Health, Alert, and Service Status System** designed to monitor multiple backend applications and propagate meaningful health-state changes to frontend clients in near real time.

Independent backend applications report their health to a centralized **Laravel Alert Server**, which stores the latest state in **Redis** and broadcasts meaningful state changes to frontend clients — without the Alert Server ever acting as a proxy for normal application traffic.

---

## Objective

The primary objective of this project is to build a generalized health and alert architecture where:

- Multiple backend applications can report their health.
- A centralized Alert Server maintains the current health state of each service.
- Redis is used as the internal state store.
- Backend applications remain independent from the Alert Server for normal business/API traffic.
- Health failures can be detected at the application/dependency level.
- Service recovery can be detected and reported.
- Frontend clients can receive health-state changes in real time.
- Clients can maintain local service state and use circuit-breaker behavior.
- The architecture distinguishes between:

  ```text
  HEALTHY
  DEGRADED
  DOWN
  UNKNOWN
  ```

The system is being developed locally using **Windows 11** and **Docker Desktop**.

---

## Technology Stack

| Layer            | Technology                          |
| ----------------- | ------------------------------------ |
| Operating System   | Windows 11                           |
| Infrastructure     | Docker Desktop, Docker Compose       |
| State Store        | Redis                                |
| Databases          | MySQL                                |
| Backend            | Laravel 12 (PHP), Django (Python)    |
| Frontend           | Next.js (JavaScript)                 |
| Communication      | HTTP/REST, Server-Sent Events (SSE)  |

---

## High-Level Architecture

The system is divided into two major planes:

1. **Control Plane** — health events, heartbeats, service state, authentication, Redis, SSE.
2. **Data Plane** — normal application APIs and databases.

Normal business traffic goes **directly** from a client to the relevant backend service (e.g. Server E). The Alert Server is used **only** for health and alert information.

---

## Architecture Diagram

```text
                         GENERAL ALERT SYSTEM
                         =====================


                           CONTROL PLANE
                           -------------

       ┌──────────────────────┐
       │      Server E        │
       │      Laravel         │
       │      :8001           │
       └──────────┬───────────┘
                   │
                   │ Health Events
                   │ Heartbeat
                   │
       ┌──────────────────────┐
       │      Server D        │
       │       Django         │
       │       :8002          │
       └──────────┬───────────┘
                   │
                   │ Health Events
                   │ Heartbeat
                   ▼
       ┌──────────────────────────────┐
       │       Alert Server (AL)      │
       │            Laravel           │
       │            :8000             │
       │                               │
       │  Health Event API             │
       │  Service Status API           │
       │  Authentication               │
       │  Health State Management      │
       └──────────────┬────────────────┘
                       │
                       ▼
               ┌───────────────┐
               │     Redis     │
               │     :6379     │
               │               │
               │ Service State │
               │ Heartbeats    │
               └───────────────┘


                           DATA PLANE
                           ----------

       ┌──────────────────────┐
       │      Server E        │
       │      Laravel         │
       │      :8001           │
       └──────────┬───────────┘
                   │
                   ▼
       ┌──────────────────────┐
       │       MySQL-E         │
       │       :3307           │
       │      server_e         │
       └──────────────────────┘


       ┌──────────────────────┐
       │      Server D        │
       │       Django         │
       │       :8002          │
       └──────────┬───────────┘
                   │
                   ▼
       ┌──────────────────────┐
       │       MySQL-D         │
       │       :3308           │
       │      server_d         │
       └──────────────────────┘


                 CONTROL-PLANE REALTIME FLOW
                 ----------------------------

                         Redis
                           │
                           │ State Changes
                           ▼
                    ┌───────────────┐
                    │ Alert Server  │
                    │      AL       │
                    └───────┬───────┘
                            │
                            │ SSE
                            ▼
                  ┌────────────────────┐
                  │   Client A         │
                  │   Next.js          │
                  └────────────────────┘

                  ┌────────────────────┐
                  │   Client B         │
                  │   Next.js          │
                  │   (planned, empty) │
                  └────────────────────┘

                  ┌────────────────────┐
                  │   Client C         │
                  │   Next.js          │
                  │   (planned, empty) │
                  └────────────────────┘
```

---

## Current Components

### Alert Server

Laravel service responsible for central health management.

**Implemented in:**

```text
HealthEventController.php
HealthService.php
HealthEventStreamController.php
AuthenticateHealthService.php
```

**Features:**

- Accepts authenticated health events.
- Supports `HEALTHY`, `DEGRADED`, `DOWN`, and `UNKNOWN` states.
- Validates event payloads.
- Stores the latest service state in Redis.
- Detects status transitions.
- Publishes only status changes to the Redis `health-events` channel.
- Exposes current service status.
- Exposes a Server-Sent Events stream for frontend clients.
- Uses service-specific bearer tokens.

**Routes:**

```http
POST /api/v1/health-events
GET  /api/v1/services/{service}/status
GET  /api/v1/events/stream
```

Configuration is defined in `health.php`.

---

### Server E (Laravel)

Laravel application representing one monitored backend.

**Implemented features:**

```http
GET /health/live
GET /health/ready
GET /api/v1/orders
```

- MySQL connectivity checking.
- Health event reporting to the Alert Server.
- Heartbeat command: `php artisan health:heartbeat`.
- Heartbeat scheduling every ten seconds (via `console.php`).
- Health reporter methods for healthy, degraded, and down states.

**Relevant files:**

```text
HealthReporter.php
HealthMonitor.php
HealthController.php
OrderController.php
```

---

### Server D (Django)

Django application representing another monitored backend.

**Implemented features:**

```http
GET /health/live
GET /health/ready
GET /api/v1/products
```

- MySQL connectivity checking.
- Health event reporting to the Alert Server.
- Heartbeat management command.
- Healthy, degraded, and down reporting methods.

**Relevant files:**

```text
health_reporter.py
health_monitor.py
views.py
urls.py
```

---

### Client A (Next.js)

Frontend demonstrating health-aware API access.

**Implemented features:**

- Fetches initial Server E status from the Alert Server.
- Opens an SSE connection for live health updates.
- Displays status, component, reason, and timestamp.
- Calls Server E's orders API.
- Blocks requests locally when Server E is `DOWN`.
- Includes a basic local circuit breaker.

**Relevant files:**

```text
page.js
useServiceHealth.js
alertClient.js
circuitBreaker.js
```

> **Client B** and **Client C** directories currently exist but are empty (planned).

---

## Request Flow

```text
1. Server D or Server E detects a health condition or sends a heartbeat.
2. The backend sends an authenticated POST request to the Alert Server.
3. The Alert Server validates the request.
4. HealthService loads the previous state from Redis.
5. The new state is written to Redis.
6. If the status changed, the Alert Server:
     a. Logs the transition.
     b. Publishes an event to Redis.
7. The SSE controller subscribes to the Redis channel.
8. Connected frontend clients receive the event.
9. Client A updates its local state.
```

Before calling Server E, Client A checks its local circuit breaker and blocks calls when the service is `DOWN`. Normal business traffic goes directly from the client to Server E — the Alert Server is used only for health and alert information.

---

## Infrastructure

`docker-compose.yml` currently provides:

| Service          | Port  |
| ----------------- | ----- |
| Redis              | 6379  |
| Server E — MySQL   | 3307  |
| Server D — MySQL   | 3308  |

The applications themselves (Alert Server, Server D, Server E, Client A) still need to be started separately.

---

## What Has Been Done

- Initial multi-service architecture.
- Laravel Alert Server.
- Redis-backed service state.
- Authenticated health-event endpoint.
- Status transition detection.
- Redis event broadcasting.
- SSE streaming.
- Laravel health reporter.
- Django health reporter.
- Server E heartbeat scheduling.
- Database readiness checks for Server D and Server E.
- Basic Server E business API.
- Client A live health UI.
- Client-side request blocking with a basic circuit breaker.
- Docker infrastructure for Redis and both MySQL databases.

Overall, the project has a **solid working prototype** of the central health-event pipeline. The largest gap is operational completeness: expiry detection, comprehensive tests, reliable reconnect/retry behavior, and the remaining clients.

---

## Remaining Work

### High Priority

- Add real feature tests for:
  - Health event validation
  - Authentication failures
  - Redis state storage
  - Status transitions
  - Duplicate events
  - Status lookup
  - SSE behavior
- Add tests for Server D and Server E health reporters and monitors.
- Wire `HealthMonitor` database checks into regular execution (currently the classes exist, but the visible scheduled flow primarily sends heartbeats).
- Add heartbeat expiry logic — a service that stops sending heartbeats currently remains `HEALTHY` indefinitely.
- Add CORS and production configuration for frontend-to-backend communication.
- Move local tokens and credentials fully into environment configuration.

### Feature Expansion

- Build Client B and Client C.
- Add a dashboard showing all registered services.
- Add alert history instead of storing only the latest state.
- Add notifications (email, Slack, or webhooks).
- Add retry and backoff behavior for health reporting.
- Improve circuit-breaker behavior with `CLOSED`, `OPEN`, and `HALF_OPEN` states.
- Handle SSE reconnects and missed events.
- Add service registration and metadata.
- Add rate limiting and stronger production authentication.
- Add structured logging and metrics.
- Add deployment documentation and complete setup instructions.

---

## Documentation Cleanup

The root README previously described SSE as planned — it is now implemented, and this document reflects that. Still to document:

- Exact startup commands.
- Required environment variables.
- Redis and MySQL setup.
- How to run heartbeat commands.
- API request examples.
- Client A configuration.
- Current limitations and implementation status.
