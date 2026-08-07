# Customer Module Architecture

## Architecture Pattern

MVC (Model–View–Controller)

## Components

### Models

Responsible for:

- Database operations
- CRUD functionality
- Business data handling

### Controllers

Responsible for:

- Processing user requests
- Calling models
- Returning customer pages

### Views

Location:

public/customer/

Responsibilities:

- Display UI
- No SQL
- Minimal PHP
- Business logic handled by controllers

## Request Flow

Customer

↓

Customer Page

↓

Controller

↓

Model

↓

Database

↓

Model

↓

Controller

↓

Customer Page

## Shared Project Structure

Controllers

app/controllers/

Models

app/models/

Views

public/customer/

Assets

public/assets/