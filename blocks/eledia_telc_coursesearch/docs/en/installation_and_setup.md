# Installation and setup    
## Installation
Place the plugin folder (`eledia_telc_coursesearch/`) into the moodle `blocks/` directory and trigger the installation process. (Go to the admin page or trigger via CLI)
## Setup
### Make the block available
Because the plugin is a *block*, it only is recommended to display it on the dashboard.  
If you need the block in other places (start page for example), your theme must support this.  
To display the plugin to all users on the dashboard, 
- go to *Site Administration* **&rarr;** *Appearance* **&rarr;** *Default Dashboard page* 
- Turn edit mode on
- Click *Add a block*
- Add the plugin
- Turn edit mode off
- Click on *Reset Dashboard for all users*
Now the course search is available to all users.
### Add custom fields
The plugin only shows custom fields that are visible to everyone.  
- go to *Site Administration* **&rarr;** *Courses* 
- in the  *Default settings* section, go to *Course custom fields*  
![Site administration](../assets/adminsettings_en.png | width=700)  
- if there is no category, click *Add a new category*  
![Customfield menu](../assets/admin_customfields_en.png)
- in the *General* section, click *Add a new custom field* and choose a field type  
![Add a new customfield](../assets/admin_customfieldsdd_en.png)  
- add *Name*, *Short name* and *Description*
    - The description is shown in the plugin to the user and formatting is supported.  
    ![Add customfield details](../assets/create_customfield_en.png | width=500)  
- **Translation:** The plugin supports german and english translation for the custom fields *Name* field:
    - Syntax: `Deutscher Name;English name`
    - If the user language is not german (any type of german), the english name is shown.
- in the *Common course custom fields settings* section set *Visible to* to **Everyone**  
![Set visibility to **Everyone**](../assets/customfield_visibility_en.png | width=500)  
- use the custom field in at least one course that is visible to all users:
    - go to the course settings. In the *Additional fields*, you will find the custom field.
    - do a selection
The order of custom fields in the plugin reflects the order of custom fields in the settings.  
To change the order, drag the custom fields into the required order.  
Unused custom fields or custom fields without the visibility set to **Eveyone** are not displayed in the plugin.
## Settings
Most settings should not be changed. Due to time constraints, it wasn't possible to fit the settings page to the new requirements of the plugin.  
Following, you find a list of the available settings and their state.
### Appearance
#### Display categories
Status: functional  
Show catecories in course list or on course info cards.  
#### Available layouts (checkboxes)
Status: DO NOT CHANGE
This breaks the plugin frontend if changed. 
- The layout switch button vanishes.
- Select both options, then eveything is as expected.
### Available filters
#### All (including removed from view)
Status: Non functional  
Some parts of the code require this option to be present. It does not change functionality.
#### All
Status: functional  
If disabled, the "All" option is not available in the course progress dropdown.  
I don't know a reason to disable it.
#### In progress
Status: functional  
If disabled, the "In progress" option is not available in the course progress dropdown.  
I don't know a reason to disable it.
#### Past
Status: functional  
If disabled, the "Past" option is not available in the course progress dropdown.  
I don't know a reason to disable it.
#### Future
Status: functional  
If disabled, the "Future" option is not available in the course progress dropdown.  
I don't know a reason to disable it.
#### Custom field
Status: non functional  
If clicked, a dropdown appears below the checkbox.  This option is expected by some parts of the code but has no effect anymore.
#### Starred
Status: non functional  
This option is expected by some parts of the code but has no effect anymore.
#### Removed from view
Status: non functional  
This option is expected by some parts of the code but has no effect anymore.
