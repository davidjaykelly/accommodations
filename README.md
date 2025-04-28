# Moodle Accommodation Management Plugin

## Overview

The Accommodation Management plugin for Moodle streamlines the administration of personalized assessment arrangements for students who require extra time during coursework and exams. This plugin is designed to reduce the administrative burden of managing accommodations across multiple courses and assessments.

⚠️ **WORK IN PROGRESS** - This plugin is currently in early development and not ready for production use. Features may be incomplete or change significantly.

## Features

- **Centralized Management**: Define accommodation profiles with specific time extensions (e.g., 25% extra time) that can be applied at course, category, or system-wide levels
- **Activity-Level Controls**: Enable or disable accommodations for specific quizzes and assignments
- **Automatic Application**: Automatically applies accommodations when new activities are created
- **Bulk Management**: Add accommodations for multiple students through CSV upload or direct selection
- **Detailed Reporting**: View accommodation usage across courses and categories

## Requirements

- Moodle 4.5 or higher
- PHP 7.4 or higher

## Installation

1. Place the plugin code in the `local/accommodations` directory of your Moodle installation
2. Visit Site Administration > Notifications to complete the installation
3. Configure the plugin settings through Site Administration > Plugins > Local Plugins > Accommodation Management

## Usage

### Accommodation Types

First, define accommodation types with default time extensions through the plugin's administration interface:

1. Navigate to Site Administration > Plugins > Local Plugins > Accommodation Management > Manage Types
2. Create accommodation types (e.g., "Learning Disability", "Language Accommodation")

### Adding Accommodations

Accommodations can be added at different levels:

- **Course Level**: Through the "Accommodations" link in the course administration menu
- **Category Level**: Through the category management interface
- **System Level**: Through the plugin's main administration page

### Applying Accommodations

When accommodations are added, they can be automatically applied to all activities or selectively applied to specific quizzes and assignments.

## Development Status

Current development focus:
- Stabilizing core functionality
- Enhancing user interface
- Adding additional reporting features
- Resolving known issues with form submissions

## Contributing

This plugin is in active development. Contributions, bug reports, and feature requests are welcome!

## License

GPL v3 or later

## Credits

Developed by David Kelly (davidkel.ly) 
© 2025
