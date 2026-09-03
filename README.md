# General Application Health & Alert System

A local, technology-independent **Application Health, Alert, and Service Status System** designed to monitor multiple backend applications and propagate meaningful health-state changes to frontend clients in near real time.

The system demonstrates how independent applications can report their health to a centralized Alert Server without making the Alert Server a proxy for normal application traffic.

---

# 1. Objective

The primary objective of this project is to build a generalized health and alert architecture where:

- Multiple backend applications can report their health.
- A centralized Alert Server maintains the current health state of each service.
- Redis is used as the internal state store.
- Backend applications remain independent from the Alert Server for normal business/API traffic.
- Health failures can be detected at the application/dependency level.
- Service recovery can be detected and reported.
- Frontend clients can eventually receive health-state changes in real time.
- Clients can maintain local service state and use circuit-breaker behavior.
- The architecture can distinguish between:
  - `HEALTHY`
  - `DEGRADED`
  - `DOWN`
  - `UNKNOWN`

The system is being developed locally using Windows 11 and Docker Desktop.

---

# 2. Technology Stack

## Operating System

- Windows 11

## Infrastructure

- Docker Desktop
- Docker Compose
- Redis
- MySQL

## Backend

- Laravel 12
- Django
- PHP
- Python

## Frontend

- Next.js
- JavaScript

## Communication

- HTTP/REST
- Server-Sent Events (SSE) — planned in Stage 5

---

# 3. High-Level Architecture

The system is divided into two major planes:

1. **Control Plane**
2. **Data Plane**

The Control Plane handles application health, service state, alerts, heartbeats, and realtime status propagation.

The Data Plane handles normal application traffic and business data.

---

# 4. Architecture Overview


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
                  │
       ┌──────────────────────┐
       │      Server D        │
       │       Django         │
       │       :8002          │
       └──────────┬───────────┘
                  │
                  │ Health Events
                  │ Heartbeat
                  │
                  ▼
       ┌──────────────────────────────┐
       │       Alert Server (AL)      │
       │            Laravel           │
       │            :8000             │
       │                              │
       │  Health Event API             │
       │  Service Status API           │
       │  Authentication               │
       │  Health State Management      │
       └──────────────┬───────────────┘
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
       │       MySQL-E        │
       │       :3307          │
       │      server_e        │
       └──────────────────────┘


       ┌──────────────────────┐
       │      Server D        │
       │       Django         │
       │       :8002          │
       └──────────┬───────────┘
                  │
                  ▼
       ┌──────────────────────┐
       │       MySQL-D        │
       │       :3308          │
       │      server_d        │
       └──────────────────────┘


                 FUTURE CONTROL-PLANE FLOW
                 -------------------------

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
                  └────────────────────┘

                  ┌────────────────────┐
                  │   Client C         │
                  │   Next.js          │
                  └────────────────────┘


Control Plane and Data Plane Architecture
5. Control Plane

The Control Plane is responsible for monitoring service health and distributing health information to clients.

Architecture
Server E
   │
   ▼
Server D
   │
   ▼
Alert Server
   │
   ▼
Redis
   │
   ▼
SSE
   │
   ▼
Clients


The Control Plane must not handle normal application API traffic.

For example, normal application traffic:

Client → Server E → MySQL-E


must remain independent from the health/control path:

Client → Alert Server → Redis


The Alert Server is a health and control system. It is not an API gateway or reverse proxy.

6. Data Plane

The Data Plane contains normal application traffic.

Server E
Client
   │
   ▼
Server E
   │
   ▼
MySQL-E

Server D
Client
   │
   ▼
Server D
   │
   ▼
MySQL-D


The Alert Server is not involved in these normal application requests.

7. Important Architectural Principles
7.1 Alert Server Is Not a Proxy

Normal application traffic should never be routed through the Alert Server.

Incorrect
Client
   │
   ▼
Alert Server
   │
   ▼
Server E

Correct
Client ────────────────► Server E


Health reporting is a separate communication path:

Server E ──────────────► Alert Server


The Alert Server receives health information; it does not forward or proxy normal application requests.

7.2 Clients Do Not Access Redis Directly

Redis is an internal Control Plane component.

Clients must never communicate directly with Redis.

Incorrect
Client → Redis

Correct
Client → Alert Server
             │
             ▼
           Redis
             │
             ▼
            SSE
             │
             ▼
           Client


The Alert Server is responsible for managing health information and distributing it to clients through Server-Sent Events (SSE).

7.3 Do Not Query the Alert Server for Every API Request

The client should not query the Alert Server before every application API request.

Incorrect
API Request
   │
   ├── Check Alert Server
   │
   ├── Check Alert Server
   │
   ├── Check Alert Server
   │
   └── Actual API request


This creates unnecessary Control Plane traffic and introduces additional latency and dependency into the Data Plane.

Instead, the client should maintain a local health state based on health events received from the Control Plane.

For example:

Client Local State

server-e = HEALTHY
server-d = DOWN


The client can use this local state when deciding whether requests should be attempted.

The Data Plane therefore remains independent from the Control Plane during normal operation.

7.4 Health Events Should Be Meaningful

The system should not broadcast every individual application exception as a health event.

For example, if a service generates:

10,000 identical database exceptions


the system should not generate:

10,000 alert broadcasts


Instead, the system should detect and propagate meaningful health-state transitions.

For example:

HEALTHY
   │
   ▼
DEGRADED
   │
   ▼
DOWN


Recovery:

DOWN
   │
   ▼
HEALTHY


This reduces unnecessary event traffic while providing clients with meaningful information about service availability.

7.5 Self-Reporting Cannot Detect Total Application Death

A completely dead application cannot report:

"I am DOWN"


because the application itself is no longer running.

Therefore, reliable health detection requires two concepts:

Heartbeat
    +
Watchdog


The current Stage 3 and Stage 4 implementation sends heartbeat events through the health-event endpoint.

A stronger TTL/Watchdog implementation is planned for the reliability stage.

The future watchdog mechanism will allow the Control Plane to detect cases where a service stops reporting entirely.

8. Health States

The system uses four logical health states:

State	Description
HEALTHY	The service and its important dependencies are operating normally.
DEGRADED	The service is available, but one or more components are experiencing problems.
DOWN	The service or an important dependency is unavailable.
UNKNOWN	There is not enough information to determine the current health state.
HEALTHY

The service and its important dependencies are operating normally.

HEALTHY

DEGRADED

The service is still available, but one or more dependencies or components are experiencing problems.

Example:

Application = available
Database    = slow


Result:

DEGRADED

DOWN

The service or an important dependency is unavailable.

Example:

MySQL = unavailable


Result:

DOWN

UNKNOWN

There is not enough information to determine the current health state.

This can occur when:

No health state has been reported yet.
The Control Plane has not received an initial health event.
The service has not yet established communication with the Alert Server.
UNKNOWN

9. Health Event Format

Health events are technology-independent.

The event format describes the logical health state of a service and its components without coupling the format to a specific application framework or implementation language.

Service Down
{
  "service": "server-e",
  "status": "DOWN",
  "component": "mysql",
  "reason": "DATABASE_UNAVAILABLE",
  "message": "Unable to connect to MySQL."
}

Service Healthy
{
  "service": "server-d",
  "status": "HEALTHY",
  "component": "mysql",
  "reason": "DATABASE_AVAILABLE"
}

Heartbeat

A heartbeat indicates that the application is alive and able to communicate with the health system.

{
  "service": "server-d",
  "status": "HEALTHY",
  "component": "application",
  "reason": "HEARTBEAT"
}

10. Overall Architecture

The overall system separates normal application traffic from health/control traffic.

Data Plane
                    DATA PLANE
                        
Client ───────────► Server E ───────────► MySQL-E
   │
   │
   └──────────────► Server D ───────────► MySQL-D

Control Plane
                   CONTROL PLANE

Server E ───────┐
                │
                ▼
          Alert Server
                │
                ▼
              Redis
                │
                ▼
               SSE
                │
                ▼
             Clients

Key Separation
┌──────────────────────────────────────────────┐
│                 DATA PLANE                   │
│                                              │
│  Client → Server E → MySQL-E                 │
│  Client → Server D → MySQL-D                 │
│                                              │
│  Normal application traffic                  │
└──────────────────────────────────────────────┘


┌──────────────────────────────────────────────┐
│               CONTROL PLANE                  │
│                                              │
│  Servers → Alert Server → Redis → SSE       │
│                                      ↓       │
│                                   Clients    │
│                                              │
│  Health and service-state information        │
└──────────────────────────────────────────────┘


The two planes remain logically independent:

Data Plane handles normal application requests.
Control Plane handles health monitoring and health-state distribution.
Alert Server is not a proxy or API gateway.
Redis remains an internal component.
Clients consume health information through the Alert Server/SSE path.
Clients maintain local health state instead of querying the Alert Server for every API request.
Health events represent meaningful state transitions rather than individual exceptions.
Heartbeats and a future watchdog/TTL mechanism provide stronger failure detection.