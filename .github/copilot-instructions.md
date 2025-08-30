# Meal Plannr WordPress Plugin

Meal Plannr is a WordPress plugin that provides custom Gutenberg blocks for recipe management with nutritional tracking. It includes PHP backend services for custom post types, REST API endpoints, and database management, plus TypeScript frontend blocks for the WordPress block editor.

Always reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.

## Working Effectively

### Bootstrap and Dependencies
Install dependencies in this exact order with appropriate timeouts:

- `composer install --no-interaction` -- NEVER CANCEL. Takes 1-2 minutes. Set timeout to 300+ seconds. May show GitHub authentication messages that can be ignored.
- `npm install` -- NEVER CANCEL. Takes 2+ minutes (tested: 2m 8s). Set timeout to 300+ seconds. Will show peer dependency warnings about react versions - these are expected and can be ignored.

### Build Process
- `npm run build` -- Fast build process. Takes ~3 seconds. Creates optimized production files in build/ directory.
- `npm start` -- Development watch mode. Initial compilation takes ~1.5 seconds, then watches for changes. Use Ctrl+C to stop.

### Linting and Code Quality
Always run linting commands before committing changes:

- `composer run phpcs` -- PHP Code Sniffer for WordPress coding standards. Takes <1 second. Currently has 1 known error in class-table-handler.php regarding $wpdb->prepare() usage that violates WordPress security standards.
- `npx wp-scripts lint-js src/` -- ESLint for JavaScript/TypeScript. Takes ~5 seconds. Currently has 11 issues (8 errors, 3 warnings) mostly related to React hooks rules and missing keys.
- `npx wp-scripts format src/` -- Prettier formatting for code consistency. Takes <1 second.

### Code Structure
- `src/blocks/`: 4 custom Gutenberg blocks written in TypeScript
  - `recipe-block/`: Main container block for recipes
  - `recipe-meta/`: Nutritional information block (calories, protein, carbs, fat)
  - `ingredients-container/`: Container for ingredient list
  - `ingredients-block/`: Individual ingredient block with quantities and units
- `src/hooks/`: React hooks for editor state management and data syncing
- `includes/`: PHP classes for WordPress integration
  - `class-theme-init.php`: Main plugin initialization and block registration
  - `class-cpt-handler.php`: Custom post types and taxonomies (recipes, cuisines)
  - `class-rest-router.php`: REST API endpoints for ingredients and macros
  - `class-table-handler.php`: Database table management for ingredients/nutrition data
- `build/`: Generated JavaScript bundles and PHP asset files (auto-generated, don't edit)

## WordPress Plugin Context

**CRITICAL**: This is a WordPress plugin, not a standalone application. It cannot be run independently.

### Plugin Installation Requirements
- WordPress 6.7+ required (specified in phpcs.xml.dist)
- Must be placed in WordPress wp-content/plugins/ directory
- Must be activated through WordPress admin interface
- Creates custom database tables on activation
- Registers 4 new Gutenberg blocks in the block editor

### Validation Scenarios
Since this plugin requires WordPress to function, complete validation requires:

1. **WordPress Environment Setup**: Install in wp-content/plugins/ directory of WordPress site
2. **Plugin Activation**: Activate "Meal Plannr" plugin in WordPress admin
3. **Block Testing**: Create/edit a post and add "Recipe Block" from block inserter
4. **Functionality Testing**: 
   - Add ingredients with quantities/units
   - Enter nutritional information (calories, protein, carbs, fat)
   - Save post and verify data persistence
   - Check frontend rendering of recipe blocks
   - Test REST API endpoints via browser developer tools

**Limitation**: Full functional validation cannot be performed without a WordPress installation. Always document when changes affect WordPress-specific functionality that cannot be tested in this environment.

### Database Integration
- Plugin creates custom tables for ingredients and nutrition data
- Uses WordPress $wpdb class for database operations
- Syncs block data to database via REST API calls on post save
- Known issue: class-table-handler.php line 144 violates WordPress security standards for SQL preparation

### Block Architecture
- Blocks are hierarchical: recipe-block contains ingredients-container and recipe-meta
- ingredients-container contains multiple ingredients-block instances  
- Data flows from blocks to database via useIngredientsSync and useMacrosSync hooks
- REST endpoints: `/mealplannr/v1/ingredients/batch` and `/mealplannr/v1/recipes/{id}/macros`

## Development Workflow

### Making Changes
1. Always run `npm install` and `composer install --no-interaction` first
2. Use `npm start` for development with live reloading  
3. Make changes to src/ files (not build/ files)
4. Test with `npm run build` to ensure production build works
5. Run linting: `composer run phpcs` and `npx wp-scripts lint-js src/`
6. Format code: `npx wp-scripts format src/`
7. For WordPress-specific features, document testing requirements in comments

### Common Development Commands
- `npm start` -- Development mode with file watching
- `npm run build` -- Production build
- `npx wp-scripts lint-js src/ --fix` -- Auto-fix linting issues where possible
- `composer run phpcbf` -- Auto-fix PHP coding standards issues

### Testing Limitations
- No unit test infrastructure exists
- No WordPress development environment (wp-env) configured
- Full functional testing requires WordPress installation
- Focus on build/lint validation and code review for changes

## Known Issues
- PHP linting error in class-table-handler.php regarding SQL query preparation
- JavaScript linting issues mostly related to React hooks usage and missing keys
- Peer dependency warnings in npm install (React version conflicts) - can be ignored
- Composer may show GitHub authentication prompts - use --no-interaction flag

## Key Files Reference
### Root Files
- `meal-plannr.php`: Main plugin file with WordPress headers
- `package.json`: npm scripts and dependencies
- `composer.json`: PHP dependencies and linting scripts  
- `phpcs.xml.dist`: PHP Code Sniffer configuration

### Build Output (Do Not Edit)
- `build/blocks/`: Compiled JavaScript bundles for each block
- `build/blocks-manifest.php`: Generated block metadata for WordPress registration

Always build and lint your changes before committing. The build process is fast but linting will reveal code quality issues that should be addressed.