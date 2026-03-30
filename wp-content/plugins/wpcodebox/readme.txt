=== Easily manage all your WordPress code ===
Contributors: WPCodeBox
Requires at least: 5.0
Tested up to: 6.0
Stable tag: 1.4.1

WPCodeBox is a complete WordPress snippet manager. With WPCodeBox you can manage all of your site's code without touching functions.php.


== Description ==

= WPCodeBox - Complete WordPress Snippet Manager =

WPCodeBox is a complete WordPress snippet manager. With WPCodeBox you can manage all of your site's code without touching functions.php.

== Changelog ==

= 1.4.1 (Released on Jun 7th 2022) =
* Bugfix: Fix error when snippet has certain conditions

= 1.4.0 (Released on Jun 7th 2022) =

* New Feature: Select hook execution location for PHP code snippets
* New Feature: Edit Cloud Snippets directly
* New Feature: Code Formatter for PHP/JS/CSS/SCSS snippets
* New Feature: ACF Autocomplete (if ACF is installed)
* New Feature: Meta Box Autocomplete (if Meta Box is installed)
* New Feature: Context menu for Code Snippets
* New Feature: Clone snippets from the context menu
* New Feature: Hook suggestions when adding ACF Code
* New Feature: Hook suggestions when adding Meta Box Code
* New Feature: Add cache-busting string to static assets on code snippet save

* Improvement: Workaround for the need to add secret keys on Local by Flywheel
* Improvement: Unload notice and confirmation if you have unsaved changes and want to leave the page/refresh
* Improvement: Mark snippet as changed when editing conditions
* Improvement: Make snippet list scrollable
* Improvement: Make code snippet top-bar smaller
* Improvement: User Role condition
* Improvement: Login Page condition
* Improvement: Allow the removal of the 1st condition in a group
* Improvement: Better support for CSS variables

* Bugfix: Inconsistencies when downloading an already opened code snippet from the cloud
* Bugfix: Snippet drag and drop doesn’t work in Firefox
* Bugfix: Snippet order resets in the UI if you drag and drop snippets and then you open/close the folder
* Bugfix: Inconsistencies when downloading an already opened code snippet from the cloud
* Bugfix: Taxonomy condition triggers warning

= 1.3.2 (Released on Mar 17th 2022) =

* New Feature: Edit multiple Code Snippets, without loosing changes when switching between them
* New Feature: Preview Cloud Snippets
* New Feature: Drag And Drop Snippets and Folders to reorder them
* New Feature: CSS Preview Window with DOM Inspector
* New Feature: Change layouts from Plugin Settings
* New Feature: Enable Dark Mode from Plugin Settings
* New Feature: Wrap Long Lines in Plugin Settings
* New Feature: Snippet quick Enable/Disable from the Snippet List
* Improvement: Search works in Snippet Code
* Improvement: Set priority to the default 10 when downloading a snippet from the cloud
* Improvement: In case a snippet is disabled because of an error, show the line number in the error report
* Improvement: Added filter to rename "Quick Actions" text
* Improvement: Preserve collapsed middle pane state when switching snippets
* Improvement: Do not autosuggest in comments
* Improvement: Do not autosuggest in strings
* Improvement: Do not autosuggest when opening PHP tag
* Bugfix: Deleting Condition Group deletes the last group, not the current one
* Bugfix: SCSS/LESS rendered as external file not loaded correctly
* Bugfix: When pressing delete in Snippet Title or Description, the delete snippet pop-up opens
* Bugfix: PHP Warning when rendering external JS Scripts with no tag options


= 1.3.1 (Released on Dec 23rd 2021) =

* Improvement: Added classes to select boxes to allow a Dark Mode snippet
* Bugfix: When using a read-only API Key cloud folder rename and upload is still available in UI

= 1.3.0 (Released on Dec 22nd 2021) =

* New Feature: Integration with the API Key Manager
* New Feature: Add/Manage External CSS and JS
* New Feature: CDNJS integration for adding external files (access and search over 4000 JS libraris from inside WPCodeBox)
* New Feature: Ability to defer/async external JS or JS that was saved to file
* New Feature: Quick Actions (add manual snippets to the top bar and run them with no page refresh)
* New Feature: HTML code snippet support
* New Feature: Save CSS/SCSS/JS to external file
* New Feature: Minify CSS/SCSS/JS code
* Improvement: Setting to show a confirmation before uploading a snippet to the cloud
* Improvement: Keep track of changes so we can detect overwriting of newer code to the cloud in the future
* Improvement: CSS Autoreload revamp, no need to refresh, autoreload external CSS/SCSS with cache busting
* Improvement: New and improved local folder logic
* Improvement: Preserve the current open snippet when reloading the page
* Improvement: Don't fail when unable to load local or cloud snippets, display an error
* Improvement: Don't run snippets on WPCodeBox requests (so you can still access WPCodeBox in case of error)
* Improvement: Don't open folders on hover, just highlight them
* Improvement: Delete selected code snippet when pressing DELETE
* Bugfix: Snippets not updated when deleting a cloud folder (changes appearing only after refresh)
* Bugfix: Drag snippets outside of cloud folders doesn't work
* Bugfix: Some very rare compatibility issues with certain server setups

= 1.2.1 (Released on Oct 3rd 2021) =

* Improvement: Set focus back to the input field when creating a new cloud folder
* Improvement: Added the editor font to the plugin, so that the experience is more consistent across different OS's
* Bugfix: Code editor UI issues on Windows PCs
* Bugfix: After saving the settings, the Font Size input field was empty
* Bugfix: In some cases, when dragging a local snippet to a folder, an error would appear in the error log


= 1.2.0 (Released on Oct 2nd 2021) =

* Improvement: Cloud Folders (create folders to organize your cloud snippets)
* Improvement: Added TXT file support (for notes that can be saved in the cloud)
* Improvement: Added editor settings (theme and font size)
* Improvement: UI Improvements
* Bugfix: When selecting header for CSS or JS, the script would be actually loaded in the footer
* Bugfix: When deleting local folders, sometimes child snippets are not deleted
* Bugfix: When having a folder open and deleting another folder, the next folder would be open

= 1.1.8 (Released on Aug 2nd 2021) =

* Improvement: Add the option to move WPCodeBox to the tools menu
* Bugfix: Parent post condition not working
* Bugfix: When changing the snippet priority, the save icon doesn't turn orange (to notify of unsaved changes)
* Bugfix: When changing the header/footer option, the value defaults to footer
* Bugfix: Custom PHP condition UI is appears over select elements (Z-index problem)

= 1.1.7 (Released on July 22nd 2021) =

* Improvement: Condition builder (for building complex conditions for when to run/not run snippets)
* Improvement: Added option to run CSS and JS in header or footer
* Improvement: Added the snippet priority option, to modify snippet execution order
* Improvement: Long names in cloud snippets make titles jump on hover which can be distracting
* Bugfix: Hide Code Snippets from the Oxygen preview select
* Bugfix: Make CSS and JS run correctly in the admin area


= 1.1.6  (Released on June 6th 2021) =

* Fixed: Editor height too small, especially on 4k displays
* Fixed: Cloud snippets not always updated correctly
* Fixed: Error when enabling dev mode for CSS snippets that are set to run on the frontend
* Fixed: Error when saving SCSS and having WP-SCSS enabled
* Fixed: Fonts not found error in console

= 1.1.5 (Released on Jun 10th 2021) =

* Fixed admin notice when repeatedly activating/deactivating the plugin

= 1.1.4 (Released on Jun 8th 2021) =

* SASS integration
* LESS integration
* Run/don't run snippets on multiple pages/posts
* Added the ability to download manually running Snippets from the Snippet Repository
* Hide license keys on the frontend
* UI improvements

= 1.1.3 (Released on June 1st 2021) =

* Added JS support
* Fixed CSS files appearing as PHP inside folders
* Fixed conflict with FluentSMTP
* Fixed running CSS snippets on archive pages


= 1.1.2 (Released on May 14th 2021) =
* Added new version and update notifications in the dashboard
* Added cache busting to static CSS assets
* Added access to the snippet repository from within the plugin
* Improved the way snippets run, make them run at a lower level

= 1.1.1 (Released on May 9th 2021) =
* Fixed snippets that should run everywhere are not running

= 1.1.0 (Released on May 2nd 2021) =
* Added support for CSS files
* Added auto-refresh for CSS changes
* Added the ability to select on which pages/posts the snippets should run
* Added the ability to collapse the edit snippet pane
* Added wrapping for long lines in the editor

= 1.0.4 (Released on Apr 29th 2021) =
* Allow the parsing of nested HTML and JS code

= 1.0.3 (Released on Apr 21st 2021) =
* Fixed compatibility with Fluent Forms

= 1.0.2 (Released on Apr 21st 2021) =
* Fixed running code with nested quotes

= 1.0.1 (Released on Apr 21st 2021) =
* The description is now a textarea.
* Allow the code editor to run on HTTP auth pages.
* Fixed header auth problems on some browsers.
* Fixed long snippet names change the cloud icon size.
* Fixed snippet running priority.
* Fixed snippet error reporting and disabling.
* Fixed small UI problems.

= 1.0.0 (Released on Apr 21st 2021) =
* Initial release