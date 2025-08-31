# Network Management Features

This document describes the network management features implemented for the Meal Plannr plugin.

## Overview

The network management system allows household owners to create networks, invite other households, and share recipes within those networks. It includes a complete invitation workflow with email notifications and access control.

## Features Implemented

### ✅ Core Network Management
- **Network Creation**: Household owners can create networks with custom names
- **Network Size Limit**: Maximum of 10 households per network (enforced)
- **Household Invitations**: Send invitations to other households
- **Invitation Workflow**: Accept/reject invitations with status tracking
- **Household Removal**: Network owners can remove households from networks

### ✅ Email Notifications
- **Invitation Emails**: Automatic emails sent with accept/reject links
- **Removal Notifications**: Optional emails when households are removed
- **Email Link Handling**: Secure links with nonce verification
- **Auto-login Redirect**: Non-authenticated users are redirected to login

### ✅ Access Control Layer
- **Recipe Visibility Levels**: 
  - `private` - Recipe author only
  - `household` - All household members
  - `network` - All accepted network households
  - `public` - Everyone
- **Network Membership Validation**: Only accepted households can access network recipes
- **Query Filtering**: Automatic filtering of recipe queries based on access rights

### ✅ Database Schema
- **Networks Table**: Stores network information
- **Network Households Table**: Manages household memberships with status tracking
  - Status values: `pending`, `accepted`, `rejected`
  - Tracks invitation and join timestamps
  - Unique constraints to prevent duplicate memberships

### ✅ REST API Endpoints

#### Network Management
- `POST /mealplannr/v1/networks` - Create network
- `GET /mealplannr/v1/networks/my` - Get user's networks
- `POST /mealplannr/v1/networks/{id}/invite` - Send invitation
- `POST /mealplannr/v1/invitations/{id}/{action}` - Accept/reject invitation
- `DELETE /mealplannr/v1/networks/{id}/households/{id}` - Remove household
- `GET /mealplannr/v1/networks/{id}/households` - Get network households
- `GET /mealplannr/v1/households/invitations` - Get household invitations

#### Recipe Sharing
- `POST /mealplannr/v1/recipes/{id}/sharing` - Set recipe sharing
- `GET /mealplannr/v1/recipes/{id}/sharing` - Get recipe sharing status
- `GET /mealplannr/v1/recipes/accessible` - Get accessible recipes

## Implementation Details

### Service Classes

#### Network_Service
Handles network management business logic:
- Network creation and validation
- Invitation workflow management
- Network size limit enforcement (10 households max)
- Email notification sending
- Household removal with validation

#### Recipe_Access_Service
Manages recipe access control:
- Recipe sharing settings management
- Access control validation
- Query filtering for recipe visibility
- Recipe accessibility checks

#### Invitation_Handler
Processes email invitation links:
- Accept/reject link handling
- Nonce verification for security
- Auto-login redirect for non-authenticated users
- Success/error message display

### Database Schema Updates

The existing `network_households` table was enhanced to support the invitation workflow:
- Added `status` enum field (pending, accepted, rejected)
- Added `invited_at` timestamp field
- Modified `joined_at` to be nullable (set when invitation is accepted)

## Security Features

- **Nonce Verification**: All invitation links include security nonces
- **User Validation**: Only household owners can accept/reject invitations
- **Network Owner Validation**: Only network creators can send invitations and remove households
- **Access Control**: Recipe access strictly enforced based on network membership status

## Usage Examples

### Creating a Network via REST API
```javascript
POST /mealplannr/v1/networks
{
  "name": "Family Recipe Network"
}
```

### Sending an Invitation
```javascript
POST /mealplannr/v1/networks/1/invite
{
  "household_id": 5
}
```

### Setting Recipe Sharing
```javascript
POST /mealplannr/v1/recipes/123/sharing
{
  "visibility": "network",
  "network_id": 1
}
```

## Acceptance Criteria Status

- ✅ Household owners can send invitations to other households
- ✅ Invited households can accept or reject invitations
- ✅ Only accepted households gain recipe access
- ✅ Household owners can remove households from their networks
- ✅ Network size never exceeds 10 households
- ✅ Email notifications are sent for invitations and optional removals
- ✅ ACL layer enforces network membership for recipe visibility

## Files Modified/Created

### New Files
- `includes/class-network-service.php` - Network management service
- `includes/class-recipe-access-service.php` - Recipe access control
- `includes/class-invitation-handler.php` - Email invitation handler

### Modified Files
- `includes/class-table-handler.php` - Database schema and network management methods
- `includes/class-rest-router.php` - REST API endpoints for network management
- `includes/class-theme-init.php` - Service registration and initialization

## Testing

The implementation includes comprehensive validation through:
- Mock integration testing of core functionality
- Database schema validation
- REST API endpoint structure verification
- Service class method testing

All core features have been tested and validated to work correctly within the WordPress environment.