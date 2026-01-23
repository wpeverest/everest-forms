# Everest Forms - Development Guide

This guide will help you set up your development environment for contributing to Everest Forms.

## Table of Contents
- [Prerequisites](#prerequisites)
- [Quick Start](#quick-start)
- [Project Structure](#project-structure)
- [Development Workflow](#development-workflow)
- [Building the Plugin](#building-the-plugin)
- [Testing](#testing)
- [Code Standards](#code-standards)
- [Useful Commands](#useful-commands)
- [Troubleshooting](#troubleshooting)

## Prerequisites

Before you start, make sure you have the following installed:

1. **PHP** (version 5.6.20 or higher)
   - Check: `php --version`

2. **Node.js** (version 18.0 or higher recommended)
   - Check: `node --version`
   - Download: https://nodejs.org/
   - Note: While the project specifies Node 8.9.3 minimum, some dependencies require Node 18+

3. **npm** (version 5.5.1 or higher)
   - Check: `npm --version`
   - Comes with Node.js

4. **Composer** (for PHP dependencies)
   - Check: `composer --version`
   - Download: https://getcomposer.org/

5. **WordPress** (local installation)
   - You can use Local by Flywheel, XAMPP, MAMP, or @wordpress/env

## Quick Start

### 1. Clone the Repository

```bash
git clone https://github.com/wpeverest/everest-forms.git
cd everest-forms
```

### 2. Install Dependencies

Install PHP dependencies:
```bash
composer install
```

Install Node.js dependencies:
```bash
npm install
```

### 3. Set Up Development Environment

#### Option A: Using Existing WordPress (Local by Flywheel, XAMPP, etc.)
If you already have WordPress installed (like Local by Flywheel):
- Copy or symlink the plugin folder to your WordPress plugins directory
- Example: `wp-content/plugins/everest-forms`
- Activate the plugin from WordPress admin

#### Option B: Using wp-env (Recommended for quick setup)
```bash
npm run prephp-install
```
This will start a Docker-based WordPress environment with the plugin installed.

Access your site at: http://localhost:8888
- Username: `admin`
- Password: `password`

### 4. Start Development

Run the development watcher to automatically compile assets when you make changes:
```bash
npm run dev
```

This will watch for changes in your JavaScript and CSS files and rebuild them automatically.

## Project Structure

```
everest-forms/
├── assets/              # CSS, images, and frontend JS
├── includes/            # PHP backend code
├── src/                 # React source files
│   ├── blocks/         # Gutenberg blocks
│   ├── dashboard/      # Admin dashboard React app
│   └── templates/      # Form templates
├── dist/                # Compiled React files (auto-generated)
├── addons/              # Premium addons
├── templates/           # PHP templates
├── tests/               # PHPUnit tests
├── languages/           # Translation files
├── package.json         # Node.js dependencies
├── composer.json        # PHP dependencies
├── Gruntfile.js         # Build tasks
├── webpack.config.js    # Webpack configuration
└── everest-forms.php    # Main plugin file
```

## Development Workflow

### Working with JavaScript/React

1. **Start the watcher:**
   ```bash
   npm run dev
   ```
   This watches your files and rebuilds on changes.

2. **Make your changes:**
   - Edit files in `src/` or `assets/js/`
   - Changes are automatically compiled to `dist/`

3. **Refresh your browser** to see changes

### Working with PHP

1. **Edit PHP files** in:
   - `includes/` - Core functionality
   - `templates/` - Template files
   - Main plugin file: `everest-forms.php`

2. **Follow WordPress Coding Standards:**
   - Check your code: `composer run-script phpcs`
   - Auto-fix issues: `composer run-script phpcbf`

### Working with Styles (SCSS)

1. **Edit SCSS files** in `assets/css/` (if using Grunt) or component-specific styles

2. **Compile with Grunt:**
   ```bash
   grunt
   ```
   or
   ```bash
   grunt watch
   ```

## Building the Plugin

### Development Build

For development with unminified code and source maps:
```bash
npm run dev
```

### Production Build

To create a production-ready build:
```bash
npm run build:core
```

This will:
- Compile and minify JavaScript
- Compile and minify CSS
- Generate translation files

### Create Distribution ZIP

To create a complete plugin ZIP file for distribution:
```bash
npm run build:zip
```

This creates a ZIP file ready to install on WordPress.

## Testing

### PHP Unit Tests

Run all PHP tests:
```bash
npm run test-unit-php
```

Watch tests (auto-rerun on changes):
```bash
npm run test-php:watch
```

### PHP Code Standards

Check code against WordPress coding standards:
```bash
npm run lint-php
```

or directly with Composer:
```bash
composer run-script phpcs
```

Auto-fix code standard issues:
```bash
composer run-script phpcbf
```

### JavaScript Linting

Check JavaScript code:
```bash
npm run lint:js
```

## Code Standards

### PHP
- Follow [WordPress PHP Coding Standards](https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/)
- Minimum PHP version: 5.6.20
- Use proper WordPress hooks and filters
- Sanitize and escape all input/output

### JavaScript
- Follow WordPress JavaScript standards
- Use ES6+ features (Babel transpiles for older browsers)
- Use meaningful variable names
- Add comments for complex logic

### Commits
- Write clear commit messages
- Reference issue numbers: `Fix #1234 - Description`
- Use conventional commits when possible:
  - `feat:` for new features
  - `fix:` for bug fixes
  - `docs:` for documentation
  - `style:` for formatting
  - `refactor:` for code restructuring

## Useful Commands

### Node.js Scripts

| Command | Description |
|---------|-------------|
| `npm run dev` | Watch and compile assets for development |
| `npm run watch` | Same as dev |
| `npm run build:core` | Build production assets |
| `npm run build:zip` | Create distribution ZIP |
| `npm run lint:js` | Lint JavaScript files |
| `npm run makepot` | Generate translation files |
| `npm run test-unit-php` | Run PHP unit tests |
| `npm run test-php:watch` | Watch and run PHP tests |

### Grunt Tasks

| Command | Description |
|---------|-------------|
| `grunt` | Run default build tasks |
| `grunt watch` | Watch and compile SCSS |

### Composer Scripts

| Command | Description |
|---------|-------------|
| `composer run-script phpcs` | Check PHP code standards |
| `composer run-script phpcbf` | Auto-fix PHP code standards |
| `composer run-script test` | Run PHP unit tests |
| `composer run-script makepot` | Generate translation POT file |

### wp-env Commands

| Command | Description |
|---------|-------------|
| `npx wp-env start` | Start WordPress environment |
| `npx wp-env stop` | Stop WordPress environment |
| `npx wp-env clean all` | Reset environment |

## Troubleshooting

### npm install fails
- Try deleting `node_modules` and `package-lock.json`, then run `npm install` again
- Make sure you have the correct Node.js version

### composer install fails
- Make sure Composer is installed and updated
- Try running `composer self-update`

### wp-env doesn't start
- Make sure Docker is installed and running
- Try `npx wp-env clean all` to reset the environment

### Assets not compiling
- Stop the dev watcher and restart it
- Check for JavaScript errors in the console
- Make sure all dependencies are installed

### Permission issues
- On macOS/Linux, you might need to use `sudo` for some commands
- Or fix npm permissions: https://docs.npmjs.com/resolving-eacces-permissions-errors-when-installing-packages-globally

### Database issues during testing
- Reset the test database: `composer run-script test:reset`

### Build script permission denied (EACCES)
- Make the script executable: `chmod +x bin/build-zip.sh`

### Grunt task "default" not found
- This has been fixed in the codebase
- If you still encounter it, make sure the Gruntfile.js contains: `grunt.registerTask('default', [...])`

## Getting Help

- **Documentation:** https://docs.everestforms.net/
- **Community Forum:** https://wordpress.org/support/plugin/everest-forms
- **GitHub Issues:** https://github.com/wpeverest/everest-forms/issues
- **Contributing Guide:** See `.github/CONTRIBUTING.md`

## Next Steps

1. Read the [Contributing Guidelines](.github/CONTRIBUTING.md)
2. Check the [open issues](https://github.com/wpeverest/everest-forms/issues) for tasks
3. Join the WordPress community forums
4. Start coding!

Happy coding! 🚀
