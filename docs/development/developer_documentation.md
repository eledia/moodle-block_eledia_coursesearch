# Moodle Block: Coursesearch

## Backend classes and functions and their relations

This document describes the backend architecture of the eLeDia Coursesearch block (made possible by TU Ilmenau).

### Entry Point: `block_eledia_coursesearch.php`

The main entry point for the block is the `block_eledia_coursesearch` class in `block_eledia_coursesearch.php`. This class is responsible for:

*   **Initialization:** The `init()` method sets the block's title.
*   **Content Rendering:** The `get_content()` method is the core of the block's rendering logic. It performs the following steps:
    1.  Retrieves user preferences for grouping, sorting, and view mode using `get_user_preferences()`.
    2.  Instantiates the `\block_eledia_coursesearch\output\main` class, passing the user preferences as arguments.
    3.  Gets the appropriate renderer for the block using `$this->page->get_renderer('block_eledia_coursesearch')`.
    4.  Renders the output of the `main` class using the renderer's `render()` method.
    5.  Sets the rendered HTML as the block's content.

### Web Services: `classes/externallib.php`

The block exposes a set of web services through the `\block_eledia_coursesearch\externallib` class. These services are defined in `db/services.php` and are used by the frontend to fetch data.

Here's a diagram illustrating the relationship between the main block class, the output class, and the external library:

```mermaid
graph TD
    A[block_eledia_coursesearch] -- instantiates --> B["main (output)"];
    B -- renders --> C[HTML];
    D[Frontend JS] -- calls --> E["externallib"];
    E -- returns --> F[JSON Data];
```

The following web services are available:

*   **`get_courseview`**: This is the primary endpoint for searching and filtering courses. It takes a set of criteria (custom fields, categories, tags, search term) and returns a list of matching courses.
*   **`get_available_categories`**: Returns a list of available course categories based on the current filter criteria.
*   **`get_available_tags`**: Returns a list of available tags based on the current filter criteria.
*   **`get_customfield_available_options`**: Returns a list of available options for a given custom field, based on the current filter criteria.
*   **`get_customfields`**: Returns a list of all visible custom fields that can be used for filtering.
*   **`get_enrolled_courses_by_timeline_classification`**: Retrieves a user's enrolled courses based on a classification (e.g., "in-progress", "past", "future").

### Database Queries

This section describes the main database queries used by the block.

### SQL Relationships per Web Service

This section provides diagrams illustrating the database table relationships for each of the main web service functions.

#### `get_courseview` / `get_filtered_courseids`

This function performs the most complex query, joining multiple tables to filter courses based on various criteria.

```mermaid
graph TD
    subgraph "Course Information"
        A[course]
    end
    subgraph "Context"
        B[context]
    end
    subgraph "Custom Fields"
        C[customfield_data]
        D[customfield_field]
        E[customfield_category]
    end
    subgraph "Tags"
        F[tag_instance]
        G[tag]
    end

    A -- one-to-one --> B;
    B -- one-to-many --> C;
    D -- one-to-many --> C;
    E -- one-to-many --> D;
    A -- many-to-many --> G;
    F -- many-to-one --> G;
    A -- one-to-many --> F;
```

#### `get_available_categories`

This function retrieves the course categories that are associated with the currently filtered list of courses.

```mermaid
graph TD
    A[course] -- many-to-one --> B[course_categories];
```

#### `get_available_tags`

This function retrieves the tags that are associated with the currently filtered list of courses.

```mermaid
graph TD
    A[course] -- one-to-many --> B[tag_instance];
    B -- many-to-one --> C[tag];
```

#### `get_customfield_available_options`

This function retrieves the available options for a specific custom field based on the values present in the currently filtered list of courses.

```mermaid
graph TD
    subgraph "Course"
        A[course]
    end
    subgraph "Context"
        B[context]
    end
    subgraph "Custom Field Data"
        C[customfield_data]
    end
    A -- one-to-one --> B;
    B -- one-to-many --> C;
```

#### `get_customfields`

This function retrieves all the course-related custom fields that are configured to be visible.

```mermaid
graph TD
    A[customfield_category] -- one-to-many --> B[customfield_field];
```

## Frontend JavaScript Code and API Endpoints

This section describes the frontend JavaScript code and its interaction with the backend API.

### Entry Point: `amd/src/main.js`

The main entry point for the block's JavaScript is the `init()` function in `amd/src/main.js`. This function initializes the two main frontend modules:

*   **`ViewNav`**: Handles the navigation elements (sorting, filtering, etc.).
*   **`View`**: Manages the display of the course list.

### Course View: `amd/src/view.js`

The `View` module is responsible for fetching and rendering the course list. It uses a `PagedContentFactory` to handle pagination.

#### Key Functions:

*   **`init(root)`**: Initializes the course view, sets up event listeners, and triggers the initial loading of courses.
*   **`initializePagedContent(root, promiseFunction, inputValue, params)`**: Creates a `PagedContentFactory` instance to handle pagination. It takes a `promiseFunction` that is responsible for fetching the course data.
*   **`getMyCourses(filters, limit, searchParams)`**: Fetches the list of courses from the backend using the `block_eledia_coursesearch_get_courseview` web service.
*   **`renderCourses(root, coursesData)`**: Renders the course list using Mustache templates.

### Course Navigation: `amd/src/view_nav.js`

The `ViewNav` module handles the user's interactions with the navigation elements (e.g., changing the sort order, filtering by category).

#### Key Functions:

*   **`init(root)`**: Initializes the navigation elements and sets up event listeners.
*   **`registerSelector(root)`**: Sets up event listeners for the filter and display options. When a user changes a filter, it updates the user's preferences and reloads the course list.

### API Calls and Data Structures

The `repository.js` module is responsible for making all API calls to the backend. It uses the `core/ajax` module to send requests to the Moodle web services. The following table details the mapping between the frontend functions and the backend web services they call.

| `repository.js` Function | Webservice Called | Important Request `args` Keys | Important Response Keys |
| :--- | :--- | :--- | :--- |
| `getEnrolledCoursesByTimeline(args)` | `block_eledia_coursesearch_get_courseview` | `criteria` (contains all filters: `name`, `selectedCategories`, `selectedCustomfields`, `limit`, `offset`, etc.) | `courses` (array of course objects), `nextoffset` |
| `getCategories(args)` | `block_eledia_coursesearch_get_available_categories` | `criteria` (filters are used to find relevant categories) | An array of category objects: `id`, `name`, `coursecount` |
| `getTags(args)` | `block_eledia_coursesearch_get_available_tags` | `criteria` (filters are used to find relevant tags) | An array of tag objects: `id`, `name` |
| `getCustomfields(args)` | `block_eledia_coursesearch_get_customfield_available_options` | `criteria` (especially `currentCustomField` to identify the target field) | An array of option objects: `name`, `value` |
| `setFavouriteCourses(args)` | `core_course_set_favourite_courses` | `courses` (an array containing `id` and `favourite` status) | `warnings` (array of warnings, empty on success) |

## Stale Functions and Methods

This section lists functions and methods that are likely stale and no longer used.

### Backend

*   **File:** `classes/externallib.php`
    *   **Class:** `externallib`
        *   **Method:** `get_data()`
        *   **Method:** `get_enrolled_courses_by_timeline_classification()`
        *   **Method:** `get_enrolled_courses_by_timeline_classification_raw()`

### Frontend

*There are no apparent stale functions in the frontend JavaScript files.*
