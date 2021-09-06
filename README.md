# RAMPART
RAMP Aggregator for Rapid Tesselation

This is a collection of PHP scripts to aid making Doom community projects by taking away the need to manually assemble individual uploads and alterations. Contributors submit their WADs to a server, which will extract the needed data and compile the submissions into a continuously updated downloadable project that can be accessed by anyone at any time.

The system is entirely file-based and does not require a database. It runs on PHP 5.6 and above.

This was originally written for the RAMP project at https://ramp.teamouse.net - no holes have yet been found that allow anyone to obliterate the entire server it's running on, but use at your own risk and feel free to contribute!

# Setup

- Git clone (or download and extract) all files to your webserver
- Change the password in admin/rampartpass to the password you want to use for the admin pages (default is rampartadmin)
- Navigate to the home page and set up your project
