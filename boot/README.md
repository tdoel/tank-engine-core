# Default config files
All files in this directory are required by the Tank Engine in alphabetical
order. Each file can be overridden by adding a file with the exact same name
to the config directory under application.

The default files are:
1. 0.config.php - defines constants such as TE_URL_ROOT and TE_DB_USER. This file *must* be overridden because the constants defined are always specific for every project.
2. 1.constants.php - defines constants that in general will not need to be overridden and are used internally by the Tank Engine.
3. 2.routes.php - defines a $routes variable. Although the default values may suffice for very specific projects, this file will in general need to be overridden.
4. 3.error_handler.php - defines an Tank Engine specific error handling function.
5. 4.parse_url.php - parses $\_GET to find the correct controller, action and data names / values. $\_GET is set (in general) set by .htaccess.
6. 5.authentication.php - if the request does not meet the criteria set by routes.php, it is rerouted according to the logic in this file.
7. 9.serve.php - serves the request.
