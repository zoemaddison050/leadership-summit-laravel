# WordPress Data Import Scripts

This directory contains scripts to import WordPress data into the Laravel application.

## Overview

The import process consists of:

1. **WordPressDataImporter.php** - Main importer class
2. **ImportWordPressData.php** - Artisan command (located in `app/Console/Commands/`)
3. **standalone_import.php** - Standalone script that can be run independently

## Prerequisites

1. WordPress data must be exported using the export scripts in `../export_scripts/`
2. Laravel application must be properly configured with database connection
3. All Laravel models must be in place and migrations run

## Usage

### Method 1: Using Artisan Command (Recommended)

```bash
# Basic import
php artisan import:wordpress-data

# Specify custom directory
php artisan import:wordpress-data --dir=/path/to/exported/data

# Dry run (check data without importing)
php artisan import:wordpress-data --dry-run

# Force import without confirmation
php artisan import:wordpress-data --force
```

### Method 2: Using Standalone Script

```bash
# Basic import
php database/migrations/import_scripts/standalone_import.php

# Specify custom directory
php database/migrations/import_scripts/standalone_import.php --dir=/path/to/exported/data

# Dry run
php database/migrations/import_scripts/standalone_import.php --dry-run

# Show help
php database/migrations/import_scripts/standalone_import.php --help
```

## Data Import Order

The import process follows this order to maintain referential integrity:

1. **Roles** - User roles and permissions
2. **Users** - User accounts with role assignments
3. **Events** - Event records
4. **Tickets** - Ticket types linked to events
5. **Speakers** - Speaker profiles
6. **Sessions** - Session records linked to events
7. **Session-Speaker Relationships** - Many-to-many relationships
8. **Pages** - Static pages and content
9. **Media** - Media files and attachments
10. **Orders** - WooCommerce orders
11. **Payments** - Payment records linked to orders
12. **Registrations** - Event registrations linked to users, events, and tickets

## Expected JSON Files

The import process expects these JSON files in the import directory:

- `roles.json` - WordPress roles and capabilities
- `users.json` - User accounts and profiles
- `events.json` - Events from The Events Calendar
- `tickets.json` - Tickets from WooCommerce products
- `speakers.json` - Speaker profiles
- `sessions.json` - Session records
- `session_speakers.json` - Session-speaker relationships
- `pages.json` - WordPress pages
- `media.json` - Media library files
- `orders.json` - WooCommerce orders
- `payments.json` - Payment records
- `registrations.json` - Event registrations

## Data Mapping

The importer maintains ID mappings between WordPress and Laravel records:

- WordPress post IDs → Laravel model IDs
- WordPress user IDs → Laravel user IDs
- WordPress taxonomy terms → JSON arrays

## Error Handling

- **Transaction Safety**: All imports run within database transactions
- **Logging**: Errors are logged to Laravel's log system
- **Graceful Failures**: Individual record failures don't stop the entire import
- **Rollback**: Failed imports are rolled back automatically

## Troubleshooting

### Common Issues

1. **Missing Files**: Some JSON files may be missing if WordPress doesn't have that data type
2. **Memory Limits**: Large datasets may require increased PHP memory limits
3. **Foreign Key Constraints**: Ensure all referenced models exist before importing

### Debug Mode

Enable Laravel's debug mode and check logs for detailed error information:

```bash
tail -f storage/logs/laravel.log
```

### Dry Run

Always perform a dry run first to check data integrity:

```bash
php artisan import:wordpress-data --dry-run
```

## Post-Import Tasks

After successful import:

1. **Verify Data**: Check that all records imported correctly
2. **Update Sequences**: Reset auto-increment sequences if needed
3. **Rebuild Indexes**: Rebuild search indexes if using full-text search
4. **Clear Caches**: Clear application caches
5. **Test Functionality**: Test key application features

## Performance Considerations

- **Batch Processing**: Large datasets are processed in batches
- **Memory Management**: Script monitors memory usage
- **Database Optimization**: Disable foreign key checks during import for speed
- **Indexing**: Consider dropping indexes during import and rebuilding after

## Security Notes

- **Password Hashes**: WordPress password hashes are preserved
- **Sensitive Data**: Review imported data for sensitive information
- **Permissions**: Ensure proper file permissions on imported media
- **Validation**: All imported data goes through Laravel model validation

## Support

For issues with the import process:

1. Check Laravel logs for detailed error messages
2. Verify WordPress export data integrity
3. Ensure all Laravel models and migrations are up to date
4. Test with a smaller dataset first
