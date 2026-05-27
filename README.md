# Chelswear - Symfony E-Commerce Platform

## Project Overview
Chelswear is a modern e-commerce platform built with Symfony 7.4 and PHP 8.2. It provides both a traditional web interface and a RESTful API.

### Main Technologies
- **Framework:** Symfony 7.4
- **Language:** PHP 8.2
- **API:** API Platform (REST)
- **Authentication:**
  - Traditional Session (Login Form)
  - JWT (for API)
  - Google OAuth2
  - Email Verification
- **Database:** MySQL 8.0 / Doctrine ORM
- **Frontend:** Twig, Symfony Asset Mapper, Stimulus, Turbo
- **Containerization:** Docker, Docker Compose

### Architecture
- **Entities:** Located in `src/Entity/`. Core entities include `User`, `Customer`, `Product`, `Order`, `ActivityLog`, and `StockLog`.
- **API Resources:** Entities like `Product` are exposed as API resources via `ApiPlatform\Metadata\ApiResource`.
- **Security:** Configured in `config/packages/security.yaml`. Includes multiple firewalls for API and Web. Role hierarchy: `ROLE_ADMIN > ROLE_STAFF > ROLE_USER`.
- **Subscribers:** `src/EventSubscriber/` handles security events and activity logging.

---

## Building and Running

### Prerequisites
- PHP 8.2+
- Composer
- Docker & Docker Compose
- Symfony CLI (optional but recommended)

### Setup Instructions
1.  **Install Dependencies:**
    ```bash
    composer install
    ```
2.  **Environment Configuration:**
    Ensure `.env` or `.env.local` is configured with database credentials.
3.  **Docker Environment:**
    ```bash
    docker-compose up -d
    ```
4.  **Database Setup:**
    ```bash
    php bin/console doctrine:migrations:migrate
    php bin/console doctrine:fixtures:load
    ```
5.  **Run Server:**
    ```bash
    symfony serve
    ```

### Testing
- Run unit and functional tests:
  ```bash
  php bin/phpunit
  ```

---

## Development Conventions

### Coding Style
- Adhere to PSR-12 coding standards.
- Use PHP 8.2/8.3 features (attributes, constructor promotion, etc.).

### API Development
- Expose resources using API Platform attributes in the Entity classes.
- Use Serialization Groups (`Groups` attribute) to control API output.

### Frontend
- Keep logic in `assets/controllers/` using Stimulus.
- Avoid large CSS frameworks; utilize the existing CSS in `public/styles/`.

### Security
- Use `#[IsGranted]` attributes on controller methods to enforce access control.
- Ensure sensitive data is never logged or exposed via API serialization.
