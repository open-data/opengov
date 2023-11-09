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
    Specify "Your current logout threshold" for an account - <i>Log out and test you cannot login again until the specified time has passed</i>
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
🟥 System Commands:
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
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Run Chron 
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Run updates
      </li>
    </ul>
  </li>
  
  <li>
    <h3>Index - Under the blue Drupal logo in the "Index" tab, you should be able to:</h3>
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      *list administative tasks for each module?*
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
🟧 Structure: 
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
    <h3>Adding a custom block - From the "Block layout" tab, select the "Add custom block" option, you should be able to:</h3>
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Create a "basic" block
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Create an "External API" block
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Create a "Feature" block
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Create a "Search block"
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Create a "Spotlight block"
      </li>
    </ul>
  </li>
  
</ol>

<h2 tabindex="-1" dir="auto">
🟪 Appearance - Managing themes and changing their settings:
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
🟨 Extend - Managing Drupal modules:
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
🟫 Configuration - Specifying behaviour of the Drupal 9 site:
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
                Change Permission for "Who can register accounts?" - <i>Test to make sure each setting behaves as they are described. Test this alongisde the "Require email verficiation" and "notification email content" features below.</i> 🟡
              </li>
              <li>
                - [ ]
                <input type="checkbox" class="task-list-item-checkbox">
                Toggle the "Require email verification when a visitor creates an account" option - <i>Test this alongside the "Who can register accounts?" permission above and the "notification email content" feature below</i> 🟡
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
                Edit the notification email content - <i>Test each scenario, such as "Welcome (new user created by admin), Welcome (no approval required), etc. Test this alongside the "Require email verification" and "Who can register accounts?" features above.</i> 🟡
              </li>
            </ul>
          </li>
          <li>
            Manage fields
            <ul>
              <li>
                - [ ]
                <input type="checkbox" class="task-list-item-checkbox">
                abc123
              </li>
            </ul>
          </li>
          <li>
            Manage form display
            <ul>
              <li>
                - [ ]
                <input type="checkbox" class="task-list-item-checkbox">
                abc123
              </li>
            </ul>
          </li>
          <li>
            Manage display
            <ul>
              <li>
                - [ ]
                <input type="checkbox" class="task-list-item-checkbox">
                abc123
              </li>
            </ul>
          </li>
          <li>
            Translate account settings
            <ul>
              <li>
                - [ ]
                <input type="checkbox" class="task-list-item-checkbox">
                abc123
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
            abc123
          </li>
        </ul>
      </li>
      <li>
      IP address bans
        <ul>
          <li>
            - [ ]
            <input type="checkbox" class="task-list-item-checkbox">
            abc123
          </li>
        </ul>
      </li>
    </ul>
  </li>
  
  <li>
    <h3>System:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      abc123 
      </li>
    </ul>
  </li>

  <li>
    <h3>Content authoring:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      abc123 
      </li>
    </ul>
  </li>

  <li>
    <h3>User interface:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      abc123 
      </li>
    </ul>
  </li>

  <li>
    <h3>Development:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      abc123 
      </li>
    </ul>
  </li>

  <li>
    <h3>Media:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      abc123 
      </li>
    </ul>
  </li>

  <li>
    <h3>Search and metadata:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      abc123 
      </li>
    </ul>
  </li>

  <li>
    <h3>Regional and language:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      abc123 
      </li>
    </ul>
  </li>

  <li>
    <h3>Web services:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      abc123 
      </li>
    </ul>
  </li>

  <li>
    <h3>Workflow:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      abc123 
      </li>
    </ul>
  </li>
  
</ol>

<h2 tabindex="-1" dir="auto">
🟥 People:
</h2>
<ol>
  
  <li>
    <h3>Searching and Filter content - In the "Content" tab you should be able to select between "Content", "Comments", "Files" and "Media". You should able to Search or Filter content as described below:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Enter the "Title" for 
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Enter some content for the "Body" for your page
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Define a "URL alias" for your page
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Select a "Save as" format
      </li>
    </ul>
  </li>
  
  <li>
    <h3>Media Files - In the "Content" tab, by navigating through the "Media" and "Add Media" tabs, you should be able to:</h3>
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Upload an "Image"
    </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Upload a "Document"
    </li>
    </ul>
  </li>
  
</ol>

<h2 tabindex="-1" dir="auto">
🟪 Reports:
</h2>
<ol>
  
  <li>
    <h3>Searching and Filter content - In the "Content" tab you should be able to select between "Content", "Comments", "Files" and "Media". You should able to Search or Filter content as described below:</h3> 
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Enter the "Title" for 
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Enter some content for the "Body" for your page
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Define a "URL alias" for your page
      </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Select a "Save as" format
      </li>
    </ul>
  </li>
  
  <li>
    <h3>Media Files - In the "Content" tab, by navigating through the "Media" and "Add Media" tabs, you should be able to:</h3>
    <ul>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Upload an "Image"
    </li>
      <li>
      - [ ]
      <input type="checkbox" class="task-list-item-checkbox">
      Upload a "Document"
    </li>
    </ul>
  </li>
  
</ol>
