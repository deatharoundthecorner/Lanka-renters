# Lanka Renters — Deployment Diagram

## Overview
This Deployment Diagram illustrates the physical infrastructure and network topology hosting the **Lanka Renters** platform. The architecture partitions workloads across client environments, an edge web/application hosting node running Apache 2.4 and PHP 8.2, a dedicated MariaDB database instance on port 3308, protected local persistent storage volumes, and external cloud services.

```mermaid
deploymentDiagram
    %% Client Device Nodes
    node "Client Workstation / Smartphone" as ClientNode {
        artifact "Web Browser\n(Chrome, Firefox, Safari, Edge)" as Browser
    }

    %% Demilitarized Zone & Edge Firewall
    boundary "Edge Firewall / Reverse Proxy" as EdgeFW {
        artifact "TLS 1.3 Termination" as TLS
        artifact "DDoS & Rate Limiting" as RateLimit
    }

    %% Primary Application Server
    node "Production Web / App Server (Ubuntu Linux / Windows Server)" as AppServer {
        node "Apache HTTP Server 2.4" as WebServer {
            artifact "mod_rewrite\n(Clean URLs & Routing)" as ModRewrite
            artifact "mod_headers & mod_ssl" as ModSSL
            artifact "VirtualHost: app.lankarenters.lk\n(DocumentRoot: /public)" as VHost
        }

        node "PHP 8.2 Engine (ZTS / FPM)" as PHPEngine {
            artifact "Lanka Renters MVC Core\n(/app/)" as MVCCore
            artifact "Public Asset Gateway\n(/public/index.php)" as FrontCtrl
            artifact "Upload Sanitizer & MIME Detector\n(fileinfo, pdo_mysql, session)" as PHPMods
        }

        node "Protected Local Storage Mount" as LocalFS {
            folder "/public/uploads/inspections/" as DirInsp
            folder "/public/uploads/police_reports/" as DirPolice
            folder "/public/uploads/customer_docs/" as DirKYC
            folder "/public/uploads/incident_photos/" as DirIncidents
        }
    }

    %% Database Host Node
    node "Database Server Node (Local / Clustered)" as DBServer {
        node "MariaDB Server 10.11+ / MySQL 8.0" as RDBMS {
            artifact "Database: lanka_renters\n(Port: 3308)" as DBInstance
            artifact "InnoDB Buffer Pool & Engine" as InnoDB
            artifact "Binary Log & Transaction Journals" as BinLog
        }
    }

    %% External Cloud & Payment Services
    cloud "External Cloud & Payment Infrastructure" as CloudServices {
        node "Payment Gateway Cloud" as IPGNode {
            artifact "IPG / Card Processor API\n(HTTPS / TLS 1.3)" as IPGAPI
        }
        node "Google Cloud Platform" as GCPNode {
            artifact "Google Maps & Geolocation API" as MapsAPI
        }
        node "Notification / SMTP Server" as MailNode {
            artifact "Transactional SMTP Relay\n(Port 587 / TLS)" as SMTPServer
        }
    }

    %% Inter-Node Communication Links
    Browser -->|HTTPS :443| TLS
    TLS --> RateLimit
    RateLimit --> VHost
    VHost --> ModRewrite
    ModRewrite --> FrontCtrl
    FrontCtrl --> MVCCore

    MVCCore -->|Local I/O Stream| LocalFS
    MVCCore -->|TCP/IP SQL Connection :3308\n(PDO utf8mb4)| DBInstance
    DBInstance --> InnoDB
    InnoDB --> BinLog

    MVCCore -.->|REST / HTTPS :443| IPGAPI
    MVCCore -.->|REST / HTTPS :443| MapsAPI
    MVCCore -.->|SMTP / TLS :587| SMTPServer
```

## Infrastructure & Configuration Specifications

| Component | Target Version / Spec | Primary Configuration Directives |
|---|---|---|
| **Web Server** | Apache HTTP Server 2.4 | `AllowOverride All`, `Options -Indexes +FollowSymLinks`, `Header always set X-Frame-Options "SAMEORIGIN"` |
| **PHP Runtime** | PHP 8.2.x (cli / mod_php / php-fpm) | `upload_max_filesize = 5M`, `post_max_size = 12M`, `memory_limit = 256M`, extensions: `pdo_mysql`, `fileinfo`, `session`, `openssl` |
| **Database Server** | MariaDB 10.x / MySQL 8.0 | Port: `3308`, Default Charset: `utf8mb4`, Collation: `utf8mb4_unicode_ci`, Storage Engine: `InnoDB`, `foreign_key_checks = ON` |
| **Upload Storage** | High-speed SSD Mount (`/public/uploads/`) | Read/Write for web user (`www-data` or equivalent), script execution disabled (`php_flag engine off` via `.htaccess`) |
| **Transport Layer** | TLS 1.3 / HTTPS | Enforced across all authenticated endpoints; HTTP strictly redirected to HTTPS |
