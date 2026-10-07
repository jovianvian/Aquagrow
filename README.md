<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

# AquaGrow AI

Sistem informasi manajemen budidaya ikan dari pembibitan hingga panen.

## Local development

Gunakan Ubuntu WSL pada project root `/home/jovia/projects/aquagrow-ai`.
PHP, Composer, Artisan, PHPUnit, dan tooling aplikasi dijalankan melalui Docker.

```bash
cp .env.example .env
docker compose build app
docker compose up -d app nginx postgres redis queue-worker scheduler
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

Aplikasi tersedia di `http://localhost:8090`; PostgreSQL dan Redis hanya berada
di network internal Compose.

Test standar menggunakan database PostgreSQL terpisah bernama `aquagrow_testing`:

```bash
./bin/test
```

Frontend production build:

```bash
docker compose --profile frontend run --rm node npm ci
docker compose --profile frontend run --rm node npm run build
```

Cycle planning and stocking foundation is available after a farm, location, and
pond are configured. The current slice supports planned, active, and cancelled
cycles; completion/harvest, monitoring, AI, and IoT remain deferred.

Farm pertama dibuat dari dashboard setelah registrasi. Pembuatnya otomatis
menjadi `FARM_OWNER`; user tanpa farm melihat empty state yang jujur. Farm aktif
disimpan di session dan selalu diverifikasi terhadap membership user.

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

## Location and pond workspace

With an active farm selected, owners can create and update locations and ponds.
Operators can view them read-only. Pond codes are unique within a farm, and
pond/location relationships are protected by a PostgreSQL composite constraint.
Measurements are optional canonical metric values: dimensions in meters, water
volume in liters, and capacity as a positive fish count. Pond deletion is not
exposed. Cultivation cycles use farm-local batch codes, allow one planned or
active cycle per pond, and keep operators read-only.

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
