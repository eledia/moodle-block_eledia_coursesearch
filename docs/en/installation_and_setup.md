# Installation and setup

## Installation
Place the plugin folder (`eledia_coursesearch/`) into the Moodle `blocks/` directory and trigger the installation process. (Go to the admin page or trigger via CLI)

## Setup

### Make the block available
Because the plugin is a *block*, it is only recommended to display it on the dashboard.  

If you need the block in other places (start page, for example), your theme must support this.  

To display the plugin to all users on the dashboard:

- Go to *Site Administration* **→** *Appearance* **→** *Default Dashboard page*  
- Turn edit mode on  
- Click *Add a block*  
- Add the plugin  
- Turn edit mode off  
- Click on *Reset Dashboard for all users*  

Now the course search is available to all users.

### Add custom fields
The plugin only shows custom fields that are visible to everyone.  

- Go to *Site Administration* **→** *Courses*  
- In the *Default settings* section, go to *Course custom fields*  

<img src="../assets/adminsettings_en.png" alt="Site administration" width="70%">

- If there is no category, click *Add a new category*  

![Customfield menu](../assets/admin_customfields_en.png)

- In the *General* section, click *Add a new custom field* and choose a field type  

![Add a new customfield](../assets/admin_customfieldsdd_en.png)

- Add *Name*, *Short name* and *Description*  
    - The description is shown in the plugin to the user and formatting is supported.  

<img src="../assets/create_customfield_en.png" alt="Add customfield details" width="60%">

- **Translation:** The plugin supports German and English translation for the custom field *Name* field:  
    - Syntax: `Deutscher Name;English name`  
    - If the user language is not German (any type of German), the English name is shown.

- In the *Common course custom fields settings* section, set *Visible to* to **Everyone**  

<img src="../assets/customfield_visibility_en.png" alt="Set visibility to Everyone" width="50%">

- Use the custom field in at least one course that is visible to all users:  
    - Go to the course settings. In the *Additional fields*, you will find the custom field.  
    - Make a selection.  

The order of custom fields in the plugin reflects the order of custom fields in the settings.  

To change the order, drag the custom fields into the required order.  

Unused custom fields or custom fields without the visibility set to **Everyone** are not displayed in the plugin.

## Settings
Most settings should not be changed.

Following is a list of the available settings and their state.

### Appearance

#### Display categories
Status: functional  
Show categories in course list or on course info cards.  

#### Available layouts (checkboxes)
Status: **DO NOT CHANGE**  
This breaks the plugin frontend if changed.  

- The layout switch button vanishes.  
- Select both options, then everything is as expected.  

#### Selected options items position
Status: functional  
Choose where the selected options items are displayed:  
- Top of the block (above the search fields)  
- Bottom of the block (below the search fields)
Default: Off

### Available filters

#### All (including removed from view)
Status: non-functional  
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
Status: non-functional  
If clicked, a dropdown appears below the checkbox. This option is expected by some parts of the code but has no effect anymore.

#### Starred
Status: non-functional  
This option is expected by some parts of the code but has no effect anymore.

#### Removed from view
Status: non-functional  
This option is expected by some parts of the code but has no effect anymore.
