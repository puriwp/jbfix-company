=== JobBoard Company Fix ===
Contributors: fauzievolute
Donate link: 
Tags: jobboard, bugfix
Requires at least: 4.0
Tested up to: 4.4
Stable tag: 4.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Bug Fix For Older Company Database. Inaccurate backend company data with the data from the frontend.

== Description ==

**REQUIRED JOBBOARD THEME VERSION 2.5.0 AND UP**

> Bug Fix For Older Company Database
> Inaccurate backend company data with the data from the frontend

**Test case :**

1. Create new company from frontend with user role Job Lister
2. Add at least one company services, clients, and portofolio
3. Edit post that you just added from the frontend via wp-admin
4. The company data such as services, clients, portofolio has gone
5. But the data is not actually gone
6. With this plugin, company data inputted from frontend will be readable in the backend, and vice versa.

== Installation ==

1. Upload the plugin directory name `jbfix-company` to the `/wp-content/plugins/` directory,
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use this tools from Tools -> jBoard Company Fix screen to configure the plugin
