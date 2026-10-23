# Views Bootstrap 5.5.x Release Notes

## Version 5.5.3

### Features

**[#3625898](https://www.drupal.org/project/views_bootstrap/issues/3625898) - Bootstrap Table: Add indicator class to caption when created from grouping**
- NEW: Added `views-bootstrap-table-caption-grouping` CSS class to `<caption>` elements when they are created from grouping
- NEW: Grouping captions now preserve HTML wrapper elements configured on the grouping field
  - If you configure a field with an HTML wrapper element (e.g., `<h2>`, `<h3>`) in the field settings, that wrapper is now applied to the caption when grouping by that field
  - Previously, only the plain text of the field was shown in the caption, ignoring the wrapper element configuration
  - Wrapper classes are also preserved
- Allows independent styling of grouping captions (typically shown above table) vs regular captions (typically shown below table)
- Previously, there was no way to distinguish between these two caption types in CSS
- Added `caption_is_from_grouping` template variable for themers
- Theme developers can now use `.views-bootstrap-table-caption-grouping` selector in their theme CSS to style grouping captions differently

**Impact:** This enables proper visual differentiation between regular table captions and grouping headers, and ensures semantic HTML markup is preserved in grouping captions, improving both styling flexibility and accessibility when using Views grouping with Bootstrap Table displays.

**Example theme CSS:**
```css
/* Display grouping caption above table */
.views-bootstrap-table-caption-grouping {
  caption-side: top;
}

/* Display regular captions below table */
table caption:not(.views-bootstrap-table-caption-grouping) {
  caption-side: bottom;
}
```

---

## Version 5.5.2

### Documentation Improvements

**Carousel Responsive Behavior Clarification**
- Enhanced documentation to address user confusion about showing different numbers of items per slide on mobile vs desktop
- Updated "Columns" field label to "Columns per slide" with clearer description explaining mobile-first behavior
- Updated "Breakpoints" field label to "Multi-column breakpoint" with detailed explanation of how responsive stacking works
- Added pixel widths to all breakpoint options for clarity (e.g., "Medium (992px+)" instead of just "Medium")
- NEW: Added collapsible "Responsive carousel behavior" help section in the Views UI with:
  - Clear explanation of how mobile vs desktop behavior works
  - Concrete example: "To show 3 items per slide on desktop and 1 item on mobile..."
  - Step-by-step configuration guidance
- Enhanced README with dedicated "Carousel Responsive Behavior" section including:
  - How the responsive feature works
  - Example configuration walkthrough
  - Expected results on different screen sizes
- Improved template documentation (views-bootstrap-carousel.html.twig) for themers with examples

**Why this matters:** The carousel already supported responsive behavior (1 item on mobile, multiple items on desktop), but the documentation didn't clearly explain how to configure it. Users were confused about how to achieve this common use case. These improvements make the existing functionality discoverable and understandable.

---

## Version 5.5.1

### Critical Bug Fixes

**[#3611739](https://www.drupal.org/project/views_bootstrap/issues/3611739) - Bootstrap tab and accordion keys are broken**
- Fixed tabs and accordions not functioning correctly when using numeric row keys
- Issue: The `clean_class` Twig filter was stripping leading digits, causing all items to share the same invalid ID
- Solution: Prepend 'key-' before sanitization to ensure unique, valid HTML IDs while maintaining XSS protection
- Impact: Tabs now switch correctly and accordion panels expand/collapse as expected

**[#2931301](https://www.drupal.org/project/views_bootstrap/issues/2931301) - Views with responsive table display no longer works** (MAJOR)
- Restored responsive table functionality
- Tables now properly wrap in responsive containers based on breakpoint settings
- Responsive breakpoint behavior now works as documented for all screen sizes

**[#3609186](https://www.drupal.org/project/views_bootstrap/issues/3609186) - Multiple accordion items open under some circumstances**
- Fixed by #3611739 - was a symptom of the key sanitization issue
- First 11 accordion items no longer open simultaneously
- Each accordion item now operates independently with unique IDs

### Features

**[#3567011](https://www.drupal.org/project/views_bootstrap/issues/3567011) - Clickable cards (stretched link)**
- NEW: Added "Enable clickable cards" option to Cards style plugin
- Automatically applies Bootstrap 5's `stretched-link` class to selected link fields
- Makes entire card clickable, not just the title or link text
- Optional link field selection - can also manually add class in field settings
- Works with both card groups and regular card layouts
- Includes helpful description with link to Bootstrap documentation

**[#3479002](https://www.drupal.org/project/views_bootstrap/issues/3479002) - Carousel breakpoints responsive behavior**
- Fixed multi-column carousel responsive stacking on mobile devices
- Now uses mobile-first approach: `col-12` (full width) on small screens, then multi-column at selected breakpoint
- Example: 2-column carousel with "medium" breakpoint now generates `col-12 col-md-6`
- Single-column carousels and extra-small breakpoint behavior unchanged

### Documentation

**[#3511460](https://www.drupal.org/project/views_bootstrap/issues/3511460) - Twig variables in carousel row classes**
- Documented that field replacement patterns ARE supported in carousel row classes
- Updated field description to mention "You may use field replacement patterns"
- No code changes needed - feature already worked via `tokenizeValue()` method
- Users can now use patterns like `{{ field_name }}` or `custom-{{ nid }}` for dynamic classes

---

## Version 5.5.0 (Released)

Initial stable release of Bootstrap 5 support for Drupal 10+.

### Requirements
- Drupal: ^10.1 || ^11 || ^12
- PHP: 8.1+
- Bootstrap 5 theme

### Components
- Accordion
- Cards
- Carousel
- Dropdown
- Grid
- List Group
- Media Object
- Table
- Tabs

---

## How to Update

1. Back up your database
2. Update the module: `composer update drupal/views_bootstrap`
3. Run database updates: `drush updatedb` or visit `/update.php`
4. Clear caches: `drush cr`

### Breaking Changes

None in 5.5.2 or 5.5.1.

### Upgrade Notes

**Version 5.5.2:**
- **Carousel Configuration UI**: Field labels and descriptions have been updated for clarity. The functionality hasn't changed - only the wording is clearer. Existing carousel views will continue to work exactly as before.
- **No action required**: This is a documentation-only release. All improvements are in help text, field labels, and documentation files.

**Version 5.5.1:**
- **Tabs and Accordions**: If you were experiencing issues with tabs not switching or accordions not working, this is now fixed. No configuration changes needed.
- **Clickable Cards**: This is a new opt-in feature. Existing card views will continue to work as before. To enable, edit your Cards view and check "Enable clickable cards."
- **Carousel Breakpoints**: Multi-column carousels will now properly stack on mobile. If you have custom CSS targeting carousel columns, test on mobile viewports.
- **Responsive Tables**: If responsive tables weren't working before, they will now work correctly with no configuration changes.

---

## Contributing

Report issues at: https://www.drupal.org/project/issues/views_bootstrap

Module page: https://www.drupal.org/project/views_bootstrap

Documentation: https://www.drupal.org/docs/extending-drupal/contributed-modules/contributed-module-documentation/views-bootstrap-for-bootstrap-5
