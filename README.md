# JobBoard Company Fix

**REQUIRED JOBBOARD THEME VERSION 2.5.1 AND UP**

> Bug Fix For Older Company Database

> Inaccurate backend company data with the data from the frontend

##### This tool is for:

1. Old jobboard user before version 2.5.1
2. Have several Companies post inputted via frontend
3. Never edited any user company post via backend

##### This tool is not for:

1. Newly user just bought JobBoard Theme on v2.5.1
2. Old users who has never upgraded to JobBoard v2.5.1
3. Old users who currently don't have any Companies post

#### Test Case

* Create new company from frontend with user role Job Lister
* Add at least one company services, clients, and portofolio
* Edit post that you just added from the frontend via wp-admin
* The company data such as services, clients, portofolio has gone
* But the data is not actually gone
* With this plugin, company data inputted from frontend will be readable in the backend, and vice versa.

#### Installation

1. Upload the plugin directory name `jbfix-company` to the `/wp-content/plugins/` directory,
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use this tools from Tools -> jBoard Company Fix screen to configure the plugin