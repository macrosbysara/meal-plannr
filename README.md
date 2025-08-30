# meal-plannr

A plugin to help you plan your meals with support for households, networks, and recipe sharing.

## Database Schema

This plugin creates several custom database tables to support its functionality:

### Core Recipe Tables
- `wp_meal_plannr_recipes` - Recipe nutritional information and metadata
- `wp_meal_plannr_recipe_ingredients` - Recipe ingredients with quantities and units

### Household Management Tables  
- `wp_households` - Household information with created_by user and max_members limit
- `wp_household_members` - Join table for household memberships with roles (owner, member, child, manager)

### Network Management Tables
- `wp_networks` - Network information for sharing between households  
- `wp_network_households` - Join table for network memberships with roles (owner, member)

### Recipe Sharing Tables
- `wp_recipe_shares` - Recipe access control with visibility levels (private, household, network, public)

All tables include proper indexes, foreign key relationships, and unique constraints to prevent duplicate memberships. Tables are created automatically on plugin activation using WordPress's `dbDelta()` function.