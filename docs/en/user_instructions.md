# User instructions

Course search is a block which lets you search courses for the following criteria:

- Full-text search of course name and description  
- Course category  
- If the course is over, ongoing, or planned for the future  
- Course custom fields  
- Tags  

You can choose between two result formats: list and cards.  

The searchable dropdown fields allow multiple selections and constrain each other.  
That means: If you select something in one field, the other fields will only show valid selection options which will return results.

The plugin consists of two main sections:  

- Search section  
- Results section  

## Search section

<img src="../assets/userview_top_en.png" alt="Search section and results view" width="50%">

### Using the searchable dropdown fields

1. Click on a field  
   - A dropdown opens with a fixed number of options.  
   - Custom select fields will additionally display a description at the top if there is one available.

2. To see other options, type a search term into the search box. The options list will update after a moment.  
   - The options list is divided into a section with selected items and one with items available for selection.  
   - To clear the search field, click the "x" symbol on the right.

3. Select an item by clicking on it. The item will appear in the selected items section.  
   - To unselect an item, click on it again.  
   - The results section of the plugin will update immediately.

Depending on the number of available search fields, there might be an **Expand search** button to reveal additional fields.

If enabled by the administrator, there is a display of all selected filters. They can also be unselected here.
<img src="../assets/ausgewaehlte_optionen.png" alt="Search section and results view" width="50%">

### Full-text search

The full-text search applies the search term to the course name and description.  
It may show a "No results" page.

## Results section

This section contains the search results either in list form or in card form, which displays the found courses.  

If there is a course description, you can expand it in list view.

<img src="../assets/userview_expanddescription_en.png" alt="Expand description" width="50%">

At the bottom, there are buttons to step through the results if there are too many to display on one page.

<img src="../assets/userview_bottom_en.png" alt="Bottom of results page" width="50%">


## Excluding courses from the search results

You can exclude courses from the search results by creating a custom course field with the type "Checkbox" and the
shortname `block_eledia_coursesearch_visible`.

Any course that has possesses this custom course field and has it set to "No" / unchecked, will be excluded from the
search results regardless of any other search criteria.
