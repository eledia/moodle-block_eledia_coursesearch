# Installation and setup

## Installation
Place the plugin folder (`eledia_coursesearch/`) into the Moodle `blocks/` directory and trigger the installation process. (Go to the admin page or trigger via CLI)

## Setup

### Make the block available

To display the plugin to all users on the dashboard:

- Go to *Site Administration* **→** *Appearance* **→** *Default Dashboard page*  
- Turn edit mode on  
- Click *Add a block*  
- Add the plugin  
- Turn edit mode off  
- Click on *Reset Dashboard for all users*  

Now the course search is available to all users.

### Public course search on the site home

The block can be added to the site home to provide a public course catalogue.
This setup requires Moodle to establish a guest session automatically; the
search services do not run in a sessionless anonymous request.

Before adding the block:

1. Verify that the Moodle guest account exists.
2. Review the guest role and grant only the intended course catalogue
   permissions, in particular `moodle/category:viewcourselist`.
3. Go to *Site administration* **→** *Users* **→** *Permissions* **→**
   *User policies* and enable *Auto-login guests*.
4. Open the site home, turn edit mode on, and add *eLeDia Course Search*.

*Auto-login guests* is a site-wide Moodle setting. Without it, an anonymous
visitor may be redirected to the login page when the block finds at least one
catalogue-visible course. A listed course can still require enrolment,
authentication, or a guest-access password when opened.

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

- **Translation:** Use Moodle's built-in multilang filter in custom-field names
  and descriptions. The legacy `Deutscher Name;English name` syntax is no
  longer supported.

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

#### Course listing style

Status: functional

Choose the standard Moodle presentation or the optional Boost Union course
cards and lists. The Boost Union option is available when the Boost Union theme
is installed and is applied only on pages whose effective theme is Boost Union
or a child theme of Boost Union.

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
- Inline within the filter input fields
- Top of the block (above the search fields)  
- Bottom of the block (below the search fields)
- Off

Default: Inline within the filter input fields.

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
