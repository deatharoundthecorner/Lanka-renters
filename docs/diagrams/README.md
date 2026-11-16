# Lanka Renters — Unified System Diagrams & Architecture Catalog

## Master Overview
This directory contains the authoritative visual models, UML specifications, relational diagrams, and deployment architecture for the **Lanka Renters** vehicle rental and fleet management ecosystem.

Every diagram in this suite is strictly aligned with the production codebase, the active native PHP 8.2 MVC runtime, and the reconciled 35-table MariaDB database (incorporating Migrations 001 through 024).

---

## Catalog of Diagrams

| # | Diagram Name | File Link | Focus & Highlights |
|---|---|---|---|
| **01** | **Use Case Diagram** | [01_use_case_diagram.md](file:///c:/Users/user/Downloads/codex/Lanka-renters-develop/docs/diagrams/01_use_case_diagram.md) | Maps functional goals across Customer, Owner, Driver, Admin, Payment Gateway, and Maps API. |
| **02** | **EER Diagram** | [02_eer_diagram.md](file:///c:/Users/user/Downloads/codex/Lanka-renters-develop/docs/diagrams/02_eer_diagram.md) | Authoritative database entity-relationship schema covering all 35 tables with exact PK, FK, UQ, and attributes. |
| **03** | **Class Diagram** | [03_class_diagram.md](file:///c:/Users/user/Downloads/codex/Lanka-renters-develop/docs/diagrams/03_class_diagram.md) | MVC structure, active model methods, helper gateways, controller action handlers, and dependency flows. |
| **04** | **Component Diagram** | [04_component_diagram.md](file:///c:/Users/user/Downloads/codex/Lanka-renters-develop/docs/diagrams/04_component_diagram.md) | Modular decomposition: presentation views, modular UI components, controllers, domain models, database, and storage. |
| **05** | **System Architecture Diagram** | [05_system_architecture_diagram.md](file:///c:/Users/user/Downloads/codex/Lanka-renters-develop/docs/diagrams/05_system_architecture_diagram.md) | 3-tier physical/logical layering: Client, Edge, Web/App server, MariaDB engine, upload volumes, external APIs. |
| **06** | **Activity Diagram** | [06_activity_diagram.md](file:///c:/Users/user/Downloads/codex/Lanka-renters-develop/docs/diagrams/06_activity_diagram.md) | End-to-end lifecycle flow: vehicle discovery, KYC gate, booking agreements, handover photos, in-trip incidents, return, and settlement. |
| **07** | **State Machine Diagram** | [07_state_diagram.md](file:///c:/Users/user/Downloads/codex/Lanka-renters-develop/docs/diagrams/07_state_diagram.md) | Discrete finite state machines for Booking, Vehicle, Driver Change Request, Incident/Replacement, and KYC. |
| **08** | **Sequence Diagram: Payment Flow** | [08_sequence_payment_flow.md](file:///c:/Users/user/Downloads/codex/Lanka-renters-develop/docs/diagrams/08_sequence_payment_flow.md) | Checkout, fee breakdown, payment gateway execution, invoice generation, transaction atomicity, and notifications. |
| **09** | **Sequence Diagram: Incident & Replacement** | [09_sequence_incident_replacement_flow.md](file:///c:/Users/user/Downloads/codex/Lanka-renters-develop/docs/diagrams/09_sequence_incident_replacement_flow.md) | In-trip emergency reporting (GPS, district, photos, police report), admin review, vehicle replacement, and customer acceptance. |
| **10** | **Sequence Diagram: Driver Change Request** | [10_sequence_driver_change_request_flow.md](file:///c:/Users/user/Downloads/codex/Lanka-renters-develop/docs/diagrams/10_sequence_driver_change_request_flow.md) | Customer driver swap request, conflict and leave validation, owner review, atomic reassignment, and tripartite alerts. |
| **11** | **Sequence Diagram: Customer KYC Verification** | [11_sequence_kyc_verification_flow.md](file:///c:/Users/user/Downloads/codex/Lanka-renters-develop/docs/diagrams/11_sequence_kyc_verification_flow.md) | NIC & driving license upload, secure validation, admin review, rejection/resubmission handling, and self-drive unlocking. |
| **12** | **Deployment Diagram** | [12_deployment_diagram.md](file:///c:/Users/user/Downloads/codex/Lanka-renters-develop/docs/diagrams/12_deployment_diagram.md) | Physical topology: Apache 2.4, PHP 8.2 engine, MariaDB port 3308, protected uploads mount, network firewalls, and external services. |

---

## Diagram Standards & Rendering
- **Format**: Standard Mermaid Markdown blocks (`mermaid`).
- **Compatibility**: Natively renders in GitHub, GitLab, VS Code Markdown Preview, and modern Markdown viewports.
- **Maintenance**: When making database or domain logic adjustments, update the corresponding diagram in lockstep.
