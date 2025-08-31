# meal-plannr

A plugin to help you plan your meals with support for households, networks, and recipe sharing.

## Features

### 🏠 Household Management
- Create and manage households with multiple members
- Role-based access (owner, member, child, manager)
- Household member invitations

### 🌐 Network Management
- Create networks of up to 10 households
- Send invitations to other households
- Accept/reject invitation workflow with email notifications
- Remove households from networks
- Network-based recipe sharing

### 🍳 Recipe Management
- Create and store recipes with nutritional information
- Ingredient management with quantities and units
- Recipe sharing with multiple visibility levels

### 🔒 Access Control
- Private recipes (author only)
- Household-shared recipes (household members)
- Network-shared recipes (accepted network households)
- Public recipes (everyone)

### 📧 Email Notifications
- Invitation emails with accept/reject links
- Removal notifications
- Secure nonce-based link verification

### 🔌 REST API
Complete REST API for frontend integration with endpoints for:
- Network management (create, invite, manage)
- Recipe sharing and access control
- Invitation workflow handling

## Database Schema

This plugin creates several custom database tables to support its functionality:

### Core Recipe Tables

-   `wp_meal_plannr_recipes` - Recipe nutritional information and metadata
-   `wp_meal_plannr_recipe_ingredients` - Recipe ingredients with quantities and units

### Household Management Tables

-   `wp_meal_plannr_households` - Household information with created_by user and max_members limit
-   `wp_meal_plannr_household_members` - Join table for household memberships with roles (owner, member, child, manager)

### Network Management Tables

-   `wp_meal_plannr_networks` - Network information for sharing between households
-   `wp_meal_plannr_network_households` - Join table for network memberships with invitation status (pending, accepted, rejected)

### Recipe Sharing Tables

-   `wp_meal_plannr_recipe_shares` - Recipe access control with visibility levels (private, household, network, public)

All tables include proper indexes, foreign key relationships, and unique constraints to prevent duplicate memberships. Tables are created automatically on plugin activation using WordPress's `dbDelta()` function.

## Installation

1. Upload the plugin files to `/wp-content/plugins/meal-plannr/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Database tables will be created automatically on activation

## Documentation

- [Network Management Features](NETWORK_MANAGEMENT.md) - Detailed documentation of the network management system

## Requirements

- WordPress 6.7.0 or higher
- PHP 8.2 or higher
