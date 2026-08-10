# CHANGES

This document tracks environment setup, workflow changes, and version migrations for this Elgg repository.

## [2.3] - Local Composer 1 Migration

### Context

- Elgg 2.3 was originally installed via ZIP download.
- The host system uses Composer 2, but Elgg 2.3 strictly requires Composer 1.
- To avoid global system conflicts, Composer 1 is installed locally within the project root.

### Workflow

1. **Install Local Composer 1:**
   Run this in the project root to download the Composer 1 phar:

   ```bash
   curl -sS https://getcomposer.org/installer | php -- --1
   ```

2. **Verify Installation:**

   ```bash
   php composer.phar --version
   ```

   *(Should output Composer version 1.x.x)*
3. **Install Dependencies:**

   ```bash
   php composer.phar install
   ```

4. **Managing Packages:**
   Always use the local phar instead of the global `composer` command:

   ```bash
   php composer.phar require vendor/package-name
   ```

---

## [3.0] - Elgg 3 Upgrade (Planned)

### Context

- Elgg 3 requires Composer 2 and shifts to a Composer-based project structure (Elgg core moves to `vendor/elgg/elgg`).

### Workflow

1. Switch back to the host's global Composer 2 (or download a Composer 2 phar).
2. Create a new Elgg 3 project using the starter project:

   ```bash
   composer create-project elgg/starter-project:3.* my-elgg3-site/
   ```

3. Move custom plugins from the old Elgg 2.3 `mod/` directory into the new project's `mod/` directory.
4. Migrate `settings.php` and the database according to the official Elgg 3 upgrade documentation.

---

<!-- 
Template for future versions:

## [X.X] - Version Title
### Context
- Briefly describe why the change is happening.

### Workflow
1. Step one...
2. Step two...
-->
