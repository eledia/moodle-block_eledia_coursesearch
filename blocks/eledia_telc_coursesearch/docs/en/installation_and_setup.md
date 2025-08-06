# Installation and setup    
## Installation
Place the plugin folder (`eledia_telc_coursesearch/`) into the moodle `blocks/` directory and trigger the installation process. (Go to the admin page or trigger via CLI)
## Setup
### Make the block available
Because the plugin is a *block*, it only is recommended to display it on the dashboard.  
If you need the block in other places (start page for example), your theme must support this.  
To display the plugin to all users on the dashboard, 
- go to *Site Administration* **&arr;** *Appearance* **&arr;** *Default Dashboard page*  
- Turn edit mode on
- Click *Add a block*
- Add the plugin
- Turn edit mode off
- Click on *Reset Dashboard for all users*
Now the course search is available to all users.
### Add custom fields
The plugin only shows custom fields that are visible to everyone.  
- go to *Site Administration* **&arr;** *Courses*
- in the  *Default settings* section, go to *Course custom fields*
- if there is no category, click *Add a new category*
- in the *General* section, click *Add a new custom field* and choose a field type
- add *Name*, *Short name* and *Description*
    - The description is shown in the plugin to the user and formatting is supported.
- in the *Common course custom fields settings* section set *Visible to* to **Everyone**
- use the custom field in at least one course that is visible to all users:
    - go to the course settings. In the *Additional fields*, you will find the custom field.
    - do a selection
The order of custom fields in the plugin reflects the order of custom fields in the settings.  
To change the order, drag the custom fields into the required order.  
Unused custom fields or custom fields without the visibility set to **Eveyone** are not displayed in the plugin.
## Settings
The settings should not be changed. Due to time constraints, it wasn't possible to fit the settings page to the new requirements of the plugin.  
<!-- TODO: Explain the settings and their consequences. -->
