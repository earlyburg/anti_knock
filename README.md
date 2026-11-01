Setup
Important: Go to the configuration page /admin/config/anti_knock and input the IP address of your webserver.
Input the number of 404 exceptions allowed from a single IP before it gets blocked.
Optional: add some subnets that should not be blocked in the format ###.###.###.*.
Click submit to save the configuration.
Functionality
This module monitors 404 requests that reach Drupal and blocks IP addresses that exceed the configured threshold.
This module only blocks 404 requests from users who are not logged in. Logged-in users requests are not evaluated.
About
Anti-Knock automatically blocks requests intent on Directory Busting / Brute-Forcing attacks using the functionality of the Ban module.
Drupal's .htaccess file provides one layer of protection against 404 requests containing malicious patterns, but this does not usually stop more sophisticated probing.
In general, an attacker may probe a site using this method looking for paths that might exist to help them profile an application.
Besides being a nuisance and filling up the logs, getting accurate information about an applications structure can assist attackers in later stage DDOS attacks.
Poorly constructed or malicious Bots or webspiders can also cause this problem.
Requests to non-existent or expired paths to content can trigger this module to block an IP, so we recommend redirecting such paths appropriately.