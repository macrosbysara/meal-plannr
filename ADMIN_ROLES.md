# WordPress Admin Role Management

This document explains the WordPress admin role management functionality added to the Meal Plannr plugin.

## Custom Roles

### Household Owner (`household_owner`)
- **Capabilities**: `read`, `edit_recipes`, `publish_recipes`, `delete_recipes`, `manage_household`
- **Access**: Recipes, Profile, Network Management
- **Permissions**: Can create households, manage household members, create networks, invite other households

### Household Member (`household_member`)  
- **Capabilities**: `read`, `edit_recipes`, `publish_recipes`
- **Access**: Recipes, Profile
- **Permissions**: Can edit and create recipes, view profile, but cannot manage households or networks

## Admin Menu Restrictions

Non-admin users with household roles see only:
- **Recipes** - Full access to recipe management
- **Profile** - User profile settings  
- **My Networks** - Network management (household owners only)

Removed from admin menu:
- Dashboard
- Posts
- Media
- Pages
- Comments
- Appearance  
- Plugins
- Users
- Tools
- Settings

## Network Management Interface

Located at: `Profile → My Networks`

### Features:
- **Current Household**: View household members and their roles
- **Invite Members**: Send email invitations to join household
- **Create Network**: Create extended family networks (household owners only)
- **Network Invitations**: Invite other households to join networks
- **View Networks**: See all networks user belongs to

### Limitations:
- Maximum 10 households per network
- Only household owners can manage networks
- Users can only belong to one household at a time

## Recipe Post Type Capabilities

The `recipe` custom post type uses custom capabilities:
- `edit_recipes` - Edit recipe posts
- `publish_recipes` - Publish recipe posts  
- `delete_recipes` - Delete recipe posts

These capabilities are automatically assigned to the household roles and administrators.

## Security Features

- **Admin Access Control**: Redirects unauthorized users to recipes page
- **Capability Filtering**: Uses `user_has_cap` filter to enforce permissions
- **Menu Restrictions**: Removes unauthorized admin menu items
- **Database Validation**: Ensures data integrity for households and networks

## Plugin Activation/Deactivation

- **Activation**: Creates custom roles and assigns capabilities
- **Deactivation**: Removes custom roles (users revert to default subscriber role)

## Testing

Basic validation tests are included in `/tmp/test-basic-validation.php` to verify:
- PHP syntax correctness
- Required methods presence  
- Role and capability definitions
- Class structure integrity