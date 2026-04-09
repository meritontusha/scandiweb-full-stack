# Scandiweb Test Assignment

This repository contains my Scandiweb test assignment submission.

The frontend is a Vite + React application deployed on Vercel, and the backend is a PHP + GraphQL application deployed on Hostinger.

Note: this is a clean submission repository, not the original development repository. The implementation and deployment are mine.

## Tech Stack

- Frontend: Vite, React, Apollo Client, React Router, Zustand
- Backend: PHP 8.1+, FastRoute, webonyx/graphql-php, PDO, phpdotenv
- Database: MySQL

## Repository Structure

- `frontend/` contains the Vite client
- `backend/` contains the PHP GraphQL API

## Local Setup

### Backend

```bash
cd backend
composer install
cp .env.example
mysql -u root -e "CREATE DATABASE IF NOT EXISTS scandiweb;"
php sql/seed.php
php -S 127.0.0.1:8000 -t public public/index.php
```

`backend/.env.example` is prefilled with localhost-friendly defaults:

- host: `127.0.0.1`
- port: `3306`
- database: `scandiweb`
- user: `root`
- password: empty

Adjust them only if your local MySQL setup is different.

### Frontend

```bash
cd frontend
npm install
cp .env.example .env.local
npm run dev
```

`frontend/.env.example` is prefilled for direct local testing against `http://127.0.0.1:8000/graphql`.
