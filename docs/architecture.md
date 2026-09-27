# CSMS PHP Architecture

## Purpose

This document describes the intended architectural organization of the PHP implementation of the Community Services Management System.

The architecture provides structure for development throughout the semester.

Some components may initially contain placeholders and will be implemented as activities introduce their responsibilities.

## Architectural Principle

CSMS separates responsibilities instead of placing all application logic in controllers or routes.

A typical request may involve:

1. A route receives an HTTP request.
2. A controller handles the HTTP interaction.
3. Input is validated.
4. A service coordinates application behavior.
5. A repository performs persistence-related operations when required.
6. A model represents domain or persisted data.
7. The controller returns the appropriate response.

Not every activity will use every layer.

Students should implement only the layers required by the assigned ticket.

## Main Application Areas

### Models

Location:

```text
app/Models/
```

Models represent important data and domain concepts in the application.

Examples may include:

```text
Resident.php
ServiceRequest.php
```

Students should not assume that an existing model is complete.

Its properties and behavior may be introduced through later activities.

### Controllers

Location:

```text
app/Http/Controllers/
```

Controllers handle HTTP requests and responses.

Controllers should remain focused on HTTP-related responsibilities rather than becoming containers for all application logic.

### Services

Location:

```text
app/Services/
```

Services coordinate application operations and business behavior.

As the application grows, services help prevent controllers from accumulating unrelated responsibilities.

### Repositories

Location:

```text
app/Repositories/
```

Repositories provide an abstraction for data access when required by the project.

Persistence logic should not be duplicated throughout the application.

### Support

Location:

```text
app/Support/
```

Supporting application utilities and shared validation-related components may be placed here when required by an activity.

### Routes

Location:

```text
routes/
```

Routes map incoming requests to application behavior.

Route definitions should remain concise.

### Tests

Location:

```text
tests/
```

The project uses automated tests to verify expected behavior.

Tests are generally separated into:

```text
tests/Unit/
tests/Feature/
```

Unit tests focus on small units of behavior.

Feature tests verify larger application behavior and framework integration.

## Dependency Direction

As the application develops, prefer clear responsibility boundaries.

For example, an HTTP controller may depend on an application service, and an application service may depend on a repository.

Avoid unnecessary coupling between unrelated components.

## Domain Models

A domain model should represent meaningful concepts and behavior from the problem domain.

Students should not treat a model merely as a collection of arbitrary variables.

When a Moodle activity defines a domain model, implement the fields, defaults, behavior, and tests specified by that activity.

Do not add speculative properties simply because they might be useful later.

## Validation

Validation protects the application from invalid input.

Validation requirements will be introduced progressively.

Place validation in the location required by the current architecture and activity rather than duplicating the same validation logic in multiple locations.

## Persistence

Database persistence will be introduced when required by the semester activities.

A model existing in `app/Models` does not automatically mean that every activity requires database interaction.

Students should distinguish between:

- representing an object
- validating an object
- persisting an object
- retrieving an object

These are related but different responsibilities.

## Testing Architecture

Every implemented requirement should be verifiable.

Students should create or update automated tests as instructed by each activity.

Before submitting work, run:

```bash
php artisan test
```

The goal is not merely to make a test green.

Students should understand what each test verifies and why the behavior is required.

## Framework Conventions

Use Laravel conventions unless the project documentation or current activity specifies otherwise.

PHP code should follow the project's configured formatting rules and should be formatted using Laravel Pint.

## Architecture Evolution

This architecture is intentionally introduced progressively.

Do not create unnecessary controllers, repositories, services, database tables, endpoints, or user interfaces before they are required.

The repository should evolve together with the semester activities.
