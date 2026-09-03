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