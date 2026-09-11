# Changelog

All notable changes to `puppr` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-09-11

### Added

- `Module` Eloquent model with `name`, `slug`, `description` and `active` attributes, plus `active()`, `inactive()` and `named()` query scopes.
- Polymorphic `module_access` pivot so modules can be granted to users, teams, tenants or any other Eloquent model.
- `HasModules` trait: `grantModule()`, `revokeModule()`, `revokeAllModules()`, `syncModules()`, `hasModule()`, `hasAllModules()`, `hasAnyModule()`, `isGrantedModule()`, `activeModules()`.
- `Puppr` manager and facade: `create()`, `find()`, `findOrFail()`, `exists()`, `isActive()`, `activate()`, `deactivate()`, `delete()`, `all()`, `active()`, `grant()`, `revoke()`, `allows()`, `allowsAny()`, `denies()`, `authorize()`, `authorizeAny()`, `bypassUsing()`.
- `module` and `module.any` route middleware.
- `module` gate ability for `$user->can('module', 'billing')` and `@can`.
- `@module`, `@unlessmodule`, `@elsemodule` and `@anymodule` Blade directives.
- Artisan commands: `puppr:install`, `puppr:make`, `puppr:list`, `puppr:activate`, `puppr:deactivate`, `puppr:grant`, `puppr:revoke`.
- Publishable configuration (`config/puppr.php`) and migration.
- Test suite (Orchestra Testbench) and GitHub Actions CI for PHP 8.1 - 8.4 and Laravel 10 - 12.

[Unreleased]: https://github.com/Trench94/Puppr/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/Trench94/Puppr/releases/tag/v1.0.0
