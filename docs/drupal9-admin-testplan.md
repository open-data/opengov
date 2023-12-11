<article class="markdown-body entry-content container-lg" itemprop="text">

<h1 tabindex="-1" dir="auto">
Drupal 9 Test Plan for Administrators
</h1>

<h2 tabindex="-1" dir="auto">
🟩 User-Account: Editing account information and viewing submissions. 
</h2>

<ol>
<li class="task-list-item">
  <h3>Editing an accounts information and permissions - In the "Edit" option within a User Account page, you should be able to:</h3>
  <ul>
    <li>
    - [ ]
    <input type="checkbox" class="task-list-item-checkbox">
    Edit an accounts "First Name"
    </li>
    <li>
    - [ ]
    <input type="checkbox" class="task-list-item-checkbox">
    Specify "Your current logout threshold" for an account - <i>Log in and test that the account is notified that "the session is about to expire" after the specified amount of inactivity time has passed. </i>
    </li>
    <li>
    - [ ]
    <input type="checkbox" class="task-list-item-checkbox">
    Edit an accounts "Last Name"
    </li>
    <li>
    - [ ]
    <input type="checkbox" class="task-list-item-checkbox">
    Edit an accounts "Email Address"
    </li>
    <li>
    - [ ]
    <input type="checkbox" class="task-list-item-checkbox">
    Edit an accounts "Username"
    </li>
    <li>
    - [ ]
    <input type="checkbox" class="task-list-item-checkbox">
    Edit an accounts "Password"
    </li>
    <li>
    - [ ]
    <input type="checkbox" class="task-list-item-checkbox">
    Edit an accounts "Status" - <i>Block an account and test the account cannot login, unblock and the account shall login</i>
    </li>
    <li>
    - [ ]
    <input type="checkbox" class="task-list-item-checkbox">
    Edit an accounts "Roles" - <i>Test that each role modifies the accounts permissions to the permissions granted by the new role</i>
    </li>
    <li>
    - [ ]
    <input type="checkbox" class="task-list-item-checkbox">
    Check that you apply "Cancel account" to an account- <i>Delete the account</i>
    </li>
    
  </ul>
</li>
<li>
  <h3>Reviewing the content submissions - In the "Submissions" option within a User Account page, you should be able to:</h3>
  <ul>
    <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      See all the content the account has created and/or modified, ordered from most recent date of modification.
    </li>
  </ul>
</li>
</ol>

<h2 tabindex="-1" dir="auto">
🟥 System Commands: Flushings caches, running Chron jobs and running Updates
</h2>
<ol>
  
  <li>
    <h3>Under the blue Drupal logo you should be able to:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Flush all caches
      </li>
        <ul>
           <li>
            - [ ]
            <input type="checkbox" class="task-list-item-checkbox">
            Flush CSS and JavaScript 
            </li>
            <li>
            - [ ]
            <input type="checkbox" class="task-list-item-checkbox">
            Flush plugins cache
            </li>
            <li>
            - [ ]
            <input type="checkbox" class="task-list-item-checkbox">
            Flush render cache
            </li>
            <li>
            - [ ]
            <input type="checkbox" class="task-list-item-checkbox">
            Flush routing and links cache
            </li>
            <li>
            - [ ]
            <input type="checkbox" class="task-list-item-checkbox">
            Flush static cache
            </li>
            <li>
            - [ ]
            <input type="checkbox" class="task-list-item-checkbox">
            Flush twig cache
            </li>
            <li>
            - [ ]
            <input type="checkbox" class="task-list-item-checkbox">
            Flush views cache
            </li>
            <li>
            - [ ]
            <input type="checkbox" class="task-list-item-checkbox">
            Rebuild theme registry
            </li>
        </ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Run Chron 
      </li>
    </ul>
  </li>
  
</ol>

<h2 tabindex="-1" dir="auto">
🟦 Content: Editing the "Layout" of a page
</h2>
<ol>
  
  <li>
    <h3>Edit the Layout of a page - When viewing a page, go to the "Layout" tab. You should be able to:</h3> 
    <ul>
      <li>
        - [ ]
        <input type="checkbox" class="task-list-item-checkbox">
        Use the "Add section" option - <i>The "One column" and "Three column" sections are most used. Test a few others you find interesting </i>
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Use the "Add block" option - <i>You can search/filter for the pre-existing blocks you'd like to test</i>
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Configure a Section - <i>You can configure the Wrapper, Classes and Regions of a section</i>
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Move a Section - <i>You can click-and-drag a section to re-arrange the page layout</i>
      </li>
    </ul>
  </li>
  
</ol>


<h2 tabindex="-1" dir="auto">
🟧 Structure: Creating and managing blocks
</h2>
<ol>
  
  <li>
    <h3>Organizing a Block Layout - In the "Structure" tab select the "Block Layout" option, you should be able to arrange the block layout for your seleted theme, as well as configure specific blocks:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Select a Theme - <i>GCweb, Claro, Seven or Bootstrap</i>
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      "Place a block" inside a block region - <i>This is adding a new block</i>
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Move a block from one region to another - <i>using the "Region" dropdown list</i>
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      "Configure" a block - <i>Go into the settings for the block and perhaps change it's Name, Roles, Content Type or configure its translation</i>
      </li>
    </ul>
  </li>
  
  <li>
    <h3>Adding and deleting a custom block - Select the "Block layout" tab, from the "Custom block library", you should be able to:</h3>
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Create a block - <i>Try creating a "basic" block as a baseline example. Or try any block type you prefer"</i>
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Delete a block - <i>Select your test block from the custom block library, the delete button is at the bottom of the page.</i>
      </li>
    </ul>
  </li>
  
</ol>

<h2 tabindex="-1" dir="auto">
🟪 Appearance: Managing themes and changing their settings:
</h2>
<ol>
  
  <li>
    <h3>Managing themes - If you go to the "Appearance" tab you should be able to :</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Install a theme
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Uninstall a theme
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Set a theme as default - <i>"Set as Default" button</i>
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Set an "Administration theme" - <i>Akin to selecting which theme you will use on the site</i>
      </li>
    </ul>
  </li>
  
  <li>
    <h3>Changing Appearance Settings - when on the Appearance page, select the "Settings" tab and you should be able to:</h3>
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Change "Global settings" - <i>Some appearance settings or behaviours that span the entire site, regardless of theme chosen</i>
    </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Change "Settings" for a specific theme - <i>Can specify some behaviours, "Favicon" and logo for one specific theme</i>
    </li>
    </ul>
  </li>
  
</ol>

<h2 tabindex="-1" dir="auto">
🟨 Extend: Managing Drupal modules
</h2>
<ol>
  
  <li>
    <h3>Installing, uninstalling, activating and deactivating Drupal modules - In the "Extend" tab, you should be able to:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Install a "Contributed module" - <i>Click the contributed modules link or browse a Drupal module site</i>
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Uninstall a module - <i>Go to the uninstall tab from the Extend page. Select a module from the list and press the "Uninstall" button at the bottom of the page. Check to make sure all functionality is removed for that module.</i>
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Activate a module - <i>On the Extend page, click the vacant checkbox for a module that is not active</i>
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Deactivate a module - <i>On the Extend page, click the non-vacant checkbox for a module that is active</i>
      </li>
    </ul>
  </li>  
</ol>

<h2 tabindex="-1" dir="auto">
🟫 Configuration: Specifying behaviour of the Drupal 9 site
</h2>
<ol>
  
  <li>
    <h3>People:</h3> 
    <ul>
      <li>
      Account Settings
        <ul>
          <li>
            Settings
            <ul>
              <li>
                - [ ]
                <input type="checkbox" class="task-list-item-checkbox">
                Specify the name used to indicate anonymous users
              </li>
              <li>
                - [ ]
                <input type="checkbox" class="task-list-item-checkbox">
                Specify how content is handled "When cancelling a user account" - <i>Test to make sure each setting behaves as they are described</i>
              </li>
              <li>
                - [ ]
                <input type="checkbox" class="task-list-item-checkbox">
                Specify the "Notification email address" 
              </li>
              <li>
                - [ ]
                <input type="checkbox" class="task-list-item-checkbox">
                Edit the notification email content - <i>Test each scenario, such as "Welcome (new user created by admin), Welcome (no approval required), etc.</i>
              </li>
            </ul>
          </li>
          <li>
            Manage form display
            <ul>
              <li>
                - [ ]
                <input type="checkbox" class="task-list-item-checkbox">
                Activate or Deactivae a "Field" - <i>Click and drag a Field into or out of the "Disabled" list</i>
              </li>
              <li>
                - [ ]
                <input type="checkbox" class="task-list-item-checkbox">
                Specify which "Form modes" should be allow to have custom display settings - <i>Inside the "Custom display settings" collapsable list. Activate then deactivate "Register" to test.</i>
              </li>
            </ul>
          </li>
          <li>
            Manage display
            <ul>
              <li>
                - [ ]
                <input type="checkbox" class="task-list-item-checkbox">
                Select a "View mode" you would like to manage - <i>Select the "Default" or "Compact" tab along the top of the "Manage display" page</i>
              </li>
              <li>
                - [ ]
                <input type="checkbox" class="task-list-item-checkbox">
                Activate or Deactivae a "Field" - <i>Click and drag a Field into or out of the "Disabled" list</i>
              </li>
              <li>
                - [ ]
                <input type="checkbox" class="task-list-item-checkbox">
                Specify the behaviour of the "Label" for a Field - <i>Above, Inline, Hidden, Visually Hidden</i> 
              </li>
              <li>
                - [ ]
                <input type="checkbox" class="task-list-item-checkbox">
                Specify the "Format" of a Field - <i>"Highlighted plain text" or "Plain text" for text fields. "Image" or "URL to image" for picture fields.</i>  
              </li>
              <li>
                - [ ]
                <input type="checkbox" class="task-list-item-checkbox">
                Specify which "View modes" should be allow to have custom display settings - <i>Inside the "Custom display settings" collapsable list. Deactivate then reactivate "Compact" to test.</i>
              </li>
            </ul>
          </li>
    </ul>
      </li>
      <li>
      Automated logout settings
        <ul>
          <li>
            - [ ]
            <input type="checkbox" class="task-list-item-checkbox">
            Test that the auto logout is working. You should be automatically logged out after one hour of inactivity.
          </li>
        </ul>
      </li>
    </ul>
  </li>
  
</ol>

<h2 tabindex="-1" dir="auto">
🟥 People: Managing site users and their permissions
</h2>
<ol>
  
  <li>
    <h3>List:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Create a user with the "Add user" button - <i>Provider a username, password, and other data for creating a new Drupal site user</i>
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Search and/or filter for a user or users - <i>Search/filter by username, status, role or permissions</i>
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Apply an action to a user or users - <i>Update URL alias, Block selected user(s), etc</i>
      </li>
    </ul>
  </li>
  
  <li>
    <h3>Permissions:</h3>
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Specify which permissions belong to which user roles. Test to make sure whichever permission you add or remove to a user role behaves as intended.
      </li>
    </ul>
  </li>

  <li>
    <h3>Roles:</h3>
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Create a new role with the "Add role" button - <i>Specify a Role name</i>
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Edit the name of an existing role - <i>Select the "Edit" button from the "Roles" tab</i>
      </li>
    </ul>
  </li>
  
</ol>

<h2 tabindex="-1" dir="auto">
🟪 Access to Information Requests: Testing an ATI record request and verifying it's received
</h2>
<ol>
  
  <li>
    <h3>Testing an ATI request :</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Modify an ATI summary in the staging or test environments by changing the email address field that receives ATI notification email for the department, to a personal email address. This can be done using an update command in Solr.
    </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      From the "Completed Access to Information Requests" page, browse, search and/or filter for an ATI record.
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      After finding an appropriate ATI record to test, select it and fill out the "Request a copy of records" form.
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Verify that you have received an ATI request email notification at the personal email you specified in the previous step.
      </li>
    </ul>
  </li>
  
</ol>
